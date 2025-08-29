<?php
/**
 * Simple SMS test - bypassing all templates
 * Access: yourdomain.com/backend/test_simple_sms.php?mobile=8940645820&msg=test123
 */

if (!isset($_GET['mobile']) || !isset($_GET['msg'])) {
    die('Usage: test_simple_sms.php?mobile=8940645820&msg=test123');
}

$mobile = $_GET['mobile'];
$message = $_GET['msg'];

// Your 2factor.in credentials
$api_key = 'd4aae05a-7209-11f0-a562-0200cd936042';
$sender_id = 'NPCOTP';

// Use EXACT approved template text
$safe_message = "$message is your OTP for NPC Pest Control login. Valid for 10 minutes.";

// Simple SMS API call (no templates, no OTP endpoints)
$url = "https://2factor.in/API/V1/$api_key/SMS/$mobile/" . rawurlencode($safe_message) . "/$sender_id";

echo "<h2>Simple SMS Test</h2>";
echo "<p><strong>URL:</strong> $url</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $http_code</p>";
echo "<p><strong>Response:</strong> $response</p>";

$result = json_decode($response, true);
if (isset($result['status']) && strtolower($result['status']) === 'success') {
    echo "<p style='color: green;'><strong>✅ SMS should be sent!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ SMS failed</strong></p>";
}

echo "<hr>";
echo "<p>Check your phone for SMS: <strong>$message</strong></p>";
?>