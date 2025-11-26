// server.js - Servidor WhatsApp con persistencia de sesiones
require('dotenv').config();

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const jwt = require('jsonwebtoken');
const QRCode = require('qrcode');
const axios = require('axios');
const winston = require('winston');
const path = require('path');
const fs = require('fs');

// Importar whatsapp-web.js
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');

// Configuración
const PORT = process.env.PORT || 3001;
const JWT_SECRET = process.env.JWT_SECRET;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;
const WEBAPP_URL = process.env.WEBAPP_URL || 'http://localhost';
const MAX_CLIENTS = parseInt(process.env.MAX_CLIENTS) || 50;
const SESSIONS_DIR = process.env.SESSIONS_DIR || './sessions';

// ⭐ NUEVO: Archivo de persistencia
const ACTIVE_SESSIONS_FILE = './active_sessions.json';

// Verificar configuración
if (!JWT_SECRET || !WEBHOOK_SECRET) {
    console.error('❌ ERROR: JWT_SECRET y WEBHOOK_SECRET son requeridos en .env');
    process.exit(1);
}

// Configurar logger
const logger = winston.createLogger({
    level: process.env.LOG_LEVEL || 'info',
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.errors({ stack: true }),
        winston.format.json()
    ),
    defaultMeta: { service: 'whatsapp-server' },
    transports: [
        new winston.transports.File({ filename: './logs/error.log', level: 'error' }),
        new winston.transports.File({ filename: './logs/combined.log' }),
        new winston.transports.Console({
            format: winston.format.combine(
                winston.format.colorize(),
                winston.format.simple()
            )
        }),
        new winston.transports.File({
            filename: './logs/info.log',
            level: 'info',
            format: winston.format((info) => {
                return info.level === 'info' ? info : false;
            })()
        }),
    ]
});

// Crear directorios necesarios
[SESSIONS_DIR, './logs'].forEach(dir => {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
});

// Variables globales
const clients = new Map();
const qrCodes = new Map();

// ==================== FUNCIONES DE PERSISTENCIA ====================

async function saveActiveSessions() {
    try {
        const sessionsData = {};
        
        for (const [userId, clientData] of clients) {
            sessionsData[userId] = {
                userId: userId,
                status: clientData.status,
                phoneNumber: clientData.info?.wid?.user || null,
                pushname: clientData.info?.pushname || null,
                lastActivity: new Date().toISOString()
            };
        }
        
        await fs.promises.writeFile(
            ACTIVE_SESSIONS_FILE, 
            JSON.stringify(sessionsData, null, 2)
        );

        logger.info('Sesiones guardadas correctamente');
        
    } catch (error) {
        logger.error('Error guardando sesiones:', error);
    }
}

async function loadActiveSessions() {
    try {
        const data = await fs.promises.readFile(ACTIVE_SESSIONS_FILE, 'utf-8');
        
        if (!data || data.trim() === '') {
            logger.warn('Archivo de sesiones vacío, inicializando nuevo archivo');
            await fs.promises.writeFile(ACTIVE_SESSIONS_FILE, '{}');
            return {};
        }
        
        const sessionsData = JSON.parse(data);
        return sessionsData;
        
    } catch (error) {
        if (error.code === 'ENOENT') {
            await fs.promises.writeFile(ACTIVE_SESSIONS_FILE, '{}');
            return {};
        }
        
        if (error instanceof SyntaxError) {
            logger.error('Archivo de sesiones corrupto, creando backup y reiniciando');
            try {
                const backupFile = `${ACTIVE_SESSIONS_FILE}.backup.${Date.now()}`;
                await fs.promises.rename(ACTIVE_SESSIONS_FILE, backupFile);
                logger.info(`Backup guardado en: ${backupFile}`);
            } catch (e) {
                await fs.promises.unlink(ACTIVE_SESSIONS_FILE).catch(() => {});
            }
            await fs.promises.writeFile(ACTIVE_SESSIONS_FILE, '{}');
            return {};
        }
        
        logger.error('Error cargando sesiones:', error);
        return {};
    }
}

