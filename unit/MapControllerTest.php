<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Controller\NavigationController;
use NokiaMaps\Session;

class MapControllerTest extends TestCase
{
    private NavigationController $controller;
    private Session $session;

    protected function setUp(): void
    {
        // Store original $_POST
        $this->originalPost = $_POST;

        // Reset session for clean test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_write_close();
        session_start();

        $this->session = new Session();
        $this->controller = new NavigationController($this->session);
    }

    protected function tearDown(): void
    {
        // Restore original $_POST
        $_POST = $this->originalPost;
    }

    public function testHandleNavigationWithLeftButton(): void
    {
        $_POST = ['left' => 'x'];
        $this->controller->handleNavigation();

        // Should have moved left
        $coords = $this->session->getCoordinates();
        $this->assertEquals(13.396, $coords['lon']);
    }

    public function testHandleNavigationWithRightButton(): void
    {
        $_POST = ['right' => 'x'];
        $this->controller->handleNavigation();

        // Should have moved right
        $coords = $this->session->getCoordinates();
        $this->assertEquals(13.404, $coords['lon']);
    }

    public function testHandleNavigationWithUpButton(): void
    {
        $_POST = ['up' => 'x'];
        $this->controller->handleNavigation();

        // Should have moved up
        $coords = $this->session->getCoordinates();
        $this->assertEqualsWithDelta(52.5228, $coords['lat'], 0.0001);
        $this->assertEquals(13.4, $coords['lon']);
    }

    public function testHandleNavigationWithDownButton(): void
    {
        $_POST = ['down' => 'x'];
        $this->controller->handleNavigation();

        // Should have moved down
        $coords = $this->session->getCoordinates();
        $this->assertEquals(52.5172, $coords['lat']);
        $this->assertEquals(13.4, $coords['lon']);
    }

    public function testHandleNavigationWithZoomInButton(): void
    {
        $_POST = ['zoom_in' => 'x'];

        // Start at default zoom 14
        $coords = $this->session->getCoordinates();
        $this->assertEquals(14, $coords['zoom']);

        $this->controller->handleNavigation();

        // Should have zoomed in to 15
        $coords = $this->session->getCoordinates();
        $this->assertEquals(15, $coords['zoom']);
    }

    public function testHandleNavigationWithZoomOutButton(): void
    {
        $_POST = ['zoom_out' => 'x'];

        // Start at default zoom 14
        $coords = $this->session->getCoordinates();
        $this->assertEquals(14, $coords['zoom']);

        $this->controller->handleNavigation();

        // Should have zoomed out to 13
        $coords = $this->session->getCoordinates();
        $this->assertEquals(13, $coords['zoom']);
    }

    public function testHandleNavigationWithLeftXButton(): void
    {
        $_POST = ['left_x' => 'x'];
        $this->controller->handleNavigation();

        $coords = $this->session->getCoordinates();
        $this->assertEquals(13.396, $coords['lon']);
    }

    public function testHandleNavigationWithLeftDotXButton(): void
    {
        $_POST = ['left.x' => 'x'];
        $this->controller->handleNavigation();

        $coords = $this->session->getCoordinates();
        $this->assertEquals(13.396, $coords['lon']);
    }

    public function testHandleNavigationWithNoButtonsDoesNothing(): void
    {
        $_POST = [];

        $coordsBefore = $this->session->getCoordinates();
        $this->controller->handleNavigation();
        $coordsAfter = $this->session->getCoordinates();

        // Coordinates should remain unchanged
        $this->assertEquals($coordsBefore, $coordsAfter);
    }

    public function testNavigationButtonPriority(): void
    {
        // Left has priority in the if-else chain
        $_POST = ['left' => 'x', 'right' => 'x', 'up' => 'x'];
        $this->controller->handleNavigation();

        $coords = $this->session->getCoordinates();
        $this->assertEquals(13.396, $coords['lon']);
        $this->assertEquals(52.52, $coords['lat']); // No vertical movement
    }

    public function testMultipleNavigationActions(): void
    {
        // Simulate multiple button presses
        $_POST = ['right' => 'x'];
        $this->controller->handleNavigation();
        $coords1 = $this->session->getCoordinates();

        $_POST = ['right' => 'x'];
        $this->controller->handleNavigation();
        $coords2 = $this->session->getCoordinates();

        $_POST = ['right' => 'x'];
        $this->controller->handleNavigation();
        $coords3 = $this->session->getCoordinates();

        // Each movement should increase longitude
        $this->assertGreaterThan($coords1['lon'], $coords2['lon']);
        $this->assertGreaterThan($coords2['lon'], $coords3['lon']);
    }

    public function testControllerHandlesInvalidPostData(): void
    {
        $_POST = ['invalid_button' => 'x'];

        $coordsBefore = $this->session->getCoordinates();
        $this->controller->handleNavigation();
        $coordsAfter = $this->session->getCoordinates();

        // Coordinates should remain unchanged
        $this->assertEquals($coordsBefore, $coordsAfter);
    }
}
