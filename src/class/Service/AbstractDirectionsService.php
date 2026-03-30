<?php

namespace NokiaMaps\Service;

/**
 * Abstract base class for directions services
 * Provides common caching functionality
 */
abstract class AbstractDirectionsService
{
    protected string $cacheDir;
    protected array $cache = [];

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache/directions';
        $this->ensureCacheDirectoryExists();
    }

    /**
     * Get route between two points - must be implemented by concrete services
     *
     * @param float $fromLat Starting latitude
     * @param float $fromLon Starting longitude
     * @param float $toLat Destination latitude
     * @param float $toLon Destination longitude
     * @param string $profile Route profile (e.g., 'driving', 'walking', 'cycling', 'transit')
     * @param array $options Additional options (departure_time, etc.)
     * @return array Route data including steps, distance, duration
     */
    abstract public function getRoute(
        float $fromLat,
        float $fromLon,
        float $toLat,
        float $toLon,
        string $profile = 'driving',
        array $options = [],
    ): array;

    /**
     * Get cached route if available and fresh
     *
     * @param string $key Cache key for this route
     * @return array|null Cached route or null
     */
    protected function getCached(string $key): ?array
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json';

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

        return $decoded['route'] ?? null;
    }

    /**
     * Save route to cache
     *
     * @param string $key Cache key
     * @param array $route Route data to cache
     * @return bool Success
     */
    protected function saveToCache(string $key, array $route): bool
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json';

        $data = [
            'route' => $route,
            'timestamp' => time(),
        ];

        $json = json_encode($data);
        if ($json === false) {
            return false;
        }

        return file_put_contents($cacheFile, $json) !== false;
    }

    /**
     * Generate cache key for a route
     *
     * @param float $fromLat Starting latitude
     * @param float $fromLon Starting longitude
     * @param float $toLat Destination latitude
     * @param float $toLon Destination longitude
     * @param string $profile Route profile
     * @param int|null $departureTime Unix timestamp (optional)
     * @return string Cache key
     */
    protected function getCacheKey(
        float $fromLat,
        float $fromLon,
        float $toLat,
        float $toLon,
        string $profile,
        ?int $departureTime = null,
    ): string {
        $parts = [$fromLat, $fromLon, $toLat, $toLon, $profile];

        if ($departureTime !== null) {
            $parts[] = $departureTime;
        }

        return md5(implode('|', $parts));
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

    /**
     * Generate route summary from steps
     *
     * @param array $steps Step array
     * @return array Summary with distance and duration
     */
    protected function generateSummary(array $steps): array
    {
        $totalDistance = 0;
        $totalDuration = 0;

        foreach ($steps as $step) {
            $totalDistance += $step['distance'] ?? 0;
            $totalDuration += $step['duration'] ?? 0;
        }

        return [
            'distance' => $totalDistance,
            'duration' => (int) $totalDuration,
        ];
    }
}
