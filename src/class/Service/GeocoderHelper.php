<?php

namespace NokiaMaps\Service;

/**
 * Geocoder Helper
 * Helps with address/coordinate parsing and geocoding coordination
 */
class GeocoderHelper
{
    private GeocodingServiceFactory $geocodingFactory;

    public function __construct(GeocodingServiceFactory $geocodingFactory)
    {
        $this->geocodingFactory = $geocodingFactory;
    }

    /**
     * Parse and geocode a location from text
     * Supports both addresses and "lat,lon" format
     *
     * @param string $location Text location (address or "lat,lon")
     * @param string $apiPreference Geocoding API preference
     * @return array|null [lat, lon] or null if not found
     */
    public function parseAndGeocode(string $location, string $apiPreference = 'mapbox'): ?array
    {
        $location = trim($location);

        // Check if it's already coordinates "lat,lon"
        if (preg_match('/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/', $location, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lon' => (float) $matches[2],
            ];
        }

        // Otherwise, geocode as address
        $geocodingService = $this->geocodingFactory->create($apiPreference);

        // Use higher limit for first result
        $results = $geocodingService->geocode($location, 1);

        if (empty($results)) {
            return null;
        }

        return [
            'lat' => (float) $results[0]['lat'],
            'lon' => (float) $results[0]['lon'],
            'display_name' => $results[0]['display_name'] ?? $location,
        ];
    }
}
