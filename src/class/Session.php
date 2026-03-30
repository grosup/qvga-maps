<?php
/**
 * Map Session Manager
 * Manages map coordinates and zoom level in session
 */

namespace NokiaMaps;

class Session
{
    private const DEFAULT_LAT = 52.52; // Berlin
    private const DEFAULT_LON = 13.4;
    private const DEFAULT_ZOOM = 14;
    private const MIN_ZOOM = 1;
    private const MAX_ZOOM = 22;
    private const DEFAULT_GEOCODING_API = 'mapbox'; // Default geocoding service
    private const DEFAULT_DIRECTIONS_PROVIDER = 'mapbox'; // Default directions provider (was openrouteservice)
    private const COOKIE_EXPIRE = 86400 * 30; // 30 days

    private array $session;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->initialize();
        $this->session = &$_SESSION;
    }

    private function initialize(): void
    {
        if (!isset($_SESSION['lat'])) {
            $_SESSION['lat'] = self::DEFAULT_LAT;
            $_SESSION['lon'] = self::DEFAULT_LON;
            $_SESSION['zoom'] = self::DEFAULT_ZOOM;
            $_SESSION['map_style'] = 'streets-v12'; // Default Mapbox style
            $_SESSION['geocoding_api'] = self::DEFAULT_GEOCODING_API; // Default geocoding service
            $_SESSION['directions_provider'] = self::DEFAULT_DIRECTIONS_PROVIDER; // Default directions provider
            $_SESSION['markers'] = []; // Initialize empty markers array
        } else {
            if (!isset($_SESSION['markers'])) {
                $_SESSION['markers'] = [];
            }
            if (!isset($_SESSION['directions_provider'])) {
                $_SESSION['directions_provider'] = self::DEFAULT_DIRECTIONS_PROVIDER;
            }
        }
    }

    /**
     * Get preferred geocoding API (mapbox or nominatim)
     *
     * @return string API preference
     */
    public function getGeocodingApi(): string
    {
        // Prefer session value, then cookie, then default
        if (isset($this->session['geocoding_api'])) {
            return $this->session['geocoding_api'];
        }
        return $this->getCookie('geocoding_api', self::DEFAULT_GEOCODING_API);
    }

    /**
     * Set preferred geocoding API
     *
     * @param string $api 'mapbox' or 'nominatim'
     * @return void
     */
    public function setGeocodingApi(string $api): void
    {
        if (in_array($api, ['mapbox', 'nominatim'])) {
            $this->session['geocoding_api'] = $api;
            $this->setCookie('geocoding_api', $api);
        }
    }

    /**
     * Get directions provider preference
     *
     * @return string Provider preference
     */
    public function getDirectionsProvider(): string
    {
        // Prefer session value, then cookie, then default
        if (isset($this->session['directions_provider'])) {
            return $this->session['directions_provider'];
        }
        return $this->getCookie('directions_provider', self::DEFAULT_DIRECTIONS_PROVIDER);
    }

    /**
     * Set directions provider preference
     *
     * @param string $provider 'openrouteservice', 'mapbox'
     * @return void
     */
    public function setDirectionsProvider(string $provider): void
    {
        $this->session['directions_provider'] = $provider;
        $this->setCookie('directions_provider', $provider);
    }

    public function getMapStyle(): string
    {
        return isset($this->session['map_style']) ? $this->session['map_style'] : 'streets-v12';
    }

    public function setMapStyle(string $style): void
    {
        $this->session['map_style'] = $style;
    }

    public function getCoordinates(): array
    {
        return [
            'lat' => $this->session['lat'],
            'lon' => $this->session['lon'],
            'zoom' => $this->session['zoom'],
        ];
    }

    public function setCoordinates(float $lat, float $lon, int $zoom): void
    {
        $this->session['lat'] = $lat;
        $this->session['lon'] = $lon;
        $this->session['zoom'] = $zoom;
    }

    public function moveBy(float $deltaLat, float $deltaLon): void
    {
        $this->session['lat'] += $deltaLat;
        $this->session['lon'] += $deltaLon;
    }

    public function setZoom(int $zoom): void
    {
        $this->session['zoom'] = max(self::MIN_ZOOM, min(self::MAX_ZOOM, $zoom));
    }

    public function moveLeft(): void
    {
        $movement = $this->calculateMovement();
        $this->moveBy(0, -$movement);
    }

    public function moveRight(): void
    {
        $movement = $this->calculateMovement();
        $this->moveBy(0, $movement);
    }

    public function moveUp(): void
    {
        $movement = $this->calculateMovement();
        $this->moveBy($movement * 0.7, 0);
    }

    public function moveDown(): void
    {
        $movement = $this->calculateMovement();
        $this->moveBy(-($movement * 0.7), 0);
    }

    public function zoomIn(): void
    {
        $this->setZoom($this->session['zoom'] + 1);
    }

    public function zoomOut(): void
    {
        $this->setZoom($this->session['zoom'] - 1);
    }

    private function calculateMovement(): float
    {
        // Zoom-dependent movement coefficients
        // Zoom 14 = baseline at 0.0022 (ok speed)
        // Lower zoom = faster (bigger steps), higher zoom = slower (tiny steps)
        // ULTRA EXTREME: Lower zoom much higher, higher zoom much lower
        $coefficients = [
            1 => 2.2,
            2 => 1.5,
            3 => 1.19,
            4 => 0.96,
            5 => 0.52,
            6 => 0.2,
            7 => 0.07,
            8 => 0.1,
            9 => 0.09,
            10 => 0.054,
            11 => 0.031,
            12 => 0.02,
            13 => 0.01,
            14 => 0.004,
            15 => 0.002,
            16 => 0.001,
            17 => 0.0005,
            18 => 0.00025,
            19 => 0.00008,
            20 => 0.00004,
            21 => 0.000025,
            22 => 0.000008,
        ];

        $currentZoom = $this->session['zoom'];
        return $coefficients[$currentZoom] ?? 0.24;
    }

    /**
     * Get all markers stored in session
     * @return array Array of marker objects ['id', 'lat', 'lon', 'color', 'timestamp']
     */
    public function getMarkers(): array
    {
        return $this->session['markers'] ?? [];
    }

    /**
     * Add a marker at the specified coordinates
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param string $color Marker color in hex format (without #)
     * @return void
     */
    public function addMarker(float $lat, float $lon, string $color): void
    {
        $markers = $this->session['markers'] ?? [];

        $markers[] = [
            'id' => uniqid('marker_', true),
            'lat' => $lat,
            'lon' => $lon,
            'color' => $color,
            'timestamp' => time(),
        ];

        $this->session['markers'] = $markers;
    }

    /**
     * Clear all markers from session
     * @return void
     */
    public function clearMarkers(): void
    {
        $this->session['markers'] = [];
    }

    /**
     * Get the next marker color based on current marker count
     * Cycles through MARKER_COLORS array
     * @return string Color code in hex format (without #)
     */
    public function getNextMarkerColor(): string
    {
        $markers = $this->session['markers'] ?? [];
        $count = count($markers);

        if (!defined('MARKER_COLORS') || !is_array(MARKER_COLORS)) {
            return 'FF0000'; // Fallback red
        }

        $colorIndex = $count % count(MARKER_COLORS);
        return MARKER_COLORS[$colorIndex] ?? 'FF0000';
    }

    /**
     * Get cookie value with fallback to default
     *
     * @param string $name Cookie name
     * @param string $default Default value if cookie not found
     * @return string Cookie value or default
     */
    private function getCookie(string $name, string $default = ''): string
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Set cookie value
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @return void
     */
    private function setCookie(string $name, string $value): void
    {
        setcookie($name, $value, time() + self::COOKIE_EXPIRE, '/', '', false, true);
    }
}
