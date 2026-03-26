<?php
// PHPUnit Bootstrap file for Nokia Maps unit tests

// Register autoloader for classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'NokiaMaps\\';
    $base_dir = __DIR__ . '/../src/class/';

    // Check if the class uses the prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separator with directory separator
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Alternatively, manually require all class files
$classFiles = [
    __DIR__ . '/../src/class/MapSession.php',
    __DIR__ . '/../src/class/MapController.php',
    __DIR__ . '/../src/class/MapView.php',
    __DIR__ . '/../src/class/MapRenderer.php',
    __DIR__ . '/../src/class/GeocodingService.php',
];

foreach ($classFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}
