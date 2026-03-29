<?php
/**
 * Map Navigation Controller
 * OOP implementation for handling button presses and navigation
 */

require_once __DIR__ . '/../autoloader.php';

use NokiaMaps\Session;
use NokiaMaps\Controller\NavigationController;

// Initialize session
$session = new Session();

// Handle navigation request
$controller = new NavigationController($session);
$controller->handleNavigation();

// Redirect to map view
header('Location: ../index.php');
exit();
