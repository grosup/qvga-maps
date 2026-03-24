<?php
/**
 * Nokia 225 Maps - Requirements Checker
 * Comprehensive diagnostic tool for shared hosting environments
 * Tests PHP modules, hosting features, and Mapbox API connectivity
 *
 * Upload to your hosting and run via browser
 */

header('Content-Type: text/html; charset=utf-8');

// Handle token submission
$submittedToken = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenValid = false;
$tokenMessage = '';
$tokenDetails = [];

if (!empty($submittedToken)) {
    // Store token in session for this check
    session_start();
    $_SESSION['test_token'] = $submittedToken;

    // Validate token format
    if (strpos($submittedToken, 'pk.') === 0) {
        $tokenValid = true;
        $tokenMessage = 'Public token format OK (pk.*)';

        // Test token with API
        $testUrl = "https://api.mapbox.com/geocoding/v5/mapbox.places/13.40,52.52.json?access_token=" . urlencode($submittedToken) . "&limit=1";
        $context = stream_context_create([
            'http' => ['timeout' => 10, 'follow_location' => true],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);

        $response = @file_get_contents($testUrl, false, $context);

        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['features']) && count($data['features']) > 0) {
                $tokenDetails['geocoding'] = 'Working';
                $tokenDetails['place_name'] = $data['features'][0]['place_name'] ?? 'Unknown';
            } else {
                $tokenDetails['geocoding'] = 'No results';
            }

            // Test styles endpoint
            $stylesUrl = "https://api.mapbox.com/styles/v1/mapbox?access_token=" . urlencode($submittedToken);
            $stylesResponse = @file_get_contents($stylesUrl, false, $context);
            if ($stylesResponse !== false && json_decode($stylesResponse) !== null) {
                $tokenDetails['styles'] = 'Working';
            } else {
                $tokenDetails['styles'] = 'Failed';
            }
        } else {
            $tokenDetails['error'] = 'API request failed';
        }
    } elseif (strpos($submittedToken, 'sk.') === 0) {
        $tokenValid = false;
        $tokenMessage = 'Using secret token - should use public token (pk.*)';
    } else {
        $tokenValid = false;
        $tokenMessage = 'Invalid token format';
    }
}

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Nokia 225 Maps - Requirements Check</title>\n";
echo "<meta charset=\"UTF-8\">\n";
echo "<style>\n";
echo "body { font-family: monospace; font-size: 12px; margin: 20px; background: #f5f5f5; }\n";
echo ".header { background: #007bff; color: white; padding: 10px; margin: -20px -20px 20px -20px; }\n";
echo ".test { background: white; padding: 10px; margin: 10px 0; border-left: 4px solid #ccc; }\n";
echo ".pass { border-left-color: #28a745; background: #d4edda; }\n";
echo ".fail { border-left-color: #dc3545; background: #f8d7da; }\n";
echo ".warn { border-left-color: #ffc107; background: #fff3cd; }\n";
echo ".info { font-size: 11px; color: #666; margin-top: 5px; }\n";
echo ".summary { background: #e7f3ff; padding: 15px; margin: 20px 0; border-radius: 5px; }\n";
echo ".token-form { background: #fff3cd; padding: 15px; margin: 10px 0; border: 1px solid #ffc107; }\n";
echo ".token-input { width: 100%; padding: 8px; font-size: 12px; border: 1px solid #ccc; font-family: monospace; }\n";
echo ".token-submit { padding: 8px 16px; background: #007bff; color: white; border: none; cursor: pointer; }\n";
echo ".token-result { margin-top: 10px; padding: 10px; background: white; }\n";
echo ".token-display { background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; font-family: monospace; font-size: 11px; }\n";
echo "</style>\n</head>\n<body>\n";

