<?php
/**
 * Map Image Renderer
 * Generates and outputs map images via Mapbox API
 */

namespace NokiaMaps;

use NokiaMaps\Session;
use NokiaMaps\Service\ReverseGeocodingService;

class Renderer
{
    private Session $session;
    private string $mapboxToken;
    private ReverseGeocodingService $reverseGeocoder;

    public function __construct(Session $session, string $mapboxToken = '')
    {
        $this->session = $session;
        $this->mapboxToken = $mapboxToken;
        $this->reverseGeocoder = new ReverseGeocodingService($mapboxToken);
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
            $token,
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
            if (
                strpos($imageData, '<!DOCTYPE') !== false ||
                strpos($imageData, '<html') !== false
            ) {
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
        return $this->reverseGeocoder->reverseGeocode($lat, $lon);
    }

    /**
     * Get cached geocode result if available and fresh
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|null Cached data or null if not available/expired
     */

    /**
     * Fetch geocode data from Mapbox Geocoding API
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|null API response data or null on failure
     */
}
