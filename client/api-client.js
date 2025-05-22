// Archivo: whatsapp-api-client.js
// Este archivo contiene las funciones para comunicarse con la API PHP

const axios = require('axios');

class WhatsAppApiClient {
    constructor(apiUrl, apiKey) {
        this.apiUrl = apiUrl;
        this.apiKey = apiKey;
        
        // Configuración por defecto para axios
        this.axiosConfig = {
            headers: {
                'Content-Type': 'application/json',
                'X-Api-Key': this.apiKey
            }
        };
    }
    
    /**
     * Registra un usuario en la API
     * @param {string} userId ID único del usuario
     * @param {object} userData Datos adicionales del usuario (opcional)
     * @returns {Promise<object>} Respuesta de la API
     */
    async registerUser(userId, userData = {}) {
        try {
            const response = await axios.post(`${this.apiUrl}/users`, {
                user_id: userId,
                name: userData.name || null,
                email: userData.email || null
            }, this.axiosConfig);
            
            return response.data;
        } catch (error) {
            this.handleApiError('registerUser', error);
            
            // Si el error es por usuario duplicado (409), intentamos obtenerlo
            if (error.response && error.response.status === 409) {
                return this.getUser(userId);
            }
            
            throw error;
        }
    }
    
    /**
     * Obtiene información de un usuario
     * @param {string} userId ID único del usuario
     * @returns {Promise<object>} Datos del usuario
     */
    async getUser(userId) {
        try {
            const response = await axios.get(
                `${this.apiUrl}/users?user_id=${encodeURIComponent(userId)}`,
                this.axiosConfig
            );
            
            if (response.data && response.data.data && response.data.data.length > 0) {
                return response.data.data[0];
            }
            
            throw new Error('Usuario no encontrado');
        } catch (error) {
            this.handleApiError('getUser', error);
            throw error;
        }
    }
    
    /**
     * Actualiza el estado de la sesión del usuario
     * @param {string} userId ID único del usuario
     * @param {string} status Estado de la sesión
     * @param {string} reason Razón de desconexión (opcional)
     * @returns {Promise<object>} Respuesta de la API
     */
    async updateSessionStatus(userId, status, reason = null) {
        try {
            // Buscar si ya existe una sesión
            let sessionId;
            try {
                const sessions = await axios.get(
                    `${this.apiUrl}/sessions?user_id=${encodeURIComponent(userId)}`,
                    this.axiosConfig
                );
                
                if (sessions.data && sessions.data.data && sessions.data.data.length > 0) {
                    sessionId = sessions.data.data[0].id;
                }
            } catch (err) {
                // Ignorar error de sesión no encontrada
            }
            
            const sessionData = {
                user_id: userId,
                status: status,
                disconnect_reason: reason,
                last_connection: new Date().toISOString().slice(0, 19).replace('T', ' ')
            };
            
            // Si la sesión existe, actualizar
            if (sessionId) {
                const response = await axios.put(
                    `${this.apiUrl}/sessions/${sessionId}`,
                    sessionData,
                    this.axiosConfig
                );
                return response.data;
            } else {
                // Si no existe, crear nueva
                const response = await axios.post(
                    `${this.apiUrl}/sessions`,
                    sessionData,
                    this.axiosConfig
                );
                return response.data;
            }
        } catch (error) {
            this.handleApiError('updateSessionStatus', error);
            throw error;
        }
    }
    
    /**
     * Registra un nuevo chat
     * @param {string} userId ID único del usuario
     * @param {string} chatId ID del chat (número@c.us)
     * @param {string} contactName Nombre del contacto (opcional)
     * @returns {Promise<object>} Respuesta de la API
     */
    async registerChat(userId, chatId, contactName = null) {
        try {
            // Verificar si el chat ya existe
            try {
                const chats = await axios.get(
                    `${this.apiUrl}/chats?user_id=${encodeURIComponent(userId)}&chat_id=${encodeURIComponent(chatId)}`,
                    this.axiosConfig
                );
                
                if (chats.data && chats.data.data && chats.data.data.length > 0) {
                    return chats.data.data[0];
                }
            } catch (err) {
                // Ignorar error de chat no encontrado
            }
            
            // Crear nuevo chat
            const response = await axios.post(`${this.apiUrl}/chats`, {
                user_id: userId,
                chat_id: chatId,
                contact_name: contactName || chatId.split('@')[0],
                last_message_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
            }, this.axiosConfig);
            
            return response.data;
        } catch (error) {
            this.handleApiError('registerChat', error);
            throw error;
        }
    }
    
