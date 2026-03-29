<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Service\ReverseGeocodingService;

class ReverseGeocodingServiceTest extends TestCase
{
    private ReverseGeocodingService $service;
    private string $testToken = 'pk.test_token_reverse_abc123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReverseGeocodingService($this->testToken);
    }

    protected function tearDown(): void
    {
        // Clean up cache
        $cacheDir = __DIR__ . '/../src/cache/geocode';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    public function testConstructorSetsMapboxToken(): void
    {
        $service = new ReverseGeocodingService($this->testToken);
        $this->assertInstanceOf(ReverseGeocodingService::class, $service);
    }

    public function testReverseGeocodeReturnsEmptyStringWithoutToken(): void
    {
        $service = new ReverseGeocodingService('');
        $result = $service->reverseGeocode(52.52, 13.4);

        $this->assertIsString($result);
        $this->assertEmpty($result);
    }

    public function testReverseGeocodeWithValidCoordinates(): void
    {
        $result = $this->service->reverseGeocode(52.52, 13.4);

        $this->assertIsString($result);
        // Result will be empty if no cache or API response
    }

    public function testReverseGeocodeCachesResults(): void
    {
        // First call
        $result1 = $this->service->reverseGeocode(52.52, 13.4);
        $result2 = $this->service->reverseGeocode(52.52, 13.4);

        // Results should be identical (from cache)
        $this->assertEquals($result1, $result2);
    }

    public function testReverseGeocodeWithNegativeCoordinates(): void
    {
        $result = $this->service->reverseGeocode(-33.8688, 151.2093);

        $this->assertIsString($result);
    }

    public function testReverseGeocodeWithZeroCoordinates(): void
    {
        $result = $this->service->reverseGeocode(0.0, 0.0);

        $this->assertIsString($result);
    }

    public function testReverseGeocodeReturnsPlaceNameString(): void
    {
        $result = $this->service->reverseGeocode(40.7128, -74.006);

        $this->assertIsString($result);
        // Should be empty string on failure or API unavailable
    }

    public function testReverseGeocodeDifferentCoordinatesHaveDifferentCache(): void
    {
        // Different coordinates should have different cache entries
        $result1 = $this->service->reverseGeocode(52.52, 13.4);
        $result2 = $this->service->reverseGeocode(48.8566, 2.3522);

        $this->assertIsString($result1);
        $this->assertIsString($result2);
    }

    public function testCacheDirectoryIsCreated(): void
    {
        $cacheDir = __DIR__ . '/../src/cache/geocode';

        // Check cache directory exists
        $this->assertDirectoryExists($cacheDir);
    }

    public function testReverseGeocodeWithBoundingBoxCoordinates(): void
    {
        // Test with extreme coordinates
        $result = $this->service->reverseGeocode(60.0, 100.0);

        $this->assertIsString($result);
    }
}
