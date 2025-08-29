<?php
/**
 * Direct SMS testing and debugging tool
 * Access: yourdomain.com/backend/debug_sms.php
 */

// Include SMS integration
require 'sms_integration.php';

// Security check
if (!isset($_GET['test']) && !isset($_POST['mobile'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>SMS Debug Tool</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .debug { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .success { background: #d4edda; color: #155724; }
            .error { background: #f8d7da; color: #721c24; }
            input, button { padding: 10px; margin: 5px; font-size: 16px; }
            button { background: #007cba; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>SMS Debug Tool</h1>
        
        <h2>Test SMS Configuration</h2>
        <form method="POST">
            <input type="text" name="mobile" placeholder="Enter mobile number (e.g., 8940645820)" required>
            <input type="text" name="otp" placeholder="Enter test OTP (e.g., 123456)" value="123456">
            <br>
            <button type="submit">Test SMS</button>
        </form>
        
        <h2>Check Configuration</h2>
        <a href="?test=config"><button type="button">Check API Configuration</button></a>
        
        <h2>View Logs</h2>
        <a href="?test=logs"><button type="button">View SMS Debug Logs</button></a>
    </body>
    </html>
    <?php
    exit;
}

// Test configuration
if (isset($_GET['test']) && $_GET['test'] === 'config') {
    echo "<h2>Configuration Check</h2>";
    echo "<div class='debug'>";
    
    global $TWOFACTOR_CONFIG;
    echo "API Key: " . substr($TWOFACTOR_CONFIG['api_key'], 0, 8) . "...<br>";
    echo "Sender ID: " . $TWOFACTOR_CONFIG['sender_id'] . "<br>";
    
    // Test balance API
    $test_url = "https://2factor.in/API/V1/" . $TWOFACTOR_CONFIG['api_key'] . "/BAL/SMS";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Balance Check URL: " . $test_url . "<br>";
    echo "HTTP Code: " . $http_code . "<br>";
    echo "Response: " . $response . "<br>";
    
    echo "</div>";
    echo "<a href='?'><button>Back</button></a>";
    exit;
}

// View logs
if (isset($_GET['test']) && $_GET['test'] === 'logs') {
    echo "<h2>SMS Debug Logs</h2>";
    
    if (file_exists('sms_debug.log')) {
        $content = file_get_contents('sms_debug.log');
        echo "<div class='debug'><pre>" . htmlspecialchars($content) . "</pre></div>";
    } else {
        echo "<div class='error'>No log file found yet. Try sending an OTP first.</div>";
    }
    
    echo "<a href='?'><button>Back</button></a>";
    exit;
}

// Test SMS sending
if (isset($_POST['mobile']) && isset($_POST['otp'])) {
    $mobile = $_POST['mobile'];
    $otp = $_POST['otp'];
    
    echo "<h2>SMS Test Results</h2>";
    
    // Test SMS sending
    $result = sendOTPSMS($mobile, $otp);
    
    if ($result) {
        echo "<div class='success'>SMS sent successfully!</div>";
    } else {
        echo "<div class='error'>SMS sending failed!</div>";
    }
    
    // Show debug info
    if (file_exists('sms_debug.log')) {
        $content = file_get_contents('sms_debug.log');
        $lines = explode("\n", $content);
        $recent_lines = array_slice($lines, -10); // Last 10 lines
        
        echo "<h3>Recent Debug Info:</h3>";
        echo "<div class='debug'><pre>" . htmlspecialchars(implode("\n", $recent_lines)) . "</pre></div>";
    }
    
    echo "<a href='?'><button>Back</button></a>";
}
?>