    /**
     * Guarda un mensaje en la API
     * @param {object} messageData Datos del mensaje
     * @returns {Promise<object>} Respuesta de la API
     */
    async saveMessage(messageData) {
        try {
            // Preparar datos para la API
            const apiMessageData = {
                user_id: messageData.userId,
                chat_id: messageData.chatId,
                message_id: messageData.messageId,
                body: messageData.body,
                direction: messageData.direction,
                timestamp: messageData.timestamp || Math.floor(Date.now() / 1000),
                is_auto_response: messageData.isAutoResponse ? 1 : 0,
                metadata: messageData.metadata || null
            };
            
            const response = await axios.post(
                `${this.apiUrl}/messages`,
                apiMessageData,
                this.axiosConfig
            );
            
            return response.data;
        } catch (error) {
            this.handleApiError('saveMessage', error);
            throw error;
        }
    }
    
    /**
     * Obtiene los mensajes de un chat específico
     * @param {string} userId ID único del usuario
     * @param {string} chatId ID del chat
     * @param {object} options Opciones adicionales (limit, offset, date_from, date_to)
     * @returns {Promise<object>} Lista de mensajes con paginación
     */
    async getChatMessages(userId, chatId, options = {}) {
        try {
            let url = `${this.apiUrl}/messages?user_id=${encodeURIComponent(userId)}&chat_id=${encodeURIComponent(chatId)}`;
            
            // Añadir opciones adicionales a la URL
            if (options.limit) url += `&limit=${options.limit}`;
            if (options.offset) url += `&offset=${options.offset}`;
            if (options.date_from) url += `&date_from=${options.date_from}`;
            if (options.date_to) url += `&date_to=${options.date_to}`;
            
            const response = await axios.get(url, this.axiosConfig);
            return response.data;
        } catch (error) {
            this.handleApiError('getChatMessages', error);
            throw error;
        }
    }
    
    /**
     * Obtiene las respuestas automáticas de un usuario
     * @param {string} userId ID único del usuario
     * @returns {Promise<Array>} Lista de respuestas automáticas
     */
    async getAutoResponses(userId) {
        try {
            const response = await axios.get(
                `${this.apiUrl}/auto-responses?user_id=${encodeURIComponent(userId)}&is_active=1`,
                this.axiosConfig
            );
            
            return response.data.data || [];
        } catch (error) {
            this.handleApiError('getAutoResponses', error);
            return []; // En caso de error, devolver array vacío
        }
    }
    
    /**
     * Guarda una respuesta automática
     * @param {string} userId ID único del usuario
     * @param {string} triggerText Texto que dispara la respuesta
     * @param {string} responseText Texto de respuesta
     * @returns {Promise<object>} Respuesta de la API
     */
    async saveAutoResponse(userId, triggerText, responseText) {
        try {
            const response = await axios.post(`${this.apiUrl}/auto-responses`, {
                user_id: userId,
                trigger_text: triggerText,
                response_text: responseText,
                is_active: 1
            }, this.axiosConfig);
            
            return response.data;
        } catch (error) {
            this.handleApiError('saveAutoResponse', error);
            throw error;
        }
    }
    
    /**
     * Obtiene estadísticas de mensajes
     * @param {string} userId ID único del usuario
     * @param {string} period Periodo (day, week, month)
     * @returns {Promise<object>} Estadísticas
     */
    async getMessageStats(userId, period = 'week') {
        try {
            const response = await axios.get(
                `${this.apiUrl}/stats?user_id=${encodeURIComponent(userId)}&period=${period}`,
                this.axiosConfig
            );
            
            return response.data;
        } catch (error) {
            this.handleApiError('getMessageStats', error);
            return { sent: 0, received: 0, auto_responses: 0 }; // Valores por defecto
        }
    }
    
    /**
     * Maneja errores de la API de forma consistente
     * @private
     * @param {string} method Método que generó el error
     * @param {Error} error Objeto de error
     */
    handleApiError(method, error) {
        if (error.response) {
            // Error con respuesta del servidor
            console.error(`API Error (${method}): ${error.response.status} - ${JSON.stringify(error.response.data)}`);
        } else if (error.request) {
            // Error sin respuesta (no se pudo conectar)
            console.error(`API Connection Error (${method}): No response received`);
        } else {
            // Otro tipo de error
            console.error(`API Request Error (${method}): ${error.message}`);
        }
    }
}

module.exports = WhatsAppApiClient;