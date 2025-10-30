<?php
// api/whatsapp-debug.php

header('Content-Type: application/json');

// Solo admins pueden acceder
$user = getAuthenticatedUser();
if (!$user || !esAdmin($user['id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = (int)($_GET['userId'] ?? 0);

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    
    switch ($action) {
        case 'report':
            if (!$userId) {
                throw new \InvalidArgumentException('userId requerido');
            }
            
            $report = $whatsappDomain->generarReporteDebug($userId);
            
            echo json_encode([
                'success' => true,
                'report' => $report
            ]);
            break;
            
        case 'sync-check':
            if (!$userId) {
                throw new \InvalidArgumentException('userId requerido');
            }
            
            $sync = $whatsappDomain->verificarSincronizacion($userId);
            
            echo json_encode([
                'success' => true,
                'sync' => $sync
            ]);
            break;
            
        case 'health':
            $serverManager = getContainer()->getWhatsAppServerManager();
            $health = $serverManager->verificarSalud();
            
            echo json_encode([
                'success' => true,
                'health' => $health
            ]);
            break;
            
        default:
            throw new \InvalidArgumentException('Acción no válida');
    }
    
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}