async function removeSessionFromFile(userId) {
    try {
        const sessions = await loadActiveSessions();
        delete sessions[userId];
        await fs.promises.writeFile(
            ACTIVE_SESSIONS_FILE, 
            JSON.stringify(sessions, null, 2)
        );
    } catch (error) {
        logger.error('Error eliminando sesión:', error);
    }
}
 
async function restoreAllSessions() {
    const savedSessions = await loadActiveSessions();
    const userIds = Object.keys(savedSessions);
    
    if (userIds.length === 0) {
        logger.info('No hay sesiones previas para restaurar');
        return;
    }
    
    logger.info(`🔄 Restaurando ${userIds.length} sesión(es) guardada(s)...`);
    
    for (const userId of userIds) {
        try {
            logger.info(`Restaurando cliente para usuario ${userId}`);
            
            const client = createWhatsAppClient(userId);
            
            clients.set(userId, {
                client: client,
                status: 'connecting',
                qr: null,
                info: null
            });
            
            await client.initialize();
            
            await new Promise(resolve => setTimeout(resolve, 2000));
            
        } catch (error) {
            logger.error(`Error restaurando sesión usuario ${userId}:`, error.message);
        }
    }
    
    logger.info('✅ Proceso de restauración completado');
}

// ==================== FIN FUNCIONES DE PERSISTENCIA ====================

// Configurar Express
const app = express();

app.use(helmet({
    contentSecurityPolicy: false
}));

app.use(cors({
    origin: [WEBAPP_URL, 'http://localhost', 'http://127.0.0.1'],
    credentials: true
}));

const limiter = rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 100,
    message: { error: 'Demasiadas peticiones, intenta más tarde' }
});
app.use('/api/', limiter);

app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

const authenticateJWT = (req, res, next) => {
    const authHeader = req.headers.authorization;
    
    if (!authHeader) {
        logger.error(`Token de autorización requerido`);
        return res.status(401).json({ error: 'Token de autorización requerido' });
    }
    
    const token = authHeader.split(' ')[1];
    
    jwt.verify(token, JWT_SECRET, (err, decoded) => {
        if (err) {
            logger.error(`Token inválido. Error: "${err.message}". Token: ${token}`);
            return res.status(403).json({ error: 'Token inválido', details: err.message });
        }
        req.userId = decoded.userId;
        next();
    });
};
 
function createWhatsAppClient(userId) {
    const clientId = `client_${userId}`;
    
    const client = new Client({
        authStrategy: new LocalAuth({
            clientId: clientId,
            dataPath: SESSIONS_DIR
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
                '--disable-gpu',
                '--disable-web-security',
                '--disable-features=VizDisplayCompositor'
            ]
        }
    });

    client.on('qr', async (qr) => {
        try {
            logger.info(`QR generado para usuario ${userId}`);
            
            const qrDataUrl = await QRCode.toDataURL(qr);
            qrCodes.set(userId, qrDataUrl);
            
            if (clients.has(userId)) {
                clients.get(userId).qr = qrDataUrl;
                clients.get(userId).status = 'waiting_qr';
            }
            
            // ⭐ Guardar sesiones
            await saveActiveSessions();
            
            notifyWebApp(userId, 'qr_generated', { qr: qrDataUrl });
            
        } catch (error) {
            logger.error(`Error generando QR para usuario ${userId}:`, error);
        }
    });

    client.on('ready', async () => {
        try {
            const info = client.info;
            logger.info(`Cliente ${userId} conectado como: ${info.wid.user}`);
            
            clients.set(userId, {
                client: client,
                status: 'ready',
                info: info,
                qr: null
            });
            
            qrCodes.delete(userId);
            
            // ⭐ Guardar sesiones
            await saveActiveSessions();
            
            notifyWebApp(userId, 'connected', {
                phoneNumber: info.wid.user,
                pushname: info.pushname
            });
            
        } catch (error) {
            logger.error(`Error en ready para usuario ${userId}:`, error);
        }
    });

    client.on('authenticated', async () => {
        logger.info(`Cliente ${userId} autenticado correctamente`);
        
        if (clients.has(userId)) {
            clients.get(userId).status = 'authenticated';
        }
        
        // ⭐ Guardar sesiones
        await saveActiveSessions();
    });

    client.on('auth_failure', async (msg) => {
        logger.error(`Fallo de autenticación para usuario ${userId}:`, msg);
        
        if (clients.has(userId)) {
            clients.get(userId).status = 'auth_failed';
        }
        
        // ⭐ Guardar sesiones
        await saveActiveSessions();
        
        notifyWebApp(userId, 'auth_failed', { error: msg });
    });

    client.on('disconnected', async (reason) => {
        logger.info(`Cliente ${userId} desconectado: ${reason}`);
        
        clients.delete(userId);
        qrCodes.delete(userId);
        
        // ⭐ Si fue logout, eliminar; si no, mantener
        if (reason === 'LOGOUT') {
            await removeSessionFromFile(userId);
        } else {
            await saveActiveSessions();
        }
        
        notifyWebApp(userId, 'disconnected', { reason: reason });
    });

    client.on('message', async (message) => {
        try {
            await handleIncomingMessage(userId, message);
        } catch (error) {
            logger.error(`Error procesando mensaje para usuario ${userId}:`, error);
        }
    });

    client.on('message_ack', async (message, ack) => {
        logger.info(`Mensaje ack para usuario ${userId}: ${message.id._serialized}, ack: ${ack}`);
        notifyWebApp(userId, 'message_ack', { ack: ack, messageId: message.id._serialized});
    });

    return client;
}

