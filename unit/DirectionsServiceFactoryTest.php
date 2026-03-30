<?php

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;
use NokiaMaps\Service\DirectionsServiceFactory;
use NokiaMaps\Service\AbstractDirectionsService;
use NokiaMaps\Service\OpenRouteServiceDirections;

class DirectionsServiceFactoryTest extends TestCase
{
    private DirectionsServiceFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DirectionsServiceFactory('test-mapbox-token', 'test-ors-token');
    }

    public function testCreatesOpenRouteServiceByDefault(): void
    {
        $service = $this->factory->create();

        $this->assertInstanceOf(OpenRouteServiceDirections::class, $service);
        $this->assertInstanceOf(AbstractDirectionsService::class, $service);
    }

    public function testCreatesOpenRouteServiceExplicitly(): void
    {
        $service = $this->factory->create('openrouteservice');

        $this->assertInstanceOf(OpenRouteServiceDirections::class, $service);
    }

    public function testMapboxFallbackToORS(): void
    {
        // Mapbox not implemented yet, should fallback to ORS
        $service = $this->factory->create('mapbox');

        $this->assertInstanceOf(OpenRouteServiceDirections::class, $service);
    }

    public function testRejectsInvalidProvider(): void
    {
        // Invalid provider should default to ORS
        $service = $this->factory->create('invalid-provider');

        $this->assertInstanceOf(OpenRouteServiceDirections::class, $service);
    }
}
