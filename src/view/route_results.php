<?php
/**
 * Route Results View
 * Displays routing/directions results for feature phones
 * Text-based display like gdir, optimized for QVGA screens
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars("{$originText} to {$destinationText}") ?></title>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0 5px; font-size:11px; max-width:320px; margin:auto;">

<?php if (!empty($errorMessage)): ?>
    <?php
    $errorMessage =
        '<div style="color:red;background:#ffe6e6;border:1px solid red;padding:5px;font-size:10px;margin:5px 0;">' .
        htmlspecialchars($errorMessage) .
        '</div>';
    echo $errorMessage;
    ?>

<?php

    // Format distance

    // Format duration

    // Format step distance

    // Format step duration

    // Name of the road/way

    // Special formatting for certain step types

    elseif ($hasSearched && $route !== null && isset($route['summary'])): ?>
    <?php
    $summary = $route['summary'];
    $distance = $summary['distance'] ?? 0;
    $duration = $summary['duration'] ?? 0;

    if ($distance >= 1000) {
        $distanceFormatted = number_format($distance / 1000, 1) . ' km';
    } else {
        $distanceFormatted = number_format($distance, 0) . ' m';
    }

    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $durationFormatted = '';
    if ($hours > 0) {
        $durationFormatted .= $hours . 'h ';
    }
    if ($minutes > 0 || $hours === 0) {
        $durationFormatted .= $minutes . 'min';
    }
    ?>

    <div style="background:#f0f0f0; padding:8px 5px; margin:5px 0 10px 0; text-align:center; border:1px solid #ccc;">
        <strong>Route: <?= htmlspecialchars($distanceFormatted) ?>, <?= htmlspecialchars(
    $durationFormatted,
) ?></strong><br>
        <span style="font-size:10px; color:#666;">
            <?= ucfirst(htmlspecialchars($profile)) ?>
        </span>
    </div>

    <ol style="margin:0; padding:0 0 0 0px; list-style-type:decimal;">
        <?php foreach ($route['steps'] as $step): ?>
            <?php
            $instruction = $step['instruction'] ?? 'Continue';
            $stepDistance = $step['distance'] ?? 0;
            $stepDuration = $step['duration'] ?? 0;

            if ($stepDistance >= 1000) {
                $stepDistanceFormatted = number_format($stepDistance / 1000, 1) . ' km';
            } else {
                $stepDistanceFormatted = number_format($stepDistance, 0) . ' m';
            }

            $stepMinutes = round($stepDuration / 60);
            $stepDurationFormatted = '';
            if ($stepMinutes >= 1) {
                $stepDurationFormatted = ', ' . $stepMinutes . ' min';
            }

            $wayName = '';
            if (!empty($step['name'])) {
                $wayName = ' on ' . htmlspecialchars($step['name']);
            }

            $stepClass = '';
            if (isset($step['type'])) {
                if (in_array($step['type'], ['arrive'])) {
                    $stepClass = 'style="font-weight:bold;"';
                } elseif (in_array($step['type'], ['depart'])) {
                    $stepClass = 'style="font-style:italic;"';
                }
            }

            $fullInstruction = htmlspecialchars($instruction) . $wayName;
            if ($fullInstruction === 'You have arrived at your destination') {
                $fullInstruction = 'You have arrived';
            }

            $detailText = htmlspecialchars("({$stepDistanceFormatted}{$stepDurationFormatted})");
            ?>

            <li <?= $stepClass ?>>
                <?= $fullInstruction ?><br>
                <span style="font-size:9px; color:#666;"><?= $detailText ?></span>
            </li>

        <?php endforeach; ?>
    </ol>

<?php else: ?>

    <form method="POST" action="../controller/route.php" style="margin:10px 0; padding:5px; background:#f9f9f9; border:1px solid #ddd;">
        <div style="margin:5px 0;">
            <label style="display:block; font-size:10px; margin-bottom:2px;">From:</label>
            <input type="text" name="origin" value="<?= htmlspecialchars(
                $originText,
            ) ?>" placeholder="Address or lat,lon" style="width:100%; padding:4px; font-size:11px; border:1px solid #ccc; box-sizing:border-box;">
        </div>

        <div style="margin:5px 0;">
            <label style="display:block; font-size:10px; margin-bottom:2px;">To:</label>
            <input type="text" name="destination" value="<?= htmlspecialchars(
                $destinationText,
            ) ?>" placeholder="Address or lat,lon" style="width:100%; padding:4px; font-size:11px; border:1px solid #ccc; box-sizing:border-box;">
        </div>

        <div style="margin:5px 0;">
            <label style="display:block; font-size:10px; margin-bottom:2px;">Mode:</label>
            <select name="profile" style="width:100%; padding:4px; font-size:11px; border:1px solid #ccc;">
                <option value="driving" <?= $profile === 'driving'
                    ? 'selected'
                    : '' ?>>Driving</option>
                <option value="walking" <?= $profile === 'walking'
                    ? 'selected'
                    : '' ?>>Walking</option>
                <option value="cycling" <?= $profile === 'cycling'
                    ? 'selected'
                    : '' ?>>Cycling</option>
                <option value="transit" <?= $profile === 'transit'
                    ? 'selected'
                    : '' ?>>Public Transit</option>
            </select>
        </div>

        <div style="text-align:center; margin:10px 0 5px 0;">
            <input type="submit" value="Calculate Route" style="padding:6px 12px; font-size:11px; background:#007bff; color:white; border:1px solid #007bff; cursor:pointer;">
        </div>
    </form>

    <?php if ($hasSearched && $route === null): ?>
        <p style="color:#999; font-size:10px; text-align:center; margin:5px 0;">Enter both locations to calculate a route.</p>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>