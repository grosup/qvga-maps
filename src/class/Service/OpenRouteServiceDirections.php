<?php

namespace NokiaMaps\Service;

use Exception;

/**
 * OpenRouteService Directions Implementation
 * Provides routing via OpenRouteService API
 */
class OpenRouteServiceDirections extends AbstractDirectionsService
{
    private string $apiKey;

    public function __construct(string $apiKey = '')
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(
        float $fromLat,
        float $fromLon,
        float $toLat,
        float $toLon,
        string $profile = 'driving',
        array $options = [],
    ): array {
        // Map profile to ORS profile (from plan documentation)
        $orsProfile = match ($profile) {
            'driving' => 'driving-car',
            'walking' => 'foot-walking',
            'cycling' => 'cycling-regular',
            'transit' => 'public-transport',
            default => 'driving-car',
        };

        // Check cache
        $cacheKey = $this->getCacheKey(
            $fromLat,
            $fromLon,
            $toLat,
            $toLon,
            $profile,
            $options['departure_time'] ?? null,
        );

        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Construct API URL
        $uri = "https://api.openrouteservice.org/v2/directions/{$orsProfile}";

        // Prepare body
        $body = [
            'coordinates' => [[$fromLon, $fromLat], [$toLon, $toLat]],
            'instructions' => true,
            'geometry' => 'false', // Text instructions only
        ];

        // Add optional departure time
        if (isset($options['departure_time'])) {
            $body['departure'] = $options['departure_time'];
        }

        // Make API call
        $result = $this->fetchRoute($uri, $body);

        // Normalize response
        $route = $this->normalizeResponse($result);

        // Cache the route
        $this->saveToCache($cacheKey, $route);

        return $route;
    }

    /**
     * Fetch route from API
     * @param string $uri
     * @param array $body
     * @return array|null
     */
    private function fetchRoute(string $uri, array $body): ?array
    {
        // Prepare request
        $jsonBody = json_encode($body);

        // Headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: QvgaMaps/1.0',
        ];

        // Add API key if provided
        if (!empty($this->apiKey)) {
            $headers[] = "Authorization: Bearer {$this->apiKey}";
        }

        // Create stream context - include ignore_errors to capture response body on HTTP errors
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $jsonBody,
                'timeout' => 10,
                'ignore_errors' => true, // This allows us to get the response body even on HTTP errors
            ],
        ]);

        // Make request
        $response = @file_get_contents($uri, false, $context);

        if ($response === false) {
            // Even when file_get_contents fails, we can get the response headers
            if (isset($http_response_header) && !empty($http_response_header)) {
                $statusLine = $http_response_header[0] ?? '';
                error_log(
                    "Failed to fetch route from OpenRouteService API. HTTP Status: {$statusLine}",
                );

                // Try to extract and log the response body if available
                foreach ($http_response_header as $header) {
                    if (
                        stripos($header, 'content-type:') !== false &&
                        stripos($header, 'application/json') !== false
                    ) {
                        // Response might be JSON with error details
                        error_log(
                            'OpenRouteService API response headers: ' .
                                json_encode($http_response_header),
                        );
                        break;
                    }
                }
            } else {
                error_log('Failed to fetch route from OpenRouteService API (no response headers)');
            }
            return null;
        }

        // Parse JSON
        $data = json_decode($response, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log('Failed to parse JSON from OpenRouteService API: ' . json_last_error_msg());
            return null;
        }

        // Check for API error in response
        if (isset($data['error'])) {
            $errorMessage = is_array($data['error'])
                ? $data['error']['message'] ?? json_encode($data['error'])
                : $data['error'];
            error_log("OpenRouteService API returned error: {$errorMessage}");
            return null;
        }

        return $data;
    }

    /**
     * Normalize ORS response to common format
     * @param array|null $response
     * @return array
     */
    private function normalizeResponse(?array $response): array
    {
        if ($response === null) {
            return [
                'error' => 'Unable to calculate route',
                'summary' => ['distance' => 0, 'duration' => 0],
                'steps' => [],
            ];
        }

        // Check for ORS errors
        if (isset($response['error'])) {
            error_log('OpenRouteService API error: ' . json_encode($response));
            return [
                'error' => $response['error']['message'] ?? 'API error occurred',
                'summary' => ['distance' => 0, 'duration' => 0],
                'steps' => [],
            ];
        }

        // Extract routes
        $routes = $response['routes'] ?? [];
        if (empty($routes)) {
            return [
                'error' => 'No routes found',
                'summary' => ['distance' => 0, 'duration' => 0],
                'steps' => [],
            ];
        }

        // Use first route
        $route = $routes[0];

        // Build summary
        $summary = [
            'distance' => $route['summary']['distance'] ?? 0,
            'duration' => $route['summary']['duration'] ?? 0,
        ];

        // Extract steps from segments
        $steps = [];
        if (isset($route['segments'])) {
            $stepNumber = 1;
            foreach ($route['segments'] as $segment) {
                if (isset($segment['steps'])) {
                    foreach ($segment['steps'] as $step) {
                        $steps[] = [
                            'instruction' => $step['instruction'] ?? 'Continue',
                            'name' => $step['name'] ?? '',
                            'distance' => $step['distance'] ?? 0,
                            'duration' => $step['duration'] ?? 0,
                            'type' => $step['type'] ?? '',
                            'step_number' => $stepNumber++,
                        ];
                    }
                }
            }
        }

        return [
            'summary' => $summary,
            'steps' => $steps,
            'copyrights' => $response['metadata']['attribution'] ?? '',
        ];
    }
}
