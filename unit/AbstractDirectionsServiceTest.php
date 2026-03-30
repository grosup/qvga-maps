<?php

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;
use NokiaMaps\Service\AbstractDirectionsService;

class TestableDirectionsService extends AbstractDirectionsService
{
    public function getRoute(
        float $fromLat,
        float $fromLon,
        float $toLat,
        float $toLon,
        string $profile = 'driving',
        array $options = [],
    ): array {
        // Return mock route for testing
        return [
            'summary' => ['distance' => 1000, 'duration' => 600],
            'steps' => [['instruction' => 'Turn left', 'distance' => 500, 'duration' => 300]],
        ];
    }
}

class AbstractDirectionsServiceTest extends TestCase
{
    private TestableDirectionsService $service;

    protected function setUp(): void
    {
        $this->service = new TestableDirectionsService();
    }

    protected function tearDown(): void
    {
        // Clean up cache directory
        $cacheDir = __DIR__ . '/../src/class/cache/directions';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($cacheDir);
        }
    }

    public function testCacheKeyGenerationWithSameParameters(): void
    {
        $key1 = $this->invokeMethod($this->service, 'getCacheKey', [
            40.0,
            -74.0,
            40.1,
            -74.1,
            'driving',
            null,
        ]);
        $key2 = $this->invokeMethod($this->service, 'getCacheKey', [
            40.0,
            -74.0,
            40.1,
            -74.1,
            'driving',
            null,
        ]);

        $this->assertEquals($key1, $key2);
    }

    public function testCacheKeyGenerationWithDifferentParameters(): void
    {
        $key1 = $this->invokeMethod($this->service, 'getCacheKey', [
            40.0,
            -74.0,
            40.1,
            -74.1,
            'driving',
            null,
        ]);
        $key2 = $this->invokeMethod($this->service, 'getCacheKey', [
            40.0,
            -74.0,
            40.1,
            -74.1,
            'walking',
            null,
        ]);

        $this->assertNotEquals($key1, $key2);
    }

    public function testCacheKeyGenerationWithTime(): void
    {
        $key1 = $this->invokeMethod($this->service, 'getCacheKey', [
            40.0,
            -74.0,
            40.1,
            -74.1,
            'driving',
            1234567890,
        ]);
        $key2 = $this->invokeMethod($this->service, 'getCacheKey', [
            40.0,
            -74.0,
            40.1,
            -74.1,
            'driving',
            1234567891,
        ]);

        $this->assertNotEquals($key1, $key2);
    }

    public function testCacheDirectoryCreation(): void
    {
        $cacheDir = __DIR__ . '/../src/class/cache/directions';

        // Ensure directory doesn't exist
        if (is_dir($cacheDir)) {
            rmdir($cacheDir);
        }

        // Create service - should create directory
        $service = new TestableDirectionsService();

        $this->assertDirectoryExists($cacheDir);
    }

    public function testCacheSaveAndRetrieve(): void
    {
        $key = 'test_route_' . uniqid();
        $route = [
            'summary' => ['distance' => 2000, 'duration' => 1200],
            'steps' => [['instruction' => 'Go straight', 'distance' => 1000, 'duration' => 600]],
        ];

        // Save to cache
        $result = $this->invokeMethod($this->service, 'saveToCache', [$key, $route]);
        $this->assertTrue($result);

        // Retrieve from cache
        $cached = $this->invokeMethod($this->service, 'getCached', [$key]);
        $this->assertEquals($route, $cached);
    }

    public function testCacheExpiry(): void
    {
        $key = 'test_expiry_' . uniqid();
        $route = ['summary' => ['distance' => 1000, 'duration' => 600], 'steps' => []];

        // Save to cache
        $this->invokeMethod($this->service, 'saveToCache', [$key, $route]);

        // Touch file to make it old (2 hours ago)
        $cacheFile = __DIR__ . '/../src/class/cache/directions/' . $key . '.json';
        touch($cacheFile, time() - 7200);

        // Should return null (expired)
        $cached = $this->invokeMethod($this->service, 'getCached', [$key]);
        $this->assertNull($cached);
    }

    public function testGenerateSummary(): void
    {
        $steps = [['distance' => 1000, 'duration' => 300], ['distance' => 2000, 'duration' => 600]];

        $summary = $this->invokeMethod($this->service, 'generateSummary', [$steps]);

        $this->assertEquals(3000, $summary['distance']);
        $this->assertEquals(900, $summary['duration']);
    }

    public function testGenerateSummaryEmpty(): void
    {
        $summary = $this->invokeMethod($this->service, 'generateSummary', [[]]);

        $this->assertEquals(0, $summary['distance']);
        $this->assertEquals(0, $summary['duration']);
    }

    /**
     * Helper to invoke protected/private methods
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
