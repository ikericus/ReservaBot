<?php
    $current = $_SERVER['REQUEST_URI'];
?>

<div class="admin-nav">
    <a href="/admin" class="<?= str_ends_with($current, '/admin') ? 'active' : '' ?>">
        <i class="ri-dashboard-line mr-2"></i>Dashboard
    </a>
    <a href="/admin/actividad" class="<?= str_ends_with($current, '/admin/actividad') ? 'active' : '' ?>">
        <i class="ri-history-line mr-2"></i>Actividad
    </a>
    <a href="/admin/usuarios" class="<?= str_ends_with($current, '/admin/usuarios') ? 'active' : '' ?>">
        <i class="ri-user-line mr-2"></i>Usuarios
    </a>
    <a href="/admin/reservas" class="<?= str_ends_with($current, '/admin/reservas') ? 'active' : '' ?>">
        <i class="ri-calendar-line mr-2"></i>Reservas
    </a>
    <a href="/admin/whatsapp" class="<?= str_ends_with($current, '/admin/whatsapp') ? 'active' : '' ?>">
        <i class="ri-whatsapp-line mr-2"></i>WhatsApp
    </a>
    <a href="/admin/logs" class="ml-auto <?= str_ends_with($current, '/admin/logs') ? 'active' : '' ?>">
        <i class="ri-bug-line mr-2"></i>Logs
    </a>
    <a href="/logout" class="ml-auto flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
        <i class="ri-logout-box-line mr-3"></i>Cerrar Sesión
    </a>
</div>

<style>

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
</style>