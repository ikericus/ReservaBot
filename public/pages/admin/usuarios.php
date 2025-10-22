<?php
// public/pages/admin/usuarios.php

/**
 * Página de gestión y estadísticas de usuarios
 */

requireAdminAuth();

$adminDomain = getContainer()->getAdminDomain();

$currentPage = 'admin-usuarios';
$pageTitle = 'ReservaBot Admin - Usuarios';

// Obtener datos
$ultimos_usuarios = $adminDomain->obtenerUltimosUsuarios(15);
$usuarios_activos = $adminDomain->obtenerUsuariosMasActivos(10);
$total_usuarios = $adminDomain->contarTotalUsuarios();
$usuarios_planes = $adminDomain->contarUsuariosPorPlan();
$tasa_retencion = $adminDomain->obtenerTasaRetencion();
$nuevos_hoy = $adminDomain->obtenerUltimosUsuarios(1);

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

.badge.premium { background: #fed7d7; color: #742a2a; }
.badge.basico { background: #bee3f8; color: #2c5282; }
.badge.gratis { background: #c6f6d5; color: #22543d; }

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #edf2f7;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #2d3748;
}

.plan-breakdown {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.plan-item {
    padding: 1rem;
    background: #f7fafc;
    border-radius: 6px;
    text-align: center;
}

.plan-item-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.plan-item-label {
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
        <a href="/admin/usuarios" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">
            <i class="ri-user-line mr-2"></i>Usuarios
        </a>
    </div>

    <!-- Header -->
    <div class="admin-header">
        <h1 class="text-3xl font-bold mb-2">
            <i class="ri-user-line mr-2"></i>Gestión de Usuarios
        </h1>
        <p>Estadísticas y análisis de la comunidad de usuarios</p>
    </div>

    <!-- Métricas Principales -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">Total de Usuarios</div>
            <div class="metric-value"><?php echo number_format($total_usuarios); ?></div>
            <div class="metric-subtitle">Todos los tiempos</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Nuevos Hoy</div>
            <div class="metric-value"><?php echo count($nuevos_hoy); ?></div>
            <div class="metric-subtitle">Desde las 00:00</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Tasa de Retención</div>
            <div class="metric-value"><?php echo $tasa_retencion['tasa_retencion']; ?>%</div>
            <div class="metric-subtitle">Últimos 30 días</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Activos (30 días)</div>
            <div class="metric-value"><?php echo $tasa_retencion['activos_30_dias']; ?></div>
            <div class="metric-subtitle">Con actividad</div>
        </div>
    </div>

    <!-- Distribución por Plan -->
    <div class="card">
        <div class="card-title">
            <i class="ri-pie-chart-2-line mr-2"></i>Distribución por Plan
        </div>
        
        <div class="plan-breakdown">
            <?php foreach ($usuarios_planes as $plan): ?>
            <div class="plan-item">
                <div class="plan-item-value"><?php echo $plan['total']; ?></div>
                <div class="plan-item-label"><?php echo ucfirst($plan['plan']); ?></div>
                <div class="text-xs text-gray-500 mt-1">
                    <?php echo round(($plan['total'] / $total_usuarios) * 100, 1); ?>%
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Gráfico -->
        <div style="height: 300px;">
            <canvas id="planChart"></canvas>
        </div>
    </div>

    <!-- Últimos Usuarios -->
    <div class="grid-2">
        <div class="card">
            <div class="card-title">
                <i class="ri-user-add-line mr-2"></i>Últimos Usuarios Registrados
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Plan</th>
                        <th>Reservas</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimos_usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <div class="font-medium text-sm"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($usuario['email']); ?></div>
                        </td>
                        <td>
                            <span class="badge <?php echo strtolower($usuario['plan']); ?>">
                                <?php echo ucfirst($usuario['plan']); ?>
                            </span>
                        </td>
                        <td class="text-center"><?php echo $usuario['total_reservas']; ?></td>
                        <td class="text-xs text-gray-600">
                            <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Usuarios Más Activos -->
        <div class="card">
            <div class="card-title">
                <i class="ri-flame-line mr-2"></i>Usuarios Más Activos
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Reservas</th>
                        <th>Actividad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios_activos as $usuario): ?>
                    <tr>
                        <td>
                            <div class="font-medium text-sm"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($usuario['email']); ?></div>
                        </td>
                        <td class="font-semibold"><?php echo $usuario['total_reservas']; ?></td>
                        <td>
                            <div class="text-xs">
                                <div class="stat-badge">
                                    <i class="ri-calendar-line"></i>
                                    <?php echo $usuario['dias_con_reservas']; ?> días
                                </div>
                            </div>
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
    const planCtx = document.getElementById('planChart')?.getContext('2d');
    if (planCtx) {
        const labels = [
            <?php foreach ($usuarios_planes as $plan): ?>
                '<?php echo ucfirst($plan['plan']); ?>',
            <?php endforeach; ?>
        ];
        
        const data = [
            <?php foreach ($usuarios_planes as $plan): ?>
                <?php echo $plan['total']; ?>,
            <?php endforeach; ?>
        ];
        
        new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
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
</script>

<?php include PROJECT_ROOT . '/includes/footer.php'; ?>
