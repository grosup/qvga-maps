<?php
/**
 * Map View Renderer
 * Generates HTML for the map interface
 */

namespace NokiaMaps\View;

use NokiaMaps\Session\MapSession;
use NokiaMaps\Renderer\MapRenderer;

class MapView {
 private $session;
 private $renderer;

 public function __construct(MapSession $session, MapRenderer $renderer) {
  $this->session = $session;
  $this->renderer = $renderer;
 }

 public function render(): void {
  $coords = $this->session->getCoordinates();

  echo $this->renderHeader();
  echo $this->renderMap();
  echo $this->renderControls();
  echo $this->renderSearch();
  echo $this->renderCoordinates($coords);
  echo $this->renderFooter();
 }

 private function renderHeader(): string {
  $coords = $this->session->getCoordinates();
  $currentStyle = $this->session->getMapStyle();

  // Map style to readable name
  $styleNames = [
   'streets-v12' => 'Streets',
   'outdoors-v12' => 'Outdoors',
   'satellite-v9' => 'Satellite'
  ];
  $styleName = $styleNames[$currentStyle] ?? 'Map';

  // Try to get human-readable location name via reverse geocoding
  $locationName = $this->renderer->reverseGeocode($coords['lat'], $coords['lon']);

  if (!empty($locationName)) {
   $title = sprintf('%s - Lat: %.2f, Lon: %.2f (%s)',
    htmlspecialchars($locationName),
    $coords['lat'],
    $coords['lon'],
    $styleName
   );
  } else {
   // Fallback to coordinates if geocoding fails
   $title = sprintf('Map - Lat: %.2f, Lon: %.2f, Zoom: %d (%s)',
    $coords['lat'],
    $coords['lon'],
    $coords['zoom'],
    $styleName
   );
  }

  return '<!DOCTYPE html>
<html>
<head>
 <title>' . $title . '</title>
 <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; font-size: 11px; max-width:320px; margin:auto;">';
 }

 private function renderMap(): string {
  return '<img src="controller/render_map.php" alt="MAP" style="width:310px; height:250px; border:1px solid #333; display:block; margin:0;">';
 }

 private function renderControls(): string {
  $buttonStyle = $this->getButtonStyle();

  $html = '<form method="POST" action="controller/controller.php" style="margin:0px; height:30px; padding:0px; background:#f0f0f0; text-align:center;">';

  // Navigation buttons with GIF icons
  $html .= $this->renderButton('left', '<', $buttonStyle);
  $html .= $this->renderButton('right', '>', $buttonStyle);
  $html .= $this->renderButton('up', '/\\', $buttonStyle);
  $html .= $this->renderButton('down', '\\/', $buttonStyle);

  // Zoom buttons (keep text for now as user only mentioned 4 navigation icons)
  $html .= $this->renderButton('zoom_in', '+', $buttonStyle);
  $html .= $this->renderButton('zoom_out', '-', $buttonStyle);

  $html .= '</form>';

  return $html;
 }

private function renderSearch(): string {
  $html = '<form method="POST" action="controller/search.php" style="margin:0; padding:0px; background:#e8f4f8;">';
  $html .= '<input type="text" name="address" placeholder="Street, City" style="width:200px; padding:5px; font-size:11px; border: 1px solid #ccc;">';
  $html .= '<input type="submit" value="SEARCH" style="padding:7px 5px 3px 5px; font-size:11px; background:#007bff; color: white; border: 1px solid #0056b3;">';
  $html .= '</form>';

  // Map style links
  $currentStyle = $this->session->getMapStyle();
  $html .= '<div style="margin:0; padding:3px 5px; background:#f0f0f0; font-size:9px;">';

  $styles = ['streets-v12' => 'Streets', 'outdoors-v12' => 'Outdoors', 'satellite-v9' => 'Satellite'];

  $first = true;
  foreach ($styles as $value => $label) {
   if (!$first) {
    $html .= ' | ';
   }
   $first = false;

   if ($currentStyle === $value) {
    // Active style - styled as plain text (different color)
    $html .= '<span style="color:#333; font-weight:bold;">' . htmlspecialchars($label) . '</span>';
   } else {
    // Inactive style - styled as clickable link
    $html .= '<a href="controller/mapstyle.php?map_style=' . urlencode($value) . '" style="color:#007bff; text-decoration:none;">' . htmlspecialchars($label) . '</a>';
   }
  }

  $html .= '</div>';

  return $html;
}

 private function renderCoordinates(array $coords): string {
  return '<p style="margin:5px 0; font-size:10px; text-align: center; color: #666;">
  Lat: ' . htmlspecialchars($coords['lat']) . ', Lon: ' . htmlspecialchars($coords['lon']) . ', Zoom: ' . htmlspecialchars($coords['zoom']) . '
</p>';
 }

 private function renderFooter(): string {
  return '</body></html>';
 }

 private function renderButton(string $name, string $label, string $style): string {
  return '<input type="submit" name="' . $name . '" value="' . $label . '" style="' . $style . '">';
 }

 private function getButtonStyle(): string {
  return 'display: inline-block; width:30px; height:30px; padding:0px; margin:0px 1px; border: 1px solid #ccc; background-color:#fff;';
 }
}
