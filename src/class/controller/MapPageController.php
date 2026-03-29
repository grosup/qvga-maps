<?php
/**
 * Map Page Controller
 * Handles the main map page request and prepares data for the view
 */

namespace NokiaMaps\Controller;

use NokiaMaps\Session;
use NokiaMaps\Renderer;

class MapPageController
{
    private Session $session;
    private Renderer $renderer;

    public function __construct(Session $session, Renderer $renderer)
    {
        $this->session = $session;
        $this->renderer = $renderer;
    }

    /**
     * Handle the map page request
     * Prepares data and includes the view template
     */
    public function render(): void
    {
        $coords = $this->session->getCoordinates();
        $currentStyle = $this->session->getMapStyle();

        // Map style to readable name
        $styleNames = [
            'streets-v12' => 'Streets',
            'outdoors-v12' => 'Outdoors',
            'satellite-v9' => 'Satellite',
        ];
        $styleName = $styleNames[$currentStyle] ?? 'Map';

        // Try to get human-readable location name via reverse geocoding
        $locationName = $this->renderer->reverseGeocode($coords['lat'], $coords['lon']);

        // Determine title based on whether we have a location name
        if (!empty($locationName)) {
            $title = sprintf(
                '%s - Lat: %.2f, Lon: %.2f (%s)',
                htmlspecialchars($locationName),
                $coords['lat'],
                $coords['lon'],
                $styleName,
            );
        } else {
            // Fallback to coordinates if geocoding fails
            $title = sprintf(
                'Map - Lat: %.2f, Lon: %.2f, Zoom: %d (%s)',
                $coords['lat'],
                $coords['lon'],
                $coords['zoom'],
                $styleName,
            );
        }

        // Prepare data for the view
        $viewData = [
            'title' => $title,
            'coords' => $coords,
            'currentStyle' => $currentStyle,
            'styleNames' => $styleNames,
        ];

        // Extract variables to be available in the included view
        extract($viewData);

        // Include the view template
        include __DIR__ . '/../../view/map_page.php';
    }
}
