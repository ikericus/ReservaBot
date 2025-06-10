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
const WEBAPP_URL = process.env.WEBAPP_URL || 'http://localhost';
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;
const MAX_CLIENTS = parseInt(process.env.MAX_CLIENTS) || 50;

// ========== SUPRESIÓN DE ERRORES DE CACHE ==========

// Guardar referencias originales
const originalConsoleError = console.error;
const originalConsoleWarn = console.warn;

// Función para filtrar errores conocidos de WhatsApp
function isKnownWhatsAppError(message) {
    const knownErrors = [
        'LocalWebCache',
        'Cannot read properties of null',
        'webCache',
        'Evaluation failed',
        'Protocol error (Runtime.evaluate)',
        'Session closed',
        'Target closed',
        'Cannot read property'
    ];
    
    return knownErrors.some(error => message.includes(error));
}

// Sobrescribir console.error
console.error = (...args) => {
    const message = args.join(' ');
    if (!isKnownWhatsAppError(message)) {
        originalConsoleError.apply(console, args);
    }
};

// Sobrescribir console.warn
console.warn = (...args) => {
    const message = args.join(' ');
    if (!isKnownWhatsAppError(message)) {
        originalConsoleWarn.apply(console, args);
    }
};

// ========== MANEJO DE ERRORES NO CAPTURADOS ==========

process.on('unhandledRejection', (reason, promise) => {
    const reasonStr = reason?.message || reason?.stack || String(reason);
    
    // Filtrar errores específicos de WhatsApp cache
    if (isKnownWhatsAppError(reasonStr)) {
        logger.debug('Ignorando error conocido de WhatsApp cache');
        return;
    }
    
    logger.error('Promesa rechazada no manejada:', {
        reason: reason,
        stack: reason?.stack,
        service: 'whatsapp-server'
    });
    
    // Manejo de cleanup solo para errores reales
    const errorString = reason?.stack || reason?.message || '';
    const userIdMatch = errorString.match(/client_(\d+)/);
    
    if (userIdMatch) {
        const affectedUserId = userIdMatch[1];
        logger.info(`Usuario afectado por error: ${affectedUserId}`);
        
        if (activeClients.has(affectedUserId)) {
            cleanup(affectedUserId);
        }
        
        cleanCorruptedSessions(affectedUserId).catch(err => {
            logger.error(`Error limpiando sesión corrupta:`, err);
        });
    }
});

process.on('uncaughtException', (error) => {
    const errorStr = error.message || error.stack || String(error);
    
    if (isKnownWhatsAppError(errorStr)) {
        logger.debug('Ignorando excepción conocida de WhatsApp cache');
        return;
    }
    
    logger.error('Excepción no capturada:', {
        error: error.message,
        stack: error.stack,
        service: 'whatsapp-server'
    });
});

// ========== MIDDLEWARE DE SEGURIDAD ==========

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
    windowMs: 15 * 60 * 1000,
    max: 100,
    message: { error: 'Demasiadas peticiones, intenta más tarde' }
});
app.use('/api/', limiter);

app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// ========== STORAGE GLOBAL ==========

const activeClients = new Map();
const pendingConnections = new Map();
const messageQueue = new Map();

// ========== MIDDLEWARE DE AUTENTICACIÓN ==========

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

function verifyWebhookSecret(req, res, next) {
    const providedSecret = req.headers['x-webhook-secret'];
    
    if (!providedSecret || providedSecret !== WEBHOOK_SECRET) {
        return res.status(401).json({ success: false, error: 'Secret inválido' });
    }
    
    next();
}

// ========== FUNCIONES DE SESIÓN ==========

async function cleanCorruptedSessions(userId) {
    const sessionPath = path.join('./sessions', `session-client_${userId}`);
    const wwebSessionPath = path.join('./sessions', `wweb_session_client_${userId}`);
    
    logger.info(`Limpiando sesiones para usuario ${userId}`);
    
    try {
        await fs.rmdir(sessionPath, { recursive: true });
        logger.info(`Sesión eliminada: ${sessionPath}`);
    } catch (error) {
        logger.debug(`No se pudo eliminar ${sessionPath}`);
    }
    
    try {
        await fs.rmdir(wwebSessionPath, { recursive: true });
        logger.info(`Sesión wweb eliminada: ${wwebSessionPath}`);
    } catch (error) {
        logger.debug(`No se pudo eliminar ${wwebSessionPath}`);
    }
}

