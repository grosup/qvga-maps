<?php
/**
 * Map Navigation Controller
 * Handles button press actions and navigation logic
 */

namespace NokiaMaps\Navigation;

use NokiaMaps\Session\MapSession;

class MapController {
 private $session;

 public function __construct(MapSession $session) {
  $this->session = $session;
 }

 public function handleNavigation(): void {
  // Apply navigation based on pressed button
  $this->applyNavigation();
 }

 private function applyNavigation(): void {
  if (isset($_POST['left']) || isset($_POST['left_x']) || isset($_POST['left.x'])) {
   $this->session->moveLeft();
  } elseif (isset($_POST['right']) || isset($_POST['right_x']) || isset($_POST['right.x'])) {
   $this->session->moveRight();
  } elseif (isset($_POST['up']) || isset($_POST['up_x']) || isset($_POST['up.x'])) {
   $this->session->moveUp();
  } elseif (isset($_POST['down']) || isset($_POST['down_x']) || isset($_POST['down.x'])) {
   $this->session->moveDown();
  } elseif (isset($_POST['zoom_in'])) {
   $this->session->zoomIn();
  } elseif (isset($_POST['zoom_out'])) {
   $this->session->zoomOut();
  }
 }
}
