<?php
/**
 * Search Controller
 * Handles search requests and prepares data for the view template
 */

namespace NokiaMaps\Controller;

use NokiaMaps\Session;
use NokiaMaps\Service\GeocodingServiceFactory;
use NokiaMaps\Service\AbstractGeocodingService;

class SearchPageController
{
    private Session $session;
    private AbstractGeocodingService $geocodingService;
    private GeocodingServiceFactory $geocodingServiceFactory;

    public function __construct(Session $session, string $mapboxToken = '')
    {
        $this->session = $session;
        $this->geocodingServiceFactory = new GeocodingServiceFactory($mapboxToken);
    }

    /**
     * Handle the search request
     * Prepares data and includes the view template
     */
    public function handle(): void
    {
        // Initialize default values
        $address = '';
        $results = [];
        $error = '';
        $errorMessage = '';

        // Capture address from either POST or GET
        if (isset($_POST['address'])) {
            $address = trim($_POST['address']);
        } elseif (isset($_GET['address'])) {
            $address = trim($_GET['address']);
        }

        // Get API preference from POST (user selection) or session (remembered choice)
        $apiPreference = $_POST['geocoding_api'] ?? $this->session->getGeocodingApi();

        // Save the preference to session if it came from POST
        if (isset($_POST['geocoding_api'])) {
            $this->session->setGeocodingApi($apiPreference);
        }

        // Create geocoding service using the factory
        $this->geocodingService = $this->geocodingServiceFactory->create($apiPreference);

        // If user selected a result, set location and redirect to map
        if (isset($_GET['select']) && is_numeric($_GET['select']) && !empty($address)) {
            $this->handleResultSelection($address, (int) $_GET['select']);
            return;
        }

        // Perform the search if address provided
        if (!empty($address) && !isset($_GET['select'])) {
            $this->performSearch($address, $results, $error);
        }

        // Prepare error message HTML if needed
        if (!empty($error)) {
            $errorMessage = $this->prepareErrorMessage($error);
        }

        // Prepare data for the view
        $viewData = [
            'errorMessage' => $errorMessage,
            'address' => $address,
            'results' => $results,
            'hasResults' => !empty($results),
            'hasSearched' => isset($_POST['address']) || isset($_GET['address']),
        ];

        // Extract variables to be available in the included view
        extract($viewData);

        // Include the view template
        include __DIR__ . '/../../view/search_results.php';
    }

    /**
     * Handle when user selects a search result
     */
    private function handleResultSelection(string $address, int $selectIndex): void
    {
        $searchResults = $this->geocodingService->geocode($address, 5);

        if (!empty($searchResults) && isset($searchResults[$selectIndex])) {
            $selected = $searchResults[$selectIndex];
            $_SESSION['lat'] = $selected['lat'];
            $_SESSION['lon'] = $selected['lon'];

            // Redirect to map
            header('Location: ../index.php');
            exit();
        } else {
            // Redirect back to search with error
            $_SESSION['search_error'] = 'Invalid selection';
            header('Location: ../controller/search.php?address=' . urlencode($address));
            exit();
        }
    }

    /**
     * Perform geocoding search
     */
    private function performSearch(string $address, array &$results, string &$error): void
    {
        $results = $this->geocodingService->geocode($address, 5);

        // Store search results in session for selection
        $_SESSION['search_results'] = $results;
        $_SESSION['search_address'] = $address;

        if (empty($results)) {
            $error = 'No results found for "' . htmlspecialchars($address) . '"';
        }
    }

    /**
     * Prepare error message HTML
     */
    private function prepareErrorMessage(string $error): string
    {
        return '<div style="color:red;background:#ffe6e6;border:1px solid red;padding:5px;font-size:10px;">' .
            htmlspecialchars($error) .
            '</div>';
    }
}
