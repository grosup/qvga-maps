<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Session;

class MapSessionTest extends TestCase
{
    private Session $session;
    private array $originalSession;

    protected function setUp(): void
    {
        // Store original session data
        $this->originalSession = $_SESSION ?? [];

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Reset session for clean test
        $_SESSION = [];
        session_write_close();
        session_start();
    }

    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = $this->originalSession;
    }

    public function testConstructorInitializesDefaultCoordinates(): void
    {
        $session = new Session();
        $coords = $session->getCoordinates();

        $this->assertEquals(52.52, $coords['lat']);
        $this->assertEquals(13.4, $coords['lon']);
        $this->assertEquals(14, $coords['zoom']);
    }

    public function testSetAndGetCoordinates(): void
    {
        $session = new Session();
        $session->setCoordinates(48.8566, 2.3522, 10);

        $coords = $session->getCoordinates();
        $this->assertEquals(48.8566, $coords['lat']);
        $this->assertEquals(2.3522, $coords['lon']);
        $this->assertEquals(10, $coords['zoom']);
    }

    public function testMoveLeft(): void
    {
        $session = new Session();
        $session->setCoordinates(52.52, 13.4, 14);

        $session->moveLeft();
        $coords = $session->getCoordinates();

        // At zoom 14, movement should be -0.004 (from coefficients)
        $this->assertEquals(52.52, $coords['lat']);
        $this->assertEquals(13.396, $coords['lon']);
    }

    public function testMoveRight(): void
    {
        $session = new Session();
        $session->setCoordinates(52.52, 13.4, 14);

        $session->moveRight();
        $coords = $session->getCoordinates();

        // At zoom 14, movement should be +0.004
        $this->assertEquals(52.52, $coords['lat']);
        $this->assertEquals(13.404, $coords['lon']);
    }

    public function testMoveUp(): void
    {
        $session = new Session();
        $session->setCoordinates(52.52, 13.4, 14);

        $session->moveUp();
        $coords = $session->getCoordinates();

        // At zoom 14, movement should be +0.004 * 0.7
        $this->assertEqualsWithDelta(52.5228, $coords['lat'], 0.0001);
        $this->assertEquals(13.4, $coords['lon']);
    }

    public function testMoveDown(): void
    {
        $session = new Session();
        $session->setCoordinates(52.52, 13.4, 14);

        $session->moveDown();
        $coords = $session->getCoordinates();

        // At zoom 14, movement should be -0.004 * 0.7
        $this->assertEquals(52.5172, $coords['lat']);
        $this->assertEquals(13.4, $coords['lon']);
    }

    public function testZoomIn(): void
    {
        $session = new Session();

        // Zoom from default 14 to 15
        $session->zoomIn();
        $coords = $session->getCoordinates();
        $this->assertEquals(15, $coords['zoom']);
    }

    public function testZoomOut(): void
    {
        $session = new Session();

        // Zoom from default 14 to 13
        $session->zoomOut();
        $coords = $session->getCoordinates();
        $this->assertEquals(13, $coords['zoom']);
    }

    public function testZoomInAtMaxZoom(): void
    {
        $session = new Session();
        $session->setCoordinates(52.52, 13.4, 22); // Set at max zoom

        $session->zoomIn(); // Should not change
        $coords = $session->getCoordinates();
        $this->assertEquals(22, $coords['zoom']);
    }

    public function testZoomOutAtMinZoom(): void
    {
        $session = new Session();
        $session->setCoordinates(52.52, 13.4, 1); // Set at min zoom

        $session->zoomOut(); // Should not change
        $coords = $session->getCoordinates();
        $this->assertEquals(1, $coords['zoom']);
    }

    public function testSetZoomWithinBounds(): void
    {
        $session = new Session();

        // Test setting zoom to various levels within bounds
        $session->setZoom(5);
        $coords = $session->getCoordinates();
        $this->assertEquals(5, $coords['zoom']);

        $session->setZoom(25); // Above max, should clamp to 22
        $coords = $session->getCoordinates();
        $this->assertEquals(22, $coords['zoom']);

        $session->setZoom(-5); // Below min, should clamp to 1
        $coords = $session->getCoordinates();
        $this->assertEquals(1, $coords['zoom']);
    }

    public function testMovementVariesByZoom(): void
    {
        $session = new Session();

        // Test different zoom levels have different movement amounts
        $session->setCoordinates(0, 0, 10);
        $session->moveRight();
        $coords = $session->getCoordinates();

        // Movement at zoom 10 should be smaller than at zoom 14
        $this->assertGreaterThan(0, $coords['lon']);

        $session->setCoordinates(0, 0, 20);
        $session->moveRight();
        $coords2 = $session->getCoordinates();

        // Very small movement at zoom 20
        $this->assertGreaterThan(0, $coords2['lon']);
        // But smaller than at zoom 10
        $this->assertLessThan($coords['lon'], $coords2['lon']);
    }

    public function testMapStyle(): void
    {
        $session = new Session();

        // Default style
        $this->assertEquals('streets-v12', $session->getMapStyle());

        // Set and get style
        $session->setMapStyle('satellite-v9');
        $this->assertEquals('satellite-v9', $session->getMapStyle());
    }
}
