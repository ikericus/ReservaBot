// Archivo: index.js
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const { Client, LocalAuth } = require('whatsapp-web.js');
const cors = require('cors');
const axios = require('axios'); // Añadido para el endpoint check-api
const WhatsAppApiClient = require('./api-client');

// Configuración de Express
const app = express();
app.use(express.json());
app.use(cors({
  origin: process.env.WEB_APP_URL || '*', // URL de tu aplicación web
  methods: ['GET', 'POST'],
  credentials: true
}));

// Crear servidor HTTP y Socket.io
const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: process.env.WEB_APP_URL || '*',
    methods: ['GET', 'POST'],
    credentials: true
  }
});

// Configurar cliente de API
const apiClient = new WhatsAppApiClient(
  process.env.PHP_API_URL || 'http://localhost/api',
  process.env.PHP_API_KEY || 'tu-clave-secreta-node'
);

// Almacenamiento de clientes de WhatsApp activos
const whatsappClients = new Map();

// Socket.io para comunicación en tiempo real
io.on('connection', (socket) => {
  console.log('Cliente conectado:', socket.id);
  
  // Escuchar cuando un cliente quiere iniciar sesión en WhatsApp
  socket.on('initWhatsAppSession', async (userId) => {
    try {
      // Registrar usuario en la API si no existe
      await apiClient.registerUser(userId);
      
      // Si ya existe una sesión para este usuario, la eliminamos
      if (whatsappClients.has(userId)) {
        const existingClient = whatsappClients.get(userId);
        try {
          await existingClient.destroy();
        } catch (err) {
          console.error(`Error al destruir cliente existente para ${userId}:`, err);
        }
        whatsappClients.delete(userId);
      }
      
      // Actualizar estado de sesión en la API
      await apiClient.updateSessionStatus(userId, 'initializing');
      
      // Crear un nuevo cliente de WhatsApp
      const client = new Client({
        authStrategy: new LocalAuth({ clientId: userId }),
        puppeteer: {
          headless: true,
          args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        }
      });
      
      // Emitir el código QR al cliente web
      client.on('qr', (qr) => {
        console.log(`QR Code generado para usuario ${userId}`);
        socket.emit('qrCode', { userId, qr });
      });
      
      // Manejar la autenticación exitosa
      client.on('authenticated', async () => {
        console.log(`Cliente ${userId} autenticado en WhatsApp`);
        socket.emit('authenticated', { userId, status: 'authenticated' });
        
        // Actualizar estado en la API
        await apiClient.updateSessionStatus(userId, 'authenticated');
      });
      
      // Manejar cuando el cliente está listo
      client.on('ready', async () => {
        console.log(`Cliente ${userId} listo`);
        socket.emit('ready', { userId, status: 'ready' });
        
        // Actualizar estado en la API
        await apiClient.updateSessionStatus(userId, 'ready');
      });
      
      // Manejar mensajes entrantes
      client.on('message', async (message) => {
        console.log(`Mensaje recibido para ${userId}:`, message.body);
        
        // Registrar el chat en la API si no existe
        const chatId = message.from;
        await apiClient.registerChat(userId, chatId);
        
        // Guardar el mensaje en la API
        await apiClient.saveMessage({
          userId: userId,
          chatId: chatId,
          messageId: message.id._serialized,
          body: message.body,
          direction: 'received',
          timestamp: message.timestamp,
          isAutoResponse: false
        });
        
        // Emitir el mensaje al cliente web
        socket.emit('incomingMessage', {
          userId,
          from: message.from,
          body: message.body,
          timestamp: message.timestamp,
          messageId: message.id._serialized
        });
        
        // Obtener respuestas automáticas de la API
        const autoResponses = await apiClient.getAutoResponses(userId);
        
        // Procesar respuestas automáticas
        for (const autoResponse of autoResponses) {
          if (message.body.toLowerCase().includes(autoResponse.trigger_text.toLowerCase())) {
            // Enviar respuesta automática
            const reply = await message.reply(autoResponse.response_text);
            
            // Guardar la respuesta automática en la API
            await apiClient.saveMessage({
              userId: userId,
              chatId: chatId,
              messageId: reply.id._serialized,
              body: autoResponse.response_text,
              direction: 'sent',
              timestamp: Math.floor(Date.now() / 1000),
              isAutoResponse: true
            });
            
            break; // Solo usar la primera respuesta que coincida
          }
        }
      });
      
      // Manejar desconexión
      client.on('disconnected', async (reason) => {
        console.log(`Cliente ${userId} desconectado:`, reason);
        socket.emit('disconnected', { userId, reason });
        
        // Actualizar estado en la API
        await apiClient.updateSessionStatus(userId, 'disconnected', reason);
        
        whatsappClients.delete(userId);
      });
      
      // Inicializar el cliente
      await client.initialize();
      
      // Guardar el cliente en el mapa
      whatsappClients.set(userId, client);
      
    } catch (error) {
      console.error(`Error al inicializar sesión para ${userId}:`, error);
      socket.emit('error', { userId, error: error.message });
    }
  });
  
  // Manejar envío de mensajes desde la aplicación web
  socket.on('sendMessage', async ({ userId, to, message }) => {
    try {
      const client = whatsappClients.get(userId);
      if (!client) {
        return socket.emit('error', { 
          userId, 
          error: 'No hay sesión activa para este usuario' 
        });
      }
      
      // Formatear el número si es necesario
      const formattedNumber = to.includes('@c.us') ? to : `${to}@c.us`;
      
      // Enviar el mensaje
      const sentMessage = await client.sendMessage(formattedNumber, message);
      
      // Registrar el chat en la API si no existe
      await apiClient.registerChat(userId, formattedNumber);
      
      // Guardar el mensaje en la API
      await apiClient.saveMessage({
        userId: userId,
        chatId: formattedNumber,
        messageId: sentMessage.id._serialized,
        body: message,
        direction: 'sent',
        timestamp: Math.floor(Date.now() / 1000),
        isAutoResponse: false
      });
      
      socket.emit('messageSent', { 
        userId, 
        to: formattedNumber, 
        messageId: sentMessage.id._serialized 
      });
      
    } catch (error) {
      console.error(`Error al enviar mensaje para ${userId}:`, error);
      socket.emit('error', { userId, error: error.message });
    }
  });
  
  // Manejar configuración de respuesta automática
  socket.on('saveAutoResponse', async ({ userId, trigger, response }) => {
    try {
      // Guardar en la API
      const result = await apiClient.saveAutoResponse(userId, trigger, response);
      
      socket.emit('autoResponseSaved', { 
        userId, 
        id: result.id,
        success: true
      });
    } catch (error) {
      console.error(`Error al guardar respuesta automática para ${userId}:`, error);
      socket.emit('error', { userId, error: error.message });
    }
  });
  
  // Solicitar respuestas automáticas desde el cliente
  socket.on('getAutoResponses', async ({ userId }) => {
    try {
      const autoResponses = await apiClient.getAutoResponses(userId);
      socket.emit('autoResponsesList', { userId, autoResponses });
    } catch (error) {
      console.error(`Error al obtener respuestas automáticas para ${userId}:`, error);
      socket.emit('error', { userId, error: error.message });
    }
  });
  
  // Solicitar historial de mensajes
  socket.on('getChatHistory', async ({ userId, chatId, options }) => {
    try {
      const messages = await apiClient.getChatMessages(userId, chatId, options);
      socket.emit('chatHistory', { userId, chatId, messages });
    } catch (error) {
      console.error(`Error al obtener historial para ${userId}:`, error);
      socket.emit('error', { userId, error: error.message });
    }
  });
  
  // Solicitar estadísticas de mensajes
  socket.on('getMessageStats', async ({ userId, period }) => {
    try {
      const stats = await apiClient.getMessageStats(userId, period);
      socket.emit('messageStats', { userId, stats });
    } catch (error) {
      console.error(`Error al obtener estadísticas para ${userId}:`, error);
      socket.emit('error', { userId, error: error.message });
    }
  });
});