async function ensureSessionsDirectory() {
    const sessionsDir = './sessions';
    const logsDir = './logs';
    
    try {
        await fs.mkdir(sessionsDir, { recursive: true });
        await fs.mkdir(logsDir, { recursive: true });
        
        await fs.access(sessionsDir, fs.constants.W_OK);
        await fs.access(logsDir, fs.constants.W_OK);
        
        logger.info('✅ Directorios verificados');
    } catch (error) {
        logger.error('❌ Error con directorios:', error.message);
        throw error;
    }
}

async function initializeSessionsCleanup() {
    logger.info('🧹 Limpieza inicial de sesiones...');
    
    const sessionsDir = './sessions';
    
    try {
        const sessions = await fs.readdir(sessionsDir).catch(() => []);
        
        for (const sessionDir of sessions) {
            if (sessionDir.startsWith('session-client_') || sessionDir.startsWith('wweb_session_client_')) {
                const sessionPath = path.join(sessionsDir, sessionDir);
                
                try {
                    const stats = await fs.stat(sessionPath);
                    const ageHours = (Date.now() - stats.mtime.getTime()) / (1000 * 60 * 60);
                    
                    if (ageHours > 24) {
                        logger.info(`Limpiando sesión antigua: ${sessionDir}`);
                        await fs.rmdir(sessionPath, { recursive: true });
                    }
                } catch (error) {
                    try {
                        await fs.rmdir(sessionPath, { recursive: true });
                        logger.info(`Sesión corrupta eliminada: ${sessionDir}`);
                    } catch (cleanupError) {
                        logger.error(`No se pudo limpiar: ${sessionDir}`);
                    }
                }
            }
        }
    } catch (error) {
        logger.error('Error durante limpieza:', error);
    }
    
    logger.info('✅ Limpieza completada');
}

// ========== FUNCIÓN PRINCIPAL DE CLIENTE WHATSAPP ==========

async function createWhatsAppClient(userId) {
    if (activeClients.has(userId)) {
        logger.warn(`Cliente ${userId} ya existe`);
        return activeClients.get(userId);
    }

    if (activeClients.size >= MAX_CLIENTS) {
        throw new Error('Máximo número de clientes alcanzado');
    }

    logger.info(`Iniciando conexión para usuario ${userId}`);

    // Limpiar sesiones previas
    await cleanCorruptedSessions(userId);

    // Configuración optimizada de Puppeteer
    const puppeteerConfig = {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--single-process',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-features=TranslateUI',
            '--disable-ipc-flooding-protection',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-default-apps',
            '--no-first-run',
            '--no-zygote',
            '--disable-web-security'
        ],
        defaultViewport: null,
        timeout: 60000
    };

    // Cliente simplificado sin configuraciones problemáticas
    const client = new Client({
        authStrategy: new LocalAuth({
            clientId: `client_${userId}`,
            dataPath: './sessions',
            webCache: false // ← fuerza desactivación
        }),
        puppeteer: puppeteerConfig,
        qrMaxRetries: 5,
        authTimeoutMs: 60000,
        takeoverOnConflict: true,
        takeoverTimeoutMs: 30000
    });

    // Timeout para inicialización
    const initTimeout = setTimeout(() => {
        logger.error(`Timeout inicializando cliente ${userId}`);
        cleanup(userId);
    }, 120000);

    // ========== EVENTOS DEL CLIENTE ==========

    client.on('qr', async (qr) => {
        logger.info(`QR generado para usuario ${userId}`);
        try {
            const qrDataURL = await QRCode.toDataURL(qr);
            pendingConnections.set(userId, { qr: qrDataURL, timestamp: Date.now() });
            await notifyWebApp('qr_generated', userId, { qr: qrDataURL });
        } catch (error) {
            logger.error(`Error generando QR:`, error.message);
        }
    });

    client.on('ready', async () => {
        clearTimeout(initTimeout);
        logger.info(`Cliente ${userId} listo: ${client.info.wid.user}`);
        
        pendingConnections.delete(userId);
        
        try {
            await notifyWebApp('client_ready', userId, {
                phoneNumber: client.info.wid.user,
                name: client.info.pushname
            });
            
            await processQueuedMessages(userId);
            
        } catch (error) {
            logger.error(`Error notificando cliente listo:`, error.message);
        }
    });

    client.on('authenticated', () => {
        logger.info(`Cliente ${userId} autenticado`);
        pendingConnections.delete(userId);
    });

    client.on('auth_failure', (msg) => {
        clearTimeout(initTimeout);
        logger.error(`Fallo autenticación ${userId}:`, msg);
        cleanup(userId);
        notifyWebApp('auth_failure', userId, { error: msg }).catch(() => {});
    });

    client.on('disconnected', (reason) => {
        clearTimeout(initTimeout);
        logger.info(`Cliente ${userId} desconectado: ${reason}`);
        cleanup(userId);
        notifyWebApp('client_disconnected', userId, { reason }).catch(() => {});
    });

    client.on('message', async (message) => {
        try {
            await handleIncomingMessage(userId, message);
        } catch (error) {
            logger.error(`Error manejando mensaje:`, error.message);
        }
    });

    client.on('message_create', async (message) => {
        if (message.fromMe) {
            try {
                await logOutgoingMessage(userId, message);
            } catch (error) {
                logger.error(`Error registrando mensaje:`, error.message);
            }
        }
    });

    client.on('error', (error) => {
        const errorStr = error.message || String(error);
        
        // Ignorar errores conocidos de cache
        if (isKnownWhatsAppError(errorStr)) {
            return;
        }
        
        logger.error(`Error del cliente ${userId}:`, error.message);
        
        // Solo cleanup para errores graves
        if (errorStr.includes('Protocol error') || 
            errorStr.includes('Target closed') ||
            errorStr.includes('Session closed')) {
            cleanup(userId);
        }
    });

    activeClients.set(userId, client);
    
    try {
        await client.initialize();
        logger.info(`Cliente ${userId} inicializado`);
    } catch (error) {
        clearTimeout(initTimeout);

        const errorStr = error.message || String(error);
        
        if (isKnownWhatsAppError(errorStr)) {
            logger.warn(`Error conocido ignorado durante inicialización de ${userId}: ${errorStr}`);
            return client; // ← devuelve aunque haya error si es de caché
        }

        logger.error(`Error inicializando cliente ${userId}:`, errorStr);

        // Extra: limpiar por si algo quedó mal
        cleanup(userId);
        await cleanCorruptedSessions(userId);

        throw error;
    }

    return client;
}

