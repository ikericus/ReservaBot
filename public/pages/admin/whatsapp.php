<?php
// pages/admin/whatsapp.php

/**
 * Página de administración y debug de WhatsApp
 */

$adminDomain = getContainer()->getAdminDomain();
$whatsappDomain = getContainer()->getWhatsAppDomain();

$currentPage = 'admin-whatsapp';
$pageTitle = 'ReservaBot Admin - WhatsApp';

// Configuración del servidor
$serverUrl = $_ENV['WHATSAPP_SERVER_URL'] ?? 'http://localhost:3001';
$jwtSecret = $_ENV['JWT_SECRET'] ?? '';
$webhookSecret = $_ENV['WEBHOOK_SECRET'] ?? '';

// Obtener datos
$salud = $adminDomain->obtenerSaludWhatsApp();
$stats = $adminDomain->obtenerEstadisticasMensajes();
$ultimos_usuarios = $adminDomain->obtenerUltimosUsuariosWhatsApp(20);
$numeros_activos = $adminDomain->obtenerNumerosMasActivos(10);
$volumen_7 = $adminDomain->obtenerVolumenMensajesPor7Dias();

include PROJECT_ROOT . '/includes/headerAdmin.php';
?>

<div class="admin-container">
   
    <?php include PROJECT_ROOT . '/pages/admin/menu.php'; ?>

    <!-- Header con tabs -->
    <div class="admin-header">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    <i class="ri-whatsapp-line mr-2"></i>WhatsApp Admin
                </h1>
                <p class="text-green-100">Monitoreo, estadísticas y herramientas de debug</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-green-100 mb-1">Estado del Servidor</div>
                <div id="serverStatus" class="text-white font-semibold text-lg">
                    <span class="loading-spinner"></span> Verificando...
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="overview">
                <i class="ri-dashboard-line"></i> Overview
            </button>
            <button class="tab-btn" data-tab="debug">
                <i class="ri-bug-line"></i> Debug Tools
            </button>
            <button class="tab-btn" data-tab="clients">
                <i class="ri-group-line"></i> Clients
            </button>
        </div>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <div class="tab-content active" id="tab-overview">
        
        <!-- Salud del Sistema -->
        <div class="grid-2 mb-6">
            <div class="health-card">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="ri-heart-pulse-line mr-2"></i>Estado del Sistema
                </h2>
                
                <div class="health-status" id="mainHealthStatus">
                    <span class="status-dot"></span>
                    <div>
                        <div class="font-semibold">Servidor WhatsApp</div>
                        <div class="text-sm">Verificando...</div>
                    </div>
                </div>

                <div class="stat-row">
                    <span class="stat-label">Conexión de Usuarios</span>
                    <span class="stat-value"><?php echo $salud['usuarios_conectados']; ?>/<?php echo $salud['usuarios_registrados']; ?></span>
                </div>

                <div class="stat-row">
                    <span class="stat-label">Tasa de Conexión</span>
                    <span class="stat-value"><?php echo round($salud['tasa_conexion'], 1); ?>%</span>
                </div>

                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo $salud['tasa_conexion']; ?>%"></div>
                </div>

                <div class="stat-row mt-4">
                    <span class="stat-label">Mensajes Hoy</span>
                    <span class="stat-value"><?php echo number_format($salud['mensajes_hoy']); ?></span>
                </div>
                
                <button onclick="checkServerHealth()" class="btn-primary mt-4 w-full">
                    <i class="ri-refresh-line mr-2"></i>Actualizar Estado
                </button>
            </div>
            
            <div class="health-card">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="ri-server-line mr-2"></i>Detalles del Servidor
                </h2>
                
                <div class="stat-row">
                    <span class="stat-label">URL</span>
                    <span class="stat-value text-xs font-mono"><?php echo htmlspecialchars($serverUrl); ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">JWT Secret</span>
                    <span class="badge <?php echo !empty($jwtSecret) ? 'connected' : 'disconnected'; ?>">
                        <?php echo !empty($jwtSecret) ? 'Configurado' : 'No configurado'; ?>
                    </span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Webhook Secret</span>
                    <span class="badge <?php echo !empty($webhookSecret) ? 'connected' : 'disconnected'; ?>">
                        <?php echo !empty($webhookSecret) ? 'Configurado' : 'No configurado'; ?>
                    </span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Uptime</span>
                    <span class="stat-value" id="serverUptime">-</span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Clientes Activos</span>
                    <span class="stat-value" id="activeClients">-</span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Tiempo de Respuesta</span>
                    <span class="stat-value" id="responseTime">-</span>
                </div>
            </div>
        </div>

        <!-- Métricas de Mensajes -->
        <div class="metrics-grid mb-6">
            <div class="metric-card">
                <div class="metric-label">Total Enviados</div>
                <div class="metric-value"><?php echo number_format($stats['total_enviados']); ?></div>
                <div class="metric-subtitle">Todos los tiempos</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Total Recibidos</div>
                <div class="metric-value"><?php echo number_format($stats['total_recibidos']); ?></div>
                <div class="metric-subtitle">Todos los tiempos</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Hoy Enviados</div>
                <div class="metric-value"><?php echo $stats['hoy_enviados']; ?></div>
                <div class="metric-subtitle">Desde las 00:00</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Promedio por Usuario</div>
                <div class="metric-value"><?php echo $stats['promedio_por_usuario']; ?></div>
                <div class="metric-subtitle">Mensajes/usuario</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid-2">
            <!-- Últimos Usuarios WhatsApp -->
            <div class="card">
                <div class="card-title">
                    <i class="ri-user-add-line mr-2"></i>Últimos Usuarios Conectados
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Conversaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimos_usuarios)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-500">
                                    No hay usuarios conectados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($ultimos_usuarios, 0, 10) as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-sm"><?php echo htmlspecialchars($usuario['nombre'] ?? 'N/A'); ?></div>
                                    <div class="text-xs text-gray-600"><?php echo htmlspecialchars($usuario['email'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="text-sm font-mono"><?php echo htmlspecialchars($usuario['phone_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $usuario['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $usuario['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-center font-semibold"><?php echo $usuario['total_conversaciones']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Números Más Activos -->
            <div class="card">
                <div class="card-title">
                    <i class="ri-phone-line mr-2"></i>Números Más Activos
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Teléfono</th>
                            <th>Mensajes</th>
                            <th>Usuarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($numeros_activos)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-8 text-gray-500">
                                    No hay datos de números activos
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($numeros_activos as $numero): ?>
                            <tr>
                                <td class="text-sm font-mono"><?php echo htmlspecialchars($numero['telefono']); ?></td>
                                <td class="text-center font-semibold"><?php echo $numero['total_conversaciones']; ?></td>
                                <td class="text-center"><?php echo $numero['total_usuarios']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gráfico de Volumen -->
        <div class="card mt-6">
            <div class="card-title">
                <i class="ri-line-chart-line mr-2"></i>Volumen de Mensajes (Últimos 7 Días)
            </div>
            <div style="height: 300px;">
                <canvas id="volumenChart"></canvas>
            </div>
        </div>
    </div>

    <!-- TAB 2: DEBUG TOOLS -->
    <div class="tab-content" id="tab-debug">
        
        <div class="grid-2 mb-6">
            <!-- API Tester -->
            <div class="card">
                <div class="card-title">
                    <i class="ri-code-s-slash-line mr-2"></i>API Tester
                </div>
                
                <div class="form-group">
                    <label>User ID</label>
                    <input type="number" id="apiUserId" class="form-input" placeholder="1" value="1">
                </div>
                
                <div class="form-group">
                    <label>Endpoint</label>
                    <select id="apiEndpoint" class="form-input" onchange="updateApiBody()">
                        <option value="/health">GET /health</option>
                        <option value="/api/connect">POST /api/connect</option>
                        <option value="/api/disconnect">POST /api/disconnect</option>
                        <option value="/api/status">GET /api/status</option>
                        <option value="/api/send">POST /api/send</option>
                        <option value="/api/chats">GET /api/chats</option>
                    </select>
                </div>
                
                <div class="form-group" id="apiBodyContainer" style="display: none;">
                    <label>Body (JSON)</label>
                    <textarea id="apiBody" class="form-input" rows="4"></textarea>
                </div>
                
                <button onclick="testApiEndpoint()" class="btn-primary w-full">
                    <i class="ri-send-plane-line mr-2"></i>Enviar Request
                </button>
                
                <div class="response-box mt-4" id="apiResponse">
                    <div class="text-gray-500 text-center py-4 text-sm">
                        La respuesta aparecerá aquí
                    </div>
                </div>
            </div>
            
            <!-- Webhook Simulator -->
            <div class="card">
                <div class="card-title">
                    <i class="ri-webhook-line mr-2"></i>Webhook Simulator
                </div>
                
                <div class="form-group">
                    <label>User ID</label>
                    <input type="number" id="webhookUserId" class="form-input" placeholder="1" value="1">
                </div>
                
                <div class="form-group">
                    <label>Evento</label>
                    <select id="webhookEvent" class="form-input" onchange="updateWebhookData()">
                        <option value="qr_generated">QR Generated</option>
                        <option value="connected">Connected</option>
                        <option value="disconnected">Disconnected</option>
                        <option value="auth_failure">Auth Failure</option>
                        <option value="message_received">Message Received</option>
                        <option value="message_sent">Message Sent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Datos (JSON)</label>
                    <textarea id="webhookData" class="form-input" rows="4"></textarea>
                </div>
                
                <button onclick="sendWebhook()" class="btn-primary w-full">
                    <i class="ri-send-plane-line mr-2"></i>Enviar Webhook
                </button>
                
                <div class="response-box mt-4" id="webhookResponse">
                    <div class="text-gray-500 text-center py-4 text-sm">
                        La respuesta aparecerá aquí
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JWT Generator -->
        <div class="card">
            <div class="card-title">
                <i class="ri-key-2-line mr-2"></i>JWT Generator
            </div>
            
            <div class="grid-3">
                <div class="form-group">
                    <label>User ID</label>
                    <input type="number" id="jwtUserId" class="form-input" placeholder="1" value="1">
                </div>
                
                <div class="form-group">
                    <label>Expiración (segundos)</label>
                    <input type="number" id="jwtExpiry" class="form-input" placeholder="3600" value="3600">
                </div>
                
                <div class="form-group flex items-end">
                    <button onclick="generateJWT()" class="btn-primary w-full">
                        <i class="ri-key-line mr-2"></i>Generar Token
                    </button>
                </div>
            </div>
            
            <div class="token-display mt-4" id="jwtTokenDisplay">
                <div class="text-gray-500 text-center py-4 text-sm">
                    El token aparecerá aquí
                </div>
            </div>
            
            <button onclick="copyJWT()" id="copyJwtBtn" class="btn-secondary w-full mt-3" style="display: none;">
                <i class="ri-file-copy-line mr-2"></i>Copiar Token
            </button>
        </div>
    </div>

    <!-- TAB 3: CLIENTS -->
    <div class="tab-content" id="tab-clients">
        <div class="card">
            <div class="card-title">
                <i class="ri-group-line mr-2"></i>Monitor de Clientes
                <button onclick="location.reload()" class="btn-secondary-sm ml-auto">
                    <i class="ri-refresh-line mr-1"></i>Actualizar
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Última Act.</th>
                            <th>Conv.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimos_usuarios)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-500">
                                    No hay usuarios con WhatsApp configurado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimos_usuarios as $usuario): ?>
                            <tr>
                                <td class="font-mono"><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre'] ?? 'N/A'); ?></td>
                                <td class="text-sm"><?php echo htmlspecialchars($usuario['email'] ?? 'N/A'); ?></td>
                                <td class="font-mono text-sm"><?php echo htmlspecialchars($usuario['phone_number'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $usuario['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $usuario['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-xs text-gray-600">
                                    <?php echo $usuario['last_activity'] ? date('d/m/Y H:i', strtotime($usuario['last_activity'])) : '-'; ?>
                                </td>
                                <td class="text-center"><?php echo $usuario['total_conversaciones']; ?></td>
                                <td>
                                    <button onclick="testUserConnection(<?php echo $usuario['id']; ?>)" class="btn-icon" title="Test conexión">
                                        <i class="ri-plug-line"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
// Sistema de tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tabId = btn.dataset.tab;
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(`tab-${tabId}`).classList.add('active');
    });
});

// Health check del servidor (vía PHP backend)
async function checkServerHealth() {
    try {
        const response = await fetch('/api/admin/whatsapp-debug?action=health');
        const data = await response.json();
        
        if (data.success && data.health.online) {
            const health = data.health;
            
            document.getElementById('serverStatus').innerHTML = `<span class="text-green-400">●</span> Online (${health.responseTime}ms)`;
            
            const healthStatus = document.getElementById('mainHealthStatus');
            healthStatus.className = 'health-status';
            healthStatus.innerHTML = `
                <span class="status-dot"></span>
                <div>
                    <div class="font-semibold">Servidor WhatsApp</div>
                    <div class="text-sm">Online</div>
                </div>
            `;
            
            document.getElementById('serverUptime').textContent = formatUptime(health.data?.uptime || 0);
            document.getElementById('activeClients').textContent = health.data?.activeClients || 0;
            document.getElementById('responseTime').textContent = `${health.responseTime}ms`;
        } else {
            throw new Error(data.health?.error || 'Servidor no responde');
        }
    } catch (error) {
        document.getElementById('serverStatus').innerHTML = `<span class="text-red-400">●</span> Offline`;
        
        const healthStatus = document.getElementById('mainHealthStatus');
        healthStatus.className = 'health-status danger';
        healthStatus.innerHTML = `
            <span class="status-dot"></span>
            <div>
                <div class="font-semibold">Servidor WhatsApp</div>
                <div class="text-sm">Offline</div>
            </div>
        `;
        
        console.error('Error conectando con servidor:', error);
    }
}

function formatUptime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours}h ${minutes}m`;
}

// JWT Generator (vía PHP backend)
async function generateJWT() {
    const userId = document.getElementById('jwtUserId').value;
    const expiry = document.getElementById('jwtExpiry').value || 3600;
    
    if (!userId) {
        alert('User ID requerido');
        return;
    }
    
    try {
        const response = await fetch('/api/admin/whatsapp-debug', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=generate-jwt&userId=${userId}&expiry=${expiry}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('jwtTokenDisplay').innerHTML = `<div class="font-mono text-xs break-all p-3">${data.token}</div>`;
            document.getElementById('copyJwtBtn').style.display = 'block';
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        alert('Error generando token: ' + error.message);
    }
}

function copyJWT() {
    const tokenText = document.getElementById('jwtTokenDisplay').textContent;
    navigator.clipboard.writeText(tokenText).then(() => {
        const btn = document.getElementById('copyJwtBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ri-check-line mr-2"></i>Copiado!';
        setTimeout(() => btn.innerHTML = originalText, 2000);
    });
}

// API Tester
function updateApiBody() {
    const endpoint = document.getElementById('apiEndpoint').value;
    const bodyContainer = document.getElementById('apiBodyContainer');
    const bodyTextarea = document.getElementById('apiBody');
    
    if (endpoint.includes('POST')) {
        bodyContainer.style.display = 'block';
        const examples = {
            '/api/send': JSON.stringify({ to: '34612345678', message: 'Test' }, null, 2),
            '/api/connect': '{}',
            '/api/disconnect': '{}'
        };
        bodyTextarea.value = examples[endpoint] || '{}';
    } else {
        bodyContainer.style.display = 'none';
    }
}

async function testApiEndpoint() {
    const userId = document.getElementById('apiUserId').value;
    const endpoint = document.getElementById('apiEndpoint').value;
    const method = endpoint.includes('POST') ? 'POST' : 'GET';
    const bodyText = document.getElementById('apiBody').value;
    
    if (!userId && !endpoint.includes('/health')) {
        alert('User ID requerido');
        return;
    }
    
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'test-api');
        formData.append('userId', userId);
        formData.append('endpoint', endpoint);
        formData.append('method', method);
        if (method === 'POST' && bodyText) {
            formData.append('body', bodyText);
        }
        
        const response = await fetch('/api/admin/whatsapp-debug', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const result = data.result;
            
            document.getElementById('apiResponse').innerHTML = `
                <div class="mb-2 text-sm">
                    <span class="font-semibold">Status:</span> 
                    <span class="badge ${result.success ? 'connected' : 'disconnected'}">${result.statusCode}</span>
                    <span class="ml-3 font-semibold">Tiempo:</span> ${result.responseTime}ms
                </div>
                <pre class="text-xs">${JSON.stringify(result.body, null, 2)}</pre>
            `;
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        document.getElementById('apiResponse').innerHTML = `<div class="text-red-600 text-sm"><i class="ri-error-warning-line mr-2"></i>${error.message}</div>`;
    }
}

// Ya no necesitamos esta función
// function generateTokenForRequest(userId) { ... }

// Webhook Simulator (vía PHP backend)
async function updateWebhookData() {
    const event = document.getElementById('webhookEvent').value;
    
    try {
        const response = await fetch(`/api/admin/whatsapp-debug?action=webhook-example&event=${event}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('webhookData').value = JSON.stringify(data.data, null, 2);
        }
    } catch (error) {
        console.error('Error obteniendo ejemplo:', error);
    }
}