// Endpoints API REST
app.get('/health', (req, res) => {
  res.status(200).json({ status: 'ok' });
});

// Obtener estado de las sesiones
app.get('/sessions', async (req, res) => {
  try {
    const sessions = [];
    for (const [userId, client] of whatsappClients.entries()) {
      sessions.push({
        userId,
        status: client.pupPage ? 'active' : 'initializing'
      });
    }
    res.json({ sessions });
  } catch (error) {
    console.error('Error al obtener sesiones:', error);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// Endpoint para sincronizar manualmente con la API PHP
app.post('/sync', async (req, res) => {
  try {
    const { userId, action } = req.body;
    
    if (!userId) {
      return res.status(400).json({ error: 'Se requiere userId' });
    }
    
    switch (action) {
      case 'session':
        // Sincronizar estado de sesión
        const client = whatsappClients.get(userId);
        let status = 'disconnected';
        
        if (client) {
          status = client.pupPage ? 'ready' : 'initializing';
        }
        
        await apiClient.updateSessionStatus(userId, status);
        res.json({ success: true, status });
        break;
        
      case 'messages':
        // Este endpoint podría usarse para una sincronización 
        // manual de mensajes si fuera necesario
        res.json({ 
          success: true, 
          message: 'Sincronización de mensajes iniciada' 
        });
        break;
        
      default:
        res.status(400).json({ error: 'Acción no reconocida' });
    }
  } catch (error) {
    console.error('Error en sincronización:', error);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// Endpoint para verificar la conexión con la API PHP
app.get('/check-api', async (req, res) => {
  try {
    const startTime = Date.now();
    const healthCheck = await axios.get(`${process.env.PHP_API_URL || 'http://localhost/api'}/health`);
    const endTime = Date.now();
    
    res.json({
      status: 'ok',
      phpApi: {
        connected: true,
        status: healthCheck.data.status,
        responseTime: `${endTime - startTime}ms`
      }
    });
  } catch (error) {
    res.status(200).json({
      status: 'warning',
      phpApi: {
        connected: false,
        error: error.message
      }
    });
  }
});

// Puerto de la aplicación
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Servidor ejecutándose en el puerto ${PORT}`);
});