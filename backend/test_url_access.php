<?php
// Test URL access for technician status update API

header('Content-Type: text/plain; charset=utf-8');

echo "=== URL Access Test ===\n\n";

echo "Current script: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Server name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

echo "Testing different URL patterns:\n";
echo "==============================\n";

$testUrls = [
    'https://npcpest.com/npc/backend/technician_status_update.php',
    'https://npcpest.com/npc/technician_status_update.php',
    'https://npcpest.com/backend/technician_status_update.php',
    'https://npcpest.com/technician_status_update.php'
];

foreach ($testUrls as $url) {
    echo "Testing: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Only get headers
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  ❌ cURL Error: $error\n";
    } else {
        echo "  HTTP Code: $httpCode\n";
        if ($httpCode === 200) {
            echo "  ✅ URL accessible\n";
        } else {
            echo "  ❌ URL not accessible\n";
        }
    }
    echo "  ---\n";
}

echo "\nChecking file existence:\n";
echo "=======================\n";

$filePaths = [
    'technician_status_update.php',
    '../technician_status_update.php',
    '../../technician_status_update.php',
    './technician_status_update.php'
];

foreach ($filePaths as $path) {
    if (file_exists($path)) {
        echo "✅ File exists: $path\n";
    } else {
        echo "❌ File not found: $path\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
