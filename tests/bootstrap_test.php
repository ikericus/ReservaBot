<?php
// tests/bootstrap_simple.php

define('PROJECT_ROOT', dirname(__DIR__) . '/public');

// Autoload
spl_autoload_register(function ($class) {
    $prefix = 'ReservaBot\\';
    $base_dir = PROJECT_ROOT . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    $filename = array_pop($parts);
    $path = strtolower(implode('/', $parts));
    
    $file = $base_dir . ($path ? $path . '/' : '') . $filename . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});