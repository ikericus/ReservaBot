// server.js - Servidor WhatsApp simplificado y estable para ReservaBot
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
const { log } = require('console');

// Configuración
const PORT = process.env.PORT || 3001;
const JWT_SECRET = process.env.JWT_SECRET;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;
const WEBAPP_URL = process.env.WEBAPP_URL || 'http://localhost';
const MAX_CLIENTS = parseInt(process.env.MAX_CLIENTS) || 50;
const SESSIONS_DIR = process.env.SESSIONS_DIR || './sessions';

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
        // Solo mensajes "info" (no incluye warn ni error)
        new winston.transports.File({
            filename: './logs/info.log',
            level: 'info',
            // Este filtro evita que se incluyan mensajes de nivel superior (warn/error)
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
const clients = new Map(); // userId -> { client, qr, status, info }
const qrCodes = new Map(); // userId -> qr string

// Configurar Express
const app = express();

// Middleware de seguridad
app.use(helmet({
    contentSecurityPolicy: false
}));

app.use(cors({
    origin: [WEBAPP_URL, 'http://localhost', 'http://127.0.0.1'],
    credentials: true
}));

// Rate limiting
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutos
    max: 100, // máximo 100 requests por ventana
    message: { error: 'Demasiadas peticiones, intenta más tarde' }
});
app.use('/api/', limiter);

// Middleware para parsing
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Middleware de autenticación JWT
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
 
