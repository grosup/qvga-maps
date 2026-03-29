<?php

namespace NokiaMaps\Service;

/**
 * Geocoding Service Factory
 * Creates the appropriate geocoding service based on user preference
 */
class GeocodingServiceFactory
{
    private string $mapboxToken;

    public function __construct(string $mapboxToken = '')
    {
        $this->mapboxToken = $mapboxToken;
    }

    /**
     * Create geocoding service based on API preference
     *
     * @param string $apiPreference 'mapbox' or 'nominatim', defaults to 'mapbox'
     * @return AbstractGeocodingService
     */
    public function create(string $apiPreference = 'mapbox'): AbstractGeocodingService
    {
        switch ($apiPreference) {
            case 'nominatim':
                return new NominatimGeocodingService();

            case 'mapbox':
            default:
                return new GeocodingService($this->mapboxToken);
        }
    }
}
