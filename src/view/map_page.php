<!DOCTYPE html>
<html>
<head>
 <title><?= $title ?></title>
 <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; font-size: 11px; max-width:320px; margin:auto;">

<img src="controller/render_map.php" data-testid="map" alt="MAP" style="width:310px; height:250px; border:1px solid #333; display:block; margin:0;">

<form method="POST" action="controller/navigation.php" style="margin:0px; height:30px; padding:0px; background:#f0f0f0; text-align:center;">
 <input type="submit" name="left" value="<" data-testid="map-left" style="display: inline-block; width:30px; height:30px; padding:0px; margin:0px 1px; border: 1px solid #ccc; background-color:#fff;">
 <input type="submit" name="right" value=">" data-testid="map-right" style="display: inline-block; width:30px; height:30px; padding:0px; margin:0px 1px; border: 1px solid #ccc; background-color:#fff;">
 <input type="submit" name="up" value="/\" data-testid="map-up" style="display: inline-block; width:30px; height:30px; padding:0px; margin:0px 1px; border: 1px solid #ccc; background-color:#fff;">
 <input type="submit" name="down" value="\/" data-testid="map-down" style="display: inline-block; width:30px; height:30px; padding:0px; margin:0px 1px; border: 1px solid #ccc; background-color:#fff;">
 <input type="submit" name="zoom_in" value="+" data-testid="map-zoom-in" style="display: inline-block; width:30px; height:30px; padding:0px; margin:0px 1px; border: 1px solid #ccc; background-color:#fff;">
 <input type="submit" name="zoom_out" value="-" data-testid="map-zoom-out" style="display: inline-block; width:30px; height:30px; padding:0px; margin:0px 1px; border: 1px solid #ccc; background-color:#fff;">
</form>

<form method="POST" action="controller/search.php" style="margin:0; padding:0px; background:#e8f4f8;">
 <input type="text" name="address" data-testid="search-address" placeholder="Street, City" style="width:200px; padding:5px; font-size:11px; border: 1px solid #ccc;">
 <input type="submit" value="SEARCH" data-testid="search-submit" style="padding:7px 5px 3px 5px; font-size:11px; background:#007bff; color: white; border: 1px solid #0056b3;">
<select name="geocoding_api" data-testid="geocoding-api-select" style="font-size:10px; width:45px; padding:1px; margin-left:2px; border:1px solid #ccc;">
<option value="mapbox"<?= isset($_SESSION['geocoding_api']) &&
$_SESSION['geocoding_api'] === 'mapbox'
    ? ' selected'
    : '' ?>>Mbx</option>
<option value="nominatim"<?= isset($_SESSION['geocoding_api']) &&
$_SESSION['geocoding_api'] === 'nominatim'
    ? ' selected'
    : '' ?>>Nom</option>
</select>
</form>

<div style="margin:0; padding:3px 5px; background:#f0f0f0; font-size:9px;">
 <?php
 $styles = [
     'streets-v12' => 'Streets',
     'outdoors-v12' => 'Outdoors',
     'satellite-v9' => 'Satellite',
 ];

 $first = true;
 foreach ($styles as $value => $label) {
     if (!$first) {
         echo ' | ';
     }
     $first = false;

     if ($currentStyle === $value) {
         // Active style - styled as plain text
         echo '<span data-testid="' .
             $value .
             '" class="active" style="color:#333; font-weight:bold;">' .
             htmlspecialchars($label) .
             '</span>';
     } else {
         // Inactive style - styled as clickable link
         echo '<a href="controller/style.php?map_style=' .
             urlencode($value) .
             '" data-testid="' .
             $value .
             '" style="color:#007bff; text-decoration:none;">' .
             htmlspecialchars($label) .
             '</a>';
     }
 }
 ?>
</div>

<p style="margin:5px 0; font-size:10px; text-align: center; color: #666;">
 Lat: <?= htmlspecialchars($coords['lat']) ?>, Lon: <?= htmlspecialchars(
    $coords['lon'],
) ?>, Zoom: <?= htmlspecialchars($coords['zoom']) ?>
</p>

<p style="margin:0; font-size:10px; text-align: center;">
 <a href="sms:?body=https://www.google.com/maps/search/?api=1&query=<?= htmlspecialchars(
     $coords['lat'],
 ) ?>,<?= htmlspecialchars($coords['lon']) ?>" >Send via SMS</a>
</p>

</body>
</html>
