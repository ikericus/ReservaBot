const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const jwt = require('jsonwebtoken');
const QRCode = require('qrcode');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const axios = require('axios');
const winston = require('winston');
const fs = require('fs').promises;
const path = require('path');
require('dotenv').config();

// Configuración del logger
const logger = winston.createLogger({
    level: process.env.LOG_LEVEL || 'info',
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.errors({ stack: true }),
        winston.format.json()
    ),
    transports: [
        new winston.transports.File({ filename: './logs/error.log', level: 'error' }),
        new winston.transports.File({ filename: './logs/combined.log' }),
        new winston.transports.Console({
            format: winston.format.combine(
                winston.format.colorize(),
                winston.format.simple()
            )
        })
    ]
});

// Configuración de la aplicación
const app = express();
const PORT = process.env.PORT || 3001;
const JWT_SECRET = process.env.JWT_SECRET;
const WEBAPP_API_URL = process.env.WEBAPP_API_URL || 'http://localhost';
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;
const MAX_CLIENTS = parseInt(process.env.MAX_CLIENTS) || 50;

// Configuración para manejar errores no capturados
process.on('unhandledRejection', (reason, promise) => {
    logger.error('Promesa rechazada no manejada:', {
        reason: reason,
        stack: reason?.stack,
        service: 'whatsapp-server'
    });
    
    // Si es error específico de WhatsApp Web.js cache
    if (reason?.message?.includes('LocalWebCache') || 
        reason?.message?.includes('Cannot read properties of null')) {
        
        logger.info('Detectado error de LocalWebCache, ejecutando limpieza...');
        
        // Obtener userId del error si es posible
        const errorString = reason?.stack || reason?.message || '';
        const userIdMatch = errorString.match(/client_(\d+)/);
        
        if (userIdMatch) {
            const affectedUserId = userIdMatch[1];
            logger.info(`Usuario afectado por error de cache: ${affectedUserId}`);
            
            // Limpiar específicamente ese usuario
            if (activeClients.has(affectedUserId)) {
                cleanup(affectedUserId);
            }
            
            // Limpiar sesión corrupta
            cleanCorruptedSessions(affectedUserId).catch(err => {
                logger.error(`Error limpiando sesión corrupta:`, err);
            });
        } else {
            // Si no podemos identificar el usuario, limpiar todas las sesiones
            logger.warn('No se pudo identificar usuario específico, limpiando todas las sesiones');
            
            for (const [userId] of activeClients) {
                cleanup(userId);
                cleanCorruptedSessions(userId).catch(err => {
                    logger.error(`Error limpiando sesión ${userId}:`, err);
                });
            }
        }
    }
});

process.on('uncaughtException', (error) => {
    logger.error('Excepción no capturada:', {
        error: error.message,
        stack: error.stack,
        service: 'whatsapp-server'
    });
});

// Middleware de seguridad
app.use(helmet({
    contentSecurityPolicy: {
        directives: {
            defaultSrc: ["'self'"],
            styleSrc: ["'self'", "'unsafe-inline'"],
            scriptSrc: ["'self'"],
            imgSrc: ["'self'", "data:", "https:"]
        }
    }
}));

app.use(cors({
    origin: process.env.WEBAPP_URL || 'http://localhost',
    credentials: true
}));

// Rate limiting
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutos
    max: 100, // máximo 100 requests por IP
    message: { error: 'Demasiadas peticiones, intenta más tarde' }
});
app.use('/api/', limiter);

// Middleware para parsing JSON
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Storage para clientes WhatsApp activos
const activeClients = new Map();
const pendingConnections = new Map();
const messageQueue = new Map(); // Cola de mensajes para clientes desconectados

// Middleware de autenticación JWT
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
        return res.status(401).json({ success: false, error: 'Token de acceso requerido' });
    }

    jwt.verify(token, JWT_SECRET, (err, decoded) => {
        if (err) {
            return res.status(403).json({ success: false, error: 'Token inválido' });
        }
        req.user = decoded;
        next();
    });
}

// Middleware para verificar webhook secret
function verifyWebhookSecret(req, res, next) {
    const providedSecret = req.headers['x-webhook-secret'];
    
    if (!providedSecret || providedSecret !== WEBHOOK_SECRET) {
        return res.status(401).json({ success: false, error: 'Secret inválido' });
    }
    
    next();
}

// ========== FUNCIONES DE SESIÓN ==========

