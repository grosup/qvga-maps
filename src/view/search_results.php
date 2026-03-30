<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <meta charset="UTF-8">
    <style>
        body { margin:0; padding:5px; font-size: 11px; font-family: Arial, sans-serif; }
        h2 { margin:20px 0 10px 0; font-size: 12px; }
        .result { padding: 7px; margin: 2px 0; background: #f5f5f5; border: 1px solid #ccc; }
        .result a { color: #000; text-decoration: none; }
        .error { background: #ffe6e6; color: red; border: 1px solid red; }
        .info { margin: 5px 0; color: #666; }
        .back-link { margin: 10px 0; }
        .back-link a { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; }
    </style>
</head>
<body style="max-width:320px; margin:auto">

<?= $errorMessage ?>

<?php if ($hasResults): ?>
<h2><?= htmlspecialchars($pageTitle ?? 'Search Results') ?>:</h2>
<?php foreach ($results as $index => $result): ?>
<div class="result">
<a href="search.php?select=<?= $index ?>&address=<?= urlencode($address) .
    (isset($mode) ? '&mode=' . $mode : '') ?>" data-testid="search-result-item-<?= $index ?>">
<?= htmlspecialchars($result['display_name']) ?>
</a>
</div>
<?php endforeach; ?>
<?php elseif ($hasSearched): ?>
<div class="result error">
No results found for "<?= htmlspecialchars($address) ?>"
</div>
<?php endif; ?>

</body>
</html>
