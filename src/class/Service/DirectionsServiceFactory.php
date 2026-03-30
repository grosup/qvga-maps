<?php

namespace NokiaMaps\Service;

/**
 * Directions Service Factory
 * Creates the appropriate directions service based on user preference
 */
class DirectionsServiceFactory
{
    private string $mapboxToken;
    private string $openRouteToken;

    public function __construct(string $mapboxToken = '', string $openRouteToken = '')
    {
        $this->mapboxToken = $mapboxToken;
        $this->openRouteToken = $openRouteToken;
    }

    /**
     * Create directions service based on provider preference
     *
     * @param string $provider 'mapbox' or 'openrouteservice', defaults to 'mapbox'
     * @return AbstractDirectionsService
     */
    public function create(string $provider = 'mapbox'): AbstractDirectionsService
    {
        return match ($provider) {
            'openrouteservice' => new OpenRouteServiceDirections($this->openRouteToken),
            'mapbox' => new MapboxDirections($this->mapboxToken),
            default => new MapboxDirections($this->mapboxToken),
        };
    }
}