// Función para limpiar sesiones corruptas
async function cleanCorruptedSessions(userId) {
    const sessionPath = path.join('./sessions', `session-client_${userId}`);
    const wwebSessionPath = path.join('./sessions', `wweb_session_client_${userId}`);
    
    logger.info(`Limpiando sesiones para usuario ${userId}`);
    
    try {
        await fs.rmdir(sessionPath, { recursive: true });
        logger.info(`Sesión eliminada: ${sessionPath}`);
    } catch (error) {
        logger.debug(`No se pudo eliminar ${sessionPath}: ${error.message}`);
    }
    
    try {
        await fs.rmdir(wwebSessionPath, { recursive: true });
        logger.info(`Sesión wweb eliminada: ${wwebSessionPath}`);
    } catch (error) {
        logger.debug(`No se pudo eliminar ${wwebSessionPath}: ${error.message}`);
    }
}

// Función para verificar integridad de sesión
async function checkSessionIntegrity(userId) {
    const sessionPath = path.join('./sessions', `session-client_${userId}`);
    
    try {
        const sessionExists = await fs.access(sessionPath).then(() => true).catch(() => false);
        
        if (sessionExists) {
            // Verificar archivos críticos de la sesión
            const criticalFiles = ['Default/Local Storage', 'Default/Session Storage'];
            
            for (const file of criticalFiles) {
                const filePath = path.join(sessionPath, file);
                try {
                    await fs.access(filePath);
                } catch (error) {
                    logger.warn(`Archivo de sesión corrupto: ${filePath}`);
                    return false;
                }
            }
        }
        
        return sessionExists;
    } catch (error) {
        logger.error(`Error verificando integridad de sesión: ${error.message}`);
        return false;
    }
}

// Función para crear directorio de sesiones si no existe
async function ensureSessionsDirectory() {
    const sessionsDir = './sessions';
    const logsDir = './logs';
    
    try {
        await fs.mkdir(sessionsDir, { recursive: true });
        await fs.mkdir(logsDir, { recursive: true });
        
        // Verificar permisos de escritura
        await fs.access(sessionsDir, fs.constants.W_OK);
        await fs.access(logsDir, fs.constants.W_OK);
        
        logger.info('✅ Directorios de sesiones y logs verificados');
    } catch (error) {
        logger.error('❌ Error con directorios:', error.message);
        throw error;
    }
}

// Función para limpiar sesiones al inicio
async function initializeSessionsCleanup() {
    logger.info('🧹 Verificando sesiones existentes...');
    
    const sessionsDir = './sessions';
    
    try {
        const sessions = await fs.readdir(sessionsDir).catch(() => []);
        
        for (const sessionDir of sessions) {
            if (sessionDir.startsWith('session-client_') || sessionDir.startsWith('wweb_session_client_')) {
                const sessionPath = path.join(sessionsDir, sessionDir);
                
                try {
                    const stats = await fs.stat(sessionPath);
                    const ageHours = (Date.now() - stats.mtime.getTime()) / (1000 * 60 * 60);
                    
                    // Si la sesión tiene más de 24 horas, considerar limpiarla
                    if (ageHours > 24) {
                        logger.info(`Limpiando sesión antigua: ${sessionDir} (${ageHours.toFixed(1)}h)`);
                        await fs.rmdir(sessionPath, { recursive: true });
                    }
                } catch (error) {
                    logger.warn(`Error verificando sesión ${sessionDir}:`, error.message);
                    try {
                        await fs.rmdir(sessionPath, { recursive: true });
                        logger.info(`Sesión corrupta eliminada: ${sessionDir}`);
                    } catch (cleanupError) {
                        logger.error(`No se pudo limpiar sesión corrupta ${sessionDir}:`, cleanupError.message);
                    }
                }
            }
        }
    } catch (error) {
        logger.error('Error durante limpieza inicial de sesiones:', error);
    }
    
    logger.info('✅ Limpieza inicial de sesiones completada');
}

// ========== FUNCIONES DE CLIENTE WHATSAPP ==========

