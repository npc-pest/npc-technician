<?php
/**
 * Simple log viewer for debugging SMS issues
 * Access this file directly in browser: yourdomain.com/backend/view_logs.php
 */

// Security: Only allow access from localhost or your IP
$allowed_ips = ['127.0.0.1', 'localhost', '::1']; // Add your IP here if needed

if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    die('Access denied. Add ?allow=1 to URL if you\'re the admin.');
}

echo "<h2>Recent Error Logs</h2>";
echo "<style>body{font-family:monospace;} .log{background:#f5f5f5;padding:10px;margin:10px 0;border-left:3px solid #007cba;}</style>";

// Read recent log entries
$log_content = '';

// Try different log file locations
$log_files = [
    'sms_debug.log',        // Our custom log file
    '../error_log',
    '../../error_log', 
    '/home/yourusername/public_html/error_log',
    'error_log'
];

foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        echo "<h3>Found log file: $log_file</h3>";
        $content = file_get_contents($log_file);
        $lines = explode("\n", $content);
        
        // Show last 50 lines
        $recent_lines = array_slice($lines, -50);
        
        foreach ($recent_lines as $line) {
            if (strpos($line, '2factor') !== false || strpos($line, 'SMS') !== false || strpos($line, 'OTP') !== false) {
                echo "<div class='log' style='color: blue;'>" . htmlspecialchars($line) . "</div>";
            } elseif (strpos($line, 'error') !== false || strpos($line, 'Error') !== false) {
                echo "<div class='log' style='color: red;'>" . htmlspecialchars($line) . "</div>";
            } elseif (!empty(trim($line))) {
                echo "<div class='log'>" . htmlspecialchars($line) . "</div>";
            }
        }
        break;
    }
}

if (empty($log_content)) {
    echo "<p>No log files found. Check cPanel Error Logs instead.</p>";
    echo "<p>Try these locations in File Manager:</p>";
    echo "<ul>";
    foreach ($log_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

echo "<hr><p><strong>Refresh this page after testing OTP to see new logs.</strong></p>";
?>