async function sendWebhook() {
    const userId = document.getElementById('webhookUserId').value;
    const event = document.getElementById('webhookEvent').value;
    const dataText = document.getElementById('webhookData').value;
    
    if (!userId) {
        alert('User ID requerido');
        return;
    }
    
    try {
        // Validar JSON
        JSON.parse(dataText);
    } catch (e) {
        alert('JSON inválido');
        return;
    }
    
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'simulate-webhook');
        formData.append('userId', userId);
        formData.append('event', event);
        formData.append('data', dataText);
        
        const response = await fetch('/api/admin/whatsapp-debug', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        
        const data = await response.json();
        
        document.getElementById('webhookResponse').innerHTML = `
            <div class="mb-2 text-sm">
                <span class="font-semibold">Status:</span> 
                <span class="badge ${data.success ? 'connected' : 'disconnected'}">${data.statusCode || 'Error'}</span>
                <span class="ml-3 font-semibold">Tiempo:</span> ${data.responseTime || 0}ms
            </div>
            <pre class="text-xs">${JSON.stringify(data.response, null, 2)}</pre>
        `;
        
    } catch (error) {
        document.getElementById('webhookResponse').innerHTML = `<div class="text-red-600 text-sm"><i class="ri-error-warning-line mr-2"></i>${error.message}</div>`;
    }
}

