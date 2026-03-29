<?php

namespace NokiaMaps\Service;

/**
 * Abstract base class for geocoding services
 * Provides common caching functionality
 */
abstract class AbstractGeocodingService
{
    protected string $cacheDir;
    protected array $cache = [];

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache/geocoding';
        $this->ensureCacheDirectoryExists();
    }

    /**
     * Geocode a query - must be implemented by concrete services
     *
     * @param string $query The address or place name to search for
     * @param int $limit Maximum number of results
     * @return array Array of geocoding results
     */
    abstract public function geocode(string $query, int $limit = 5): array;

    /**
     * Get cached results if available and fresh
     *
     * @param string $query The search query
     * @return array|null Cached results or null
     */
    protected function getCached(string $query): ?array
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
    protected function saveToCache(string $query, array $results): bool
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
    protected function getCacheKey(string $query): string
    {
        return md5(strtolower(trim($query)));
    }

    /**
     * Ensure cache directory exists
     */
    protected function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
}
