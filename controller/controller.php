<?php
/**
 * Map Navigation Controller
 * OOP implementation for handling button presses and navigation
 */

require_once __DIR__ . '/../class/MapSession.php';
require_once __DIR__ . '/../class/MapController.php';

use NokiaMaps\Session\MapSession;
use NokiaMaps\Navigation\MapController;

// Initialize session
$session = new MapSession();

// Handle navigation request
$controller = new MapController($session);
$controller->handleNavigation();

// Redirect to map view
header('Location: ../index.php');
exit();
