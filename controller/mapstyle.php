<?php
/**
 * Map Style Controller
 * Handles map style selection and updates session
 */

session_start();

require_once __DIR__ . '/../class/MapSession.php';

use NokiaMaps\Session\MapSession;

// Initialize session
$session = new MapSession();

// Get selected map style from GET or POST
$style = $_GET['map_style'] ?? $_POST['map_style'] ?? '';

// Validate and set style
if (!empty($style)) {
    // Only allow specific mapbox styles
    $allowedStyles = ['streets-v12', 'outdoors-v12', 'satellite-v9'];
    if (in_array($style, $allowedStyles)) {
        $session->setMapStyle($style);
    }
}

// Redirect back to map
header('Location: ../index.php');
exit();