// Función para crear cliente WhatsApp
// Reemplaza la función createWhatsAppClient con esta versión sin cache:
async function createWhatsAppClient(userId) {
    if (activeClients.has(userId)) {
        logger.warn(`Cliente ${userId} ya existe`);
        return activeClients.get(userId);
    }

    if (activeClients.size >= MAX_CLIENTS) {
        throw new Error('Máximo número de clientes alcanzado');
    }

    logger.info(`Iniciando conexión para usuario ${userId}`, { service: 'whatsapp-server' });

    // Limpiar cualquier sesión previa
    await cleanCorruptedSessions(userId);

    const puppeteerConfig = {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--single-process'
        ]
    };

    // CLIENTE SIMPLE SIN CACHE
    const client = new Client({
        authStrategy: new LocalAuth({
            clientId: `client_${userId}`,
            dataPath: './sessions'
        }),
        puppeteer: puppeteerConfig
        // Sin webVersion ni webVersionCache
    });

    // Suprimir errores de LocalWebCache específicamente
    const originalConsoleError = console.error;
    console.error = (...args) => {
        const message = args.join(' ');
        if (!message.includes('LocalWebCache') && !message.includes('Cannot read properties of null')) {
            originalConsoleError.apply(console, args);
        }
    };

    // Resto de eventos igual...
    client.on('qr', async (qr) => {
        logger.info(`QR generado para usuario ${userId}`);
        try {
            const qrDataURL = await QRCode.toDataURL(qr);
            pendingConnections.set(userId, { qr: qrDataURL, timestamp: Date.now() });
            await notifyWebApp('qr_generated', userId, { qr: qrDataURL });
        } catch (error) {
            logger.error(`Error generando QR:`, error);
        }
    });

    // ... resto de eventos

    activeClients.set(userId, client);
    
    try {
        await client.initialize();
    } catch (error) {
        if (!error.message.includes('LocalWebCache')) {
            logger.error(`Error inicializando cliente ${userId}:`, error);
            throw error;
        }
        // Ignorar errores de LocalWebCache
        logger.info(`Cliente inicializado (ignorando errores de cache)`);
    }

    return client;
}

// Función para manejar mensajes entrantes
async function handleIncomingMessage(userId, message) {
    try {
        logger.info(`Mensaje entrante para ${userId} de ${message.from}: ${message.body}`);

        const messageData = {
            id: message.id._serialized,
            from: message.from,
            to: message.to,
            body: message.body,
            type: message.type,
            timestamp: message.timestamp,
            isGroupMsg: message.isGroupMsg,
            author: message.author,
            hasMedia: message.hasMedia
        };

        if (message.hasMedia) {
            try {
                const media = await message.downloadMedia();
                messageData.media = {
                    mimetype: media.mimetype,
                    filename: media.filename,
                    data: media.data
                };
            } catch (error) {
                logger.error(`Error descargando media:`, error);
            }
        }

        await notifyWebApp('message_received', userId, messageData);

    } catch (error) {
        logger.error(`Error procesando mensaje entrante:`, error);
    }
}

// Función para registrar mensajes salientes
async function logOutgoingMessage(userId, message) {
    try {
        const messageData = {
            id: message.id._serialized,
            to: message.to,
            body: message.body,
            type: message.type,
            timestamp: message.timestamp
        };

        await notifyWebApp('message_sent', userId, messageData);
    } catch (error) {
        logger.error(`Error registrando mensaje saliente:`, error);
    }
}

// Función para notificar a la web app
async function notifyWebApp(event, userId, data) {
    try {
        const payload = {
            event,
            userId,
            data,
            timestamp: Date.now()
        };

        await axios.post(`${WEBAPP_API_URL}/api/whatsapp-webhook`, payload, {
            headers: {
                'Content-Type': 'application/json',
                'X-Webhook-Secret': WEBHOOK_SECRET
            },
            timeout: 5000
        });

        logger.debug(`Webhook enviado: ${event} para usuario ${userId}`);
    } catch (error) {
        logger.error(`Error enviando webhook:`, error.message);
    }
}

// Función para procesar mensajes en cola
async function processQueuedMessages(userId) {
    const queue = messageQueue.get(userId);
    if (!queue || queue.length === 0) return;

    logger.info(`Procesando ${queue.length} mensajes en cola para ${userId}`);

    for (const queuedMessage of queue) {
        try {
            await sendMessage(userId, queuedMessage.to, queuedMessage.message);
            logger.info(`Mensaje de cola enviado a ${queuedMessage.to}`);
        } catch (error) {
            logger.error(`Error enviando mensaje de cola:`, error);
        }
    }

    messageQueue.delete(userId);
}

// Función para limpiar recursos de cliente
function cleanup(userId) {
    logger.info(`Iniciando cleanup para usuario ${userId}`);
    
    if (activeClients.has(userId)) {
        const client = activeClients.get(userId);
        try {
            if (client && typeof client.destroy === 'function') {
                client.destroy();
            }
        } catch (error) {
            logger.error(`Error destruyendo cliente ${userId}:`, error);
        }
        activeClients.delete(userId);
    }
    
    pendingConnections.delete(userId);
    logger.info(`Cleanup completado para usuario ${userId}`);
}

