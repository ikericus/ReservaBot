<?php
// public/pages/admin/reservas.php

/**
 * Página de estadísticas de reservas
 */

requireAdminAuth();

$adminDomain = getContainer()->getAdminDomain();

$currentPage = 'admin-reservas';
$pageTitle = 'ReservaBot Admin - Reservas';

// Obtener datos
$stats = $adminDomain->obtenerEstadisticasReservas();
$ultimas_reservas = $adminDomain->obtenerUltimasReservas(15);
$volumen_30 = $adminDomain->obtenerVolumenReservasPor30Dias();
$volumen_hoy = $adminDomain->obtenerVolumenReservasPorHoraHoy();

include PROJECT_ROOT . '/includes/header.php';
?>

<style>
.admin-container {
    background: #f7fafc;
    min-height: 100vh;
    padding: 2rem;
}

.admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.badge.confirmada { background: #c6f6d5; color: #22543d; }
.badge.pendiente { background: #feebc8; color: #7c2d12; }
.badge.cancelada { background: #fed7d7; color: #742a2a; }

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.status-item {
    padding: 1rem;
    background: #f7fafc;
    border-radius: 6px;
    text-align: center;
    border-left: 4px solid #667eea;
}

.status-item.confirmada {
    border-left-color: #48bb78;
}

.status-item.pendiente {
    border-left-color: #ed8936;
}

.status-item.cancelada {
    border-left-color: #f56565;
}

.status-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.status-label {
    font-size: 0.85rem;
    color: #718096;
    font-weight: 600;
}

.chart-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}
</style>

<div class="admin-container">
    <!-- Navegación -->
    <div class="mb-8 flex gap-2 flex-wrap">
        <a href="/admin/dashboard" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-white hover:shadow">
            <i class="ri-arrow-left-line mr-2"></i>Volver
        </a>
        <a href="/admin/reservas" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">
            <i class="ri-calendar-line mr-2"></i>Reservas
        </a>
    </div>

    <!-- Header -->
    <div class="admin-header">
        <h1 class="text-3xl font-bold mb-2">
            <i class="ri-calendar-line mr-2"></i>Estadísticas de Reservas
        </h1>
        <p>Análisis completo del volumen y estado de reservas</p>
    </div>

    <!-- Métricas Principales -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">Total Reservas</div>
            <div class="metric-value"><?php echo number_format($stats['total']); ?></div>
            <div class="metric-subtitle">Todos los tiempos</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Hoy</div>
            <div class="metric-value"><?php echo $stats['hoy']; ?></div>
            <div class="metric-subtitle">Desde las 00:00</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Esta Semana</div>
            <div class="metric-value"><?php echo $stats['semana']; ?></div>
            <div class="metric-subtitle">Últimos 7 días</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Promedio Diario</div>
            <div class="metric-value"><?php echo $stats['promedio_diario']; ?></div>
            <div class="metric-subtitle">Últimos 30 días</div>
        </div>
    </div>

    <!-- Distribución por Estado -->
    <div class="card">
        <div class="card-title">
            <i class="ri-pie-chart-2-line mr-2"></i>Distribución por Estado
        </div>
        
        <div class="status-grid">
            <?php foreach ($stats['estado_distribucion'] as $estado): ?>
            <div class="status-item <?php echo strtolower($estado['estado']); ?>">
                <div class="status-value"><?php echo $estado['total']; ?></div>
                <div class="status-label"><?php echo ucfirst($estado['estado']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Gráfico -->
        <div style="height: 300px;">
            <canvas id="estadoChart"></canvas>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid-2">
        <!-- Volumen últimos 30 días -->
        <div class="chart-container">
            <div class="card-title">
                <i class="ri-line-chart-line mr-2"></i>Volumen Últimos 30 Días
            </div>
            <div style="height: 300px;">
                <canvas id="volumenChart"></canvas>
            </div>
        </div>

        <!-- Volumen por hora hoy -->
        <div class="chart-container">
            <div class="card-title">
                <i class="ri-bar-chart-line mr-2"></i>Distribución Horaria Hoy
            </div>
            <div style="height: 300px;">
                <canvas id="horaChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Últimas Reservas -->
    <div class="card">
        <div class="card-title">
            <i class="ri-list-check-line mr-2"></i>Últimas Reservas Creadas
        </div>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Usuario</th>
                        <th>Servicio</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Creada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas_reservas as $reserva): ?>
                    <tr>
                        <td class="font-medium"><?php echo htmlspecialchars($reserva['cliente_nombre']); ?></td>
                        <td class="text-sm text-gray-600"><?php echo htmlspecialchars($reserva['usuario_email']); ?></td>
                        <td class="text-sm"><?php echo htmlspecialchars($reserva['servicio'] ?? 'N/A'); ?></td>
                        <td class="text-sm">
                            <?php echo date('d/m H:i', strtotime($reserva['fecha_inicio'])); ?>
                        </td>
                        <td>
                            <span class="badge <?php echo strtolower($reserva['estado']); ?>">
                                <?php echo ucfirst($reserva['estado']); ?>
                            </span>
                        </td>
                        <td class="text-xs text-gray-600">
                            <?php echo date('d/m/Y H:i', strtotime($reserva['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    // Gráfico de Estado
    const estadoCtx = document.getElementById('estadoChart')?.getContext('2d');
    if (estadoCtx) {
        const estados = [
            <?php foreach ($stats['estado_distribucion'] as $e): ?>
                '<?php echo ucfirst($e['estado']); ?>',
            <?php endforeach; ?>
        ];
        
        const totales = [
            <?php foreach ($stats['estado_distribucion'] as $e): ?>
                <?php echo $e['total']; ?>,
            <?php endforeach; ?>
        ];
        
        const colors = ['#48bb78', '#ed8936', '#f56565'];
        
        new Chart(estadoCtx, {
            type: 'doughnut',
            data: {
                labels: estados,
                datasets: [{
                    data: totales,
                    backgroundColor: colors.slice(0, estados.length),
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

    // Gráfico de Volumen 30 días
    const volumenCtx = document.getElementById('volumenChart')?.getContext('2d');
    if (volumenCtx) {
        const fechas = [
            <?php foreach ($volumen_30 as $v): ?>
                '<?php echo date('d/m', strtotime($v['fecha'])); ?>',
            <?php endforeach; ?>
        ];
        
        const cantidades = [
            <?php foreach ($volumen_30 as $v): ?>
                <?php echo $v['cantidad']; ?>,
            <?php endforeach; ?>
        ];
        
        new Chart(volumenCtx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [{
                    label: 'Reservas',
                    data: cantidades,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
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

    // Gráfico de Horas
    const horaCtx = document.getElementById('horaChart')?.getContext('2d');
    if (horaCtx) {
        const horas = [
            <?php for ($i = 0; $i < 24; $i++): ?>
                '<?php echo sprintf("%02d:00", $i); ?>',
            <?php endfor; ?>
        ];
        
        const cantidades = [
            <?php 
            $volumen_map = array_column($volumen_hoy, 'cantidad', 'hora');
            for ($i = 0; $i < 24; $i++) {
                echo ($volumen_map[$i] ?? 0) . ',';
            }
            ?>
        ];
        
        new Chart(horaCtx, {
            type: 'bar',
            data: {
                labels: horas,
                datasets: [{
                    label: 'Reservas',
                    data: cantidades,
                    backgroundColor: '#764ba2',
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>

<?php include PROJECT_ROOT . '/includes/footer.php'; ?>
