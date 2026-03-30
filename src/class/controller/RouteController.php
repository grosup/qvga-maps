<?php
/**
 * Route Controller
 * Handles route/directions requests and prepares data for the view template
 */

namespace NokiaMaps\Controller;

use NokiaMaps\Session;
use NokiaMaps\Service\AbstractDirectionsService;
use NokiaMaps\Service\DirectionsServiceFactory;
use NokiaMaps\Service\GeocodingServiceFactory;
use NokiaMaps\Service\GeocoderHelper;

class RouteController
{
    private Session $session;
    private AbstractDirectionsService $directionsService;
    private DirectionsServiceFactory $directionsFactory;
    private GeocoderHelper $geocoderHelper;

    public function __construct(
        Session $session,
        string $mapboxToken = '',
        string $openRouteToken = '',
    ) {
        $this->session = $session;
        $this->directionsFactory = new DirectionsServiceFactory($mapboxToken, $openRouteToken);
        $geocodingFactory = new GeocodingServiceFactory($mapboxToken);
        $this->geocoderHelper = new GeocoderHelper($geocodingFactory);
    }

    /**
     * Handle the route request
     * Prepares data and includes the view template
     */
    public function handle(): void
    {
        // Initialize defaults
        $originText = '';
        $destinationText = '';
        $profile = 'driving';
        $route = null;
        $errorMessage = '';
        $hasSearched = false;

        // Capture and validate inputs
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $originText = trim($_POST['origin'] ?? '');
            $destinationText = trim($_POST['destination'] ?? '');
            $profile = $_POST['profile'] ?? 'driving';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $originText = trim($_GET['origin'] ?? '');
            $destinationText = trim($_GET['destination'] ?? '');
            $profile = $_GET['profile'] ?? 'driving';
        }

        // Get directions provider preference
        $provider = $_POST['directions_provider'] ?? $this->session->getDirectionsProvider();
        if (isset($_POST['directions_provider'])) {
            $this->session->setDirectionsProvider($provider);
        }
        $this->directionsService = $this->directionsFactory->create($provider);

        // Perform route calculation if both locations provided
        if (!empty($originText) && !empty($destinationText)) {
            $hasSearched = true;
            $this->performRoute($originText, $destinationText, $profile, $route, $errorMessage);
        }

        // Prepare data for the view
        $viewData = [
            'errorMessage' => $errorMessage,
            'originText' => $originText,
            'destinationText' => $destinationText,
            'profile' => $profile,
            'route' => $route,
            'hasSearched' => $hasSearched,
            'hasResult' => $route !== null && !isset($route['error']),
        ];

        // Extract variables to be available in the included view
        extract($viewData);

        // Include the view template
        include __DIR__ . '/../../view/route_results.php';
    }

    /**
     * Perform routing calculation
     */
    private function performRoute(
        string $originText,
        string $destinationText,
        string $profile,
        ?array &$route,
        string &$errorMessage,
    ): void {
        // Parse and geocode origin
        $origin = $this->geocoderHelper->parseAndGeocode(
            $originText,
            $this->session->getGeocodingApi(),
        );
        if ($origin === null) {
            $errorMessage = 'Could not find origin location';
            return;
        }

        // Parse and geocode destination
        $destination = $this->geocoderHelper->parseAndGeocode(
            $destinationText,
            $this->session->getGeocodingApi(),
        );
        if ($destination === null) {
            $errorMessage = 'Could not find destination location';
            return;
        }

        try {
            // Get route from directions service
            $route = $this->directionsService->getRoute(
                $origin['lat'],
                $origin['lon'],
                $destination['lat'],
                $destination['lon'],
                $profile,
            );

            // Check for errors in response
            if (isset($route['error'])) {
                $errorMessage = $route['error'];
                $route = null;
            }
        } catch (Exception $e) {
            error_log('Route calculation error: ' . $e->getMessage());
            $errorMessage = 'Unable to calculate route. Please try again.';
            $route = null;
        }
    }
}