// Función para enviar mensaje
async function sendMessage(userId, to, message, media = null) {
    const client = activeClients.get(userId);
    
    if (!client) {
        throw new Error('Cliente WhatsApp no conectado');
    }

    if (!client.info) {
        throw new Error('Cliente WhatsApp no está listo');
    }

    let chatId = to;
    if (!to.includes('@')) {
        chatId = `${to}@c.us`;
    }

    try {
        let sentMessage;
        
        if (media) {
            const messageMedia = new MessageMedia(media.mimetype, media.data, media.filename);
            sentMessage = await client.sendMessage(chatId, messageMedia, { caption: message });
        } else {
            sentMessage = await client.sendMessage(chatId, message);
        }

        logger.info(`Mensaje enviado desde ${userId} a ${to}`);
        return sentMessage;
    } catch (error) {
        logger.error(`Error enviando mensaje:`, error);
        throw error;
    }
}

// ========== RUTAS API ==========

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'healthy',
        uptime: process.uptime(),
        timestamp: new Date().toISOString(),
        activeClients: activeClients.size,
        pendingConnections: pendingConnections.size
    });
});

// Información del servidor
app.get('/api/info', authenticateToken, (req, res) => {
    res.json({
        success: true,
        server: {
            version: '1.0.0',
            uptime: process.uptime(),
            activeClients: activeClients.size,
            maxClients: MAX_CLIENTS,
            pendingConnections: pendingConnections.size,
            messageQueues: messageQueue.size
        }
    });
});

