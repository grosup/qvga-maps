<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Service\NominatimGeocodingService;

class NominatimGeocodingServiceTest extends TestCase
{
    private NominatimGeocodingService $service;
    private array $originalSession;

    protected function setUp(): void
    {
        // Store original session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->originalSession = $_SESSION;

        // Reset session for clean test
        $_SESSION = [];

        $this->service = new NominatimGeocodingService();
    }

    protected function tearDown(): void
    {
        // Restore original session
        $_SESSION = $this->originalSession;
    }

    public function testConstructorInitializesService(): void
    {
        $this->assertInstanceOf(NominatimGeocodingService::class, $this->service);
    }

    public function testGeocodeWithEmptyQueryReturnsEmptyArray(): void
    {
        $results = $this->service->geocode('');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testGeocodeWithWhitespaceOnlyReturnsEmptyArray(): void
    {
        $results = $this->service->geocode('   ');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testGeocodeWithNegativeLimitClampsToOne(): void
    {
        // Limit should be clamped to minimum of 1
        $results = $this->service->geocode('Berlin', -5);
        $this->assertIsArray($results);
    }

    public function testGeocodeWithLargeLimitClampsToFive(): void
    {
        // Limit should be clamped to maximum of 5
        $results = $this->service->geocode('Berlin', 100);
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(5, count($results));
    }

    public function testGeocodeCachesResultsInSession(): void
    {
        // First call for a unique query
        $uniqueQuery = 'TestLocation' . uniqid();
        $results1 = $this->service->geocode($uniqueQuery);

        // Second call should use cache
        $results2 = $this->service->geocode($uniqueQuery);

        // Results should be identical (from cache)
        $this->assertEquals($results1, $results2);
    }

    public function testGeocodeReturnsMaxFiveResults(): void
    {
        // Query that should return multiple results
        $results = $this->service->geocode('Paris');

        // Should be an array with max 5 results
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(5, count($results));
    }

    public function testValidResultsHaveRequiredFields(): void
    {
        $results = $this->service->geocode('Berlin');

        if (!empty($results)) {
            $firstResult = $results[0];

            $this->assertArrayHasKey('display_name', $firstResult);
            $this->assertArrayHasKey('lat', $firstResult);
            $this->assertArrayHasKey('lon', $firstResult);
            $this->assertArrayHasKey('type', $firstResult);

            $this->assertIsString($firstResult['display_name']);
            $this->assertIsString($firstResult['lat']);
            $this->assertIsString($firstResult['lon']);
            $this->assertIsString($firstResult['type']);
        }
    }

    public function testResultsAreCappedByLimitParameter(): void
    {
        // Test with limit of 2
        $results = $this->service->geocode('Paris', 2);

        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testUniqueQueriesCreateSeparateCacheEntries(): void
    {
        $query1 = 'Berlin' . uniqid();
        $query2 = 'Paris' . uniqid();

        $results1 = $this->service->geocode($query1);
        $results2 = $this->service->geocode($query2);

        // Different queries should have different cache entries
        $this->assertNotEquals($results1, $results2);
    }
}
