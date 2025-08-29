<?php
/**
 * Test Correct API Format with Template Name
 * Access: yourdomain.com/backend/test_correct_api.php?mobile=YOUR_MOBILE
 */

header('Content-Type: text/html; charset=UTF-8');

$mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';

if (empty($mobile)) {
    echo '<h2>Correct API Format Test</h2>';
    echo '<p>Usage: test_correct_api.php?mobile=YOUR_MOBILE_NUMBER</p>';
    echo '<p>Example: test_correct_api.php?mobile=9876543210</p>';
    exit;
}

// Validate mobile number
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    echo '<h2>❌ Invalid Mobile Number</h2>';
    echo '<p>Please enter a valid Indian mobile number (10 digits starting with 6-9)</p>';
    exit;
}

echo '<h2>🔐 Correct API Format Test</h2>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .debug { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>';

// Your 2factor.in credentials
$api_key = 'd4aae05a-7209-11f0-a562-0200cd936042';
$template_name = 'OTP Service';

// Generate test OTP
$test_otp = sprintf('%06d', mt_rand(0, 999999));

echo '<div class="debug">';
echo '<h3>Configuration</h3>';
echo '<p><strong>API Key:</strong> ' . substr($api_key, 0, 8) . '...</p>';
echo '<p><strong>Template Name:</strong> <span style="color: green; font-weight: bold;">' . $template_name . '</span></p>';
echo '<p><strong>Mobile:</strong> ' . $mobile . '</p>';
echo '<p><strong>Test OTP:</strong> ' . $test_otp . '</p>';
echo '</div>';

// Method 1: Correct API format with template name
echo '<div class="debug">';
echo '<h3>Method 1: Correct API Format (Template Name)</h3>';

$correct_url = "https://2factor.in/API/V1/$api_key/SMS/$mobile/$test_otp/" . urlencode($template_name);
echo '<p><strong>URL:</strong> ' . $correct_url . '</p>';
echo '<p><strong>Expected Format:</strong> /SMS/PHONE/OTP/TEMPLATE_NAME</p>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $correct_url);
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
    echo '<p class="success">✅ Correct API format successful!</p>';
    echo '<p class="info">📱 Expected Sender: <strong>NPCSRV</strong></p>';
    echo '<p class="info">📝 Expected Message: "NPC SERVICE : Your OTP for login is ' . $test_otp . '. It will be valid for 5 minutes. Do not share this code with anyone."</p>';
} else {
    echo '<p class="error">❌ Correct API format failed</p>';
    echo '<p class="info">🔍 Status: ' . $status . '</p>';
    echo '<p class="info">🔍 Details: ' . (isset($result['Details']) ? $result['Details'] : 'Unknown') . '</p>';
}
echo '</div>';

// Method 2: Old format with sender ID (for comparison)
echo '<div class="debug">';
echo '<h3>Method 2: Old Format (Sender ID) - For Comparison</h3>';

$sender_id = 'NPCSRV';
$old_url = "https://2factor.in/API/V1/$api_key/SMS/$mobile/$test_otp/$sender_id";
echo '<p><strong>URL:</strong> ' . $old_url . '</p>';
echo '<p><strong>Old Format:</strong> /SMS/PHONE/OTP/SENDER_ID</p>';

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $old_url);
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
    echo '<p class="success">✅ Old format also successful!</p>';
    echo '<p class="info">📱 Check which sender ID appears on phone</p>';
} else {
    echo '<p class="error">❌ Old format failed</p>';
}
echo '</div>';

echo '<div class="debug">';
echo '<h3>Expected Results</h3>';
echo '<p><strong>✅ Method 1 (Template Name):</strong></p>';
echo '<ul>';
echo '<li>Sender ID: <strong>NPCSRV</strong></li>';
echo '<li>Message: "NPC SERVICE : Your OTP for login is ' . $test_otp . '. It will be valid for 5 minutes. Do not share this code with anyone."</li>';
echo '<li>API Format: /SMS/PHONE/OTP/OTP+Service</li>';
echo '</ul>';
echo '<p><strong>❓ Method 2 (Sender ID):</strong></p>';
echo '<ul>';
echo '<li>May or may not work</li>';
echo '<li>API Format: /SMS/PHONE/OTP/NPCSRV</li>';
echo '</ul>';
echo '</div>';

echo '<div class="debug">';
echo '<h3>Next Steps</h3>';
echo '<p><strong>1. Check your phone</strong> - Which sender ID appears?</p>';
echo '<p><strong>2. Compare results</strong> - Method 1 should show NPCSRV</p>';
echo '<p><strong>3. Update system</strong> - Use the working method</p>';
echo '</div>';

echo '<hr>';
echo '<p><a href="?mobile=' . $mobile . '">🔄 Test Again</a> | <a href="?">🏠 Back to Test Page</a></p>';
?>