// Conectar WhatsApp
app.post('/api/connect', authenticateToken, async (req, res) => {
    const userId = req.user.userId;

    try {
        if (activeClients.has(userId)) {
            const client = activeClients.get(userId);
            if (client.info) {
                return res.json({
                    success: true,
                    status: 'ready',
                    message: 'Cliente ya conectado',
                    phoneNumber: client.info.wid.user
                });
            }
        }

        await createWhatsAppClient(userId);
        
        res.json({
            success: true,
            status: 'connecting',
            message: 'Proceso de conexión iniciado'
        });

    } catch (error) {
        logger.error(`Error conectando cliente ${userId}:`, error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Desconectar WhatsApp
app.post('/api/disconnect', authenticateToken, async (req, res) => {
    const userId = req.user.userId;

    try {
        if (!activeClients.has(userId)) {
            return res.json({
                success: true,
                status: 'already_disconnected',
                message: 'Cliente no estaba conectado'
            });
        }

        cleanup(userId);
        
        res.json({
            success: true,
            status: 'disconnected',
            message: 'Cliente desconectado correctamente'
        });

    } catch (error) {
        logger.error(`Error desconectando cliente ${userId}:`, error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Estado de la conexión
app.get('/api/status', authenticateToken, (req, res) => {
    const userId = req.user.userId;

    try {
        const client = activeClients.get(userId);
        const pending = pendingConnections.get(userId);

        if (client && client.info) {
            res.json({
                success: true,
                status: 'ready',
                info: {
                    phoneNumber: client.info.wid.user,
                    name: client.info.pushname
                }
            });
        } else if (client || pending) {
            res.json({
                success: true,
                status: 'waiting_qr',
                qr: pending?.qr || null
            });
        } else {
            res.json({
                success: true,
                status: 'disconnected'
            });
        }
    } catch (error) {
        logger.error(`Error obteniendo status ${userId}:`, error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Enviar mensaje
app.post('/api/send', authenticateToken, async (req, res) => {
    const userId = req.user.userId;
    const { to, message, media } = req.body;

    if (!to || !message) {
        return res.status(400).json({
            success: false,
            error: 'Faltan parámetros requeridos (to, message)'
        });
    }

    try {
        const client = activeClients.get(userId);
        
        if (!client || !client.info) {
            if (!messageQueue.has(userId)) {
                messageQueue.set(userId, []);
            }
            messageQueue.get(userId).push({ to, message, timestamp: Date.now() });
            
            return res.json({
                success: true,
                queued: true,
                message: 'Mensaje añadido a la cola (cliente no conectado)'
            });
        }

        const sentMessage = await sendMessage(userId, to, message, media);
        
        res.json({
            success: true,
            messageId: sentMessage.id._serialized,
            timestamp: sentMessage.timestamp
        });

    } catch (error) {
        logger.error(`Error enviando mensaje desde ${userId}:`, error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Obtener chats
app.get('/api/chats', authenticateToken, async (req, res) => {
    const userId = req.user.userId;

    try {
        const client = activeClients.get(userId);
        
        if (!client || !client.info) {
            return res.status(400).json({
                success: false,
                error: 'Cliente no conectado'
            });
        }

        const chats = await client.getChats();
        const chatList = chats.slice(0, 20).map(chat => ({
            id: chat.id._serialized,
            name: chat.name,
            isGroup: chat.isGroup,
            lastMessage: chat.lastMessage ? {
                body: chat.lastMessage.body,
                timestamp: chat.lastMessage.timestamp,
                from: chat.lastMessage.from
            } : null,
            unreadCount: chat.unreadCount
        }));

        res.json({
            success: true,
            chats: chatList
        });

    } catch (error) {
        logger.error(`Error obteniendo chats ${userId}:`, error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Obtener mensajes de un chat
app.get('/api/chat/:chatId/messages', authenticateToken, async (req, res) => {
    const userId = req.user.userId;
    const { chatId } = req.params;
    const limit = parseInt(req.query.limit) || 50;

    try {
        const client = activeClients.get(userId);
        
        if (!client || !client.info) {
            return res.status(400).json({
                success: false,
                error: 'Cliente no conectado'
            });
        }

        const chat = await client.getChatById(chatId);
        const messages = await chat.fetchMessages({ limit });

        const messageList = messages.map(msg => ({
            id: msg.id._serialized,
            body: msg.body,
            from: msg.from,
            to: msg.to,
            timestamp: msg.timestamp,
            fromMe: msg.fromMe,
            type: msg.type,
            hasMedia: msg.hasMedia
        }));

        res.json({
            success: true,
            messages: messageList
        });

    } catch (error) {
        logger.error(`Error obteniendo mensajes ${userId}:`, error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Webhook para recibir notificaciones de la web app
app.post('/api/webhook', verifyWebhookSecret, async (req, res) => {
    const { event, userId, data } = req.body;

    logger.info(`Webhook recibido: ${event} para usuario ${userId}`);

    try {
        switch (event) {
            case 'send_message':
                const client = activeClients.get(userId);
                if (client && client.info) {
                    await sendMessage(userId, data.to, data.message, data.media);
                } else {
                    if (!messageQueue.has(userId)) {
                        messageQueue.set(userId, []);
                    }
                    messageQueue.get(userId).push({
                        to: data.to,
                        message: data.message,
                        timestamp: Date.now()
                    });
                }
                break;

            case 'disconnect_client':
                cleanup(userId);
                break;

            default:
                logger.warn(`Evento webhook desconocido: ${event}`);
        }

        res.json({ success: true });
    } catch (error) {
        logger.error(`Error procesando webhook:`, error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Endpoint para generar token JWT (para testing)
app.post('/api/generate-token', (req, res) => {
    const { userId } = req.body;
    
    if (!userId) {
        return res.status(400).json({
            success: false,
            error: 'userId requerido'
        });
    }

    const token = jwt.sign(
        { userId: userId },
        JWT_SECRET,
        { expiresIn: '24h' }
    );

    res.json({
        success: true,
        token: token
    });
});

// Obtener estadísticas básicas
app.get('/api/stats', authenticateToken, (req, res) => {
    const userId = req.user.userId;
    
    res.json({
        success: true,
        stats: {
            messagesSent: 0, // TODO: Implementar contador real
            messagesReceived: 0, // TODO: Implementar contador real
            activeChats: 0, // TODO: Implementar contador real
            connectionStatus: activeClients.has(userId) ? 'connected' : 'disconnected'
        }
    });
});

// Obtener conversaciones (preview)
app.get('/api/conversations', authenticateToken, async (req, res) => {
    const userId = req.user.userId;
    const limit = parseInt(req.query.limit) || 5;

    try {
        const client = activeClients.get(userId);
        
        if (!client || !client.info) {
            return res.json({
                success: true,
                conversations: []
            });
        }

        const chats = await client.getChats();
        const conversations = chats.slice(0, limit).map(chat => ({
            name: chat.name || chat.id.user,
            phone: chat.id.user,
            lastMessage: chat.lastMessage?.body || 'Sin mensajes',
            lastMessageTime: chat.lastMessage ? 
                new Date(chat.lastMessage.timestamp * 1000).toLocaleTimeString('es-ES', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                }) : '--'
        }));

        res.json({
            success: true,
            conversations: conversations
        });

    } catch (error) {
        logger.error(`Error obteniendo conversaciones ${userId}:`, error);
        res.json({
            success: true,
            conversations: []
        });
    }
});

// Manejo de errores global
app.use((error, req, res, next) => {
    logger.error('Error no manejado:', error);
    res.status(500).json({
        success: false,
        error: 'Error interno del servidor'
    });
});

// Manejo de rutas no encontradas
app.use('*', (req, res) => {
    res.status(404).json({
        success: false,
        error: 'Ruta no encontrada'
    });
});

// ========== INICIALIZACIÓN DEL SERVIDOR ==========

// Función de inicialización del servidor
async function initializeServer() {
    try {
        await ensureSessionsDirectory();
        await initializeSessionsCleanup();
        
        logger.info('🚀 Servidor WhatsApp iniciado en puerto ' + PORT);
        logger.info('📱 Máximo de clientes: ' + MAX_CLIENTS);
        logger.info('🔐 JWT Secret configurado: ' + !!JWT_SECRET);
        logger.info('🌐 Web App URL: ' + (process.env.WEBAPP_URL || 'no configurada'));
        logger.info('🔗 Webhook URL: ' + WEBAPP_API_URL);
        
        return true;
    } catch (error) {
        logger.error('❌ Error inicializando servidor:', error);
        throw error;
    }
}

// Limpieza al cerrar el servidor
process.on('SIGINT', async () => {
    logger.info('🛑 Cerrando servidor (SIGINT)...');
    
    // Cerrar todos los clientes activos
    const cleanupPromises = [];
    for (const [userId, client] of activeClients) {
        cleanupPromises.push(
            new Promise(async (resolve) => {
                try {
                    await client.destroy();
                    logger.info(`Cliente ${userId} cerrado correctamente`);
                } catch (error) {
                    logger.error(`Error cerrando cliente ${userId}:`, error);
                }
                resolve();
            })
        );
    }
    
    // Esperar a que todos los clientes se cierren (máximo 10 segundos)
    await Promise.race([
        Promise.all(cleanupPromises),
        new Promise(resolve => setTimeout(resolve, 10000))
    ]);
    
    logger.info('✅ Servidor cerrado correctamente');
    process.exit(0);
});

process.on('SIGTERM', async () => {
    logger.info('🛑 SIGTERM recibido, cerrando servidor gracefulmente...');
    
    for (const [userId, client] of activeClients) {
        try {
            await client.destroy();
            logger.info(`Cliente ${userId} cerrado por SIGTERM`);
        } catch (error) {
            logger.error(`Error cerrando cliente ${userId}:`, error);
        }
    }
    
    logger.info('✅ Cierre graceful completado');
    process.exit(0);
});

// Monitoreo de memoria (opcional)
setInterval(() => {
    const memUsage = process.memoryUsage();
    const memUsageMB = {
        rss: Math.round(memUsage.rss / 1024 / 1024),
        heapTotal: Math.round(memUsage.heapTotal / 1024 / 1024),
        heapUsed: Math.round(memUsage.heapUsed / 1024 / 1024),
        external: Math.round(memUsage.external / 1024 / 1024)
    };
    
    logger.debug('Uso de memoria:', {
        ...memUsageMB,
        activeClients: activeClients.size,
        pendingConnections: pendingConnections.size
    });
    
    // Alertar si el uso de memoria es muy alto (más de 1GB)
    if (memUsageMB.heapUsed > 1024) {
        logger.warn(`⚠️ Alto uso de memoria: ${memUsageMB.heapUsed}MB heap used`);
    }
}, 300000); // Cada 5 minutos

// Limpiar conexiones pendientes que llevan mucho tiempo
setInterval(() => {
    const now = Date.now();
    const timeout = 10 * 60 * 1000; // 10 minutos
    
    for (const [userId, connection] of pendingConnections) {
        if (now - connection.timestamp > timeout) {
            logger.info(`Limpiando conexión pendiente expirada para usuario ${userId}`);
            pendingConnections.delete(userId);
            
            // También limpiar el cliente si existe pero no está conectado
            if (activeClients.has(userId)) {
                const client = activeClients.get(userId);
                if (!client.info) {
                    cleanup(userId);
                }
            }
        }
    }
}, 60000); // Cada minuto

// Iniciar servidor
app.listen(PORT, async () => {
    try {
        await initializeServer();
    } catch (error) {
        logger.error('💥 Error fatal durante inicialización:', error);
        process.exit(1);
    }
});

// Exportar para testing
module.exports = app;