$results = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function test($name, $condition, $details = '') {
    global $results, $totalTests, $passedTests, $failedTests;
    $totalTests++;
    $status = $condition ? 'PASS' : 'FAIL';

    if ($condition) {
        $passedTests++;
        $class = 'pass';
    } else {
        $failedTests++;
        $class = 'fail';
    }

    $results[] = [
        'name' => $name,
        'status' => $status,
        'details' => $details,
        'class' => $class
    ];

    echo "<div class=\"test $class\">\n";
    echo "  <strong>[$status]</strong> $name\n";
    if ($details) {
        echo "  <div class=\"info\">$details</div>\n";
    }
    echo "</div>\n";
}

echo "<div class=\"header\">\n";
echo "  <h1>📱 Nokia 225 Maps - Requirements Check</h1>\n";
echo "  <p>Testing hosting environment compatibility</p>\n";
echo "</div>\n";

// =====================================================
// SECTION: Token Input
// =====================================================

echo "<h2>🔑 Mapbox Token Verification</h2>\n";

if (empty($submittedToken)) {
    echo "<div class=\"token-form\">\n";
    echo "  <form method=\"POST\" action=\"\">\n";
    echo "    <p><strong>Enter your Mapbox token to test:</strong></p>\n";
    echo "    <input type=\"text\" name=\"token\" class=\"token-input\" placeholder=\"pk.eyJ1...\">\n";
    echo "    <br><br>\n";
    echo "    <button type=\"submit\" class=\"token-submit\">Verify Token</button>\n";
    echo "  </form>\n";
    echo "  <div class=\"info\">Get token at: https://account.mapbox.com/access-tokens/</div>\n";
    echo "</div>\n";
} else {
    echo "<div class=\"token-form\">\n";
    echo "  <strong>Token Test:</strong>\n";
    test("Token Format", $tokenValid, $tokenMessage);

    if (!empty($tokenDetails)) {
        echo "  <div class=\"token-result\">\n";
        foreach ($tokenDetails as $key => $value) {
            echo "    <div class=\"info\">" . ucfirst($key) . ": $value</div>\n";
        }
        echo "  </div>\n";
    }

    echo "  <div style=\"margin-top: 10px;\">\n";
    echo "    <a href=\"?\" style=\"color: #007bff;\">Test with different token</a>\n";
    echo "  </div>\n";
    echo "</div>\n";
}

// =====================================================
// SECTION 1: PHP Version & Core Requirements
// =====================================================

echo "<h2>1️⃣ PHP Version & Core Requirements</h2>\n";

$phpVersion = PHP_VERSION;
$phpVersionOK = version_compare(PHP_VERSION, '7.4.0', '>=');
test("PHP Version", $phpVersionOK, "Current: $phpVersion (Required: 7.4+)");

// Check required extensions
$requiredExtensions = ['json', 'session', 'curl'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    test("Extension: $ext", $loaded, $loaded ? "Loaded" : "NOT LOADED");
}

$memoryLimit = ini_get('memory_limit');
$memoryLimitBytes = parseSize($memoryLimit);
$memoryOK = $memoryLimitBytes >= 32 * 1024 * 1024;
test("Memory Limit", $memoryOK, "Current: $memoryLimit (Required: 32M+), Actual: " . round($memoryLimitBytes / 1024 / 1024) . "M");

// =====================================================
// SECTION 2: File System & Permissions
// =====================================================

echo "<h2>2️⃣ File System & Permissions</h2>\n";

$cacheDir = dirname(__DIR__) . '/cache';
$cacheExists = is_dir($cacheDir);
test("Cache Directory Exists", $cacheExists, $cacheExists ? "$cacheDir" : "Directory does not exist");

if ($cacheExists) {
    $cacheWritable = is_writable($cacheDir);
    test("Cache Directory Writable", $cacheWritable, $cacheWritable ? "Permissions OK" : "Not writable - set to 755");
} else {
    test("Cache Directory Writable", false, "Directory does not exist");
}

// =====================================================
// SECTION 3: Network & API Connectivity
// =====================================================

echo "<h2>3️⃣ Network & API Connectivity</h2>\n";

