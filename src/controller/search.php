<?php
/**
 * Search Endpoint
 * Simple wrapper that instantiates SearchPageController and handles the request
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../autoloader.php';

use NokiaMaps\Controller\SearchPageController;

session_start();

// Create controller and handle request
$controller = new SearchPageController(MAPBOX_TOKEN);
$controller->handle();