// ========== FUNCIONES DE MANEJO DE MENSAJES ==========

async function handleIncomingMessage(userId, message) {
    try {
        logger.info(`Mensaje entrante ${userId}: ${message.from}`);

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
                logger.error(`Error descargando media:`, error.message);
            }
        }

        await notifyWebApp('message_received', userId, messageData);

    } catch (error) {
        logger.error(`Error procesando mensaje:`, error.message);
    }
}

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
        logger.error(`Error registrando mensaje:`, error.message);
    }
}

async function notifyWebApp(event, userId, data) {
    try {
        const payload = {
            event,
            userId,
            data,
            timestamp: Date.now()
        };

        const response = await axios.post(`${WEBAPP_URL}/api/whatsapp-webhook`, payload, {
            headers: {
                'Content-Type': 'application/json',
                'X-Webhook-Secret': WEBHOOK_SECRET
            },
            timeout: 10000,
            validateStatus: (status) => status < 500
        });

        logger.debug(`Webhook enviado: ${event} para usuario ${userId}`);
        return response.data;
        
    } catch (error) {
        if (error.code === 'ECONNREFUSED') {
            logger.warn(`Webhook no disponible: ${event} - ${error.message}`);
        } else {
            logger.error(`Error webhook: ${event}`, {
                message: error.message,
                code: error.code,
                status: error.response?.status,
                data: error.response?.data
            });
        }
    }
}

async function processQueuedMessages(userId) {
    const queue = messageQueue.get(userId);
    if (!queue || queue.length === 0) return;

    logger.info(`Procesando ${queue.length} mensajes en cola para ${userId}`);

    for (const queuedMessage of queue) {
        try {
            await sendMessage(userId, queuedMessage.to, queuedMessage.message);
            logger.info(`Mensaje de cola enviado a ${queuedMessage.to}`);
        } catch (error) {
            logger.error(`Error enviando mensaje de cola:`, error.message);
        }
    }

    messageQueue.delete(userId);
}

