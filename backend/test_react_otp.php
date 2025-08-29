<?php
/**
 * Test React OTP Flow
 * This simulates the exact request that React sends
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include required files
require 'conn.php';
require 'sms_integration.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    echo '<h2>🔍 React OTP Flow Test</h2>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .debug { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>';
    
    echo '<div class="debug">';
    echo '<h3>Step 1: Input Validation</h3>';
    echo '<p><strong>Raw Input:</strong> ' . json_encode($input) . '</p>';
    
    // Validate input
    if (empty($input['phone'])) {
        echo '<p class="error">❌ Phone number is missing</p>';
        exit;
    }
    
    $mobile_number = $input['phone'];
    echo '<p class="success">✅ Phone number: ' . $mobile_number . '</p>';
    
    // Remove country code if present
    $mobile_number = preg_replace('/^(\+91|91)/', '', $mobile_number);
    echo '<p class="info">📱 Cleaned mobile number: ' . $mobile_number . '</p>';
    
    // Validate mobile number format
    if (!preg_match('/^[6-9]\d{9}$/', $mobile_number)) {
        echo '<p class="error">❌ Invalid mobile number format</p>';
        exit;
    }
    echo '<p class="success">✅ Mobile number format is valid</p>';
    echo '</div>';
    
    echo '<div class="debug">';
    echo '<h3>Step 2: User Check</h3>';
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, customer_name FROM users WHERE mobile_number = ?");
    $stmt->bind_param("s", $mobile_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo '<p class="error">❌ No account found with this mobile number</p>';
        exit;
    }
    echo '<p class="success">✅ User found: ' . $user['customer_name'] . '</p>';
    echo '</div>';
    
    echo '<div class="debug">';
    echo '<h3>Step 3: OTP Generation</h3>';
    
    // Generate local OTP
    $local_otp = sprintf('%06d', mt_rand(0, 999999));
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    echo '<p class="info">🔢 Generated OTP: ' . $local_otp . '</p>';
    echo '<p class="info">⏰ Expiry: ' . $otp_expiry . '</p>';
    echo '</div>';
    
    echo '<div class="debug">';
    echo '<h3>Step 4: Database Update</h3>';
    
    // Update user with OTP
    $updateStmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ?, is_otp_verified = 0 WHERE mobile_number = ?");
    $updateStmt->bind_param("sss", $local_otp, $otp_expiry, $mobile_number);
    
    if ($updateStmt->execute()) {
        echo '<p class="success">✅ OTP stored in database</p>';
    } else {
        echo '<p class="error">❌ Failed to store OTP in database</p>';
        exit;
    }
    echo '</div>';
    
    echo '<div class="debug">';
    echo '<h3>Step 5: SMS Sending</h3>';
    
    // Test SMS configuration
    testSMSConfig();
    
    // Send SMS with OTP
    $sms_result = sendOTPSMS($mobile_number, $local_otp);
    echo '<p class="info">📱 SMS Result: ' . json_encode($sms_result) . '</p>';
    
    if ($sms_result === true || (is_string($sms_result) && strlen($sms_result) === 6)) {
        echo '<p class="success">✅ SMS sent successfully</p>';
        
        // Generate session ID
        $sessionId = bin2hex(random_bytes(16));
        echo '<p class="info">🔑 Session ID: ' . $sessionId . '</p>';
        
        // Store session mapping
        $sessionStmt = $conn->prepare("UPDATE users SET sessionid = ? WHERE mobile_number = ?");
        $sessionStmt->bind_param("ss", $sessionId, $mobile_number);
        $sessionStmt->execute();
        
        echo '<p class="success">✅ Session ID stored</p>';
        echo '</div>';
        
        echo '<div class="debug">';
        echo '<h3>Step 6: Response</h3>';
        
        $response = [
            'Status' => 'Success',
            'Details' => $sessionId,
            'message' => 'OTP sent successfully',
            'mobile_number' => $mobile_number,
            'debug' => 'SMS sent successfully using NPCSRV template',
            'otp' => $local_otp // For development only
        ];
        
        echo '<p class="success">✅ Response:</p>';
        echo '<pre>' . json_encode($response, JSON_PRETTY_PRINT) . '</pre>';
        echo '</div>';
        
        echo '<div class="debug">';
        echo '<h3>Step 7: React Expected Behavior</h3>';
        echo '<p>React should:</p>';
        echo '<ul>';
        echo '<li>✅ Receive Status: "Success"</li>';
        echo '<li>✅ Store sessionId: ' . $sessionId . '</li>';
        echo '<li>✅ Set showOTPInput to true</li>';
        echo '<li>✅ Show OTP input field</li>';
        echo '<li>✅ Display OTP: ' . $local_otp . ' (in console)</li>';
        echo '</ul>';
        echo '</div>';
        
    } else {
        echo '<p class="error">❌ SMS sending failed</p>';
        echo '<p class="info">📱 SMS Result: ' . json_encode($sms_result) . '</p>';
    }
    
    $updateStmt->close();
    $stmt->close();
    $sessionStmt->close();
} else {
    echo '<h2>React OTP Flow Test</h2>';
    echo '<p>This test simulates the React OTP request.</p>';
    echo '<p>Send a POST request with:</p>';
    echo '<pre>{ "phone": "8940645820" }</pre>';
}

$conn->close();
?>
