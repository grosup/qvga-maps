<?php

use PHPUnit\Framework\TestCase;
use NokiaMaps\Service\GeocodingServiceFactory;
use NokiaMaps\Service\GeocodingService;
use NokiaMaps\Service\NominatimGeocodingService;
use NokiaMaps\Service\AbstractGeocodingService;

class GeocodingServiceFactoryTest extends TestCase
{
    private string $testToken = 'pk.test_token_abc123';

    public function testConstructorSetsMapboxToken(): void
    {
        $factory = new GeocodingServiceFactory($this->testToken);
        $this->assertInstanceOf(GeocodingServiceFactory::class, $factory);
    }

    public function testCreateReturnsMapboxServiceByDefault(): void
    {
        $factory = new GeocodingServiceFactory($this->testToken);
        $service = $factory->create();

        $this->assertInstanceOf(GeocodingService::class, $service);
        $this->assertInstanceOf(AbstractGeocodingService::class, $service);
    }

    public function testCreateWithMapboxPreference(): void
    {
        $factory = new GeocodingServiceFactory($this->testToken);
        $service = $factory->create('mapbox');

        $this->assertInstanceOf(GeocodingService::class, $service);
        $this->assertInstanceOf(AbstractGeocodingService::class, $service);
    }

    public function testCreateWithNominatimPreference(): void
    {
        $factory = new GeocodingServiceFactory($this->testToken);
        $service = $factory->create('nominatim');

        $this->assertInstanceOf(NominatimGeocodingService::class, $service);
        $this->assertInstanceOf(AbstractGeocodingService::class, $service);
    }

    public function testCreateWithEmptyStringDefaultsToMapbox(): void
    {
        $factory = new GeocodingServiceFactory($this->testToken);
        $service = $factory->create('');

        $this->assertInstanceOf(GeocodingService::class, $service);
    }

    public function testFactoryCanCreateMultipleServices(): void
    {
        $factory = new GeocodingServiceFactory($this->testToken);

        $mapboxService = $factory->create('mapbox');
        $nominatimService = $factory->create('nominatim');

        $this->assertInstanceOf(GeocodingService::class, $mapboxService);
        $this->assertInstanceOf(NominatimGeocodingService::class, $nominatimService);
        $this->assertNotSame($mapboxService, $nominatimService);
    }
}
