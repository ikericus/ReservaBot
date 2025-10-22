<?php
// public/pages/admin/dashboard.php

/**
 * Dashboard principal de administración
 */

requireAdminAuth();

$adminDomain = getContainer()->getAdminDomain();
$resumen = $adminDomain->obtenerResumenGeneral();
$saludWhatsApp = $adminDomain->obtenerSaludWhatsApp();

$currentPage = 'admin-dashboard';
$pageTitle = 'ReservaBot - Admin Dashboard';
$pageScript = 'admin/dashboard';

include PROJECT_ROOT . '/includes/header.php';
?>

<style>
.admin-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
}

.stat-card h3 {
    color: #667eea;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
}

.stat-card .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1a202c;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-card .change {
    font-size: 0.85rem;
    color: #48bb78;
}

.stat-card.warning .value {
    color: #ed8936;
}

.stat-card.danger .value {
    color: #f56565;
}

.admin-nav {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.admin-nav a {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    color: #4a5568;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.admin-nav a:hover,
.admin-nav a.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.health-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f0fff4;
    border-left: 4px solid #48bb78;
    border-radius: 4px;
    font-size: 0.9rem;
}

.health-status.warning {
    background: #fffff0;
    border-color: #ed8936;
}

.health-status.danger {
    background: #fff5f5;
    border-color: #f56565;
}

.chart-container {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.chart-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 1rem;
}
</style>

<div class="admin-container">
    <!-- Navegación -->
    <div class="admin-nav">
        <a href="/admin/dashboard" class="active">
            <i class="ri-dashboard-line mr-2"></i>Dashboard
        </a>
        <a href="/admin/actividad">
            <i class="ri-history-line mr-2"></i>Actividad
        </a>
        <a href="/admin/usuarios">
            <i class="ri-user-line mr-2"></i>Usuarios
        </a>
        <a href="/admin/reservas">
            <i class="ri-calendar-line mr-2"></i>Reservas
        </a>
        <a href="/admin/whatsapp">
            <i class="ri-whatsapp-line mr-2"></i>WhatsApp
        </a>
        <a href="/admin/logs" class="ml-auto">
            <i class="ri-bug-line mr-2"></i>Logs
        </a>
    </div>

    <!-- Título -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">
            <i class="ri-admin-line mr-3"></i>Panel de Administración
        </h1>
        <p class="text-indigo-100">Monitoriza el estado del sistema en tiempo real</p>
    </div>

    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Usuarios -->
        <div class="stat-card">
            <h3>
                <i class="ri-user-fill mr-2"></i>Usuarios Total
            </h3>
            <div class="value"><?php echo number_format($resumen['usuarios_total']); ?></div>
            <div class="change">
                <i class="ri-arrow-up-line"></i>
                +<?php echo $resumen['usuarios_nuevos_hoy']; ?> hoy
            </div>
        </div>

        <!-- Reservas -->
        <div class="stat-card">
            <h3>
                <i class="ri-calendar-fill mr-2"></i>Reservas Total
            </h3>
            <div class="value"><?php echo number_format($resumen['reservas_total']); ?></div>
            <div class="change">
                <i class="ri-arrow-up-line"></i>
                +<?php echo $resumen['reservas_hoy']; ?> hoy
            </div>
        </div>

        <!-- WhatsApp Conectados -->
        <div class="stat-card <?php echo $saludWhatsApp['tasa_conexion'] < 50 ? 'warning' : ''; ?>">
            <h3>
                <i class="ri-whatsapp-fill mr-2"></i>WhatsApp Conectados
            </h3>
            <div class="value"><?php echo $saludWhatsApp['usuarios_conectados']; ?></div>
            <div class="change">
                <?php echo round($saludWhatsApp['tasa_conexion'], 1); ?>% de registrados
            </div>
        </div>

        <!-- Mensajes Hoy -->
        <div class="stat-card">
            <h3>
                <i class="ri-message-fill mr-2"></i>Mensajes Hoy
            </h3>
            <div class="value"><?php echo number_format($resumen['mensajes_hoy']); ?></div>
            <div class="change">
                <i class="ri-check-line"></i>Estado activo
            </div>
        </div>
    </div>

    <!-- Salud del Sistema -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="chart-container">
            <div class="chart-title">Salud del Sistema WhatsApp</div>
            
            <div class="space-y-4">
                <div class="health-status <?php echo $saludWhatsApp['estado_servidor'] === 'online' ? '' : 'danger'; ?>">
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                    Servidor: <?php echo ucfirst($saludWhatsApp['estado_servidor']); ?>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span>Conexión (<?php echo $saludWhatsApp['usuarios_conectados']; ?>/<?php echo $saludWhatsApp['usuarios_registrados']; ?>)</span>
                        <span class="font-bold"><?php echo round($saludWhatsApp['tasa_conexion'], 1); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-green-400 to-blue-500 h-2 rounded-full" 
                             style="width: <?php echo $saludWhatsApp['tasa_conexion']; ?>%"></div>
                    </div>
                </div>
                
                <div class="text-xs text-gray-600 pt-2 border-t">
                    <div>📅 Registrados: <?php echo $saludWhatsApp['usuarios_registrados']; ?></div>
                    <div>🟢 Conectados: <?php echo $saludWhatsApp['usuarios_conectados']; ?></div>
                    <div>💬 Mensajes hoy: <?php echo $resumen['mensajes_hoy']; ?></div>
                </div>
            </div>
        </div>

        <!-- Distribución de Planes -->
        <div class="chart-container">
            <div class="chart-title">Usuarios por Plan</div>
            <div id="planChart" style="height: 300px;"></div>
        </div>

        <!-- Estado de Reservas -->
        <div class="chart-container">
            <div class="chart-title">Estado de Reservas</div>
            <div id="estadoChart" style="height: 300px;"></div>
        </div>
    </div>

    <!-- Últimos Usuarios y Reservas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Últimos Usuarios -->
        <div class="chart-container">
            <div class="chart-title">Últimos Usuarios Registrados</div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php 
                $ultimos = $adminDomain->obtenerUltimosUsuarios(5);
                foreach ($ultimos as $usuario): 
                ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($usuario['email']); ?></div>
                            <div class="text-xs text-gray-500">
                                Plan: <span class="font-semibold"><?php echo ucfirst($usuario['plan']); ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-indigo-600">
                                <?php echo $usuario['total_reservas']; ?> reservas
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="/admin/usuarios" class="text-indigo-600 text-sm font-medium mt-4 inline-block hover:text-indigo-700">
                Ver todos →
            </a>
        </div>

        <!-- Últimas Reservas -->
        <div class="chart-container">
            <div class="chart-title">Últimas Reservas Creadas</div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php 
                $ultimas = $adminDomain->obtenerUltimasReservas(5);
                foreach ($ultimas as $reserva): 
                ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($reserva['cliente_nombre']); ?></div>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($reserva['usuario_email']); ?></div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('d/m H:i', strtotime($reserva['fecha_inicio'])); ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-semibold px-2 py-1 rounded-full 
                                <?php 
                                echo match($reserva['estado']) {
                                    'confirmada' => 'bg-green-100 text-green-800',
                                    'cancelada' => 'bg-red-100 text-red-800',
                                    'pendiente' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                <?php echo ucfirst($reserva['estado']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="/admin/reservas" class="text-indigo-600 text-sm font-medium mt-4 inline-block hover:text-indigo-700">
                Ver todas →
            </a>
        </div>
    </div>
</div>

<!-- Chart.js para gráficos -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    // Gráfico de Planes
    const planCtx = document.getElementById('planChart')?.getContext('2d');
    if (planCtx) {
        new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: ['Premium', 'Básico', 'Gratis'],
                datasets: [{
                    data: [45, 35, 20],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Gráfico de Estado de Reservas
    const estadoCtx = document.getElementById('estadoChart')?.getContext('2d');
    if (estadoCtx) {
        new Chart(estadoCtx, {
            type: 'bar',
            data: {
                labels: ['Confirmadas', 'Pendientes', 'Canceladas'],
                datasets: [{
                    label: 'Reservas',
                    data: [450, 120, 80],
                    backgroundColor: [
                        '#48bb78',
                        '#ed8936',
                        '#f56565'
                    ],
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
</script>

<?php include PROJECT_ROOT . '/includes/footer.php'; ?>
