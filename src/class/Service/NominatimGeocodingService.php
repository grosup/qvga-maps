<?php

namespace NokiaMaps\Service;

/**
 * Nominatim (OpenStreetMap) Geocoding Service
 * Concrete implementation using OSM Nominatim API (free, no API key required)
 */
class NominatimGeocodingService extends AbstractGeocodingService
{
    /**
     * Geocode a query using Nominatim API
     *
     * @param string $query The address or place name to search for
     * @param int $limit Maximum number of results (default: 5, max: 5)
     * @return array Array of geocoding results in normalized format
     */
    public function geocode(string $query, int $limit = 5): array
    {
        $query = trim($query);

        if (empty($query)) {
            return [];
        }

        // Clamp limit between 1 and 5
        $limit = max(1, min(5, $limit));

        // Check cache first
        $cached = $this->getCached($query);
        if ($cached !== null) {
            return array_slice($cached, 0, $limit);
        }

        // Fetch from API
        $response = $this->fetchFromApi($query);

        if ($response === null || empty($response)) {
            return [];
        }

        // Process results
        $results = $this->processResults($response);

        // Cache the results
        $this->saveToCache($query, $results);

        return array_slice($results, 0, $limit);
    }

    /**
     * Fetch results from Nominatim API
     *
     * @param string $query The search query
     * @return array|null API response or null on failure
     */
    private function fetchFromApi(string $query): ?array
    {
        // Nominatim API URL (free, no API key required)
        // Parameters: q=query, format=jsonv2, limit=5, addressdetails=1
        $url = sprintf(
            'https://nominatim.openstreetmap.org/search?q=%s&format=jsonv2&limit=5&addressdetails=1',
            urlencode($query),
        );

        $context = stream_context_create([
            'http' => [
                'header' => [
                    'User-Agent: QvgaMap/1.0 Nokia225App (map.grosup.eu)',
                    'Accept: application/json',
                ],
                'timeout' => 10,
            ],
        ]);

        // Nominatim requires User-Agent header for requests
        // Without it, requests may be blocked
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log('Failed to fetch from Nominatim API');
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Failed to parse JSON response from Nominatim API: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }

    /**
     * Process raw Nominatim API response into normalized format
     *
     * @param array $apiResponse Raw response from Nominatim API
     * @return array Processed results
     */
    private function processResults(array $apiResponse): array
    {
        if (!is_array($apiResponse) || empty($apiResponse)) {
            return [];
        }

        $results = [];

        foreach ($apiResponse as $result) {
            $processed = $this->processResult($result);
            if ($processed !== null) {
                $results[] = $processed;
            }
        }

        return $results;
    }

    /**
     * Process a single Nominatim result into normalized format
     *
     * @param array $result Raw result from Nominatim API
     * @return array|null Normalized result or null if invalid
     */
    private function processResult(array $result): ?array
    {
        if (!isset($result['display_name']) || !isset($result['lat']) || !isset($result['lon'])) {
            return null;
        }

        // Determine type from OSM type or class
        $type = 'unknown';
        if (!empty($result['osm_type'])) {
            $type = $result['osm_type']; // 'node', 'way', or 'relation'
        } elseif (!empty($result['class'])) {
            $type = $result['class']; // e.g., 'highway', 'place', 'building'
        }

        return [
            'display_name' => (string) $result['display_name'],
            'lat' => (string) $result['lat'],
            'lon' => (string) $result['lon'],
            'type' => $type,
        ];
    }
}
