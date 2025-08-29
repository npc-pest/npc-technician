<?php
/**
 * Direct NPCSRV Sender ID Test
 * Access: yourdomain.com/backend/test_npcsrv_direct.php?mobile=YOUR_MOBILE
 */

header('Content-Type: text/html; charset=UTF-8');

$mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';

if (empty($mobile)) {
    echo '<h2>NPCSRV Direct Test</h2>';
    echo '<p>Usage: test_npcsrv_direct.php?mobile=YOUR_MOBILE_NUMBER</p>';
    echo '<p>Example: test_npcsrv_direct.php?mobile=9876543210</p>';
    exit;
}

// Validate mobile number
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    echo '<h2>❌ Invalid Mobile Number</h2>';
    echo '<p>Please enter a valid Indian mobile number (10 digits starting with 6-9)</p>';
    exit;
}

echo '<h2>🔐 NPCSRV Direct Test</h2>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .debug { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>';

// Your 2factor.in credentials
$api_key = 'd4aae05a-7209-11f0-a562-0200cd936042';
$sender_id = 'NPCSRV'; // Using NPCSRV sender ID

// Generate test OTP
$test_otp = sprintf('%06d', mt_rand(0, 999999));

echo '<div class="debug">';
echo '<h3>Configuration</h3>';
echo '<p><strong>API Key:</strong> ' . substr($api_key, 0, 8) . '...</p>';
echo '<p><strong>Sender ID:</strong> <span style="color: green; font-weight: bold;">' . $sender_id . '</span></p>';
echo '<p><strong>Mobile:</strong> ' . $mobile . '</p>';
echo '<p><strong>Test OTP:</strong> ' . $test_otp . '</p>';
echo '</div>';

// Method 1: Direct template call with NPCSRV
echo '<div class="debug">';
echo '<h3>Method 1: Direct Template Call (NPCSRV)</h3>';

$template_url = "https://2factor.in/API/V1/$api_key/SMS/$mobile/$test_otp/$sender_id";
echo '<p><strong>URL:</strong> ' . $template_url . '</p>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $template_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo '<p><strong>HTTP Code:</strong> ' . $http_code . '</p>';
echo '<p><strong>Response:</strong> ' . $response . '</p>';
if ($curl_error) {
    echo '<p><strong>CURL Error:</strong> ' . $curl_error . '</p>';
}

$result = json_decode($response, true);
$status = isset($result['Status']) ? strtolower($result['Status']) : '';

if ($status === 'success' || $status === 'sent') {
    echo '<p class="success">✅ NPCSRV Template SMS sent successfully!</p>';
    echo '<p class="info">📱 Expected Sender: <strong>NPCSRV</strong></p>';
    echo '<p class="info">📝 Expected Message: "NPC SERVICE : Your OTP for login is ' . $test_otp . '. It will be valid for 5 minutes. Do not share this code with anyone."</p>';
} else {
    echo '<p class="error">❌ NPCSRV Template SMS failed</p>';
    echo '<p class="info">🔍 Status: ' . $status . '</p>';
    echo '<p class="info">🔍 Details: ' . (isset($result['Details']) ? $result['Details'] : 'Unknown') . '</p>';
}
echo '</div>';

// Method 2: AUTOGEN method (for comparison)
echo '<div class="debug">';
echo '<h3>Method 2: AUTOGEN Method (Comparison)</h3>';

$autogen_url = "https://2factor.in/API/V1/$api_key/SMS/$mobile/AUTOGEN/OTP1";
echo '<p><strong>URL:</strong> ' . $autogen_url . '</p>';

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $autogen_url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response2 = curl_exec($ch2);
$http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo '<p><strong>HTTP Code:</strong> ' . $http_code2 . '</p>';
echo '<p><strong>Response:</strong> ' . $response2 . '</p>';

$result2 = json_decode($response2, true);
$status2 = isset($result2['Status']) ? strtolower($result2['Status']) : '';

if ($status2 === 'success' || $status2 === 'sent') {
    echo '<p class="success">✅ AUTOGEN SMS sent successfully!</p>';
    echo '<p class="info">📱 Expected Sender: <strong>Generic (AX-SLVECH-S)</strong></p>';
    echo '<p class="info">📝 Expected Message: Generic OTP message</p>';
} else {
    echo '<p class="error">❌ AUTOGEN SMS failed</p>';
}
echo '</div>';

echo '<div class="debug">';
echo '<h3>Expected Results</h3>';
echo '<p><strong>✅ Method 1 (NPCSRV Template):</strong></p>';
echo '<ul>';
echo '<li>Sender ID: <strong>NPCSRV</strong></li>';
echo '<li>Message: "NPC SERVICE : Your OTP for login is ' . $test_otp . '. It will be valid for 5 minutes. Do not share this code with anyone."</li>';
echo '</ul>';
echo '<p><strong>❌ Method 2 (AUTOGEN):</strong></p>';
echo '<ul>';
echo '<li>Sender ID: <strong>AX-SLVECH-S</strong> (or similar generic ID)</li>';
echo '<li>Message: Generic OTP message</li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<p><a href="?mobile=' . $mobile . '">🔄 Test Again</a> | <a href="?">🏠 Back to Test Page</a></p>';
?>