$allowUrlFopen = ini_get('allow_url_fopen');
test("allow_url_fopen", $allowUrlFopen, $allowUrlFopen ? "ENABLED - Required for API calls" : "DISABLED - Cannot make API calls!");

if ($allowUrlFopen && !empty($submittedToken)) {
    // Test API connectivity in detail
    $publicUrl = "https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/13.40,52.52,14,0/300x200?access_token=" . urlencode($submittedToken);

    echo "<div class=\"test\"><strong>[TEST]</strong> Testing Mapbox Static Images API...<br>\n";
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'follow_location' => true],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);

    $start = microtime(true);
    $response = @file_get_contents($publicUrl, false, $context);
    $duration = microtime(true) - $start;

    if ($response !== false && strlen($response) > 1000) {
        echo "<div class=\"test pass\">\n";
        echo "  <strong>[PASS]</strong> Static Images API - OK (" . strlen($response) . " bytes, " . number_format($duration, 2) . "s)\n";
        echo "</div>\n";
        $passedTests++;
    } else {
        echo "<div class=\"test fail\">\n";
        echo "  <strong>[FAIL]</strong> Static Images API - Failed\n";
        echo "</div>\n";
        $failedTests++;
    }
    $totalTests++;
}

// =====================================================
// SECTION 4: Session Configuration
// =====================================================

echo "<h2>4️⃣ Session Configuration</h2>\n";

$sessionEnabled = extension_loaded('session');
test("Session Extension", $sessionEnabled, $sessionEnabled ? "Session enabled" : "Session disabled");

if ($sessionEnabled) {
    $sessionPath = session_save_path() ?: sys_get_temp_dir();
    $sessionWritable = is_writable($sessionPath);
    test("Session Save Path", $sessionWritable, $sessionWritable ? "$sessionPath" : "$sessionPath is not writable");
}

// =====================================================
// SECTION 5: Image Processing
// =====================================================

echo "<h2>5️⃣ Image Processing</h2>\n";

$gdLoaded = extension_loaded('gd');
test("GD Extension", $gdLoaded, $gdLoaded ? "Error map generation available" : "Required for placeholder images");

// =====================================================
// SECTION 6: Summary
// =====================================================

echo "<h2>6️⃣ Summary</h2>\n";

$percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;

if ($percentage >= 90) {
    $summaryClass = 'summary pass';
    $status = '🟢 READY FOR DEPLOYMENT';
} elseif ($percentage >= 70) {
    $summaryClass = 'summary warn';
    $status = '🟡 MOSTLY READY - See warnings below';
} else {
    $summaryClass = 'summary fail';
    $status = '🔴 NOT READY - Fix issues below';
}

echo "<div class=\"$summaryClass\">\n";
echo "  <h2>$status</h2>\n";
echo "  <p><strong>Test Results:</strong> $passedTests / $totalTests passed ($percentage%)</p>\n";

if ($failedTests > 0) {
    echo "  <p><strong>Required Fixes:</strong></p>\n";
    echo "  <ul>\n";
    foreach ($results as $result) {
        if ($result['status'] === 'FAIL') {
            echo "    <li>{$result['name']}: {$result['details']}</li>\n";
        }
    }
    echo "  </ul>\n";
}

echo "</div>\n";

// Debug info at bottom
echo "<div style=\"margin-top: 30px; font-size: 10px; color: #999;\">\n";
echo "  <hr>\n";
echo "  <p>Server: " . $_SERVER['SERVER_SOFTWARE'] . " | PHP: " . PHP_VERSION . " | Time: " . date('Y-m-d H:i:s') . "</p>\n";
echo "</div>\n";

echo "</body></html>\n";

/**
 * Helper function to parse PHP size values
 */
function parseSize($size) {
    $size = strtolower(trim($size));
    $bytes = (int) $size;
    if (strpos($size, 'k') !== false) $bytes *= 1024;
    elseif (strpos($size, 'm') !== false) $bytes *= 1024 * 1024;
    elseif (strpos($size, 'g') !== false) $bytes *= 1024 * 1024 * 1024;
    return $bytes;
}
?>