// Clients Monitor
function testUserConnection(userId) {
    document.getElementById('apiUserId').value = userId;
    document.getElementById('apiEndpoint').value = '/api/status';
    document.querySelector('[data-tab="debug"]').click();
    setTimeout(() => testApiEndpoint(), 300);
}

// Gráfico de volumen
const volumenCtx = document.getElementById('volumenChart')?.getContext('2d');
if (volumenCtx) {
    const fechas = [<?php foreach ($volumen_7 as $v): ?>'<?php echo date('d/m', strtotime($v['fecha'])); ?>',<?php endforeach; ?>];
    const enviados = [<?php foreach ($volumen_7 as $v): ?><?php echo $v['enviados'] ?? 0; ?>,<?php endforeach; ?>];
    const recibidos = [<?php foreach ($volumen_7 as $v): ?><?php echo $v['recibidos'] ?? 0; ?>,<?php endforeach; ?>];
    
    new Chart(volumenCtx, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [
                {
                    label: 'Enviados',
                    data: enviados,
                    borderColor: '#25d366',
                    backgroundColor: 'rgba(37, 211, 102, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Recibidos',
                    data: recibidos,
                    borderColor: '#128c7e',
                    backgroundColor: 'rgba(18, 140, 126, 0.1)',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    checkServerHealth();
    updateApiBody();
    updateWebhookData();
    setInterval(checkServerHealth, 30000);
});
</script>

<style>
.admin-container {
    background: #f7fafc;
    min-height: 100vh;
    padding: 2rem;
}

.admin-header {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.tabs-header {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

.tab-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border: none;
    background: rgba(255,255,255,0.2);
    color: white;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.tab-btn:hover {
    background: rgba(255,255,255,0.3);
}

.tab-btn.active {
    background: white;
    color: #128c7e;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.metric-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.metric-label {
    color: #718096;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
}

.metric-subtitle {
    font-size: 0.85rem;
    color: #a0aec0;
    margin-top: 0.5rem;
}

.health-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
}

.health-status {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f0fff4;
    border-left: 4px solid #48bb78;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.health-status.danger {
    background: #fff5f5;
    border-left-color: #f56565;
}

.health-status .status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #48bb78;
    animation: pulse 2s infinite;
}

.health-status.danger .status-dot {
    background: #f56565;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.card-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f7fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    color: #2d3748;
    border-bottom: 2px solid #e2e8f0;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.table tbody tr:hover {
    background: #f7fafc;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge.connected { background: #c6f6d5; color: #22543d; }
.badge.connecting { background: #feebc8; color: #7c2d12; }
.badge.disconnected { background: #fed7d7; color: #742a2a; }
.badge.waiting_qr { background: #bee3f8; color: #2c5282; }

.stat-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-label {
    color: #718096;
    font-size: 0.95rem;
}

.stat-value {
    font-weight: 600;
    color: #2d3748;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin: 0.5rem 0;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #25d366, #128c7e);
    transition: width 0.3s ease;
}

.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.grid-3 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
}

.form-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-secondary-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    background: #f1f5f9;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: #e2e8f0;
}

.response-box,
.token-display {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    min-height: 100px;
    overflow: auto;
}

.overflow-x-auto {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .admin-container {
        padding: 1rem;
    }
    
    .grid-2, .grid-3 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include PROJECT_ROOT . '/includes/footerAdmin.php'; ?>