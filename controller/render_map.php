<?php
/**
 * Map image renderer - OOP implementation
 * Outputs PNG image directly using MapRenderer
 */

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../class/MapSession.php';
require_once __DIR__ . '/../class/MapRenderer.php';

use NokiaMaps\Session\MapSession;
use NokiaMaps\Renderer\MapRenderer;

// Initialize session
$session = new MapSession();

// Create renderer using token from config
$renderer = new MapRenderer($session, MAPBOX_TOKEN);

// Output map image
$renderer->renderImage();
