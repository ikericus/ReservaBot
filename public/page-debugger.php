<?php
/**
 * Ejemplo de cómo integrar el debug en clientes.php
 * Agregar al INICIO del archivo clientes.php
 */

// 1. Incluir el sistema de debug
require_once __DIR__ . '/page-debugger.php'; // Ajustar ruta según sea necesario

// 2. Inicializar el debugger
PageDebugger::init('CLIENTES PAGE');

// 3. Verificar dependencias
page_log("🔍 Verificando dependencias...");
page_check_file(__DIR__ . '/includes/db-config.php', 'DB Config');
page_check_file(__DIR__ . '/includes/auth.php', 'Auth System');

// 4. Verificar middleware (si aplica)
page_log("🛡️ Verificando middleware...");
page_check_var('currentUser', $currentUser ?? null);
page_check_var('csrfToken', $csrfToken ?? null);

if (isset($currentUser)) {
    page_log("👤 Usuario autenticado: " . $currentUser['email'], 'SUCCESS');
} else {
    page_log("❌ Usuario NO disponible", 'ERROR');
}

// 5. Verificar base de datos
page_log("🗄️ Verificando conexión BD...");
if (isset($pdo)) {
    page_log("✅ Variable \$pdo disponible", 'SUCCESS');
    try {
        $stmt = $pdo->query("SELECT 1");
        page_log("✅ Conexión BD activa", 'SUCCESS');
    } catch (Exception $e) {
        page_log("❌ Error en BD: " . $e->getMessage(), 'ERROR');
    }
} else {
    page_log("❌ Variable \$pdo NO disponible", 'ERROR');
}

// 6. Checkpoint antes de incluir el middleware original
page_checkpoint('Antes del middleware original');

// AQUÍ va tu código original de clientes.php...
// Por ejemplo:

try {
    page_log("📋 Incluyendo middleware auth...");
    require_once 'middleware/auth-middleware.php';
    page_log("✅ Middleware incluido correctamente", 'SUCCESS');
} catch (Exception $e) {
    page_log("❌ Error en middleware: " . $e->getMessage(), 'ERROR');
}

page_checkpoint('Después del middleware');

// 7. Resto del código de clientes.php
page_log("🏗️ Iniciando lógica de la página...");

try {
    // Tu código de clientes aquí
    page_log("📄 Configurando página...");
    
    // Ejemplo de tus variables
    $currentPage = 'clientes';
    $pageTitle = 'ReservaBot - Clientes';
    page_log("📋 Variables de página configuradas");
    
    // Obtener datos (ejemplo)
    page_log("🔍 Obteniendo datos de clientes...");
    $userId = $currentUser['id'] ?? 0;
    page_log("👤 ID de usuario: $userId");
    
    // Aquí iría tu query de clientes
    page_checkpoint('Antes de query de clientes');
    
    // ... resto de tu código original ...
    
    page_checkpoint('Antes de incluir header');
    
    // Incluir header
    page_log("📄 Incluyendo header...");
    include 'includes/header.php';
    page_log("✅ Header incluido");
    
    page_checkpoint('Después de header');
    
} catch (Exception $e) {
    page_log("💥 Error en lógica principal: " . $e->getMessage(), 'ERROR');
    throw $e;
}

// El resto de tu HTML y código PHP va aquí...
// El panel de debug se mostrará automáticamente al final

?>

<!-- Tu HTML original continúa aquí -->
<div class="container">
    <!-- Contenido de clientes.php -->
    
    <?php 
    // Puedes agregar logs en cualquier parte del HTML también
    page_log("🎨 Renderizando contenido HTML");
    ?>
    
    <h1>Clientes</h1>
    
    <?php 
    page_checkpoint('Renderizado completado');
    // El panel se mostrará automáticamente al final
    ?>
</div>