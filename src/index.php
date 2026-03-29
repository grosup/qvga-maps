<?php
/**
 * Map Application Entry Point
 * Uses MVC pattern: Controller prepares data, View template handles presentation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoloader.php';

use NokiaMaps\Session;
use NokiaMaps\Renderer;
use NokiaMaps\Controller\MapPageController;

// Initialize session
$session = new Session();

// Initialize renderer using token from config
$renderer = new Renderer($session, MAPBOX_TOKEN);

// Use controller to handle the request and render the page
$controller = new MapPageController($session, $renderer);
$controller->render();
