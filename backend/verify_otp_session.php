<?php
// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include required files
require 'conn.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($input['sessionId']) || empty($input['otp'])) {
        echo json_encode(['Status' => 'Error', 'Details' => 'Session ID and OTP are required']);
        exit;
    }
    
    $sessionId = $input['sessionId'];
    $otp = $input['otp'];
    $fcm_token = isset($input['fcm_token']) ? $input['fcm_token'] : 'web_token';
    
    // Debug: Log the verification attempt
    error_log("Session OTP Verification Attempt - SessionId: $sessionId, OTP: $otp");
    
    // Validate OTP format (6 digits)
    if (!preg_match('/^\d{6}$/', $otp)) {
        echo json_encode(['Status' => 'Error', 'Details' => 'OTP must be 6 digits']);
        exit;
    }
    
    // Find user by sessionId
    $stmt = $conn->prepare("SELECT user_id, customer_name, mobile_number, otp, otp_expiry, is_otp_verified FROM users WHERE sessionid = ?");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['Status' => 'Error', 'Details' => 'Invalid session ID']);
        exit;
    }
    
    // Debug logging
    error_log("Database OTP for session $sessionId: " . $user['otp']);
    error_log("Submitted OTP: $otp");
    error_log("OTP Match: " . ($user['otp'] === $otp ? 'YES' : 'NO'));
    error_log("OTP Expiry: " . $user['otp_expiry']);
    error_log("Already Verified: " . ($user['is_otp_verified'] ? 'YES' : 'NO'));
    
    // Verify OTP
    if ($user['otp'] !== $otp) {
        echo json_encode(['Status' => 'Error', 'Details' => 'Invalid OTP']);
        exit;
    }
    
    // Check if OTP is expired
    if (strtotime($user['otp_expiry']) < time()) {
        echo json_encode(['Status' => 'Error', 'Details' => 'OTP has expired. Please request a new one.']);
        exit;
    }
    
    // Check if OTP was already used
    if ($user['is_otp_verified']) {
        echo json_encode(['Status' => 'Error', 'Details' => 'OTP has already been used. Please request a new one.']);
        exit;
    }
    
    // OTP is valid, proceed with login
    $newSessionId = generateSessionId();
    $sessionExpiry = getSessionExpiry();
    
    // Update session and clear OTP
    $updateStmt = $conn->prepare("UPDATE users SET sessionid = ?, session_expiry = ?, fcm_token = ?, otp = NULL, otp_expiry = NULL, is_otp_verified = 1 WHERE user_id = ?");
    $updateStmt->bind_param("sssi", $newSessionId, $sessionExpiry, $fcm_token, $user['user_id']);
    
    if ($updateStmt->execute()) {
        // Get complete user data
        $userDataStmt = $conn->prepare("SELECT user_id, customer_name, mobile_number, email_id, profile_pic FROM users WHERE user_id = ?");
        $userDataStmt->bind_param("i", $user['user_id']);
        $userDataStmt->execute();
        $userDataResult = $userDataStmt->get_result();
        $userData = $userDataResult->fetch_assoc();
        
        echo json_encode([
            'Status' => 'Success',
            'Details' => 'OTP verified successfully',
            'sessionid' => $newSessionId,
            'session_expiry' => $sessionExpiry,
            'fcm_token' => $fcm_token,
            'data' => $userData
        ]);
        
        $userDataStmt->close();
    } else {
        echo json_encode(['Status' => 'Error', 'Details' => 'Login failed']);
    }
    
    $updateStmt->close();
    $stmt->close();
}

$conn->close();
?>
