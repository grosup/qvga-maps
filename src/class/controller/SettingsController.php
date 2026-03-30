<?php
/**
 * Settings Controller
 * Handles settings page display and saving API preferences
 */

namespace NokiaMaps\Controller;

use NokiaMaps\Session;

class SettingsController
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Handle the settings request
     * Shows settings form and handles save
     */
    public function handle(): void
    {
        // Handle POST (save settings)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
            $this->saveSettings();
            // Redirect back to map
            header('Location: ../index.php');
            exit();
        }

        // GET - show settings form
        $this->showSettings();
    }

    /**
     * Save settings from POST
     */
    private function saveSettings(): void
    {
        // Save geocoding API preference
        if (
            isset($_POST['geocoding_api']) &&
            in_array($_POST['geocoding_api'], ['mapbox', 'nominatim'])
        ) {
            $this->session->setGeocodingApi($_POST['geocoding_api']);
        }

        // Save directions provider preference
        if (
            isset($_POST['directions_provider']) &&
            in_array($_POST['directions_provider'], ['mapbox', 'openrouteservice'])
        ) {
            $this->session->setDirectionsProvider($_POST['directions_provider']);
        }
    }

    /**
     * Show settings form
     */
    private function showSettings(): void
    {
        // Get current preferences
        $geocodingApi = $this->session->getGeocodingApi();
        $directionsProvider = $this->session->getDirectionsProvider();

        // Prepare data for view
        $viewData = [
            'geocodingApi' => $geocodingApi,
            'directionsProvider' => $directionsProvider,
        ];

        // Extract variables for view
        extract($viewData);

        // Include view template
        include __DIR__ . '/../../view/settings.php';
    }
}