// Función para crear cliente WhatsApp
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

    // Event handlers
    client.on('qr', async (qr) => {
        try {
            logger.info(`QR generado para usuario ${userId}`);
            
            const qrDataUrl = await QRCode.toDataURL(qr);
            qrCodes.set(userId, qrDataUrl);
            
            // Actualizar estado del cliente
            if (clients.has(userId)) {
                clients.get(userId).qr = qrDataUrl;
                clients.get(userId).status = 'waiting_qr';
            }
            
            // Notificar a la webapp (opcional)
            notifyWebApp(userId, 'qr_generated', { qr: qrDataUrl });
            
        } catch (error) {
            logger.error(`Error generando QR para usuario ${userId}:`, error);
        }
    });

    client.on('ready', async () => {
        try {
            const info = client.info;
            logger.info(`Cliente ${userId} conectado como: ${info.wid.user}`);
            
            // Actualizar estado del cliente
            clients.set(userId, {
                client: client,
                status: 'ready',
                info: info,
                qr: null
            });
            
            // Limpiar QR
            qrCodes.delete(userId);
            
            // Notificar a la webapp
            notifyWebApp(userId, 'connected', {
                phoneNumber: info.wid.user,
                pushname: info.pushname
            });
            
        } catch (error) {
            logger.error(`Error en ready para usuario ${userId}:`, error);
        }
    });

    client.on('authenticated', () => {
        logger.info(`Cliente ${userId} autenticado correctamente`);
        
        if (clients.has(userId)) {
            clients.get(userId).status = 'authenticated';
        }
    });

    client.on('auth_failure', (msg) => {
        logger.error(`Fallo de autenticación para usuario ${userId}:`, msg);
        
        // Actualizar estado
        if (clients.has(userId)) {
            clients.get(userId).status = 'auth_failed';
        }
        
        // Notificar error
        notifyWebApp(userId, 'auth_failed', { error: msg });
    });

    client.on('disconnected', (reason) => {
        logger.info(`Cliente ${userId} desconectado: ${reason}`);
        
        // Limpiar cliente
        clients.delete(userId);
        qrCodes.delete(userId);
        
        // Notificar desconexión
        notifyWebApp(userId, 'disconnected', { reason: reason });
    });

    client.on('message', async (message) => {
        try {
            logger.info(`Mensaje recibido para usuario ${userId} de ${message.from}: ${message.body}`);
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

// Función para manejar mensajes entrantes
async function handleIncomingMessage(userId, message) {
    try {
        const chat = await message.getChat();
        const contact = await message.getContact();
        
        const messageData = {
            id: message.id._serialized,
            from: message.from,
            to: message.to,
            body: message.body,
            type: message.type,
            timestamp: message.timestamp,
            isForwarded: message.isForwarded,
            isGroup: chat.isGroup,
            chatName: chat.name,
            contactName: contact.pushname || contact.number,
            phoneNumber: contact.number
        };
        
        logger.info(`Mensaje entrante para usuario ${userId}: ${message.body}`);
        
        // Enviar a la webapp
        await notifyWebApp(userId, 'message_received', messageData);
        
    } catch (error) {
        logger.error(`Error procesando mensaje entrante:`, error);
    }
}

// Función para notificar a la webapp
async function notifyWebApp(userId, event, data) {
    try {
        const webhookUrl = `${WEBAPP_URL}/api/whatsapp-webhook`;
        
        const payload = {
            userId: userId,
            event: event,
            data: data,
            timestamp: new Date().toISOString()
        };
        
        // Crear token para el webhook
        const token = jwt.sign(payload, WEBHOOK_SECRET, { expiresIn: '5m' });
        
        logger.info(`Enviando webhook a ${webhookUrl} para usuario ${userId}, evento: ${event}`);

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
        //logger.error(`Error enviando webhook (userId: ${userId}, evento: ${event}, data: ${JSON.stringify(data)}):`, error);
        logger.error({  msg: "Error enviando webhook",
                        userId,
                        event,
                        dataSummary: {
                        keys: Object.keys(data),
                        size: JSON.stringify(data).length
                        },
                        error: error.stack || error.message
                    });
    }
}

// ==================== RUTAS DE LA API ====================

// Health check
app.get('/health', (req, res) => {
    const uptime = process.uptime();
    const activeClients = Array.from(clients.values()).filter(c => c.status === 'ready').length;

    // Obtener IP real del solicitante
    const ip = req.headers['x-forwarded-for']?.split(',')[0] || req.ip;

    logger.info(`Health check solicitado desde IP: ${ip}. Uptime: ${uptime}s, Clientes activos: ${activeClients}, Total clientes: ${clients.size}, Conexiones pendientes: ${qrCodes.size}`);

    res.json({
        status: 'healthy',
        uptime: uptime,
        timestamp: new Date().toISOString(),
        activeClients: activeClients,
        totalClients: clients.size,
        pendingConnections: qrCodes.size,
        requestIP: ip
    });
});


// Conectar usuario
app.post('/api/connect', authenticateJWT, async (req, res) => {
    const userId = req.userId;
    
    logger.info(`Solicitud de conexión para usuario ${userId}`);

    try {
        // Verificar si ya existe
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
        
        // Verificar límite de clientes
        if (clients.size >= MAX_CLIENTS) {
            return res.status(429).json({
                success: false,
                error: 'Máximo número de clientes alcanzado'
            });
        }
        
        logger.info(`Iniciando conexión para usuario ${userId}`);
        
        // Crear nuevo cliente
        const client = createWhatsAppClient(userId);
        
        // Almacenar cliente
        clients.set(userId, {
            client: client,
            status: 'connecting',
            qr: null,
            info: null
        });
        
        // Inicializar cliente
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

// Desconectar usuario
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
        
        // Desconectar cliente
        if (client) {
            await client.logout();
            await client.destroy();
        }
        
        // Limpiar datos
        clients.delete(userId);
        qrCodes.delete(userId);
        
        res.json({
            success: true,
            message: 'Usuario desconectado correctamente'
        });
        
    } catch (error) {
        logger.error(`Error desconectando usuario ${userId}:`, error);
        
        // Forzar limpieza
        clients.delete(userId);
        qrCodes.delete(userId);
        
        res.json({
            success: true,
            message: 'Usuario desconectado (con errores)'
        });
    }
}); 

// Estado del usuario
app.get('/api/status', authenticateJWT, (req, res) => {
    const userId = req.userId;
    
    // Obtener IP real del solicitante
    const ip = req.headers['x-forwarded-for']?.split(',')[0] || req.ip;

    logger.info(`Estado solicitado para usuario ${userId} desde IP: ${ip}`);

    if (!clients.has(userId)) {
        logger.info(`Usuario ${userId} no conectado`);
        return res.json({
            success: true,
            status: 'disconnected',
            qr: null
        });
    }
    
    const clientData = clients.get(userId);
    const qr = qrCodes.get(userId) || null;
    
    logger.info(`Estado para usuario ${userId}: ${clientData.status}`);

    res.json({
        success: true,
        status: clientData.status,
        qr: qr,
        info: clientData.info
    });
});

app.post('/api/isuser', authenticateJWT, async (req, res) => {
    
    const phoneNum = req.phoneNum;
    const userId = req.userId;    

    logger.info(`Comprobando nº para usuario ${userId} a ${phoneNum}`);

    try {
        // Validar parámetros
        if (!phoneNum) {
            return res.status(400).json({
                success: false,
                error: 'Parámetro "phoneNum" es obligatario '
            });
        }
        
        // Verificar cliente conectado
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
        
        // Formatear número
        const user = phoneNum.includes('@') ? phoneNum : `${phoneNum}@c.us`;
        
        logger.info(`Enviando mensaje de usuario ${userId} a ${phoneNumber}`);
        
        // Enviar mensaje
        const isRegistered  = await client.isRegisteredUser(user);
        
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

// Enviar mensaje
app.post('/api/send', authenticateJWT, async (req, res) => {
    const userId = req.userId;
    const { to, message, type = 'text' } = req.body;
    logger.info(`Enviando mensaje para usuario ${userId} a ${to}`);
    try {
        // Validar parámetros
        if (!to || !message) {
            return res.status(400).json({
                success: false,
                error: 'Parámetros "to" y "message" son requeridos'
            });
        }
        
        // Verificar cliente conectado
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
        
        // Formatear número
        const phoneNumber = to.includes('@') ? to : `${to}@c.us`;
        
        logger.info(`Enviando mensaje de usuario ${userId} a ${phoneNumber}`);
        
        // Enviar mensaje
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

// Obtener chats
app.get('/api/chats', authenticateJWT, async (req, res) => {
    const userId = req.userId;
    logger.info(`Obteniendo chats para usuario ${userId}`);
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

// Middleware de manejo de errores
app.use((error, req, res, next) => {
    logger.error('Error no manejado:', error);
    res.status(500).json({
        success: false,
        error: 'Error interno del servidor'
    });
});

// Ruta catch-all
app.use('*', (req, res) => {
    logger.warn(`Endpoint no encontrado: ${req.method} ${req.originalUrl}`);
    res.status(404).json({
        success: false,
        error: 'Endpoint no encontrado'
    });
});

// Iniciar servidor
app.listen(PORT, () => {
    logger.info(`🚀 Servidor WhatsApp iniciado en puerto ${PORT}`);
    logger.info(`📱 Máximo de clientes: ${MAX_CLIENTS}`);
    logger.info(`🔐 JWT Secret configurado: ${!!JWT_SECRET}`);
    logger.info(`🌐 Web App URL: ${WEBAPP_URL}`);
    logger.info(`📂 Directorio de sesiones: ${SESSIONS_DIR}`);
});

// Manejo de cierre graceful
process.on('SIGINT', async () => {
    logger.info('Cerrando servidor...');
    
    // Desconectar todos los clientes
    for (const [userId, clientData] of clients) {
        try {
            if (clientData.client) {
                await clientData.client.destroy();
            }
        } catch (error) {
            logger.error(`Error cerrando cliente ${userId}:`, error);
        }
    }
    
    process.exit(0);
});

process.on('uncaughtException', (error) => {
    logger.error('Excepción no capturada:', error);
    process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
    logger.error('Promesa rechazada no manejada:', reason);
});

module.exports = app;
