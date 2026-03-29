<?php
/**
 * Search Endpoint
 * Simple wrapper that instantiates SearchPageController and handles the request
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../autoloader.php';

use NokiaMaps\Controller\SearchPageController;
use NokiaMaps\Session;

session_start();

// Create session and controller
$session = new Session();
$controller = new SearchPageController($session, MAPBOX_TOKEN);
$controller->handle();
