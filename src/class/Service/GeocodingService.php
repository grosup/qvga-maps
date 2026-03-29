<?php

namespace NokiaMaps\Service;

class GeocodingService
{
    private string $mapboxToken;
    private string $cacheDir;
    private array $cache = [];

    public function __construct(string $mapboxToken)
    {
        $this->mapboxToken = $mapboxToken;
        $this->cacheDir = __DIR__ . '/../cache/geocoding';
        $this->ensureCacheDirectoryExists();
    }

    /**
     * Geocode a query using Mapbox Geocoding API
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

        if ($response === null || empty($response['features'])) {
            return [];
        }

        // Process results
        $results = $this->processResults($response);

        // Cache the results
        $this->saveToCache($query, $results);

        return array_slice($results, 0, $limit);
    }

    /**
     * Fetch results from Mapbox Geocoding API
     *
     * @param string $query The search query
     * @return array|null API response or null on failure
     */
    private function fetchFromApi(string $query): ?array
    {
        if (empty($this->mapboxToken)) {
            error_log('Mapbox token not provided or is empty');
            return null;
        }

        $url = sprintf(
            'https://api.mapbox.com/geocoding/v5/mapbox.places/%s.json?access_token=%s&limit=5',
            urlencode($query),
            $this->mapboxToken,
        );

        $context = stream_context_create([
            'http' => [
                'header' => ['User-Agent: QvgaMap/1.0 Nokia225App', 'Accept: application/json'],
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log('Failed to fetch from Mapbox Geocoding API');
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Failed to parse JSON response from Mapbox API: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }

    /**
     * Process raw API response into normalized format
     *
     * @param array $apiResponse Raw response from Mapbox API
     * @return array Processed results
     */
    private function processResults(array $apiResponse): array
    {
        if (!isset($apiResponse['features']) || !is_array($apiResponse['features'])) {
            return [];
        }

        $results = [];

        foreach ($apiResponse['features'] as $feature) {
            $result = $this->processFeature($feature);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Process a single feature into normalized format
     *
     * @param array $feature Raw feature from Mapbox API
     * @return array|null Normalized feature or null if invalid
     */
    private function processFeature(array $feature): ?array
    {
        if (!isset($feature['place_name']) || !isset($feature['center'])) {
            return null;
        }

        $center = $feature['center'];

        if (!is_array($center) || count($center) !== 2) {
            return null;
        }

        $lon = (string) $center[0];
        $lat = (string) $center[1];

        // Determine type from place_type array or use default
        $type = 'unknown';
        if (!empty($feature['place_type']) && is_array($feature['place_type'])) {
            $type = $feature['place_type'][0] ?? 'unknown';
        }

        return [
            'display_name' => (string) $feature['place_name'],
            'lat' => $lat,
            'lon' => $lon,
            'type' => $type,
        ];
    }

    /**
     * Get cached results if available and fresh
     *
     * @param string $query The search query
     * @return array|null Cached results or null
     */
    private function getCached(string $query): ?array
    {
        $cacheKey = $this->getCacheKey($query);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';

        if (!file_exists($cacheFile)) {
            return null;
        }

        // Check if cache is fresh (< 1 hour)
        if (filemtime($cacheFile) < time() - 3600) {
            return null;
        }

        $data = file_get_contents($cacheFile);

        if ($data === false) {
            return null;
        }

        $decoded = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded['results'] ?? null;
    }

    /**
     * Save results to cache
     *
     * @param string $query The search query
     * @param array $results Results to cache
     * @return bool Success
     */
    private function saveToCache(string $query, array $results): bool
    {
        $cacheKey = $this->getCacheKey($query);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';

        $data = [
            'query' => $query,
            'results' => $results,
            'timestamp' => time(),
        ];

        $json = json_encode($data);

        if ($json === false) {
            return false;
        }

        return file_put_contents($cacheFile, $json) !== false;
    }

    /**
     * Generate cache key for a query
     *
     * @param string $query The search query
     * @return string Cache key
     */
    private function getCacheKey(string $query): string
    {
        return md5(strtolower(trim($query)));
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
}
