<?php
/**
 * Test Auto-Generated OTP System
 * Access: yourdomain.com/backend/test_autogen_otp.php?mobile=YOUR_MOBILE
 */

header('Content-Type: text/html; charset=UTF-8');

// Include required files
require 'conn.php';
require 'sms_integration.php';

$mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';

if (empty($mobile)) {
    echo '<h2>Auto-Generated OTP Test</h2>';
    echo '<p>Usage: test_autogen_otp.php?mobile=YOUR_MOBILE_NUMBER</p>';
    echo '<p>Example: test_autogen_otp.php?mobile=9876543210</p>';
    exit;
}

// Validate mobile number
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    echo '<h2>❌ Invalid Mobile Number</h2>';
    echo '<p>Please enter a valid Indian mobile number (10 digits starting with 6-9)</p>';
    exit;
}

echo '<h2>🔐 Auto-Generated OTP Test</h2>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .debug { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .step { background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>';

echo '<div class="step">';
echo '<h3>Step 1: Configuration Check</h3>';

global $TWOFACTOR_CONFIG;
echo '<p><strong>API Key:</strong> ' . substr($TWOFACTOR_CONFIG['api_key'], 0, 8) . '...</p>';
echo '<p><strong>Sender ID:</strong> ' . $TWOFACTOR_CONFIG['sender_id'] . '</p>';
echo '<p><strong>Template:</strong> ' . $TWOFACTOR_CONFIG['template_name'] . '</p>';

// Test balance
$test_url = "https://2factor.in/API/V1/" . $TWOFACTOR_CONFIG['api_key'] . "/BAL/SMS";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$balance_result = json_decode($response, true);
$balance = isset($balance_result['Details']) ? $balance_result['Details'] : 'Unknown';

echo '<p><strong>Balance:</strong> ' . $balance . ' SMS credits</p>';
echo '</div>';

echo '<div class="step">';
echo '<h3>Step 2: Testing Auto-Generated OTP</h3>';
echo '<p><strong>Mobile Number:</strong> ' . $mobile . '</p>';

// Test auto-generated OTP
echo '<p class="info">🔄 Calling 2factor.in AUTOGEN endpoint...</p>';

$result = sendOTPVia2Factor($mobile);

if (is_string($result) && strlen($result) === 6) {
    echo '<p class="success">✅ Auto-generated OTP received: <strong>' . $result . '</strong></p>';
    echo '<p class="info">📱 SMS should be sent to: ' . $mobile . '</p>';
    echo '<p class="info">📝 Message: "NPC SERVICE : Your OTP for login is ' . $result . '. It will be valid for 5 minutes. Do not share this code with anyone."</p>';
    
    // Store OTP in database for testing
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ?, is_otp_verified = 0 WHERE mobile_number = ?");
    $stmt->bind_param("sss", $result, $otp_expiry, $mobile);
    $stmt->execute();
    $stmt->close();
    
    echo '<p class="success">💾 OTP stored in database for verification testing</p>';
    
} elseif ($result === true) {
    echo '<p class="success">✅ SMS sent successfully (using local OTP)</p>';
    echo '<p class="info">📱 Check your phone for SMS</p>';
    
} else {
    echo '<p class="error">❌ SMS sending failed</p>';
    echo '<p class="info">🔍 Check the debug logs for more details</p>';
}

echo '</div>';

echo '<div class="step">';
echo '<h3>Step 3: Verification Test</h3>';
echo '<p>To test OTP verification, use:</p>';
echo '<div class="debug">';
echo '<strong>POST /verify_otp.php</strong><br>';
echo '<strong>Body:</strong> {<br>';
echo '&nbsp;&nbsp;"mobile_number": "' . $mobile . '",<br>';
echo '&nbsp;&nbsp;"otp": "' . (is_string($result) ? $result : 'XXXXXX') . '"<br>';
echo '}';
echo '</div>';
echo '</div>';

echo '<div class="step">';
echo '<h3>Step 4: Debug Information</h3>';
echo '<p><a href="debug_sms.php?test=logs" target="_blank">📋 View SMS Debug Logs</a></p>';
echo '<p><a href="debug_sms.php?test=config" target="_blank">⚙️ View Configuration</a></p>';
echo '</div>';

echo '<hr>';
echo '<p><a href="?mobile=' . $mobile . '">🔄 Test Again</a> | <a href="?">🏠 Back to Test Page</a></p>';

$conn->close();
?>
