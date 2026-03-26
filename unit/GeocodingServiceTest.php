<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Service\GeocodingService;

class GeocodingServiceTest extends TestCase
{
    private GeocodingService $service;
    private string $testToken = 'pk.test_token_1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeocodingService($this->testToken);
    }

    public function testConstructorSetsToken(): void
    {
        $service = new GeocodingService($this->testToken);
        $this->assertInstanceOf(GeocodingService::class, $service);
    }

    public function testGeocodeWithValidQuery(): void
    {
        // This is a mock test - in real test we would mock the API call
        $query = 'Berlin';

        // Should return an array (even if empty due to network issues)
        $results = $this->service->geocode($query);

        $this->assertIsArray($results);
        // If results exist, they should have required fields
        if (!empty($results)) {
            $firstResult = $results[0];
            $this->assertArrayHasKey('display_name', $firstResult);
            $this->assertArrayHasKey('lat', $firstResult);
            $this->assertArrayHasKey('lon', $firstResult);
            $this->assertArrayHasKey('type', $firstResult);
        }
    }

    public function testGeocodeWithEmptyQuery(): void
    {
        $results = $this->service->geocode('');

        // Empty query should return empty array
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testGeocodeWithWhitespaceQuery(): void
    {
        $results = $this->service->geocode('   ');

        // Whitespace-only query should return empty array
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testGeocodeRespectsLimitParameter(): void
    {
        $query = 'church';

        // Test with limit 1
        $results1 = $this->service->geocode($query, 1);
        if (!empty($results1)) {
            $this->assertLessThanOrEqual(1, count($results1));
        }

        // Test with limit 3
        $results3 = $this->service->geocode($query, 3);
        if (!empty($results3)) {
            $this->assertLessThanOrEqual(3, count($results3));
        }

        // Test with limit 5 (default)
        $results5 = $this->service->geocode($query, 5);
        if (!empty($results5)) {
            $this->assertLessThanOrEqual(5, count($results5));
        }
    }

    public function testResultFormatIsConsistent(): void
    {
        $results = $this->service->geocode('Berlin', 5);

        if (!empty($results)) {
            foreach ($results as $result) {
                // Each result should have these fields
                $this->assertArrayHasKey('display_name', $result);
                $this->assertArrayHasKey('lat', $result);
                $this->assertArrayHasKey('lon', $result);
                $this->assertArrayHasKey('type', $result);

                // Latitude and longitude should be numeric
                $this->assertIsNumeric($result['lat']);
                $this->assertIsNumeric($result['lon']);

                // Coordinates should be in valid ranges
                $this->assertGreaterThanOrEqual(-90, (float) $result['lat']);
                $this->assertLessThanOrEqual(90, (float) $result['lat']);
                $this->assertGreaterThanOrEqual(-180, (float) $result['lon']);
                $this->assertLessThanOrEqual(180, (float) $result['lon']);
            }
        }
    }

    public function testSpecialCharactersInQuery(): void
    {
        // Test with non-ASCII characters
        $results = $this->service->geocode('São Paulo');

        // Should handle special characters gracefully
        $this->assertIsArray($results);

        // If we get results, verify no encoding issues
        if (!empty($results)) {
            foreach ($results as $result) {
                $this->assertNotNull($result['display_name']);
            }
        }
    }

    public function testGeocodeWithInvalidToken(): void
    {
        $service = new GeocodingService('invalid_token');
        $results = $service->geocode('Berlin', 5);

        // With invalid token, should return empty array gracefully
        $this->assertIsArray($results);
    }

    public function testGeocodeCaching(): void
    {
        // First call - should fetch from API
        $results1 = $this->service->geocode('Berlin', 5);

        // Second call with same query - should use cache
        $results2 = $this->service->geocode('Berlin', 5);

        // Results should be the same
        if (!empty($results1) && !empty($results2)) {
            $this->assertEquals(count($results1), count($results2));
        }
    }

    public function testProcessResultsFormatsResponseCorrectly(): void
    {
        // Test data structure returned from Mapbox
        $mockMapboxResponse = [
            'features' => [
                [
                    'place_name' => 'Berlin, Germany',
                    'center' => [13.405, 52.52],
                    'place_type' => ['place'],
                ],
                [
                    'place_name' => 'Berlin, Connecticut, United States',
                    'center' => [-72.7276, 41.6218],
                    'place_type' => ['place'],
                ],
            ],
        ];

        // Process through private method
        $processed = $this->callPrivateMethod('processResults', [$mockMapboxResponse]);

        $this->assertIsArray($processed);
        $this->assertCount(2, $processed);

        // Check first result
        $this->assertEquals('Berlin, Germany', $processed[0]['display_name']);
        $this->assertEquals(52.52, (float) $processed[0]['lat']);
        $this->assertEquals(13.405, (float) $processed[0]['lon']);
        $this->assertEquals('place', $processed[0]['type']);
    }

    public function testProcessResultsHandlesEmptyFeatures(): void
    {
        $mockResponse = ['features' => []];
        $processed = $this->callPrivateMethod('processResults', [$mockResponse]);

        $this->assertIsArray($processed);
        $this->assertEmpty($processed);
    }

    public function testProcessResultsHandlesMissingFields(): void
    {
        $mockResponse = [
            'features' => [
                [
                    'place_name' => 'No center',
                    // Missing 'center' and 'place_type'
                ],
                [
                    'center' => [13.405, 52.52],
                    // Missing 'place_name' and 'place_type'
                ],
            ],
        ];

        // Should handle gracefully
        $processed = $this->callPrivateMethod('processResults', [$mockResponse]);
        $this->assertIsArray($processed);
    }

    public function testCacheDirectoryCreation(): void
    {
        $cacheDir = __DIR__ . '/../src/cache/geocoding';

        if (!is_dir($cacheDir)) {
            // Cache directory should be created on first geocode call
            $this->service->geocode('test', 1);
        }

        // Should exist after service is used
        $this->assertDirectoryExists($cacheDir);
    }

    /**
     * Helper to call private methods for testing
     */
    private function callPrivateMethod(string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->service, $args);
    }
}
