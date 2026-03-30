<?php
/**
 * Settings Endpoint
 * Simple wrapper that instantiates SettingsController and handles the request
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../autoloader.php';

use NokiaMaps\Controller\SettingsController;
use NokiaMaps\Session;

session_start();

// Create session and controller
$session = new Session();
$controller = new SettingsController($session);
$controller->handle();
