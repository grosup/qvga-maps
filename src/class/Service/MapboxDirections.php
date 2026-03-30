<?php

namespace NokiaMaps\Service;

/**
 * Mapbox Directions Implementation
 * Provides routing via Mapbox Directions API
 */
class MapboxDirections extends AbstractDirectionsService
{
    private string $mapboxToken;

    public function __construct(string $mapboxToken = '')
    {
        parent::__construct();
        $this->mapboxToken = $mapboxToken;
    }

    /**
     * Get route between two points
     *
     * @param float $fromLat Starting latitude
     * @param float $fromLon Starting longitude
     * @param float $toLat Destination latitude
     * @param float $toLon Destination longitude
     * @param string $profile Route profile (driving, walking, cycling, transit)
     * @param array $options Additional options
     * @return array Route data
     */
    public function getRoute(
        float $fromLat,
        float $fromLon,
        float $toLat,
        float $toLon,
        string $profile = 'driving',
        array $options = [],
    ): array {
        // Map profile to Mapbox profile
        $mapboxProfile = match ($profile) {
            'driving' => 'driving',
            'walking' => 'walking',
            'cycling' => 'cycling',
            'transit' => 'driving-traffic', // Mapbox doesn't have pure transit, use driving-traffic
            default => 'driving',
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

        // Build coordinates string for Mapbox (lon,lat format)
        $coordinates = "{$fromLon},{$fromLat};{$toLon},{$toLat}";

        // Construct API URL
        $uri = "https://api.mapbox.com/directions/v5/mapbox/{$mapboxProfile}/{$coordinates}";

        // Add parameters
        $params = [
            'access_token' => $this->mapboxToken,
            'geometries' => 'geojson',
            'overview' => 'simplified',
            'steps' => 'true',
            'alternatives' => 'false',
        ];

        $url = $uri . '?' . http_build_query($params);

        // Make request
        $result = $this->fetchRoute($url);

        // Normalize response
        $route = $this->normalizeResponse($result);

        // Cache the route
        $this->saveToCache($cacheKey, $route);

        return $route;
    }

    /**
     * Fetch route from API
     * @param string $url
     * @return array|null
     */
    private function fetchRoute(string $url): ?array
    {
        $headers = ['User-Agent: QvgaMaps/1.0', 'Accept: application/json'];

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            if (isset($http_response_header) && !empty($http_response_header)) {
                $statusLine = $http_response_header[0] ?? '';
                error_log(
                    "Failed to fetch route from Mapbox Directions API. HTTP Status: {$statusLine}",
                );

                foreach ($http_response_header as $header) {
                    if (
                        stripos($header, 'content-type:') !== false &&
                        stripos($header, 'application/json') !== false
                    ) {
                        error_log(
                            'Mapbox Directions API response headers: ' .
                                json_encode($http_response_header),
                        );
                        break;
                    }
                }
            } else {
                error_log('Failed to fetch route from Mapbox Directions API (no response headers)');
            }
            return null;
        }

        // Parse JSON
        $data = json_decode($response, true);

        if ($data === null) {
            error_log('Failed to parse JSON from Mapbox Directions API: ' . json_last_error_msg());
            return null;
        }

        // Check for API error
        if (isset($data['message'])) {
            error_log("Mapbox Directions API error: {$data['message']}");
            return null;
        }

        if (isset($data['code'])) {
            $errorMessage = $data['message'] ?? $data['code'];
            error_log(
                "Mapbox Directions API returned error code '{$data['code']}': {$errorMessage}",
            );
            return null;
        }

        return $data;
    }

    /**
     * Normalize Mapbox response to common format
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

        if (isset($response['routes']) && !empty($response['routes'])) {
            $route = $response['routes'][0];
            $leg = $route['legs'][0] ?? [];

            $summary = [
                'distance' => $leg['distance'] ?? 0,
                'duration' => $leg['duration'] ?? 0,
            ];

            $steps = [];
            $stepNumber = 1;

            if (isset($leg['steps']) && is_array($leg['steps'])) {
                foreach ($leg['steps'] as $step) {
                    $steps[] = [
                        'instruction' => $step['maneuver']['instruction'] ?? 'Continue',
                        'name' => $step['name'] ?? '',
                        'distance' => $step['distance'] ?? 0,
                        'duration' => $step['duration'] ?? 0,
                        'type' => $step['maneuver']['type'] ?? '',
                        'step_number' => $stepNumber++,
                    ];
                }
            }

            return [
                'summary' => $summary,
                'steps' => $steps,
                'copyrights' => 'Mapbox',
            ];
        }

        return [
            'error' => 'No routes available',
            'summary' => ['distance' => 0, 'duration' => 0],
            'steps' => [],
        ];
    }
}
