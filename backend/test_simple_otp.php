<?php
/**
 * Simple OTP Test - Returns JSON response
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
    
    // Validate input
    if (empty($input['phone'])) {
        echo json_encode(['Status' => 'Error', 'Details' => 'Phone number is required']);
        exit;
    }
    
    $mobile_number = $input['phone'];
    
    // Remove country code if present
    $mobile_number = preg_replace('/^(\+91|91)/', '', $mobile_number);
    
    // Validate mobile number format
    if (!preg_match('/^[6-9]\d{9}$/', $mobile_number)) {
        echo json_encode(['Status' => 'Error', 'Details' => 'Invalid mobile number format']);
        exit;
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, customer_name FROM users WHERE mobile_number = ?");
    $stmt->bind_param("s", $mobile_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['Status' => 'Error', 'Details' => 'No account found with this mobile number']);
        exit;
    }
    
    // Generate OTP
    $local_otp = sprintf('%06d', mt_rand(0, 999999));
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Update user with OTP
    $updateStmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ?, is_otp_verified = 0 WHERE mobile_number = ?");
    $updateStmt->bind_param("sss", $local_otp, $otp_expiry, $mobile_number);
    
    if ($updateStmt->execute()) {
        // Send SMS
        $sms_result = sendOTPSMS($mobile_number, $local_otp);
        
        if ($sms_result === true || (is_string($sms_result) && strlen($sms_result) === 6)) {
            // Generate session ID
            $sessionId = bin2hex(random_bytes(16));
            
            // Store session mapping
            $sessionStmt = $conn->prepare("UPDATE users SET sessionid = ? WHERE mobile_number = ?");
            $sessionStmt->bind_param("ss", $sessionId, $mobile_number);
            $sessionStmt->execute();
            
            $response = [
                'Status' => 'Success',
                'Details' => $sessionId,
                'message' => 'OTP sent successfully',
                'mobile_number' => $mobile_number,
                'debug' => 'SMS sent successfully',
                'otp' => $local_otp // For development only
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['Status' => 'Error', 'Details' => 'Failed to send SMS']);
        }
        
        $sessionStmt->close();
    } else {
        echo json_encode(['Status' => 'Error', 'Details' => 'Failed to store OTP']);
    }
    
    $updateStmt->close();
    $stmt->close();
} else {
    echo json_encode(['Status' => 'Error', 'Details' => 'Invalid request method']);
}

$conn->close();
?>