function cleanup(userId) {
    logger.info(`Cleanup para usuario ${userId}`);
    
    if (activeClients.has(userId)) {
        const client = activeClients.get(userId);
        try {
            if (client && typeof client.destroy === 'function') {
                client.destroy();
            }
        } catch (error) {
            logger.debug(`Error destruyendo cliente (ignorado):`, error.message);
        }
        activeClients.delete(userId);
    }
    
    pendingConnections.delete(userId);
    logger.info(`Cleanup completado para usuario ${userId}`);
}

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
        logger.error(`Error enviando mensaje:`, error.message);
        throw error;
    }
}

// ========== RUTAS API ==========

app.get('/health', (req, res) => {
    const memUsage = process.memoryUsage();
    
    res.json({
        status: 'healthy',
        uptime: process.uptime(),
        timestamp: new Date().toISOString(),
        activeClients: activeClients.size,
        pendingConnections: pendingConnections.size,
        messageQueues: messageQueue.size,
        memory: {
            rss: Math.round(memUsage.rss / 1024 / 1024) + 'MB',
            heapUsed: Math.round(memUsage.heapUsed / 1024 / 1024) + 'MB'
        }
    });
});

app.get('/api/info', authenticateToken, (req, res) => {
    res.json({
        success: true,
        server: {
            version: '1.0.1',
            uptime: process.uptime(),
            activeClients: activeClients.size,
            maxClients: MAX_CLIENTS,
            pendingConnections: pendingConnections.size,
            messageQueues: messageQueue.size
        }
    });
});

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
        logger.error(`Error conectando cliente ${userId}:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

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
        logger.error(`Error desconectando cliente ${userId}:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

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
        logger.error(`Error obteniendo status ${userId}:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

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
                message: 'Mensaje añadido a la cola'
            });
        }

        const sentMessage = await sendMessage(userId, to, message, media);
        
        res.json({
            success: true,
            messageId: sentMessage.id._serialized,
            timestamp: sentMessage.timestamp
        });

    } catch (error) {
        logger.error(`Error enviando mensaje desde ${userId}:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

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
        logger.error(`Error obteniendo chats ${userId}:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

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
        logger.error(`Error obteniendo mensajes ${userId}:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

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
        logger.error(`Error procesando webhook:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

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

app.get('/api/stats', authenticateToken, (req, res) => {
    const userId = req.user.userId;
    
    res.json({
        success: true,
        stats: {
            connectionStatus: activeClients.has(userId) ? 'connected' : 'disconnected',
            queuedMessages: messageQueue.get(userId)?.length || 0
        }
    });
});

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
        logger.error(`Error obteniendo conversaciones ${userId}:`, error.message);
        res.json({
            success: true,
            conversations: []
        });
    }
});

app.post('/api/restart-client', authenticateToken, async (req, res) => {
    const userId = req.user.userId;

    try {
        logger.info(`Reiniciando cliente para usuario ${userId}`);
        
        cleanup(userId);
        await cleanCorruptedSessions(userId);
        await createWhatsAppClient(userId);
        
        res.json({
            success: true,
            message: 'Cliente reiniciado correctamente'
        });

    } catch (error) {
        logger.error(`Error reiniciando cliente ${userId}:`, error.message);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Manejo de errores global
app.use((error, req, res, next) => {
    logger.error('Error no manejado:', error.message);
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

// ========== FUNCIONES DE INICIALIZACIÓN ==========

async function initializeServer() {
    try {
        await ensureSessionsDirectory();
        await initializeSessionsCleanup();
        
        logger.info('🚀 Servidor WhatsApp iniciado en puerto ' + PORT);
        logger.info('📱 Máximo de clientes: ' + MAX_CLIENTS);
        logger.info('🔐 JWT Secret configurado: ' + !!JWT_SECRET);
        logger.info('🔗 Webhook URL: ' + WEBAPP_URL);
        
        return true;
    } catch (error) {
        logger.error('❌ Error inicializando servidor:', error.message);
        throw error;
    }
}

async function periodicSessionMaintenance() {
    logger.info('🧹 Mantenimiento periódico de sesiones');
    
    try {
        const sessionsDir = './sessions';
        const sessions = await fs.readdir(sessionsDir).catch(() => []);
        
        for (const sessionDir of sessions) {
            if (sessionDir.startsWith('session-client_') || sessionDir.startsWith('wweb_session_client_')) {
                const sessionPath = path.join(sessionsDir, sessionDir);
                
                try {
                    const stats = await fs.stat(sessionPath);
                    const ageHours = (Date.now() - stats.mtime.getTime()) / (1000 * 60 * 60);
                    
                    // Limpiar sesiones muy antiguas (más de 7 días)
                    if (ageHours > 168) {
                        logger.info(`Limpiando sesión muy antigua: ${sessionDir}`);
                        await fs.rmdir(sessionPath, { recursive: true });
                    }
                } catch (error) {
                    logger.warn(`Error en mantenimiento de sesión ${sessionDir}`);
                }
            }
        }
    } catch (error) {
        logger.error('Error en mantenimiento periódico:', error.message);
    }
}

// ========== MANEJO DE CIERRE GRACEFUL ==========

process.on('SIGINT', async () => {
    logger.info('🛑 Cerrando servidor (SIGINT)...');
    
    const cleanupPromises = [];
    for (const [userId, client] of activeClients) {
        cleanupPromises.push(
            new Promise(async (resolve) => {
                try {
                    if (client && typeof client.destroy === 'function') {
                        await client.destroy();
                        logger.info(`Cliente ${userId} cerrado correctamente`);
                    }
                } catch (error) {
                    logger.error(`Error cerrando cliente ${userId}:`, error.message);
                }
                resolve();
            })
        );
    }
    
    // Esperar máximo 10 segundos para cerrar todos los clientes
    await Promise.race([
        Promise.all(cleanupPromises),
        new Promise(resolve => setTimeout(resolve, 10000))
    ]);
    
    logger.info('✅ Servidor cerrado correctamente');
    process.exit(0);
});

process.on('SIGTERM', async () => {
    logger.info('🛑 SIGTERM recibido, cerrando servidor...');
    
    for (const [userId, client] of activeClients) {
        try {
            if (client && typeof client.destroy === 'function') {
                await client.destroy();
                logger.info(`Cliente ${userId} cerrado por SIGTERM`);
            }
        } catch (error) {
            logger.error(`Error cerrando cliente ${userId}:`, error.message);
        }
    }
    
    logger.info('✅ Cierre graceful completado');
    process.exit(0);
});

// ========== MONITOREO Y MANTENIMIENTO ==========

// Monitoreo de memoria cada 5 minutos
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
    
    // Alertar si el uso de memoria es muy alto
    if (memUsageMB.heapUsed > 1024) {
        logger.warn(`⚠️ Alto uso de memoria: ${memUsageMB.heapUsed}MB heap used`);
        
        // Si supera 2GB, forzar garbage collection
        if (memUsageMB.heapUsed > 2048) {
            logger.warn('🗑️ Forzando garbage collection');
            if (global.gc) {
                global.gc();
            }
        }
    }
}, 300000);

// Limpiar conexiones pendientes expiradas cada minuto
setInterval(() => {
    const now = Date.now();
    const timeout = 10 * 60 * 1000; // 10 minutos
    
    for (const [userId, connection] of pendingConnections) {
        if (now - connection.timestamp > timeout) {
            logger.info(`Limpiando conexión pendiente expirada para usuario ${userId}`);
            pendingConnections.delete(userId);
            
            // También limpiar el cliente si no está conectado
            if (activeClients.has(userId)) {
                const client = activeClients.get(userId);
                if (!client.info) {
                    cleanup(userId);
                }
            }
        }
    }
}, 60000);

// Mantenimiento periódico de sesiones cada 6 horas
setInterval(periodicSessionMaintenance, 6 * 60 * 60 * 1000);

// Verificar estado de clientes cada 30 segundos
setInterval(() => {
    for (const [userId, client] of activeClients) {
        try {
            // Si el cliente existe pero no tiene info después de mucho tiempo, limpiar
            if (!client.info && !pendingConnections.has(userId)) {
                const clientAge = Date.now() - (client._createdAt || 0);
                if (clientAge > 300000) { // 5 minutos
                    logger.warn(`Cliente ${userId} sin info después de 5 minutos, limpiando`);
                    cleanup(userId);
                }
            }
        } catch (error) {
            logger.error(`Error verificando estado del cliente ${userId}:`, error.message);
            cleanup(userId);
        }
    }
}, 30000);

// ========== INICIAR SERVIDOR ==========

app.listen(PORT, async () => {
    try {
        await initializeServer();
        
        // Ejecutar mantenimiento inicial después de un minuto
        setTimeout(periodicSessionMaintenance, 60000);
        
        logger.info('✅ Servidor completamente inicializado y funcionando');
        
    } catch (error) {
        logger.error('💥 Error fatal durante inicialización:', error.message);
        process.exit(1);
    }
});

// Exportar para testing
module.exports = app;