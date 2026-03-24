<?php
/**
 * Map Interface - Object-Oriented Implementation
 */

require_once 'config.php';
require_once 'class/MapSession.php';
require_once 'class/MapRenderer.php';
require_once 'class/MapView.php';

use NokiaMaps\Session\MapSession;
use NokiaMaps\Renderer\MapRenderer;
use NokiaMaps\View\MapView;

// Initialize session
$session = new MapSession();

// Initialize renderer using token from config
$renderer = new MapRenderer($session, MAPBOX_TOKEN);

// Render the map interface
$view = new MapView($session, $renderer);
$view->render();
