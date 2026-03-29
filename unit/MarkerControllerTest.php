<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Controller\MarkerController;
use NokiaMaps\Session;

class MarkerControllerTest extends TestCase
{
    private Session $session;
    private array $originalPost;
    private array $originalServer;
    private array $originalCookie;

    protected function setUp(): void
    {
        // Store original globals
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        $this->originalCookie = $_COOKIE;

        // Reset session for clean test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_write_close();
        session_start();

        // Initialize session
        $this->session = new Session();

        // Set default coordinates
        $this->session->setCoordinates(52.52, 13.4, 14);
    }

    protected function tearDown(): void
    {
        // Restore original globals
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;
        $_COOKIE = $this->originalCookie;

        // Clean up cookies
        if (isset($_COOKIE['nokiamaps_markers'])) {
            unset($_COOKIE['nokiamaps_markers']);
        }
    }

    /**
     * Helper to test marker addition logic without triggering exit
     */
    private function testMarkerAddition(string $action): void
    {
        $_POST = ['action' => $action];

        // Store marker count before
        $beforeCount = count($this->session->getMarkers());

        // Add marker through session method (simulating controller logic)
        $coords = $this->session->getCoordinates();
        $color = $this->session->getNextMarkerColor();
        $this->session->addMarker($coords['lat'], $coords['lon'], $color);

        // Verify marker was added
        $markers = $this->session->getMarkers();
        $this->assertCount($beforeCount + 1, $markers);
        $this->assertEquals($coords['lat'], $markers[$beforeCount]['lat']);
        $this->assertEquals($coords['lon'], $markers[$beforeCount]['lon']);
        $this->assertEquals($color, $markers[$beforeCount]['color']);
    }

    public function testMarkerColorCycleCorrectly(): void
    {
        $expectedColors = ['FF0000', '0000FF', '00FF00', 'FFFF00', 'FF00FF', 'FF8800'];

        for ($i = 0; $i < 6; $i++) {
            $coords = $this->session->getCoordinates();
            $color = $this->session->getNextMarkerColor();
            $this->session->addMarker($coords['lat'], $coords['lon'], $color);

            $markers = $this->session->getMarkers();
            $this->assertEquals($expectedColors[$i], $markers[$i]['color']);
        }
    }

    public function testMarkerCountIncreasesWithEachAdd(): void
    {
        $this->assertCount(0, $this->session->getMarkers());

        // Add first marker
        $coords = $this->session->getCoordinates();
        $color = $this->session->getNextMarkerColor();
        $this->session->addMarker($coords['lat'], $coords['lon'], $color);
        $this->assertCount(1, $this->session->getMarkers());

        // Add second marker
        $color = $this->session->getNextMarkerColor();
        $this->session->addMarker($coords['lat'] + 0.1, $coords['lon'] + 0.1, $color);
        $this->assertCount(2, $this->session->getMarkers());
    }

    public function testClearMarkersRemovesAllMarkers(): void
    {
        // Add some markers first
        $coords = $this->session->getCoordinates();
        $this->session->addMarker($coords['lat'], $coords['lon'], 'FF0000');
        $this->session->addMarker($coords['lat'] + 0.1, $coords['lon'] + 0.1, '0000FF');

        $this->assertCount(2, $this->session->getMarkers());

        // Clear markers
        $this->session->clearMarkers();

        // Verify all markers cleared
        $this->assertCount(0, $this->session->getMarkers());
    }

    public function testAddMarkerUsesCurrentCoordinates(): void
    {
        // Set custom coordinates
        $this->session->setCoordinates(48.8566, 2.3522, 10);

        $coords = $this->session->getCoordinates();
        $color = $this->session->getNextMarkerColor();
        $this->session->addMarker($coords['lat'], $coords['lon'], $color);

        $markers = $this->session->getMarkers();
        $this->assertEquals(48.8566, $markers[0]['lat']);
        $this->assertEquals(2.3522, $markers[0]['lon']);
    }

    public function testGetNextMarkerColorStartsWithRed(): void
    {
        $color = $this->session->getNextMarkerColor();
        $this->assertEquals('FF0000', $color);
    }

    public function testGetNextMarkerColorRespectsColorArray(): void
    {
        // Test first 6 markers get unique colors from MARKER_COLORS
        $expectedColors = ['FF0000', '0000FF', '00FF00', 'FFFF00', 'FF00FF', 'FF8800'];

        foreach ($expectedColors as $expectedColor) {
            $color = $this->session->getNextMarkerColor();
            $this->assertEquals($expectedColor, $color);

            // Add marker to increment count
            $coords = $this->session->getCoordinates();
            $this->session->addMarker($coords['lat'], $coords['lon'], $color);
        }
    }

    public function testMarkersAreStoredWithCompleteData(): void
    {
        $coords = $this->session->getCoordinates();
        $color = $this->session->getNextMarkerColor();

        $this->session->addMarker($coords['lat'], $coords['lon'], $color);

        $markers = $this->session->getMarkers();

        // Check marker has all required fields
        $this->assertArrayHasKey('id', $markers[0]);
        $this->assertArrayHasKey('lat', $markers[0]);
        $this->assertArrayHasKey('lon', $markers[0]);
        $this->assertArrayHasKey('color', $markers[0]);
        $this->assertArrayHasKey('timestamp', $markers[0]);

        // Verify data types
        $this->assertIsString($markers[0]['id']);
        $this->assertIsFloat($markers[0]['lat']);
        $this->assertIsFloat($markers[0]['lon']);
        $this->assertIsString($markers[0]['color']);
        $this->assertIsInt($markers[0]['timestamp']);
    }
}
