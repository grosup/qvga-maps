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

          // Capture address from either POST or GET BEFORE mode-specific handling
          if (isset($_POST['address'])) {
              $address = trim($_POST['address']);
          } elseif (isset($_GET['address'])) {
              $address = trim($_GET['address']);
          }

          // Check if navigate checkbox was checked on map page
          if (isset($_POST['navigate']) && $_POST['navigate'] === '1') {
              $mode = 'destination';
          } else {
              $mode = $_GET['mode'] ?? null; // 'destination' or 'origin'
          }

          // Capture profile when in origin mode
          $profile = $_SESSION['nav_profile'] ?? 'driving';
          if ($mode === 'origin' && isset($_GET['profile'])) {
              $profile = $_GET['profile'];
              $_SESSION['nav_profile'] = $profile;
          }

          // Special handling for mode='origin' without address - show search form
          if ($mode === 'origin' && empty($address)) {
              $this->showOriginForm();
              return;
          }
          $pageTitle = 'Search Results';

          // Get API preference from session (no longer from POST since settings page handles that)
          $apiPreference = $this->session->getGeocodingApi();

          // Create geocoding service using the factory
          $this->geocodingService = $this->geocodingServiceFactory->create($apiPreference);

          // Handle result selection with mode awareness
          if (isset($_GET['select']) && is_numeric($_GET['select']) && !empty($address)) {
              $this->handleModeBasedSelection($address, (int) $_GET['select'], $mode);
              return;
          }

          // Set page title based on mode
          if ($mode === 'destination') {
              $pageTitle = 'Where are you traveling to?';
          } elseif ($mode === 'origin') {
              $pageTitle = 'Where are you traveling from?';
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
              'mode' => $mode,
              'pageTitle' => $pageTitle,
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
       * Handle result selection based on mode (NAV flow or normal)
       */
      private function handleModeBasedSelection(
          string $address,
          int $selectIndex,
          ?string $mode,
      ): void {
          // Use stored profile from session for routes (set when mode=origin)
          $profile = $_SESSION['nav_profile'] ?? 'driving';
          $searchResults = $this->geocodingService->geocode($address, 5);

          if (!empty($searchResults) && isset($searchResults[$selectIndex])) {
              $selected = $searchResults[$selectIndex];

              // Normal search flow (no mode) - set location and go to map
              if ($mode === null) {
                  $_SESSION['lat'] = $selected['lat'];
                  $_SESSION['lon'] = $selected['lon'];
                  header('Location: ../index.php');
                  exit();
              }

              // NAV flow - handle based on mode
              if ($mode === 'destination') {
                  // Store destination and go to origin selection
                  $_SESSION['nav_destination'] = $selected;
                  header('Location: ../controller/search.php?mode=origin');
                  exit();
              } elseif ($mode === 'origin') {
                  // Get stored destination and go to route calculation
                  $destination = $_SESSION['nav_destination'] ?? null;
                  if ($destination === null) {
                      $_SESSION['search_error'] = 'No destination selected';
                      header('Location: ../controller/search.php?mode=destination');
                      exit();
                  }

                  // Redirect to route controller with origin and destination
                  $origin = $selected;
                  header(
                      'Location: ../controller/route.php?origin=' .
                      urlencode($origin['display_name']) .
                      '&destination=' .
                      urlencode($destination['display_name']) .
                      '&profile=' . $profile,
                  );
                  exit();
              }
          } else {
              // Redirect back to search with error
              $_SESSION['search_error'] = 'Invalid selection';
              $url = '../controller/search.php?address=' . urlencode($address);
              if ($mode) {
                  $url .= '&mode=' . $mode;
              }
              header('Location: ' . $url);
              exit();
          }
      }

      /**
       * Show origin search form when mode='origin' but no address provided yet
       */
      private function showOriginForm(): void
      {
          $destination = $_SESSION['nav_destination'] ?? null;
          if ($destination === null) {
              // No destination - redirect back to destination selection
              header('Location: ../controller/search.php?mode=destination');
              exit();
          }

          // Get profile from session
          $profile = $_SESSION['nav_profile'] ?? 'driving';

          // Include the origin search form view
          $viewData = [
              'destination' => $destination,
              'profile' => $profile,
          ];
          extract($viewData);
          include __DIR__ . '/../../view/search_origin.php';
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