async function handleIncomingMessage(userId, message) {
    try {
        const phoneNumber = message.from.split('@')[0];
        
        let messageData = {
            id: message.id._serialized,
            from: message.from,
            to: message.to,
            body: message.body,
            type: message.type,
            timestamp: message.timestamp,
            isForwarded: message.isForwarded || false,
            phoneNumber: phoneNumber,
            contactName: phoneNumber,
            isGroup: false,
            chatName: null
        };
        
        try {
            const chat = await message.getChat();
            if (chat) {
                messageData.isGroup = chat.isGroup || false;
                messageData.chatName = chat.name || null;
            }
        } catch (chatError) {
            logger.debug(`No se pudo obtener chat: ${chatError.message}`);
        }
        
        try {
            const contact = await Promise.race([
                message.getContact(),
                new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Timeout')), 2000)
                )
            ]);
            
            if (contact) {
                messageData.contactName = contact.pushname || contact.name || phoneNumber;
            }
        } catch (contactError) {
            logger.debug(`Contacto no disponible: ${phoneNumber}`);
        }
        
        logger.info(`📥 Mensaje de ${messageData.contactName}: "${message.body.substring(0, 50)}..."`);
        
        await notifyWebApp(userId, 'message_received', messageData);
        
    } catch (error) {
        logger.error(`Error procesando mensaje entrante:`, error.message);
    }
}

async function notifyWebApp(userId, event, data) {
    try {
        const webhookUrl = `${WEBAPP_URL}/api/whatsapp-webhook`;
        
        const payload = {
            userId: userId,
            event: event,
            data: data,
            timestamp: new Date().toISOString()
        };
        
        const token = jwt.sign(payload, WEBHOOK_SECRET, { expiresIn: '5m' });
        
        await axios.post(webhookUrl, payload, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'X-Webhook-Secret': WEBHOOK_SECRET
            },
            timeout: 10000
        });
        
        logger.info(`Webhook enviado para usuario ${userId}, evento: ${event}`);
        
    } catch (error) {
        logger.error(`Error enviando webhook (userId: ${userId}, evento: ${event}):`, error.message);
    }
}

// ==================== RUTAS ====================

app.get('/health', (req, res) => {
    const uptime = process.uptime();
    const activeClients = Array.from(clients.values()).filter(c => c.status === 'ready').length;
    
    res.json({
        status: 'healthy',
        uptime: uptime,
        timestamp: new Date().toISOString(),
        activeClients: activeClients,
        totalClients: clients.size,
        pendingConnections: qrCodes.size
    });
});

