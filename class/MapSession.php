<?php
/**
 * Map Session Manager
 * Manages map coordinates and zoom level in session
 */

namespace NokiaMaps\Session;

class MapSession {
    private const DEFAULT_LAT = 52.52; // Berlin
    private const DEFAULT_LON = 13.40;
    private const DEFAULT_ZOOM = 14;
    private const MIN_ZOOM = 1;
    private const MAX_ZOOM = 22;

    private array $session;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->initialize();
        $this->session = &$_SESSION;
    }

    private function initialize(): void {
        if (!isset($_SESSION['lat'])) {
            $_SESSION['lat'] = self::DEFAULT_LAT;
            $_SESSION['lon'] = self::DEFAULT_LON;
            $_SESSION['zoom'] = self::DEFAULT_ZOOM;
            $_SESSION['map_style'] = 'streets-v12'; // Default Mapbox style
        }
    }

    public function getMapStyle(): string {
        return isset($this->session['map_style']) ? $this->session['map_style'] : 'streets-v12';
    }

    public function setMapStyle(string $style): void {
        $this->session['map_style'] = $style;
    }

    public function getCoordinates(): array {
        return [
            'lat' => $this->session['lat'],
            'lon' => $this->session['lon'],
            'zoom' => $this->session['zoom']
        ];
    }

    public function setCoordinates(float $lat, float $lon, int $zoom): void {
        $this->session['lat'] = $lat;
        $this->session['lon'] = $lon;
        $this->session['zoom'] = $zoom;
    }

    public function moveBy(float $deltaLat, float $deltaLon): void {
        $this->session['lat'] += $deltaLat;
        $this->session['lon'] += $deltaLon;
    }

    public function setZoom(int $zoom): void {
        $this->session['zoom'] = max(self::MIN_ZOOM, min(self::MAX_ZOOM, $zoom));
    }

    public function moveLeft(): void {
        $movement = $this->calculateMovement();
        $this->moveBy(0, -$movement);
    }

    public function moveRight(): void {
        $movement = $this->calculateMovement();
        $this->moveBy(0, $movement);
    }

    public function moveUp(): void {
        $movement = $this->calculateMovement();
        $this->moveBy($movement * 0.7000000, 0);
    }

    public function moveDown(): void {
        $movement = $this->calculateMovement();
        $this->moveBy(-$movement * 0.7000000, 0);
    }

    public function zoomIn(): void {
        $this->setZoom($this->session['zoom'] + 1);
    }

    public function zoomOut(): void {
        $this->setZoom($this->session['zoom'] - 1);
    }

    private function calculateMovement(): float {
        // Zoom-dependent movement coefficients
        // Zoom 14 = baseline at 0.0022 (ok speed)
        // Lower zoom = faster (bigger steps), higher zoom = slower (tiny steps)
        // ULTRA EXTREME: Lower zoom much higher, higher zoom much lower
        $coefficients = [
            1 => 2.2000, 2 => 1.5000, 3 => 1.1900, 4 => 0.9600, 5 => 0.5200,
            6 => 0.2000, 7 => 0.0700, 8 => 0.1000, 9 => 0.0900, 10 => 0.0540,
            11 => 0.0310, 12 => 0.0200, 13 => 0.0100, 14 => 0.0040, 15 => 0.00200,
            16 => 0.00100, 17 => 0.00050, 18 => 0.00025, 19 => 0.00008, 20 => 0.00004,
            21 => 0.000025, 22 => 0.000008
        ];

        $currentZoom = $this->session['zoom'];
        return $coefficients[$currentZoom] ?? 0.240;
    }
}
