<?php
/**
 * Map Image Renderer
 * Generates and outputs map images via Mapbox API
 */

namespace NokiaMaps\Renderer;

use NokiaMaps\Session\MapSession;

class MapRenderer
{
    private MapSession $session;
    private string $mapboxToken;

    public function __construct(MapSession $session, string $mapboxToken = '')
    {
        $this->session = $session;
        $this->mapboxToken = $mapboxToken;
    }

    /**
     * Render map image and output directly to browser
     */
    public function renderImage(): void
    {
        $coords = $this->session->getCoordinates();

        // Get map data
        $mapFile = $this->generateMap($coords['lat'], $coords['lon'], $coords['zoom']);

        // Output image headers
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        readfile($mapFile);
        exit();
    }

    /**
     * Generate map and return file path
     */
    private function generateMap(float $lat, float $lon, int $zoom): string
    {
        $token = $this->mapboxToken;

        if (empty($token)) {
            // Fallback placeholder image
            return $this->generatePlaceholder();
        }

        // Get map style from session
        $style = $this->session->getMapStyle();

        // Default to streets if style is empty
        if (empty($style)) {
            $style = 'streets-v12';
        }

        // Dimensions optimized for Nokia 225 (320x240)
        $width = 310;
        $height = 250;
        $scale = 1;

        // Build Mapbox Static Images API URL
        $url = sprintf(
            'https://api.mapbox.com/styles/v1/mapbox/%s/static/%s,%s,%s,0/%dx%d?access_token=%s',
            $style,
            $lon,
            $lat,
            $zoom,
            $width,
            $height,
            $token
        );

        $cacheDir = __DIR__ . '/../cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheKey = md5($url);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.png';

        // Use cached version if available (1 hour)
        if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 3600) {
            return $cacheFile;
        }

        // Fetch from API
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $imageData !== false) {
            // Check if response is HTML (error)
            if (strpos($imageData, '<!DOCTYPE') !== false || strpos($imageData, '<html') !== false) {
                // Error response
                error_log('Mapbox API error response');
                return $this->generatePlaceholder();
            }

            file_put_contents($cacheFile, $imageData);
            return $cacheFile;
        }

        // Error handling
        error_log("Mapbox API call failed with code: $httpCode");
        return $this->generatePlaceholder();
    }

    /**
     * Generate a simple placeholder image when API is unavailable
     */
    private function generatePlaceholder(): string
    {
        $cacheDir = __DIR__ . '/../cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $placeholderFile = $cacheDir . '/placeholder.png';

        // Simple placeholder grid
        $img = imagecreatetruecolor(320, 200);
        $bg = imagecolorallocate($img, 240, 240, 240);
        $grid = imagecolorallocate($img, 200, 200, 200);

        imagefilledrectangle($img, 0, 0, 320, 200, $bg);

        // Draw grid
        for ($x = 0; $x < 320; $x += 20) {
            imageline($img, $x, 0, $x, 200, $grid);
        }
        for ($y = 0; $y < 200; $y += 20) {
            imageline($img, 0, $y, 320, $y, $grid);
        }

        imagepng($img, $placeholderFile);
        imagedestroy($img);

        return $placeholderFile;
    }

    /**
     * Reverse geocode coordinates to get human-readable location name
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return string Human-readable location name or empty string on failure
     */
    public function reverseGeocode(float $lat, float $lon): string
    {
        if (empty($this->mapboxToken)) {
            return '';
        }

        $cached = $this->getCachedGeocode($lat, $lon);
        if ($cached !== null) {
            return $cached['place_name'] ?? '';
        }

        $apiResult = $this->fetchGeocodeFromApi($lat, $lon);
        if ($apiResult === null) {
            return '';
        }

        return $apiResult['place_name'] ?? '';
    }

    /**
     * Get cached geocode result if available and fresh
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|null Cached data or null if not available/expired
     */
    private function getCachedGeocode(float $lat, float $lon): ?array
    {
        $cacheDir = __DIR__ . '/../cache/geocode';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheKey = md5("{$lon}_{$lat}");
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';

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

        return $decoded;
    }

    /**
     * Fetch geocode data from Mapbox Geocoding API
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|null API response data or null on failure
     */
    private function fetchGeocodeFromApi(float $lat, float $lon): ?array
    {
        $url = sprintf(
            'https://api.mapbox.com/geocoding/v5/mapbox.places/%s,%s.json?access_token=%s&limit=1',
            $lon,
            $lat,
            $this->mapboxToken
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Shorter timeout for geocoding

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            error_log("Mapbox Geocoding API call failed with code: $httpCode");
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Failed to parse Mapbox Geocoding API response');
            return null;
        }

        if (empty($data['features'][0])) {
            error_log('No features found in geocoding response');
            return null;
        }

        $placeName = $data['features'][0]['place_name'] ?? '';

        // Cache the result
        $cacheDir = __DIR__ . '/../cache/geocode';
        $cacheKey = md5("{$lon}_{$lat}");
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';

        $cacheData = [
            'place_name' => $placeName,
            'timestamp' => time()
        ];

        file_put_contents($cacheFile, json_encode($cacheData));

        return $cacheData;
    }
}