app.post('/api/connect', authenticateJWT, async (req, res) => {
    const userId = req.userId;
    
    try {
        if (clients.has(userId)) {
            const existingClient = clients.get(userId);
            if (existingClient.status === 'ready') {
                return res.json({
                    success: true,
                    message: 'Cliente ya conectado',
                    status: 'ready',
                    info: existingClient.info
                });
            }
        }
        
        if (clients.size >= MAX_CLIENTS) {
            return res.status(429).json({
                success: false,
                error: 'Máximo número de clientes alcanzado'
            });
        }
        
        logger.info(`Iniciando conexión para usuario ${userId}`);
        
        const client = createWhatsAppClient(userId);
        
        clients.set(userId, {
            client: client,
            status: 'connecting',
            qr: null,
            info: null
        });
        
        await client.initialize();
        
        res.json({
            success: true,
            message: 'Proceso de conexión iniciado',
            status: 'connecting'
        });
        
    } catch (error) {
        logger.error(`Error conectando usuario ${userId}:`, error);
        clients.delete(userId);
        
        res.status(500).json({
            success: false,
            error: 'Error iniciando conexión'
        });
    }
});

app.post('/api/disconnect', authenticateJWT, async (req, res) => {
    const userId = req.userId;
    
    try {
        if (!clients.has(userId)) {
            return res.json({
                success: true,
                message: 'Usuario no estaba conectado'
            });
        }
        
        const clientData = clients.get(userId);
        const client = clientData.client;
        
        logger.info(`Desconectando usuario ${userId}`);
        
        if (client) {
            await client.logout();
            await client.destroy();
        }
        
        clients.delete(userId);
        qrCodes.delete(userId);
        
        // ⭐ Eliminar de persistencia
        await removeSessionFromFile(userId);
        
        res.json({
            success: true,
            message: 'Usuario desconectado correctamente'
        });
        
    } catch (error) {
        logger.error(`Error desconectando usuario ${userId}:`, error);
        
        clients.delete(userId);
        qrCodes.delete(userId);
        await removeSessionFromFile(userId);
        
        res.json({
            success: true,
            message: 'Usuario desconectado (con errores)'
        });
    }
}); 

app.get('/api/status', authenticateJWT, (req, res) => {
    const userId = req.userId;
    
    if (!clients.has(userId)) {
        return res.json({
            success: true,
            status: 'disconnected',
            qr: null
        });
    }
    
    const clientData = clients.get(userId);
    const qr = qrCodes.get(userId) || null;
    
    res.json({
        success: true,
        status: clientData.status,
        qr: qr,
        info: clientData.info
    });
});

app.post('/api/isuser', authenticateJWT, async (req, res) => {
    const phoneNum = req.body.phoneNum;
    const userId = req.userId;    

    try {
        if (!phoneNum) {
            return res.status(400).json({
                success: false,
                error: 'Parámetro "phoneNum" es obligatorio'
            });
        }
        
        if (!clients.has(userId)) {
            return res.status(400).json({
                success: false,
                error: 'Cliente no conectado'
            });
        }
        
        const clientData = clients.get(userId);
        if (clientData.status !== 'ready') {
            return res.status(400).json({
                success: false,
                error: 'Cliente no está listo para comprobar nº'
            });
        }
        
        const client = clientData.client;
        const user = phoneNum.includes('@') ? phoneNum : `${phoneNum}@c.us`;
        
        logger.info(`Comprobando si ${phoneNum} es usuario de WhatsApp`);
        
        const isRegistered = await client.isRegisteredUser(user);
        
        res.json({
            success: true,
            isRegistered: isRegistered
        });
        
    } catch (error) {
        logger.error(`Error comprobando nº:`, error);
        
        res.status(500).json({
            success: false,
            error: 'Error comprobando nº'
        });
    }
});

