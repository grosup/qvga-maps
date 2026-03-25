<?php
/**
 * Comprehensive Search Handler
 * Handles POST/GET, robust error handling, always shows map
 */

session_start();

// Initialize default session values if not set
if (!isset($_SESSION['lat'])) {
    $_SESSION['lat'] = 52.52;
    $_SESSION['lon'] = 13.4;
    $_SESSION['zoom'] = 14;
}

// Debug: Log what's being received
$requestMethod = $_SERVER['REQUEST_METHOD'];
$hasAddress = isset($_GET['address']) || isset($_POST['address']);
$hasSelect = isset($_GET['select']);

// Capture address from either POST or GET
$address = '';
if (isset($_POST['address'])) {
    $address = trim($_POST['address']);
} elseif (isset($_GET['address'])) {
    $address = trim($_GET['address']);
}

$results = [];
$error = '';
$successMessage = '';

// If user selected a result, set location and redirect to map
if ($hasSelect && isset($_GET['select']) && is_numeric($_GET['select'])) {
    // First, we need to fetch the search results to know what was selected
    if (!empty($address)) {
        // We'll do the search again to populate results
        $url =
            'https://nominatim.openstreetmap.org/search?' .
            'q=' .
            urlencode($address) .
            '&format=json' .
            '&limit=5' .
            '&addressdetails=1';

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: Nokia225MapApp/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response !== false) {
            $results = json_decode($response, true);
            $selectIndex = (int) $_GET['select'];

            if (isset($results[$selectIndex])) {
                $selected = $results[$selectIndex];
                $_SESSION['lat'] = $selected['lat'];
                $_SESSION['lon'] = $selected['lon'];
                // Redirect to map
                header('Location: ../index.php');
                exit();
            } else {
                $error = 'Invalid selection.';
            }
        } else {
            $error = 'Search failed. Please try again.';
        }
    } else {
        $error = 'No address provided.';
    }
}

// Perform the search
if (!empty($address) && !$hasSelect) {
    $url =
        'https://nominatim.openstreetmap.org/search?' .
        'q=' .
        urlencode($address) .
        '&format=json' .
        '&limit=5' .
        '&addressdetails=1';

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Nokia225MapApp/1.0\r\n",
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response !== false) {
        $results = json_decode($response, true);

        // Store search results in session for selection
        $_SESSION['search_results'] = $results;
        $_SESSION['search_address'] = $address;
    } else {
        $error = 'Search failed. Please try again.';
    }
}

// If no results, show error
$errorMessage = '';
if (!empty($error)) {
    $errorMessage = "<div style='color:red;background:#ffe6e6;border:1px solid red;padding:5px;font-size:10px;'>$error</div>";
}

// Render search results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <meta charset="UTF-8">
    <style>
    body { margin:0; padding:5px; font-size: 11px; font-family: Arial, sans-serif; }
    h2 { margin:0 0 10px 0; font-size: 12px; }
    .result { padding: 7px; margin: 2px 0; background: #f5f5f5; border: 1px solid #ccc; }
    .result a { color: #000; text-decoration: none; }
    .error { background: #ffe6e6; color: red; border: 1px solid red; }
    .info { margin: 5px 0; color: #666; }
    .refresh { margin: 10px 0; }
    .refresh a { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; }
    </style>
</head>
<body style="max-width:320px; margin:auto">

<?= $errorMessage ?>

<div class="refresh">
    <a href="../index.php" data-testid="search-result-back-header">Back to Map</a>
</div>

<?php if (!empty($results)): ?>
    <h2>Search Results for "<?= htmlspecialchars($address) ?>":</h2>
    <?php foreach ($results as $index => $result): ?>
        <div class="result">
            <a href="../controller/search.php?select=<?= $index ?>&address=<?= urlencode(
    $address,
) ?>" data-testid="search-result-item-<?= $index ?>">
                <?= htmlspecialchars($result['display_name']) ?>
				</a>
			</div>
		<?php endforeach; ?>
<?php elseif (isset($_POST['address']) || isset($_GET['address'])): ?>
    <div class="result error">
        No results found for "<?= htmlspecialchars($address) ?>"
    </div>
<?php endif; ?>

<div class="refresh">
    <a href="../index.php" data-testid="search-result-back-footer">Back to Map</a>
</div>

</body>
</html>
