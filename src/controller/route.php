<?php
/**
 * Route Endpoint
 * Simple wrapper that instantiates RouteController and handles the request
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../autoloader.php';

use NokiaMaps\Controller\RouteController;
use NokiaMaps\Session;

session_start();

// Create session and controller (pass both Mapbox token and OpenRouteService token)
$session = new Session();
$controller = new RouteController($session, MAPBOX_TOKEN, OPENROUTESERVICE_TOKEN);
$controller->handle();
