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

// Función para crear cliente WhatsApp
async function createWhatsAppClient(userId) {
    if (activeClients.has(userId)) {
        logger.warn(`Cliente ${userId} ya existe`);
        return activeClients.get(userId);
    }

    if (activeClients.size >= MAX_CLIENTS) {
        throw new Error('Máximo número de clientes alcanzado');
    }

    const client = new Client({
        authStrategy: new LocalAuth({
            clientId: `client_${userId}`,
            dataPath: './sessions'
        }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--disable-gpu'
            ]
        }
    });

    // Eventos del cliente
    client.on('qr', async (qr) => {
        logger.info(`QR generado para usuario ${userId}`);
        try {
            const qrDataURL = await QRCode.toDataURL(qr, {
                width: 256,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            });
            
            pendingConnections.set(userId, {
                qr: qrDataURL,
                timestamp: Date.now()
            });

            // Notificar a la web app
            await notifyWebApp('qr_generated', userId, { qr: qrDataURL });
        } catch (error) {
            logger.error(`Error generando QR para ${userId}:`, error);
        }
    });

    client.on('ready', async () => {
        logger.info(`Cliente WhatsApp ${userId} listo`);
        pendingConnections.delete(userId);
        
        const clientInfo = client.info;
        logger.info(`Conectado como: ${clientInfo.wid.user}`);

        // Notificar a la web app
        await notifyWebApp('connected', userId, {
            phoneNumber: clientInfo.wid.user,
            name: clientInfo.pushname
        });

        // Procesar mensajes en cola
        await processQueuedMessages(userId);
    });

    client.on('authenticated', () => {
        logger.info(`Cliente ${userId} autenticado`);
    });

    client.on('auth_failure', async (msg) => {
        logger.error(`Fallo de autenticación para ${userId}:`, msg);
        await notifyWebApp('auth_failure', userId, { error: msg });
        cleanup(userId);
    });

    client.on('disconnected', async (reason) => {
        logger.warn(`Cliente ${userId} desconectado:`, reason);
        await notifyWebApp('disconnected', userId, { reason });
        cleanup(userId);
    });

    client.on('message', async (message) => {
        await handleIncomingMessage(userId, message);
    });

    client.on('message_create', async (message) => {
        if (message.fromMe) {
            await logOutgoingMessage(userId, message);
        }
    });

    activeClients.set(userId, client);
    
    try {
        await client.initialize();
        logger.info(`Inicializando cliente WhatsApp para usuario ${userId}`);
    } catch (error) {
        logger.error(`Error inicializando cliente ${userId}:`, error);
        cleanup(userId);
        throw error;
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

        // Si hay media, procesarla
        if (message.hasMedia) {
            try {
                const media = await message.downloadMedia();
                messageData.media = {
                    mimetype: media.mimetype,
                    filename: media.filename,
                    data: media.data // Base64
                };
            } catch (error) {
                logger.error(`Error descargando media:`, error);
            }
        }

        // Enviar a la web app
        await notifyWebApp('message_received', userId, messageData);

        // TODO: Aquí se puede añadir lógica de auto-respuesta
        // await handleAutoResponse(userId, message);

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
        // En producción, aquí podríamos implementar un sistema de retry
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
    if (activeClients.has(userId)) {
        const client = activeClients.get(userId);
        try {
            client.destroy();
        } catch (error) {
            logger.error(`Error destruyendo cliente ${userId}:`, error);
        }
        activeClients.delete(userId);
    }
    pendingConnections.delete(userId);
    logger.info(`Recursos limpiados para usuario ${userId}`);
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

    // Formatear número de teléfono
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

// Información del servidor (requiere auth)
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
            return res.json({
                success: true,
                status: 'already_connected',
                message: 'Cliente ya conectado'
            });
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
                status: 'connected',
                phoneNumber: client.info.wid.user,
                name: client.info.pushname,
                connectedAt: client.info.connected
            });
        } else if (client || pending) {
            res.json({
                success: true,
                status: 'connecting',
                qr: pending?.qr || null,
                qrTimestamp: pending?.timestamp || null
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
            // Si no está conectado, añadir a la cola
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
                    // Añadir a cola
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

// Limpieza al cerrar el servidor
process.on('SIGINT', async () => {
    logger.info('Cerrando servidor...');
    
    // Cerrar todos los clientes activos
    for (const [userId, client] of activeClients) {
        try {
            await client.destroy();
            logger.info(`Cliente ${userId} cerrado`);
        } catch (error) {
            logger.error(`Error cerrando cliente ${userId}:`, error);
        }
    }
    
    process.exit(0);
});

process.on('SIGTERM', async () => {
    logger.info('SIGTERM recibido, cerrando servidor gracefulmente...');
    
    for (const [userId, client] of activeClients) {
        try {
            await client.destroy();
        } catch (error) {
            logger.error(`Error cerrando cliente ${userId}:`, error);
        }
    }
    
    process.exit(0);
});

// Iniciar servidor
app.listen(PORT, () => {
    logger.info(`🚀 Servidor WhatsApp iniciado en puerto ${PORT}`);
    logger.info(`📱 Máximo de clientes: ${MAX_CLIENTS}`);
    logger.info(`🔐 JWT Secret configurado: ${!!JWT_SECRET}`);
    logger.info(`🌐 Web App URL: ${process.env.WEBAPP_URL}`);
});

module.exports = app;