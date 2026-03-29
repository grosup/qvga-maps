<?php
/**
 * Style Endpoint
 * Simple wrapper that instantiates StyleController and handles the request
 */

require_once __DIR__ . '/../autoloader.php';

use NokiaMaps\Session;
use NokiaMaps\Controller\StyleController;

session_start();

// Create controller and handle request
$session = new Session();
$controller = new StyleController($session);
$controller->handle();
