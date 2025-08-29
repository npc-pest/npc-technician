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
    if (empty($input['mobile_number']) || empty($input['otp'])) {
        echo json_encode(['status' => 'error', 'message' => 'Mobile number and OTP are required']);
        exit;
    }
    
    $mobile_number = $input['mobile_number'];
    $otp = $input['otp'];
    $fcm_token = isset($input['fcm_token']) ? $input['fcm_token'] : 'web_token';
    
    // Debug: Log the verification attempt
    error_log("OTP Verification Attempt - Mobile: $mobile_number, OTP: $otp");
    
    // Validate OTP format (6 digits)
    if (!preg_match('/^\d{6}$/', $otp)) {
        echo json_encode(['status' => 'error', 'message' => 'OTP must be 6 digits']);
        exit;
    }
    
    // First, let's check what OTP is actually stored in the database
    $debugStmt = $conn->prepare("SELECT user_id, customer_name, otp, otp_expiry, is_otp_verified FROM users WHERE mobile_number = ?");
    $debugStmt->bind_param("s", $mobile_number);
    $debugStmt->execute();
    $debugResult = $debugStmt->get_result();
    $debugUser = $debugResult->fetch_assoc();
    
    if ($debugUser) {
        error_log("Database OTP for $mobile_number: " . $debugUser['otp']);
        error_log("Submitted OTP: $otp");
        error_log("OTP Match: " . ($debugUser['otp'] === $otp ? 'YES' : 'NO'));
        error_log("OTP Expiry: " . $debugUser['otp_expiry']);
        error_log("Already Verified: " . ($debugUser['is_otp_verified'] ? 'YES' : 'NO'));
        
        // Check if this is a new user from session
        session_start();
        $isNewUser = isset($_SESSION['new_user_' . $mobile_number]) && $_SESSION['new_user_' . $mobile_number];
        if ($isNewUser) {
            // Clear the session flag after use
            unset($_SESSION['new_user_' . $mobile_number]);
        }
    } else {
        error_log("No user found for mobile number: $mobile_number");
        echo json_encode(['status' => 'error', 'message' => 'No account found with this mobile number']);
        exit;
    }
    
    // Verify OTP
    $stmt = $conn->prepare("SELECT user_id, customer_name, otp, otp_expiry FROM users WHERE mobile_number = ? AND otp = ?");
    $stmt->bind_param("ss", $mobile_number, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
        exit;
    }
    
    // Check if OTP is expired
    if (strtotime($user['otp_expiry']) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new one.']);
        exit;
    }
    
    // Check if OTP was already used
    if ($debugUser['is_otp_verified']) {
        echo json_encode(['status' => 'error', 'message' => 'OTP has already been used. Please request a new one.']);
        exit;
    }
    
    // OTP is valid, proceed with login
    $sessionId = generateSessionId();
    $sessionExpiry = getSessionExpiry();
    
    // Update session and clear OTP
    $updateStmt = $conn->prepare("UPDATE users SET sessionid = ?, session_expiry = ?, fcm_token = ?, otp = NULL, otp_expiry = NULL, is_otp_verified = 1 WHERE user_id = ?");
    $updateStmt->bind_param("sssi", $sessionId, $sessionExpiry, $fcm_token, $user['user_id']);
    
    if ($updateStmt->execute()) {
        // Get complete user data
        $userDataStmt = $conn->prepare("SELECT user_id, customer_name, mobile_number, email_id, profile_pic FROM users WHERE user_id = ?");
        $userDataStmt->bind_param("i", $user['user_id']);
        $userDataStmt->execute();
        $userDataResult = $userDataStmt->get_result();
        $userData = $userDataResult->fetch_assoc();
        
        $message = $isNewUser ? 'Account created and login successful' : 'Login successful';
        
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'sessionid' => $sessionId,
            'session_expiry' => $sessionExpiry,
            'fcm_token' => $fcm_token,
            'is_new_user' => $isNewUser,
            'data' => $userData
        ]);
        
        $userDataStmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Login failed']);
    }
    
    $updateStmt->close();
    $stmt->close();
}

$conn->close();
?> 