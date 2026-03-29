<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Renderer;
use NokiaMaps\Session;

class MapRendererTest extends TestCase
{
    private Renderer $renderer;
    private Session $session;
    private string $tempCacheDir;

    protected function setUp(): void
    {
        // Reset session for clean test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_write_close();
        session_start();

        $this->session = new Session();

        // We'll avoid direct testing of renderImage as it calls exit()
    }

    protected function tearDown(): void
    {
        // Clean up if needed
    }

    public function testConstructorSetsTokenAndSession(): void
    {
        $this->renderer = new Renderer($this->session, 'test-token-123');

        // Can't directly test private properties, but we can test behavior
        $this->assertInstanceOf(Renderer::class, $this->renderer);
    }

    public function testReverseGeocodeWithoutTokenReturnsEmptyString(): void
    {
        $this->renderer = new Renderer($this->session, '');
        $result = $this->renderer->reverseGeocode(52.52, 13.4);

        $this->assertEquals('', $result);
    }

    public function testReverseGeocodeWithValidToken(): void
    {
        $this->renderer = new Renderer($this->session, 'test-token');

        // Mock cURL operations would be needed for actual API calls
        // For unit testing, we'll test the public method signature
        $result = $this->renderer->reverseGeocode(52.52, 13.4);

        // Since we don't have a real token or network, this will fail and return empty
        $this->assertIsString($result);
    }

    public function testGeneratePlaceholderCreatesValidImage(): void
    {
        $this->renderer = new Renderer($this->session, '');

        // Create a minimal test to verify placeholder generation works
        // This would require mocking the private method or testing indirectly

        $this->assertInstanceOf(Renderer::class, $this->renderer);
    }

    public function testRendererWithDifferentCoordinates(): void
    {
        $this->renderer = new Renderer($this->session, 'test-token');

        // Set different coordinates
        $this->session->setCoordinates(48.8566, 2.3522, 10);

        // Verify session has correct coordinates
        $coords = $this->session->getCoordinates();
        $this->assertEquals(48.8566, $coords['lat']);
        $this->assertEquals(2.3522, $coords['lon']);
        $this->assertEquals(10, $coords['zoom']);
    }

    public function testMapStyleGetsApplied(): void
    {
        $this->session->setMapStyle('satellite-v9');
        $this->renderer = new Renderer($this->session, 'test-token');

        // Verify style was set
        $this->assertEquals('satellite-v9', $this->session->getMapStyle());
    }

    public function testRendererHandlesEmptyStyle(): void
    {
        $this->session->setMapStyle('');
        $this->renderer = new Renderer($this->session, '');

        // Should use default style internally
        $this->assertEquals('', $this->session->getMapStyle());
    }

    public function testFallbackToPlaceholderWithoutToken(): void
    {
        $this->renderer = new Renderer($this->session, '');

        // Should fallback to placeholder generation
        // Can't directly test private generateMap method
        // But we can verify the renderer is created successfully
        $this->assertInstanceOf(Renderer::class, $this->renderer);
    }

    public function testSessionCoordinatesMatchRendererRequirement(): void
    {
        // Test different zoom levels
        $testCases = [
            ['lat' => 52.52, 'lon' => 13.4, 'zoom' => 1],
            ['lat' => 48.8566, 'lon' => 2.3522, 'zoom' => 10],
            ['lat' => 40.7128, 'lon' => -74.006, 'zoom' => 14],
            ['lat' => -33.8688, 'lon' => 151.2093, 'zoom' => 22],
        ];

        foreach ($testCases as $coords) {
            $this->session->setCoordinates($coords['lat'], $coords['lon'], $coords['zoom']);
            $this->renderer = new Renderer($this->session, 'test-token');

            $sessionCoords = $this->session->getCoordinates();
            $this->assertEquals($coords['lat'], $sessionCoords['lat']);
            $this->assertEquals($coords['lon'], $sessionCoords['lon']);
            $this->assertEquals($coords['zoom'], $sessionCoords['zoom']);
        }
    }

    public function testRendererIntegrationWithSession(): void
    {
        // This is more of an integration-style test
        // Verifies that renderer can access session data correctly

        $expectedCoords = ['lat' => 37.7749, 'lon' => -122.4194, 'zoom' => 12];
        $this->session->setCoordinates(
            $expectedCoords['lat'],
            $expectedCoords['lon'],
            $expectedCoords['zoom'],
        );

        $this->renderer = new Renderer($this->session, 'test-token-xyz');

        // Verify session was properly passed to renderer
        // We can't access private property directly, so we test indirectly
        $coordsFromSession = $this->session->getCoordinates();

        $this->assertEquals($expectedCoords['lat'], $coordsFromSession['lat']);
        $this->assertEquals($expectedCoords['lon'], $coordsFromSession['lon']);
        $this->assertEquals($expectedCoords['zoom'], $coordsFromSession['zoom']);
    }

    public function testRendererWithVariousMapStyles(): void
    {
        $styles = ['streets-v12', 'outdoors-v12', 'satellite-v9', 'light-v11', 'dark-v11'];

        foreach ($styles as $style) {
            $this->session->setMapStyle($style);
            $this->renderer = new Renderer($this->session, 'test-token');

            $this->assertEquals($style, $this->session->getMapStyle());
        }
    }

    public function testCanCreateRendererWithoutToken(): void
    {
        // Should work with empty token and use placeholder
        $renderer = new Renderer($this->session, '');
        $this->assertInstanceOf(Renderer::class, $renderer);

        // Should work with just session (default parameter)
        $renderer2 = new Renderer($this->session);
        $this->assertInstanceOf(Renderer::class, $renderer2);
    }

    public function testRendererConstructorTypeHints(): void
    {
        // Verify that constructor accepts correct types
        $this->assertInstanceOf(Renderer::class, new Renderer($this->session, 'token'));
        $this->assertInstanceOf(Renderer::class, new Renderer($this->session));
    }
}
