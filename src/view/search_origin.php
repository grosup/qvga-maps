<!DOCTYPE html>
<html>
<head>
    <title>Search Origin</title>
    <meta charset="UTF-8">
    <style>
        body { margin:0; padding:5px; font-size: 11px; font-family: Arial, sans-serif; }
        h2 { margin:10px 0 10px 0; font-size: 12px; }
        .result { padding: 7px; margin: 2px 0; background: #f5f5f5; border: 1px solid #ccc; }
        .search-form { margin: 15px 0; padding: 10px; background: #f5f5f5; border: 1px solid #ccc; }
        .search-address { padding: 5px; font-size: 11px; border: 1px solid #ccc; width: 180px; }
        .search-submit { padding:7px 0px 3px 5px; margin:5px 0 0 0; font-size:11px; background:#007bff; color: white; border: 1px solid #007bff; }
        select { font-size:11px; margin: 0 5px; padding: 5px; border: 1px solid #ccc; }
        .profile-row { margin: 10px 0; }
    </style>
</head>
<body style="max-width:320px; margin:auto">

<h2 style="margin:10px 0 10px 0;">How and where from are you traveling?</h2>

<form method="GET" action="./search.php" style="margin:10px 0;">
<input type="hidden" name="mode" value="origin">
	<select name="profile" class="">
	<option value="driving">By Car</option>
	<option value="walking">Walking</option>
	<option value="cycling">Bike</option>
	<option value="public_transit">Public Transport</option>
	</select>
	<br />
<input type="text" name="address" placeholder="Enter starting location" class="search-address" style="width:200px; margin-top:5px;">
<input type="submit" value="OK" class="search-submit">
</form>

<h2 style="margin:20px 0 10px 0;">Your destination: </h2>
<div class="result">
<?= htmlspecialchars($destination['display_name'] ?? 'Unknown') ?>
</div>

</body>
</html>
