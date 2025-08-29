<?php
// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include required files
require 'conn.php';
require 'functions.php';
require 'sms_integration.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($input['phone']) && empty($input['mobile_number'])) {
            echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
            exit;
        }
        
        // Support both 'phone' and 'mobile_number' parameters for React compatibility
        $mobile_number = isset($input['phone']) ? $input['phone'] : $input['mobile_number'];
        
        // Remove country code if present (e.g., +91, 91)
        $mobile_number = preg_replace('/^(\+91|91)/', '', $mobile_number);
        
        // Validate Indian mobile number format
        if (!preg_match('/^[6-9]\d{9}$/', $mobile_number)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid Indian mobile number']);
            exit;
        }
        
        error_log("Processing OTP request for mobile: $mobile_number");
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, customer_name FROM users WHERE mobile_number = ?");
        $stmt->bind_param("s", $mobile_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $isNewUser = !$user;
        error_log("Is new user: " . ($isNewUser ? 'YES' : 'NO'));
        
        // If user doesn't exist, create a new account
        if ($isNewUser) {
            $customer_name = "User_" . substr($mobile_number, -4); // Generate a default name
            $email_id = $mobile_number . "@npc.com"; // Generate a default email
            $default_password = password_hash($mobile_number, PASSWORD_DEFAULT); // Use mobile number as default password
            $default_address = "Address to be updated"; // Default address
            
            error_log("Creating new user with name: $customer_name, email: $email_id");
            
            $createUserStmt = $conn->prepare("INSERT INTO users (mobile_number, customer_name, email_id, password, address1) VALUES (?, ?, ?, ?, ?)");
            $createUserStmt->bind_param("sssss", $mobile_number, $customer_name, $email_id, $default_password, $default_address);
            
            if ($createUserStmt->execute()) {
                $user_id = $conn->insert_id;
                $user = ['user_id' => $user_id, 'customer_name' => $customer_name];
                error_log("New user created successfully: $mobile_number, user_id: $user_id");
                
                // Store in session that this is a new user
                session_start();
                $_SESSION['new_user_' . $mobile_number] = true;
            } else {
                error_log("Failed to create user: " . $conn->error . " for mobile: $mobile_number");
                echo json_encode(['status' => 'error', 'message' => 'Failed to create account: ' . $conn->error]);
                exit;
            }
            $createUserStmt->close();
        }
        
        // Generate local OTP as fallback (6-digit)
        $local_otp = sprintf('%06d', mt_rand(0, 999999));
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes')); // OTP valid for 5 minutes (matching template)
        
        error_log("Generated OTP: $local_otp, expires: $otp_expiry");
        
        // Update user with local OTP first
        $updateStmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ?, is_otp_verified = 0 WHERE mobile_number = ?");
        $updateStmt->bind_param("sss", $local_otp, $otp_expiry, $mobile_number);
        
        if ($updateStmt->execute()) {
            error_log("OTP updated in database successfully");
            
            // Test SMS configuration first
            testSMSConfig();
            
            // Send SMS with our OTP using NPCSRV template (pass OTP to use template method)
            $sms_result = sendOTPSMS($mobile_number, $local_otp);
            
            // Check if 2factor returned a generated OTP (fallback case)
            if (is_string($sms_result) && strlen($sms_result) === 6) {
                // 2factor generated the OTP, update our database with it
                $generated_otp = $sms_result;
                $updateOtpStmt = $conn->prepare("UPDATE users SET otp = ? WHERE mobile_number = ?");
                $updateOtpStmt->bind_param("ss", $generated_otp, $mobile_number);
                $updateOtpStmt->execute();
                $updateOtpStmt->close();
                
                // Generate a session ID for React frontend
                $sessionId = bin2hex(random_bytes(16)); // 32-character session ID
                
                // Store session mapping in database
                $sessionStmt = $conn->prepare("UPDATE users SET sessionid = ? WHERE mobile_number = ?");
                $sessionStmt->bind_param("ss", $sessionId, $mobile_number);
                $sessionStmt->execute();
                $sessionStmt->close();
                
                $message = $isNewUser ? 'Account created and OTP sent successfully' : 'OTP sent successfully';
                
                echo json_encode([
                    'Status' => 'Success',
                    'Details' => $sessionId, // React expects this format
                    'message' => $message,
                    'mobile_number' => $mobile_number,
                    'is_new_user' => $isNewUser,
                    'debug' => 'Auto-generated OTP sent via 2factor.in (fallback)',
                    'otp' => $generated_otp // For development only - remove in production
                ]);
            } elseif ($sms_result === true) {
                // SMS sent successfully using NPCSRV template
                $sessionId = bin2hex(random_bytes(16));
                
                $sessionStmt = $conn->prepare("UPDATE users SET sessionid = ? WHERE mobile_number = ?");
                $sessionStmt->bind_param("ss", $sessionId, $mobile_number);
                $sessionStmt->execute();
                $sessionStmt->close();
                
                $message = $isNewUser ? 'Account created and OTP sent successfully' : 'OTP sent successfully';
                
                echo json_encode([
                    'Status' => 'Success',
                    'Details' => $sessionId,
                    'message' => $message,
                    'mobile_number' => $mobile_number,
                    'is_new_user' => $isNewUser,
                    'debug' => 'SMS sent successfully using NPCSRV template',
                    'otp' => $local_otp // For development only - remove in production
                ]);
            } else {
                // SMS failed, but we still have local OTP for testing
                $sessionId = bin2hex(random_bytes(16));
                
                $sessionStmt = $conn->prepare("UPDATE users SET sessionid = ? WHERE mobile_number = ?");
                $sessionStmt->bind_param("ss", $sessionId, $mobile_number);
                $sessionStmt->execute();
                $sessionStmt->close();
                
                $message = $isNewUser ? 'Account created and OTP sent successfully (local fallback)' : 'OTP sent successfully (local fallback)';
                
                echo json_encode([
                    'Status' => 'Success',
                    'Details' => $sessionId,
                    'message' => $message,
                    'mobile_number' => $mobile_number,
                    'is_new_user' => $isNewUser,
                    'debug' => 'SMS API failed, but local OTP available for testing',
                    'otp' => $local_otp // For development only - remove in production
                ]);
            }
        } else {
            error_log("Failed to update OTP in database: " . $conn->error);
            echo json_encode(['Status' => 'Error', 'Details' => 'Failed to send OTP: ' . $conn->error]);
        }
        
        $updateStmt->close();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Exception in send_otp.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
}

$conn->close();
?> 