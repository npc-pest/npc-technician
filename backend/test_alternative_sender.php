<?php
/**
 * Test Alternative Sender IDs
 * Access: yourdomain.com/backend/test_alternative_sender.php?mobile=YOUR_MOBILE
 */

header('Content-Type: text/html; charset=UTF-8');

$mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';

if (empty($mobile)) {
    echo '<h2>Alternative Sender ID Test</h2>';
    echo '<p>Usage: test_alternative_sender.php?mobile=YOUR_MOBILE_NUMBER</p>';
    echo '<p>Example: test_alternative_sender.php?mobile=9876543210</p>';
    exit;
}

// Validate mobile number
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    echo '<h2>❌ Invalid Mobile Number</h2>';
    echo '<p>Please enter a valid Indian mobile number (10 digits starting with 6-9)</p>';
    exit;
}

echo '<h2>🔐 Alternative Sender ID Test</h2>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .debug { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>';

// Your 2factor.in credentials
$api_key = 'd4aae05a-7209-11f0-a562-0200cd936042';

// Generate test OTP
$test_otp = sprintf('%06d', mt_rand(0, 999999));

echo '<div class="debug">';
echo '<h3>Configuration</h3>';
echo '<p><strong>API Key:</strong> ' . substr($api_key, 0, 8) . '...</p>';
echo '<p><strong>Mobile:</strong> ' . $mobile . '</p>';
echo '<p><strong>Test OTP:</strong> ' . $test_otp . '</p>';
echo '</div>';

// Test different sender IDs
$sender_ids = ['NPCSRV', 'NPCOTP', 'NPC', 'NPCSVC', 'NPCPST'];

foreach ($sender_ids as $sender_id) {
    echo '<div class="debug">';
    echo '<h3>Testing Sender ID: ' . $sender_id . '</h3>';
    
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
    
    $result = json_decode($response, true);
    $status = isset($result['Status']) ? strtolower($result['Status']) : '';
    
    if ($status === 'success' || $status === 'sent') {
        echo '<p class="success">✅ SMS sent successfully with ' . $sender_id . '!</p>';
        echo '<p class="info">📱 Check your phone - sender should be: <strong>' . $sender_id . '</strong></p>';
    } else {
        echo '<p class="error">❌ Failed with ' . $sender_id . '</p>';
        echo '<p class="info">🔍 Status: ' . $status . '</p>';
        echo '<p class="info">🔍 Details: ' . (isset($result['Details']) ? $result['Details'] : 'Unknown') . '</p>';
    }
    echo '</div>';
}

// Test with template name
echo '<div class="debug">';
echo '<h3>Testing with Template Name</h3>';

$template_name = 'OTP Service';
$template_url = "https://2factor.in/API/V1/$api_key/SMS/$mobile/$test_otp/" . urlencode($template_name);
echo '<p><strong>URL:</strong> ' . $template_url . '</p>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $template_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo '<p><strong>HTTP Code:</strong> ' . $http_code . '</p>';
echo '<p><strong>Response:</strong> ' . $response . '</p>';

$result = json_decode($response, true);
$status = isset($result['Status']) ? strtolower($result['Status']) : '';

if ($status === 'success' || $status === 'sent') {
    echo '<p class="success">✅ SMS sent successfully with template name!</p>';
    echo '<p class="info">📱 Check your phone for sender ID</p>';
} else {
    echo '<p class="error">❌ Failed with template name</p>';
}
echo '</div>';

echo '<div class="debug">';
echo '<h3>Next Steps</h3>';
echo '<p><strong>1. Check your phone</strong> - Which sender ID appears?</p>';
echo '<p><strong>2. Contact 2factor.in</strong> - Ask them to verify your sender ID configuration</p>';
echo '<p><strong>3. Check dashboard</strong> - Verify sender ID approval status</p>';
echo '</div>';

echo '<hr>';
echo '<p><a href="?mobile=' . $mobile . '">🔄 Test Again</a> | <a href="?">🏠 Back to Test Page</a></p>';
?>
