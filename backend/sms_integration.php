<?php
/**
 * SMS Integration for OTP using 2factor.in
 * 
 * 2factor.in is cost-effective and easy to set up
 * Sign up at: https://2factor.in
 * 
 * API Endpoint: https://2factor.in/API/V1/API_KEY/SMS/PHONE/OTP/TEMPLATE_NAME
 */

// 2factor.in Configuration - Updated with approved template
$TWOFACTOR_CONFIG = [
    'api_key' => 'd4aae05a-7209-11f0-a562-0200cd936042', // Your API key from dashboard
    'sender_id' => 'NPCSRV',              // Approved Sender ID
    'template_name' => 'OTP Service'      // Approved template name
];

// API Endpoint Format: https://2factor.in/API/V1/API_KEY/SMS/PHONE/OTP/TEMPLATE_NAME

/**
 * Send OTP using 2factor.in with approved template (Primary Method)
 * This uses your approved template name (not sender ID)
 */
function sendOTPVia2Factor($to, $otp = null) {
    global $TWOFACTOR_CONFIG;
    
    $api_key = $TWOFACTOR_CONFIG['api_key'];
    $template_name = $TWOFACTOR_CONFIG['template_name'];
    
    // Method 1: Use approved template name (Primary) - CORRECT FORMAT
    // Format: https://2factor.in/API/V1/API_KEY/SMS/PHONE/OTP/TEMPLATE_NAME
    $template_url = "https://2factor.in/API/V1/" . $api_key . "/SMS/" . $to . "/" . $otp . "/" . urlencode($template_name);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $template_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Enhanced logging for debugging
    $log_message = "=== 2factor.in Template OTP Attempt (OTP Service) ===\n";
    $log_message .= "Template URL: " . $template_url . "\n";
    $log_message .= "HTTP Code: " . $http_code . "\n";
    $log_message .= "Response: " . $response . "\n";
    if ($curl_error) {
        $log_message .= "CURL Error: " . $curl_error . "\n";
    }
    
    // Write to custom log file
    file_put_contents('sms_debug.log', date('Y-m-d H:i:s') . " - " . $log_message . "\n", FILE_APPEND);
    
    // Also use error_log if available
    error_log("=== 2factor.in Template OTP Attempt (OTP Service) ===");
    error_log("Template URL: " . $template_url);
    error_log("HTTP Code: " . $http_code);
    error_log("Response: " . $response);
    if ($curl_error) {
        error_log("CURL Error: " . $curl_error);
    }
    
    $result = json_decode($response, true);
    
    // Check if template method was successful
    // 2factor.in returns "Status" (capital S) not "status"
    $status = isset($result['Status']) ? strtolower($result['Status']) : '';
    $details = isset($result['Details']) ? $result['Details'] : '';
    
    if ($status === 'success' || $status === 'sent' || strpos($details, 'sent') !== false) {
        error_log("Template method successful! Status: $status, Details: $details");
        return true;
    } else {
        error_log("Template method failed. Status: $status, Details: $details");
    }
    
    // Method 2: Try with sender ID as fallback (old method)
    error_log("Template method failed, trying sender ID method...");
    
    $sender_id = $TWOFACTOR_CONFIG['sender_id'];
    $sender_url = "https://2factor.in/API/V1/" . $api_key . "/SMS/" . $to . "/" . $otp . "/" . $sender_id;
    
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $sender_url);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response2 = curl_exec($ch2);
    $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $curl_error2 = curl_error($ch2);
    curl_close($ch2);
    
    $log_message2 = "=== 2factor.in Sender ID OTP Attempt (NPCSRV) ===\n";
    $log_message2 .= "Sender URL: " . $sender_url . "\n";
    $log_message2 .= "HTTP Code: " . $http_code2 . "\n";
    $log_message2 .= "Response: " . $response2 . "\n";
    if ($curl_error2) {
        $log_message2 .= "CURL Error: " . $curl_error2 . "\n";
    }
    
    file_put_contents('sms_debug.log', date('Y-m-d H:i:s') . " - " . $log_message2 . "\n", FILE_APPEND);
    
    $result2 = json_decode($response2, true);
    $status2 = isset($result2['Status']) ? strtolower($result2['Status']) : '';
    $details2 = isset($result2['Details']) ? $result2['Details'] : '';
    
    if ($status2 === 'success' || $status2 === 'sent' || strpos($details2, 'sent') !== false) {
        error_log("Sender ID method successful! Status: $status2, Details: $details2");
        return true;
    } else {
        error_log("Sender ID method also failed. Status: $status2, Details: $details2");
    }
    
    // Method 3: Try AUTOGEN endpoint as final fallback
    error_log("Both methods failed, trying AUTOGEN method...");
    
    // Use AUTOGEN endpoint (2factor generates OTP)
    // Format: https://2factor.in/API/V1/API_KEY/SMS/PHONE/AUTOGEN/OTP1
    $autogen_url = "https://2factor.in/API/V1/" . $api_key . "/SMS/" . $to . "/AUTOGEN/OTP1";
    
    $ch3 = curl_init();
    curl_setopt($ch3, CURLOPT_URL, $autogen_url);
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch3, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch3, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response3 = curl_exec($ch3);
    $http_code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    $curl_error3 = curl_error($ch3);
    curl_close($ch3);
    
    $log_message3 = "=== 2factor.in AUTOGEN OTP Attempt (Fallback) ===\n";
    $log_message3 .= "AUTOGEN URL: " . $autogen_url . "\n";
    $log_message3 .= "HTTP Code: " . $http_code3 . "\n";
    $log_message3 .= "Response: " . $response3 . "\n";
    if ($curl_error3) {
        $log_message3 .= "CURL Error: " . $curl_error3 . "\n";
    }
    
    file_put_contents('sms_debug.log', date('Y-m-d H:i:s') . " - " . $log_message3 . "\n", FILE_APPEND);
    
    $result3 = json_decode($response3, true);
    $status3 = isset($result3['Status']) ? strtolower($result3['Status']) : '';
    $details3 = isset($result3['Details']) ? $result3['Details'] : '';
    
    if ($status3 === 'success' || $status3 === 'sent' || strpos($details3, 'sent') !== false) {
        error_log("AUTOGEN method successful! Status: $status3, Details: $details3");
        
        // When using AUTOGEN, 2factor generates the OTP and returns it in the response
        if (isset($result3['Details'])) {
            error_log("2factor Response Details: " . $result3['Details']);
            
            // Try to extract OTP from the response
            if (preg_match('/(\d{6})/', $result3['Details'], $matches)) {
                $generated_otp = $matches[1];
                error_log("Extracted OTP from 2factor response: " . $generated_otp);
                
                // Return the generated OTP so we can use it instead of our generated one
                return $generated_otp;
            }
        }
        
        return true;
    } else {
        error_log("AUTOGEN method also failed. Status: $status3, Details: $details3");
    }
    
    // All methods failed
    error_log("All SMS methods failed!");
    return false;
}

