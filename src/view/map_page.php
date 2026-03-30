<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
    <meta charset="UTF-8">
	<style>
	    .map-nav-form { margin:0px; height:30px; padding:0px; background:#f0f0f0; }
		.map-nav-btn{ display: inline-block; width:30px; height:30px; padding:0px; margin:0px 2px 0 0; border: 1px solid #ccc; background-color:#fff; }
		.map-img {width:310px; height:250px; border:1px solid #333; display:block; margin:0; }
		
		.default-form{margin:0; padding: 0px; background:#fff};
        .search-address { width:200px; padding:5px; margin:0px; font-size:11px; border: 1px solid #ccc; }
        .search-submit { padding:7px 0px 3px 5px; font-size:11px; background:#007bff; color: white; border: 1px solid #007bff; }
		.m-0 {margin:0px;}
		.btn{ display: inline-block; padding:4px 0px 0px 5px; margin:0px 2px 0 0 ; border: 1px solid #ccc; background-color:#ccc; font-size:11px; height:30px; }
	</style>
</head>
<body style="margin:0; padding:0; font-size: 11px; max-width:320px; margin:auto;">

<img src="controller/render_map.php" data-testid="map" alt="MAP" class="map-img">

<form method="POST" action="controller/navigation.php" class="map-nav-form">
<span>.</span>
<input type="submit" name="left" value="<" data-testid="map-left" class="map-nav-btn">
<input type="submit" name="right" value=">" data-testid="map-right" class="map-nav-btn">
<input type="submit" name="up" value="/\" data-testid="map-up" class="map-nav-btn">
<input type="submit" name="down" value="\/" data-testid="map-down" class="map-nav-btn">
<input type="submit" name="zoom_in" value="+" data-testid="map-zoom-in" class="map-nav-btn">
<input type="submit" name="zoom_out" value="-" data-testid="map-zoom-out" class="map-nav-btn">
</form>

<form method="POST" action="controller/search.php" class="default-form">
<input type="text" name="address" data-testid="search-address" placeholder="Street, City" class="search-address" style="width:200px; padding:5px; font-size:11px; border: 1px solid #ccc;">
<input type="submit" value="OK" data-testid="search-submit" class="search-submit" >
<label style="font-size:11px; margin-left:0px;"><input type="checkbox" name="navigate" value="1" data-testid="nav-checkbox" class="m-0">Directions</label>
</form>

<form method="POST" action="controller/markers.php" class="default-form">
<span>.</span>
<input type="submit" name="action" value="+ pin" data-testid="marker-add" class="btn" style="border: 1px solid #ADF089; background-color:#ADF089; ">
<input type="submit" name="action" value="clear" data-testid="marker-clear" class="btn" style="border: 1px solid #F09B89; background-color:#F09B89;">
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
<!--
<p style="margin:5px 0; font-size:10px; text-align: center; color: #666;">
Lat: <?= htmlspecialchars($coords['lat']) ?>, Lon: <?= htmlspecialchars(
    $coords['lon'],
) ?>, Zoom: <?= htmlspecialchars($coords['zoom']) ?>
</p>
-->
<p style="margin:0; text-align: center;">
<a href="sms:?body=https://www.google.com/maps/search/?api=1&query=<?= htmlspecialchars(
    $coords['lat'],
) ?>,<?= htmlspecialchars(
    $coords['lon'],
) ?>" >Send via SMS</a> | <a href="controller/settings.php" data-testid="settings-link">Settings</a>
</p>

</body>
</html>
