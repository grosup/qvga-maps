<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
    <meta charset="UTF-8">
    <style>
        body { margin:0; padding:5px; font-size: 11px; font-family: Arial, sans-serif; }
        h2 { margin:0 0 10px 0; font-size: 12px; }
        .setting-group { margin: 15px 0; padding: 10px; background: #f5f5f5; border: 1px solid #ccc; }
        .setting-group h3 { margin:0 0 8px 0; font-size: 11px; font-weight: bold; }
        .radio-option { margin: 5px 0; }
        .radio-option input { margin-right: 5px; }
        .buttons { margin: 20px 0; text-align: center; }
        .buttons input { padding: 7px 15px; font-size: 11px; background: #007bff; color: white; border: 1px solid #007bff; margin: 0 5px; }
        .buttons a { display: inline-block; padding: 7px 15px; font-size: 11px; background: #6c757d; color: white; border: 1px solid #6c757d; text-decoration: none; }
    </style>
</head>
<body style="max-width:320px; margin:auto">

<h2>Settings</h2>

<form method="POST" action="">

    <div class="setting-group">
        <h3>Geocoding API for address search:</h3>
        <div class="radio-option">
            <label>
                <input type="radio" name="geocoding_api" value="mapbox" <?= $geocodingApi ===
                'mapbox'
                    ? 'checked'
                    : '' ?>>
                Mapbox
            </label>
        </div>
        <div class="radio-option">
            <label>
                <input type="radio" name="geocoding_api" value="nominatim" <?= $geocodingApi ===
                'nominatim'
                    ? 'checked'
                    : '' ?>>
                OpenStreetMap Nominatim
            </label>
        </div>
    </div>

    <div class="setting-group">
        <h3>Directions API for navigation:</h3>
        <div class="radio-option">
            <label>
                <input type="radio" name="directions_provider" value="mapbox" <?= $directionsProvider ===
                'mapbox'
                    ? 'checked'
                    : '' ?>>
                Mapbox
            </label>
        </div>
        <div class="radio-option">
            <label>
                <input type="radio" name="directions_provider" value="openrouteservice" <?= $directionsProvider ===
                'openrouteservice'
                    ? 'checked'
                    : '' ?>>
                OpenRouteService
            </label>
        </div>
    </div>

    <div class="buttons">
        <input type="submit" name="save" value="SAVE" data-testid="settings-ok">
    </div>

</form>

</body>
</html>