/**
 * Send SMS using 2factor.in (Legacy method)
 */
function sendSMSVia2Factor($to, $message) {
    global $TWOFACTOR_CONFIG;
    
    $api_key = $TWOFACTOR_CONFIG['api_key'];
    $sender_id = $TWOFACTOR_CONFIG['sender_id'];
    
    // 2factor.in API URL for sending SMS with template
    $url = "https://2factor.in/API/V1/" . $api_key . "/SMS/" . $to . "/" . urlencode($message) . "/" . $sender_id;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log response for debugging
    error_log("2factor.in Response: " . $response);
    
    $result = json_decode($response, true);
    return isset($result['status']) && $result['status'] === 'success';
}

/**
 * Main function to send SMS using 2factor.in
 */
function sendSMS($to, $message) {
    // For development, just log the message
    error_log("SMS to $to: $message");
    
    // For production, use 2factor.in
    return sendSMSVia2Factor($to, $message);
    
    // For testing without SMS, uncomment this line:
    // return true;
}

/**
 * Send OTP SMS - Updated to use template name first
 */
function sendOTPSMS($mobile_number, $otp = null) {
    // Primary: Use approved template name (correct format)
    $result = sendOTPVia2Factor($mobile_number, $otp);
    
    // If 2factor returned a generated OTP, use that instead
    if (is_string($result) && strlen($result) === 6) {
        error_log("Using 2factor generated OTP: " . $result);
        return $result; // Return the generated OTP
    }
    
    // Otherwise, return boolean success status
    return $result;
}

/**
 * Test SMS configuration (for debugging)
 */
function testSMSConfig() {
    global $TWOFACTOR_CONFIG;
    
    error_log("=== SMS Configuration Test ===");
    error_log("API Key: " . substr($TWOFACTOR_CONFIG['api_key'], 0, 8) . "...");
    error_log("Sender ID: " . $TWOFACTOR_CONFIG['sender_id']);
    error_log("Template: " . $TWOFACTOR_CONFIG['template_name']);
    
    // Test with a simple API call
    $test_url = "https://2factor.in/API/V1/" . $TWOFACTOR_CONFIG['api_key'] . "/BAL/SMS";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("Balance Check URL: " . $test_url);
    error_log("Balance Check Response: " . $response);
    error_log("Balance Check HTTP Code: " . $http_code);
}
?> 