app.post('/api/send', authenticateJWT, async (req, res) => {
    const userId = req.userId;
    const { to, message, type = 'text' } = req.body;
    
    try {
        if (!to || !message) {
            return res.status(400).json({
                success: false,
                error: 'Parámetros "to" y "message" son requeridos'
            });
        }
        
        if (!clients.has(userId)) {
            return res.status(400).json({
                success: false,
                error: 'Cliente no conectado'
            });
        }
        
        const clientData = clients.get(userId);
        if (clientData.status !== 'ready') {
            return res.status(400).json({
                success: false,
                error: 'Cliente no está listo para enviar mensajes'
            });
        }
        
        const client = clientData.client;
        const phoneNumber = to.includes('@') ? to : `${to}@c.us`;
        
        logger.info(`Enviando mensaje de usuario ${userId} a ${phoneNumber}`);
        
        const sentMessage = await client.sendMessage(phoneNumber, message);
        
        res.json({
            success: true,
            messageId: sentMessage.id._serialized,
            timestamp: sentMessage.timestamp
        });
        
    } catch (error) {
        logger.error(`Error enviando mensaje:`, error);
        
        res.status(500).json({
            success: false,
            error: 'Error enviando mensaje'
        });
    }
});

app.get('/api/chats', authenticateJWT, async (req, res) => {
    const userId = req.userId;
    
    try {
        if (!clients.has(userId) || clients.get(userId).status !== 'ready') {
            return res.status(400).json({
                success: false,
                error: 'Cliente no conectado'
            });
        }
        
        const client = clients.get(userId).client;
        const chats = await client.getChats();
        
        const formattedChats = chats.map(chat => ({
            id: chat.id._serialized,
            name: chat.name,
            isGroup: chat.isGroup,
            unreadCount: chat.unreadCount,
            lastMessage: chat.lastMessage ? {
                body: chat.lastMessage.body,
                timestamp: chat.lastMessage.timestamp,
                from: chat.lastMessage.from
            } : null
        }));
        
        res.json({
            success: true,
            chats: formattedChats
        });
        
    } catch (error) {
        logger.error(`Error obteniendo chats:`, error);
        
        res.status(500).json({
            success: false,
            error: 'Error obteniendo chats'
        });
    }
});

app.use((error, req, res, next) => {
    logger.error('Error no manejado:', error);
    res.status(500).json({
        success: false,
        error: 'Error interno del servidor'
    });
});

app.use('*', (req, res) => {
    res.status(404).json({
        success: false,
        error: 'Endpoint no encontrado'
    });
});

// ⭐ Iniciar servidor con restauración
async function startServer() {
    try {
        logger.info('🚀 Iniciando servidor WhatsApp...');
        
        await restoreAllSessions();
        
        app.listen(PORT, () => {
            logger.info(`✅ Servidor WhatsApp iniciado en puerto ${PORT}`);
            logger.info(`📱 Máximo de clientes: ${MAX_CLIENTS}`);
            logger.info(`🔐 JWT Secret configurado: ${!!JWT_SECRET}`);
            logger.info(`🌐 Web App URL: ${WEBAPP_URL}`);
            logger.info(`📂 Directorio de sesiones: ${SESSIONS_DIR}`);
            logger.info(`📊 Sesiones activas: ${clients.size}`);
        });
        
    } catch (error) {
        logger.error('❌ Error iniciando servidor:', error);
        process.exit(1);
    }
}

startServer();

const gracefulShutdown = async () => {
    logger.info('🛑 Cerrando servidor...');
    
    // Solo destruir clientes (sin logout para mantener sesión)
    for (const [userId, clientData] of clients) {
        try {
            if (clientData.client) {
                await clientData.client.destroy();
            }
        } catch (error) {
            logger.error(`Error cerrando cliente ${userId}:`, error);
        }
    }
    
    logger.info('✅ Servidor cerrado correctamente');
    process.exit(0);
};

process.on('SIGINT', gracefulShutdown);
process.on('SIGTERM', gracefulShutdown);

process.on('uncaughtException', (error) => {
    logger.error('Excepción no capturada:', error);
    process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
    logger.error('Promesa rechazada no manejada:', reason);
});

module.exports = app;