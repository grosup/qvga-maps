<?php
/**
 * Autoloader for NokiaMaps classes
 * Automatically loads classes from the class/ directory based on namespace
 * Works on both Windows and Linux production servers
 */

spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'NokiaMaps\\';

    // Base directory for classes
    $base_dir = __DIR__ . '/class/';

    // Check if the class uses our namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Not our namespace, move to next autoloader
    }

    // Get the relative class name (remove prefix)
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators
    // and append with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});
