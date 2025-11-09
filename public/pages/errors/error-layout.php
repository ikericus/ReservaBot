<?php
// pages/errors/error-layout.php
// $errorCode, $errorTitle, $errorMessage, $errorIcon deben estar definidos antes de incluir

http_response_code($errorCode);
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title><?php echo $errorCode; ?> - <?php echo $errorTitle; ?> | ReservaBot</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css' rel='stylesheet'>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body class='bg-gray-50 min-h-screen flex flex-col'>
    
    <!-- Header -->
    <div class='gradient-bg'>
        <div class='max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8'>
            <div class='flex items-center space-x-3 text-white'>
                <i class='ri-calendar-check-line text-2xl'></i>
                <h1 class='text-xl font-bold'>ReservaBot</h1>
            </div>
        </div>
    </div>
    
    <!-- Contenido -->
    <div class='flex-1 flex items-center justify-center px-4 py-12'>
        <div class='max-w-2xl w-full text-center fade-in'>
            
            <div class='mb-8 float-animation'>
                <div class='inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-100 to-blue-100 rounded-full'>
                    <i class='<?php echo $errorIcon; ?> text-5xl text-purple-600'></i>
                </div>
            </div>
            
            <h1 class='text-7xl sm:text-8xl font-bold text-gray-800 mb-4'><?php echo $errorCode; ?></h1>
            <h2 class='text-2xl sm:text-3xl font-semibold text-gray-700 mb-4'>
                <?php echo $errorTitle; ?>
            </h2>
            <p class='text-gray-600 mb-8 max-w-md mx-auto'>
                <?php echo $errorMessage; ?>
            </p>
            
            <div class='flex flex-col sm:flex-row gap-4 justify-center items-center'>
                <a href='/' class='btn-primary text-white px-8 py-3 rounded-lg font-medium shadow-lg inline-flex items-center space-x-2'>
                    <i class='ri-home-line'></i>
                    <span>Volver al inicio</span>
                </a>
                <button onclick='history.back()' class='bg-white text-gray-700 px-8 py-3 rounded-lg font-medium shadow-md hover:shadow-lg transition-all border border-gray-300 hover:border-gray-400 inline-flex items-center space-x-2'>
                    <i class='ri-arrow-left-line'></i>
                    <span>Página anterior</span>
                </button>
            </div>
            
            <?php if (!empty($errorLinks)): ?>
            <div class='mt-12 pt-8 border-t border-gray-200'>
                <p class='text-sm text-gray-500 mb-4'>¿Necesitas ayuda? Prueba estos enlaces:</p>
                <div class='flex flex-wrap justify-center gap-6 text-sm'>
                    <?php foreach ($errorLinks as $link): ?>
                        <a href='<?php echo $link['url']; ?>' class='text-purple-600 hover:text-purple-700 font-medium inline-flex items-center space-x-1'>
                            <i class='<?php echo $link['icon']; ?>'></i>
                            <span><?php echo $link['text']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class='py-6 text-center text-sm text-gray-500'>
        <p>© <?php echo date('Y'); ?> ReservaBot - Sistema de gestión de reservas</p>
    </div>
    
</body>
</html>