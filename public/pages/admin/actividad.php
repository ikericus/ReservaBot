<?php
// public/pages/admin/actividad.php

/**
 * Página de monitoreo de actividad del sistema
 */

$adminDomain = getContainer()->getAdminDomain();

$currentPage = 'admin-actividad';
$pageTitle = 'ReservaBot Admin - Actividad';

// Obtener datos
$ultimos_accesos = $adminDomain->obtenerUltimosAccesos(20);
$logins_hoy = $adminDomain->obtenerLoginsHoy();
$usuarios_activos_hora = $adminDomain->obtenerUsuariosActivosUltimaHora();
$recursos = $adminDomain->obtenerEstadisticasRecursos();
$errores = $adminDomain->obtenerErroresRecientes(10);

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

.stat-box {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.stat-box.warning {
    border-left: 4px solid #ed8936;
}

.stat-box.danger {
    border-left: 4px solid #f56565;
}

.stat-box.success {
    border-left: 4px solid #48bb78;
}

.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.table-container table {
    width: 100%;
    border-collapse: collapse;
}

.table-container thead {
    background: #f7fafc;
    border-bottom: 2px solid #e2e8f0;
}

.table-container th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2d3748;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-container td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #4a5568;
}

.table-container tbody tr:hover {
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

.badge.error { background: #fed7d7; color: #c53030; }
.badge.warning { background: #feebc8; color: #c05621; }
.badge.success { background: #c6f6d5; color: #22543d; }
.badge.info { background: #bee3f8; color: #2c5282; }

.timeline {
    position: relative;
}

.timeline-item {
    padding-left: 3rem;
    padding-bottom: 1.5rem;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: -1.5rem;
    width: 2px;
    background: #e2e8f0;
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-item::after {
    content: '';
    position: absolute;
    left: -0.25rem;
    top: 0.5rem;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    background: #667eea;
    border: 3px solid white;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
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
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2d3748;
    line-height: 1;
}

.metric-change {
    font-size: 0.85rem;
    color: #48bb78;
    margin-top: 0.5rem;
}
</style>

<div class="admin-container">
    <!-- Navegación -->
    <div class="mb-8 flex gap-2 flex-wrap">
        <a href="/admin/dashboard" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-white hover:shadow">
            <i class="ri-arrow-left-line mr-2"></i>Volver
        </a>
        <a href="/admin/actividad" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">
            <i class="ri-history-line mr-2"></i>Actividad
        </a>
    </div>

    <!-- Header -->
    <div class="admin-header">
        <h1 class="text-3xl font-bold mb-2">
            <i class="ri-history-line mr-2"></i>Monitoreo de Actividad
        </h1>
        <p>Seguimiento en tiempo real de la actividad del sistema</p>
    </div>

    <!-- Métricas Principales -->
    <div class="metrics-grid">
        <div class="metric-card success">
            <div class="metric-label">
                <i class="ri-login-box-line mr-2"></i>Logins Hoy
            </div>
            <div class="metric-value"><?php echo $logins_hoy; ?></div>
            <div class="metric-change">Desde hace 24 horas</div>
        </div>

        <div class="metric-card warning">
            <div class="metric-label">
                <i class="ri-user-check-line mr-2"></i>Usuarios Activos (1h)
            </div>
            <div class="metric-value"><?php echo $usuarios_activos_hora; ?></div>
            <div class="metric-change">Últimos 60 minutos</div>
        </div>

        <div class="metric-card info">
            <div class="metric-label">
                <i class="ri-server-line mr-2"></i>Total Accesos (7d)
            </div>
            <div class="metric-value"><?php echo count($ultimos_accesos); ?></div>
            <div class="metric-change">Datos agregados</div>
        </div>
    </div>

    <!-- Últimos Accesos -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Reservas</th>
                    <th>Días Activos</th>
                    <th>Último Acceso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimos_accesos as $acceso): ?>
                <tr>
                    <td class="font-medium"><?php echo htmlspecialchars($acceso['nombre']); ?></td>
                    <td class="text-gray-600"><?php echo htmlspecialchars($acceso['email']); ?></td>
                    <td>
                        <span class="badge <?php echo strtolower($acceso['plan']); ?>">
                            <?php echo ucfirst($acceso['plan']); ?>
                        </span>
                    </td>
                    <td><?php echo $acceso['total_reservas']; ?></td>
                    <td><?php echo $acceso['dias_activo']; ?></td>
                    <td class="text-sm">
                        <?php 
                        if ($acceso['last_activity']) {
                            echo date('d/m/Y H:i', strtotime($acceso['last_activity']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Recursos Más Solicitados -->
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="ri-database-line mr-2"></i>Recursos Más Solicitados
    </h2>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Recurso</th>
                    <th>Total Accesos</th>
                    <th>Días Accedidos</th>
                    <th>Popularidad</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recursos as $recurso): ?>
                <tr>
                    <td class="font-medium"><?php echo htmlspecialchars($recurso['resource']); ?></td>
                    <td><?php echo number_format($recurso['total_accesos']); ?></td>
                    <td><?php echo $recurso['dias_accedidos']; ?></td>
                    <td>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" 
                                 style="width: <?php echo min(100, ($recurso['total_accesos'] / 100) * 100); ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Errores Recientes -->
    <?php if (!empty($errores)): ?>
    <h2 class="text-2xl font-bold text-gray-900 mb-4 mt-8">
        <i class="ri-error-warning-line mr-2"></i>Errores Recientes
    </h2>
    
    <div class="timeline">
        <?php foreach ($errores as $error): ?>
        <div class="stat-box danger">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <span class="badge error"><?php echo $error['nivel']; ?></span>
                    <span class="text-gray-600 text-sm ml-3">
                        <?php echo date('d/m/Y H:i:s', strtotime($error['created_at'])); ?>
                    </span>
                </div>
            </div>
            <p class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($error['mensaje']); ?></p>
            <p class="text-xs text-gray-600">
                <code><?php echo htmlspecialchars($error['archivo']); ?></code>:<?php echo $error['linea']; ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include PROJECT_ROOT . '/includes/footer.php'; ?>
