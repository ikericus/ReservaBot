<?php
// debug-whatsapp-connect.php
// Script de diagnóstico para identificar problemas

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico WhatsApp Connect</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: #059669; }
        .error { color: #dc2626; }
        .warning { color: #f59e0b; }
        h2 { margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de WhatsApp Connect</h1>
    
    <div class="section">
        <h2>1. Verificación de Funciones Requeridas</h2>
        <?php
        $requiredFunctions = ['getAuthenticatedUser', 'getContainer'];
        foreach ($requiredFunctions as $func) {
            if (function_exists($func)) {
                echo "<div class='success'>✓ $func() existe</div>";
            } else {
                echo "<div class='error'>✗ $func() NO existe</div>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>2. Prueba de Autenticación</h2>
        <?php
        if (function_exists('getAuthenticatedUser')) {
            try {
                $user = getAuthenticatedUser();
                if ($user) {
                    echo "<div class='success'>✓ Usuario autenticado</div>";
                    echo "<pre>" . print_r($user, true) . "</pre>";
                } else {
                    echo "<div class='warning'>⚠ No hay usuario autenticado</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>✗ getAuthenticatedUser() no disponible</div>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Prueba de Container</h2>
        <?php
        if (function_exists('getContainer')) {
            try {
                $container = getContainer();
                if ($container) {
                    echo "<div class='success'>✓ Container obtenido</div>";
                    echo "<div>Clase: " . get_class($container) . "</div>";
                    
                    // Verificar método getWhatsAppDomain
                    if (method_exists($container, 'getWhatsAppDomain')) {
                        echo "<div class='success'>✓ Método getWhatsAppDomain() existe</div>";
                        
                        try {
                            $whatsappDomain = $container->getWhatsAppDomain();
                            if ($whatsappDomain) {
                                echo "<div class='success'>✓ WhatsApp Domain obtenido</div>";
                                echo "<div>Clase: " . get_class($whatsappDomain) . "</div>";
                                
                                // Verificar método conectarWhatsApp
                                if (method_exists($whatsappDomain, 'conectarWhatsApp')) {
                                    echo "<div class='success'>✓ Método conectarWhatsApp() existe</div>";
                                } else {
                                    echo "<div class='error'>✗ Método conectarWhatsApp() NO existe</div>";
                                }
                            } else {
                                echo "<div class='error'>✗ getWhatsAppDomain() devolvió null</div>";
                            }
                        } catch (Exception $e) {
                            echo "<div class='error'>✗ Error obteniendo WhatsApp Domain: " . $e->getMessage() . "</div>";
                        }
                    } else {
                        echo "<div class='error'>✗ Método getWhatsAppDomain() NO existe</div>";
                    }
                } else {
                    echo "<div class='error'>✗ getContainer() devolvió null</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>✗ getContainer() no disponible</div>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Simulación de Request</h2>
        <p>Simula una llamada al endpoint con un usuario de prueba</p>
        <button onclick="testEndpoint()">Probar Endpoint</button>
        <div id="result" style="margin-top: 10px;"></div>
    </div>
    
    <div class="section">
        <h2>5. Información del Servidor</h2>
        <div>PHP Version: <code><?php echo PHP_VERSION; ?></code></div>
        <div>Server Software: <code><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></code></div>
        <div>Document Root: <code><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></code></div>
        <div>Current File: <code><?php echo __FILE__; ?></code></div>
    </div>
    
    <div class="section">
        <h2>6. Verificación de Headers</h2>
        <?php
        if (headers_sent($file, $line)) {
            echo "<div class='warning'>⚠ Headers ya enviados en $file línea $line</div>";
        } else {
            echo "<div class='success'>✓ Headers no enviados todavía</div>";
        }
        ?>
    </div>
    
    <script>
        async function testEndpoint() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div style="color: #3b82f6;">Probando...</div>';
            
            try {
                const response = await fetch('/api/whatsapp-connect', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const contentType = response.headers.get('content-type');
                const status = response.status;
                
                let result = `<div>Status: <code>${status}</code></div>`;
                result += `<div>Content-Type: <code>${contentType}</code></div>`;
                
                if (contentType && contentType.includes('application/json')) {
                    try {
                        const data = await response.json();
                        result += '<div style="color: #059669;">✓ Respuesta JSON válida</div>';
                        result += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } catch (e) {
                        result += '<div style="color: #dc2626;">✗ Error parseando JSON: ' + e.message + '</div>';
                    }
                } else {
                    const text = await response.text();
                    result += '<div style="color: #dc2626;">✗ Respuesta NO es JSON</div>';
                    result += '<pre>' + text + '</pre>';
                }
                
                resultDiv.innerHTML = result;
            } catch (error) {
                resultDiv.innerHTML = '<div style="color: #dc2626;">✗ Error: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>