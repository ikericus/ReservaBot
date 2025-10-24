<?php
// pages/admin/whatsapp.php

/**
 * Página de monitoreo de WhatsApp
 */

$adminDomain = getContainer()->getAdminDomain();

$currentPage = 'admin-whatsapp';
$pageTitle = 'ReservaBot Admin - WhatsApp';

// Obtener datos
$salud = $adminDomain->obtenerSaludWhatsApp();
$stats = $adminDomain->obtenerEstadisticasMensajes();
$ultimos_usuarios = $adminDomain->obtenerUltimosUsuariosWhatsApp(10);
$numeros_activos = $adminDomain->obtenerNumerosMasActivos(10);
$volumen_7 = $adminDomain->obtenerVolumenMensajesPor7Dias();

include PROJECT_ROOT . '/includes/headerAdmin.php';
?>

<div class="admin-container">
   
    <?php include PROJECT_ROOT . '/pages/admin/menu.php'; ?>

    <!-- Salud del Sistema -->
    <div class="health-card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            <i class="ri-heart-pulse-line mr-2"></i>Estado del Sistema
        </h2>
        
        <div class="health-status <?php echo $salud['estado_servidor'] === 'online' ? '' : 'danger'; ?>">
            <span class="status-dot"></span>
            <div>
                <div class="font-semibold">Servidor WhatsApp</div>
                <div class="text-sm"><?php echo ucfirst($salud['estado_servidor']); ?></div>
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
    </div>

    <!-- Métricas de Mensajes -->
    <div class="metrics-grid">
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
                        <?php foreach ($ultimos_usuarios as $usuario): ?>
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
    <div class="card">
        <div class="card-title">
            <i class="ri-line-chart-line mr-2"></i>Volumen de Mensajes (Últimos 7 Días)
        </div>
        <div style="height: 300px;">
            <canvas id="volumenChart"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    // Gráfico de Volumen
    const volumenCtx = document.getElementById('volumenChart')?.getContext('2d');
    if (volumenCtx) {
        const fechas = [
            <?php foreach ($volumen_7 as $v): ?>
                '<?php echo date('d/m', strtotime($v['fecha'])); ?>',
            <?php endforeach; ?>
        ];
        
        const enviados = [
            <?php foreach ($volumen_7 as $v): ?>
                <?php echo $v['enviados'] ?? 0; ?>,
            <?php endforeach; ?>
        ];
        
        const recibidos = [
            <?php foreach ($volumen_7 as $v): ?>
                <?php echo $v['recibidos'] ?? 0; ?>,
            <?php endforeach; ?>
        ];
        
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
                        fill: false,
                        pointBackgroundColor: '#25d366',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Recibidos',
                        data: recibidos,
                        borderColor: '#128c7e',
                        backgroundColor: 'rgba(18, 140, 126, 0.1)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#128c7e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
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

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
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
    margin-bottom: 2rem;
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

.health-status.warning {
    background: #fffff0;
    border-left-color: #ed8936;
}

.health-status.danger {
    background: #fff5f5;
    border-left-color: #f56565;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #48bb78;
    animation: pulse 2s infinite;
}

.health-status.warning .status-dot {
    background: #ed8936;
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
    margin-bottom: 2rem;
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
.badge.error { background: #fed7d7; color: #742a2a; }

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
</style>