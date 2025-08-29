<?php
// Check server directory structure

header('Content-Type: text/plain; charset=utf-8');

echo "=== Server Directory Structure Check ===\n\n";

echo "Current script location: " . __FILE__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n\n";

echo "Step 1: Checking if technician_status_update_final.php exists in different locations...\n";
echo "================================================================================\n";

$possiblePaths = [
    'technician_status_update_final.php',
    'backend/technician_status_update_final.php',
    'npc/technician_status_update_final.php',
    '../technician_status_update_final.php',
    '../../technician_status_update_final.php'
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        echo "✅ Found: $path\n";
        echo "   Full path: " . realpath($path) . "\n";
    } else {
        echo "❌ Not found: $path\n";
    }
}

echo "\nStep 2: Checking current directory contents...\n";
echo "=============================================\n";

$currentDir = dirname(__FILE__);
echo "Current directory: $currentDir\n";
echo "Contents:\n";

$files = scandir($currentDir);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $fullPath = $currentDir . '/' . $file;
        if (is_dir($fullPath)) {
            echo "📁 $file/\n";
        } else {
            echo "📄 $file\n";
        }
    }
}

echo "\nStep 3: Checking parent directory...\n";
echo "==================================\n";

$parentDir = dirname($currentDir);
echo "Parent directory: $parentDir\n";
echo "Contents:\n";

if (is_dir($parentDir)) {
    $files = scandir($parentDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $parentDir . '/' . $file;
            if (is_dir($fullPath)) {
                echo "📁 $file/\n";
            } else {
                echo "📄 $file\n";
            }
        }
    }
} else {
    echo "❌ Parent directory not accessible\n";
}

echo "\n=== Check Complete ===\n";
?>
