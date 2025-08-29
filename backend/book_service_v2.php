<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/booking_errors.log');

//added for CROS (hari)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $errorMsg = "PHP Error [$errno]: $errstr in $errfile on line $errline";
    error_log("[CUSTOM_ERROR] " . $errorMsg);
    return false; // Let PHP handle it normally
}

// Custom exception handler
function customExceptionHandler($exception) {
    $errorMsg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log("[CUSTOM_EXCEPTION] " . $errorMsg);
    echo json_encode([
        "status" => "error",
        "message" => "Exception: " . $exception->getMessage(),
        "file" => $exception->getFile(),
        "line" => $exception->getLine()
    ]);
}

// Set custom handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Session-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Test endpoint
if (isset($_GET['test'])) {
    echo json_encode([
        "status" => "success",
        "message" => "PHP endpoint working",
        "timestamp" => date('Y-m-d H:i:s'),
        "php_version" => PHP_VERSION
    ]);
    exit;
}

// Log function
function writeLog($message) {
    error_log("[BOOKING] " . date('Y-m-d H:i:s') . " - " . $message);
}

// Function to generate PDF using PDFShift
function generatePDFWithPDFShift($html_content) {
    try {
        // PDFShift API endpoint
        $api_url = 'https://api.pdfshift.io/v3/convert/pdf';
        
        // PDFShift API key - Replace with your actual API key
        // Sign up for free at https://pdfshift.io/
        $api_key = 'sk_7914256c228ea275f14f3959dca8a4392165aa47';
        
        writeLog("PDFShift: Using API key: " . substr($api_key, 0, 10) . "...");
        
        // Prepare the request data
        $data = array(
            'source' => $html_content,
            'landscape' => false,
            'use_print' => true,
            'margin_top' => '10mm',
            'margin_right' => '10mm',
            'margin_bottom' => '10mm',
            'margin_left' => '10mm'
        );
        
        writeLog("PDFShift: Request data prepared, HTML content length: " . strlen($html_content));
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));
        
        // Execute the request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        writeLog("PDFShift: HTTP Code: $http_code, Response size: " . strlen($response));
        if ($curl_error) {
            writeLog("PDFShift: cURL Error: $curl_error");
        }
        
        if ($http_code === 200) {
            writeLog("PDFShift: PDF generated successfully - Response size: " . strlen($response) . " bytes");
            writeLog("PDFShift: First 100 chars of response: " . substr($response, 0, 100));
            
            // Validate that we got actual PDF content
            if (substr($response, 0, 4) === '%PDF') {
                writeLog("PDFShift: Valid PDF header detected");
            return $response; // This is the PDF content
            } else {
                writeLog("PDFShift: Invalid PDF content - doesn't start with %PDF");
                writeLog("PDFShift: Content starts with: " . substr($response, 0, 20));
                return false;
            }
        } else {
            writeLog("PDFShift: Failed to generate PDF. HTTP Code: $http_code, Response: $response");
            return false;
        }
        
    } catch (Exception $e) {
        writeLog("PDFShift error: " . $e->getMessage());
        return false;
    }
}

// Fallback function to create a simple PDF using basic PDF structure
function createSimplePDF($userName, $bookingId, $serviceName, $totalAmount, $serviceDate, $timeSlot, $serviceAddress) {
    try {
        writeLog("Creating simple PDF fallback for booking: $bookingId");
        
        // Create a basic PDF structure (PDF 1.4)
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Catalog\n";
        $pdf .= "/Pages 2 0 R\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n";
        
        $pdf .= "2 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Pages\n";
        $pdf .= "/Kids [3 0 R]\n";
        $pdf .= "/Count 1\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n";
        
        $pdf .= "3 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Page\n";
        $pdf .= "/Parent 2 0 R\n";
        $pdf .= "/MediaBox [0 0 612 792]\n";
        $pdf .= "/Contents 4 0 R\n";
        $pdf .= "/Resources <<\n";
        $pdf .= "/Font <<\n";
        $pdf .= "/F1 <<\n";
        $pdf .= "/Type /Font\n";
        $pdf .= "/Subtype /Type1\n";
        $pdf .= "/BaseFont /Helvetica\n";
        $pdf .= ">>\n";
        $pdf .= ">>\n";
        $pdf .= ">>\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n";
        
        // Create content stream with better formatting
        $content = "BT\n";
        $content .= "/F1 16 Tf\n";
        $content .= "50 750 Td\n";
        $content .= "(NPC Services - Invoice) Tj\n";
        $content .= "0 -30 Td\n";
        $content .= "/F1 12 Tf\n";
        $content .= "(Invoice #: $bookingId) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Customer: $userName) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Service: $serviceName) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Amount: ₹" . number_format($totalAmount, 2) . ") Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Date: " . ($serviceDate ? date('d/m/Y', strtotime($serviceDate)) : 'To be scheduled') . ") Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Time: $timeSlot) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Address: $serviceAddress) Tj\n";
        $content .= "0 -30 Td\n";
        $content .= "(Thank you for choosing NPC Services!) Tj\n";
        $content .= "ET\n";
        
        $pdf .= "4 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Length " . strlen($content) . "\n";
        $pdf .= ">>\n";
        $pdf .= "stream\n";
        $pdf .= $content;
        $pdf .= "endstream\n";
        $pdf .= "endobj\n";
        
        $pdf .= "xref\n";
        $pdf .= "0 5\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= "0000000009 00000 n \n";
        $pdf .= "0000000058 00000 n \n";
        $pdf .= "0000000115 00000 n \n";
        $pdf .= "0000000250 00000 n \n";
        $pdf .= "trailer\n";
        $pdf .= "<<\n";
        $pdf .= "/Size 5\n";
        $pdf .= "/Root 1 0 R\n";
        $pdf .= ">>\n";
        $pdf .= "startxref\n";
        $pdf .= strlen($pdf) . "\n";
        $pdf .= "%%EOF\n";
        
        writeLog("Simple PDF created successfully - Size: " . strlen($pdf) . " bytes");
        return $pdf;
        
    } catch (Exception $e) {
        writeLog("Simple PDF creation error: " . $e->getMessage());
        return false;
    }
}

// Function to send booking confirmation email
function sendBookingConfirmationEmail($conn, $bookingId, $userId, $serviceName, $serviceDate, $timeSlot, $totalAmount, $serviceAddress, $userEmail = null) {
    try {
        // Get user details
        $userStmt = $conn->prepare("SELECT customer_name, email_id FROM users WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            writeLog("User not found for email sending");
            return false;
        }
        
        $user = $userResult->fetch_assoc();
        $userName = $user['customer_name'];
        
        // Use provided email if available, otherwise use database email
        if (!empty($userEmail)) {
            $emailToUse = $userEmail;
            writeLog("Using email from booking form: $emailToUse");
        } else {
            $emailToUse = $user['email_id'];
            writeLog("Using email from database: $emailToUse");
        }
        
        if (empty($emailToUse)) {
            writeLog("User email is empty, cannot send confirmation email");
            return false;
        }
        
        writeLog("Sending booking confirmation email to: $emailToUse");
        
        // Email subject and content
        $subject = "Booking Confirmation - NPC Services";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                .header { background: linear-gradient(135deg, #0d9488, #0891b2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: white; padding: 20px; border: 1px solid #ddd; border-top: none; }
                .booking-details { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .amount { font-size: 24px; font-weight: bold; color: #0d9488; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>NPC Services</h1>
                <p>Professional Pest Control & Home Services</p>
            </div>
            
            <div class='content'>
                <h2>Dear $userName,</h2>
                
                <p>Thank you for choosing NPC Services! Your booking has been confirmed successfully.</p>
                
                <div class='booking-details'>
                    <h3>Booking Details:</h3>
                    <p><strong>Booking ID:</strong> #$bookingId</p>
                    <p><strong>Service:</strong> $serviceName</p>
                    <p><strong>Service Date:</strong> $serviceDate</p>
                    <p><strong>Service Time:</strong> $timeSlot</p>
                    <p><strong>Service Address:</strong> $serviceAddress</p>
                    <p><strong>Total Amount:</strong> <span class='amount'>₹$totalAmount</span></p>
                </div>
                
                <p>Your booking details are included in this email. You can also view your booking details in your account dashboard.</p>
                
                <p>Our team will contact you shortly to confirm the details and provide any additional information.</p>
                
                <p>You can track your booking status in your account dashboard.</p>
                
                <p>If you have any questions or need to make changes to your booking, please don't hesitate to contact our support team.</p>
                
                <p>Thank you for trusting NPC Services!</p>
                
                <p>Best regards,<br>
                NPC Services Team</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated email. Please do not reply to this message.</p>
                <p>For support, contact us at support@npcservices.com</p>
            </div>
        </body>
        </html>";
        
        // Send HTML-only email (no PDF attachment)
        writeLog("Sending HTML-only booking confirmation email");
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: NPC Services <info@npcpest.com>',
            'Reply-To: support@npcservices.com',
            'X-Mailer: PHP/' . phpversion()
        );
        
        // Send email
        $mailSent = mail($emailToUse, $subject, $message, implode("\r\n", $headers));
        
        if ($mailSent) {
            writeLog("Booking confirmation email sent successfully to: $emailToUse");
            
            // Log the email sending in database
            try {
                $logSql = "INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param("sssss", $emailToUse, $userName, $bookingId, $serviceName, $totalAmount);
                $logStmt->execute();
                writeLog("Email log saved to database");
            } catch (Exception $dbError) {
                writeLog("Database logging error: " . $dbError->getMessage());
            }
            
            return true;
        } else {
            writeLog("Failed to send booking confirmation email to: $emailToUse");
            return false;
        }
        
    } catch (Exception $e) {
        writeLog("Email sending error: " . $e->getMessage());
        return false;
    }
}

// Function to send admin notification email
function sendAdminNotificationEmail($conn, $bookingId, $userId, $serviceName, $serviceDate, $timeSlot, $totalAmount, $serviceAddress, $userEmail = null) {
    try {
        // Get user details
        $userStmt = $conn->prepare("SELECT customer_name, email_id, mobile_number FROM users WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            writeLog("User not found for admin notification email");
            return false;
        }
        
        $user = $userResult->fetch_assoc();
        $userName = $user['customer_name'];
        $userMobile = $user['mobile_number'];
        
        // Use provided email if available, otherwise use database email
        if (!empty($userEmail)) {
            $customerEmail = $userEmail;
        } else {
            $customerEmail = $user['email_id'];
        }
        
        writeLog("Sending admin notification email for booking: $bookingId");
        
        // Email subject and content
        $subject = "New Booking Received - Booking ID: #$bookingId";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .booking-details { background: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #2196F3; }
                .customer-details { background: #e3f2fd; padding: 15px; margin: 20px 0; border-left: 4px solid #2196F3; }
                .amount { color: #2196F3; font-weight: bold; font-size: 18px; }
                .footer { background: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .urgent { color: #f44336; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Booking Received</h1>
                    <p>NPC Services - Admin Notification</p>
                </div>
                
                <div class='content'>
                    <h2>Hello Admin,</h2>
                    
                    <p class='urgent'>A new booking has been received and requires your attention.</p>
                    
                    <div class='booking-details'>
                        <h3>Booking Details:</h3>
                        <p><strong>Booking ID:</strong> #$bookingId</p>
                        <p><strong>Service:</strong> $serviceName</p>
                        <p><strong>Service Date:</strong> $serviceDate</p>
                        <p><strong>Service Time:</strong> $timeSlot</p>
                        <p><strong>Service Address:</strong> $serviceAddress</p>
                        <p><strong>Total Amount:</strong> <span class='amount'>₹$totalAmount</span></p>
                    </div>
                    
                    <div class='customer-details'>
                        <h3>Customer Information:</h3>
                        <p><strong>Customer Name:</strong> $userName</p>
                        <p><strong>Email:</strong> $customerEmail</p>
                        <p><strong>Mobile:</strong> $userMobile</p>
                    </div>
                    
                    <p>Please review this booking and take necessary action:</p>
                    <ul>
                        <li>Assign a technician to this booking</li>
                        <li>Contact the customer if needed</li>
                        <li>Update the booking status</li>
                    </ul>
                    
                    <p>You can manage this booking from your admin dashboard.</p>
                    
                    <p>Best regards,<br>
                    NPC Services System</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from NPC Services booking system.</p>
                    <p>Booking ID: #$bookingId | Received at: " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send HTML email to admin
        writeLog("Sending admin notification email");
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: NPC Services <info@npcpest.com>',
            'Reply-To: support@npcservices.com',
            'X-Mailer: PHP/' . phpversion()
        );
        
        // Send email to admin
        $adminEmail = 'info@npcpest.com';
        $mailSent = mail($adminEmail, $subject, $message, implode("\r\n", $headers));
        
        if ($mailSent) {
            writeLog("Admin notification email sent successfully to: $adminEmail");
            return true;
        } else {
            writeLog("Failed to send admin notification email to: $adminEmail");
            return false;
        }
        
    } catch (Exception $e) {
        writeLog("Admin notification email error: " . $e->getMessage());
        return false;
    }
}

writeLog("=== SCRIPT STARTED ===");

try {
    writeLog("Attempting to include conn.php");
    
    // Check if conn.php exists
    if (!file_exists('conn.php')) {
        throw new Exception("conn.php file not found");
    }
    
    include 'conn.php';
    writeLog("conn.php included successfully");
    
    // Check connection variable
    if (!isset($conn)) {
        throw new Exception("Connection variable \$conn not set");
    }
    
    if (!$conn) {
        throw new Exception("Database connection is null or false");
    }
    
    // Test the connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    writeLog("Database connection verified");
    
    // Session validation function
    function validateSession($conn, $sessionId) {
        writeLog("Starting session validation for: " . substr($sessionId, 0, 10) . "...");
        
        try {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE sessionid = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare session query: " . $conn->error);
            }
            
            $stmt->bind_param("s", $sessionId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute session query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $stmt->close();
                writeLog("Session valid - User ID: " . $user['user_id']);
                return $user['user_id'];
            }
            
            $stmt->close();
            writeLog("Session invalid - no matching user found");
            return false;
            
        } catch (Exception $e) {
            writeLog("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get session ID from headers
    writeLog("Getting headers...");
    $headers = getallheaders();
    
    if (!$headers) {
        throw new Exception("Failed to get headers");
    }
    
    $sessionId = '';
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'session-id') {
            $sessionId = $value;
            break;
        }
    }
    
    writeLog("Session ID found: " . (empty($sessionId) ? "EMPTY" : "PRESENT"));
    
    if (empty($sessionId)) {
        echo json_encode(["status" => "error", "message" => "Session ID required"]);
        exit;
    }
    
    // Validate session
    $userId = validateSession($conn, $sessionId);
    if (!$userId) {
        echo json_encode(["status" => "error", "message" => "Invalid session"]);
        exit;
    }
    
    writeLog("User authenticated successfully: " . $userId);
    
    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        writeLog("GET Action requested: " . $action);
        
        if ($action === 'get_address') {
            try {
                $stmt = $conn->prepare("SELECT address1, address2, customer_name, mobile_number, email_id FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $fullAddress = trim($user['address1'] ?? '');
                    if (!empty($user['address2'])) {
                        $fullAddress .= ', ' . trim($user['address2']);
                    }
                    
                    echo json_encode([
                        "status" => "success",
                        "address" => $fullAddress,
                        "has_address" => !empty($fullAddress),
                        "customer_name" => $user['customer_name'] ?? '',
                        "mobile_number" => $user['mobile_number'] ?? '',
                        "email_id" => $user['email_id'] ?? ''
                    ]);
                } else {
                    echo json_encode(["status" => "error", "message" => "User not found"]);
                }
            } catch (Exception $e) {
                writeLog("Get address error: " . $e->getMessage());
                echo json_encode(["status" => "error", "message" => "Failed to get address"]);
            }
        }
        
        elseif ($action === 'validate_coupon') {
            $couponCode = $_GET['coupon_code'] ?? '';
            $orderAmount = floatval($_GET['order_amount'] ?? 0);
            
            if (empty($couponCode)) {
                echo json_encode(["status" => "error", "message" => "Coupon code is required"]);
                exit;
            }
            
            try {
                $stmt = $conn->prepare("SELECT * FROM offers WHERE coupon_number = ? AND offer_starts_on <= CURDATE() AND expires_on >= CURDATE()");
                $stmt->bind_param("s", $couponCode);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $offer = $result->fetch_assoc();
                    $discountAmount = ($orderAmount * $offer['offer_percentage']) / 100;
                    
                    echo json_encode([
                        "status" => "success",
                        "offer" => $offer,
                        "discount_amount" => $discountAmount,
                        "message" => "Coupon applied successfully! You saved ₹" . number_format($discountAmount, 2)
                    ]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Invalid or expired coupon code"]);
                }
            } catch (Exception $e) {
                writeLog("Validate coupon error: " . $e->getMessage());
                echo json_encode(["status" => "error", "message" => "Failed to validate coupon"]);
            }
        }
    }
    
    // Handle POST requests
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        writeLog("Processing POST request");
        
        $input = file_get_contents('php://input');
        writeLog("Input received - Length: " . strlen($input));
        
        if (empty($input)) {
            echo json_encode(["status" => "error", "message" => "No data received"]);
            exit;
        }
        
        $data = json_decode($input, true);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE) {
            writeLog("JSON decode error: " . json_last_error_msg());
            echo json_encode(["status" => "error", "message" => "Invalid JSON: " . json_last_error_msg()]);
            exit;
        }
        
        $action = $data['action'] ?? '';
        writeLog("POST Action requested: " . $action);
        
        if ($action === 'update_address') {
            $address = $data['address'] ?? '';
            
            if (empty($address)) {
                echo json_encode(["status" => "error", "message" => "Address is required"]);
                exit;
            }
            
            try {
                $stmt = $conn->prepare("UPDATE users SET address1 = ? WHERE user_id = ?");
                $stmt->bind_param("si", $address, $userId);
                
                if ($stmt->execute()) {
                    echo json_encode(["status" => "success", "message" => "Address updated successfully"]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to update address"]);
                }
            } catch (Exception $e) {
                writeLog("Update address error: " . $e->getMessage());
                echo json_encode(["status" => "error", "message" => "Failed to update address"]);
            }
        }
        
        elseif ($action === 'create_booking') {
            writeLog("=== CREATING BOOKING ===");
            
            // Generate booking ID
            $bookingId = 'BK' . date('Ymd') . rand(1000, 9999);
            writeLog("Generated booking ID: " . $bookingId);
            
            // Extract and validate data - INCLUDING RAZORPAY FIELDS
            $serviceName = $data['service_name'] ?? '';
            $serviceDate = $data['service_date'] ?? '';
            $timeSlot = $data['time_slot'] ?? '';
            $serviceAddress = $data['service_address'] ?? '';
            $specialNotes = $data['special_notes'] ?? '';
            $subtotal = floatval($data['subtotal'] ?? 0);
            $discountAmount = floatval($data['discount_amount'] ?? 0);
            $couponCode = $data['coupon_code'] ?? null;
            $couponDiscount = floatval($data['coupon_discount'] ?? 0);
            $totalAmount = floatval($data['total_amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $cartItems = $data['cart_items'] ?? [];
            $razorpayOrderId = $data['razorpay_order_id'] ?? null;
            $userEmail = $data['user_email'] ?? ''; // New field for email from form
            $customerName = $data['customer_name'] ?? ''; // New field for customer name from form
            
            writeLog("Extracted data - Service: $serviceName, Date: $serviceDate, Payment: $paymentMethod, Items: " . count($cartItems));
            writeLog("Razorpay Order ID: " . ($razorpayOrderId ?? 'NULL'));
            
            // Validation
            $missingFields = [];
            if (empty($serviceName)) $missingFields[] = "service_name";
            if (empty($serviceDate)) $missingFields[] = "service_date";
            if (empty($timeSlot)) $missingFields[] = "time_slot";
            if (empty($serviceAddress)) $missingFields[] = "service_address";
            if (empty($cartItems)) $missingFields[] = "cart_items";
            
            if (!empty($missingFields)) {
                writeLog("Missing fields: " . implode(", ", $missingFields));
                echo json_encode([
                    "status" => "error", 
                    "message" => "Missing required fields: " . implode(", ", $missingFields)
                ]);
                exit;
            }
            
            // Check if tables exist
            writeLog("Checking if tables exist...");
            $tableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");
            if ($tableCheck->num_rows === 0) {
                throw new Exception("Table 'bookings' does not exist");
            }
            
            $tableCheck = $conn->query("SHOW TABLES LIKE 'booking_items'");
            if ($tableCheck->num_rows === 0) {
                throw new Exception("Table 'booking_items' does not exist");
            }
            
            writeLog("Tables verified - starting transaction");
            
            // Start transaction
            if (!$conn->begin_transaction()) {
                throw new Exception("Failed to start transaction: " . $conn->error);
            }
            
            try {
                // Insert booking - CORRECTED PARAMETER COUNT
                writeLog("Preparing booking insert statement");
                $sql = "INSERT INTO bookings (booking_id, user_id, service_name, service_date, time_slot, service_address, special_notes, subtotal, discount_amount, coupon_code, coupon_discount, total_amount, payment_method, payment_status, razorpay_order_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Failed to prepare booking statement: " . $conn->error);
                }
                
                writeLog("Binding parameters for booking");
                // FIXED: 14 parameters total (payment_status is hardcoded, so 13 bind params + 1 hardcoded = 14)
                // But we're binding 14 variables, so we need 14 characters in type string
                $bindResult = $stmt->bind_param("sisssssddsdsss", 
                    $bookingId,        // s - string (1)
                    $userId,           // i - integer (2)
                    $serviceName,      // s - string (3)
                    $serviceDate,      // s - string (4)
                    $timeSlot,         // s - string (5)
                    $serviceAddress,   // s - string (6)
                    $specialNotes,     // s - string (7)
                    $subtotal,         // d - double (8)
                    $discountAmount,   // d - double (9)
                    $couponCode,       // s - string (10)
                    $couponDiscount,   // d - double (11)
                    $totalAmount,      // d - double (12)
                    $paymentMethod,    // s - string (13)
                    $razorpayOrderId   // s - string (14)
                );
                
                if (!$bindResult) {
                    throw new Exception("Failed to bind parameters: " . $stmt->error);
                }
                
                writeLog("Executing booking insert");
                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute booking insert: " . $stmt->error);
                }
                
                writeLog("Booking record inserted successfully");
                $stmt->close();
                
                // Insert booking items
                writeLog("Processing " . count($cartItems) . " cart items");
                $itemSql = "INSERT INTO booking_items (booking_id, service_type_name, room_size, price, quantity, item_total) VALUES (?, ?, ?, ?, ?, ?)";
                $itemStmt = $conn->prepare($itemSql);
                
                if (!$itemStmt) {
                    throw new Exception("Failed to prepare item statement: " . $conn->error);
                }
                
                foreach ($cartItems as $index => $item) {
                    writeLog("Processing item " . ($index + 1) . ": " . ($item['service_type_name'] ?? 'UNKNOWN'));
                    
                    // Store values in variables first
                    $itemServiceType = $item['service_type_name'] ?? '';
                    $itemRoomSize = $item['room_size'] ?? '';
                    $itemPrice = floatval($item['price'] ?? 0);
                    $itemQuantity = intval($item['quantity'] ?? 0);
                    $itemTotal = $itemPrice * $itemQuantity;
                    
                    // Bind with variables
                    $itemBindResult = $itemStmt->bind_param("sssdid",
                        $bookingId,        // s - string
                        $itemServiceType,  // s - string
                        $itemRoomSize,     // s - string  
                        $itemPrice,        // d - double
                        $itemQuantity,     // i - integer
                        $itemTotal         // d - double
                    );
                    
                    if (!$itemBindResult) {
                        throw new Exception("Failed to bind item parameters: " . $itemStmt->error);
                    }
                    
                    if (!$itemStmt->execute()) {
                        throw new Exception("Failed to insert item " . ($index + 1) . ": " . $itemStmt->error);
                    }
                }
                
                $itemStmt->close();
                writeLog("All items inserted successfully");
                
                // Commit transaction
                if (!$conn->commit()) {
                    throw new Exception("Failed to commit transaction: " . $conn->error);
                }
                
                writeLog("=== BOOKING CREATED SUCCESSFULLY ===");
                
                // Update user's email and customer name in profile if provided
                if (!empty($userEmail) || !empty($customerName)) {
                    try {
                        if (!empty($userEmail) && !empty($customerName)) {
                            // Update both email and customer name
                            $updateUserStmt = $conn->prepare("UPDATE users SET email_id = ?, customer_name = ? WHERE user_id = ?");
                            if ($updateUserStmt) {
                                $updateUserStmt->bind_param("ssi", $userEmail, $customerName, $userId);
                                if ($updateUserStmt->execute()) {
                                    writeLog("User email and customer name updated in profile: $userEmail, $customerName");
                                } else {
                                    writeLog("Failed to update user email and customer name in profile: " . $updateUserStmt->error);
                                }
                                $updateUserStmt->close();
                            }
                        } elseif (!empty($userEmail)) {
                            // Update only email
                            $updateEmailStmt = $conn->prepare("UPDATE users SET email_id = ? WHERE user_id = ?");
                            if ($updateEmailStmt) {
                                $updateEmailStmt->bind_param("si", $userEmail, $userId);
                                if ($updateEmailStmt->execute()) {
                                    writeLog("User email updated in profile: $userEmail");
                                } else {
                                    writeLog("Failed to update user email in profile: " . $updateEmailStmt->error);
                                }
                                $updateEmailStmt->close();
                            }
                        } elseif (!empty($customerName)) {
                            // Update only customer name
                            $updateNameStmt = $conn->prepare("UPDATE users SET customer_name = ? WHERE user_id = ?");
                            if ($updateNameStmt) {
                                $updateNameStmt->bind_param("si", $customerName, $userId);
                                if ($updateNameStmt->execute()) {
                                    writeLog("User customer name updated in profile: $customerName");
                                } else {
                                    writeLog("Failed to update user customer name in profile: " . $updateNameStmt->error);
                                }
                                $updateNameStmt->close();
                            }
                        }
                    } catch (Exception $userUpdateError) {
                        writeLog("Error updating user profile: " . $userUpdateError->getMessage());
                    }
                }
                
                echo json_encode([
                    "status" => "success",
                    "message" => "Booking created successfully",
                    "booking_id" => $bookingId
                ]);
                
                // Send booking confirmation email using PHP mail()
                try {
                    $emailSent = sendBookingConfirmationEmail($conn, $bookingId, $userId, $serviceName, $serviceDate, $timeSlot, $totalAmount, $serviceAddress, $userEmail);
                    if ($emailSent) {
                        writeLog("Booking confirmation email sent successfully via PHP mail()");
                        
                        // Send admin notification email
                        try {
                            $adminEmailSent = sendAdminNotificationEmail($conn, $bookingId, $userId, $serviceName, $serviceDate, $timeSlot, $totalAmount, $serviceAddress, $userEmail);
                            if ($adminEmailSent) {
                                writeLog("Admin notification email sent successfully");
                            } else {
                                writeLog("Failed to send admin notification email");
                            }
                        } catch (Exception $adminEmailError) {
                            writeLog("Admin notification email error: " . $adminEmailError->getMessage());
                        }
                    } else {
                        writeLog("Failed to send booking confirmation email via PHP mail()");
                    }
                } catch (Exception $emailError) {
                    writeLog("Email sending error: " . $emailError->getMessage());
                }
                
            } catch (Exception $e) {
                writeLog("Rolling back transaction due to error: " . $e->getMessage());
                $conn->rollback();
                
                echo json_encode([
                    "status" => "error",
                    "message" => "Database error: " . $e->getMessage()
                ]);
            }
        }
        
        elseif ($action === 'update_payment') {
            writeLog("=== UPDATING PAYMENT STATUS ===");
            
            $bookingId = $data['booking_id'] ?? '';
            $paymentStatus = $data['payment_status'] ?? '';
            $razorpayPaymentId = $data['razorpay_payment_id'] ?? null;
            
            writeLog("Booking ID: $bookingId");
            writeLog("Payment Status: $paymentStatus");
            writeLog("Razorpay Payment ID: $razorpayPaymentId");
            
            // Validate required fields
            if (empty($bookingId)) {
                echo json_encode(["status" => "error", "message" => "Booking ID is required"]);
                exit;
            }
            
            if (empty($paymentStatus)) {
                echo json_encode(["status" => "error", "message" => "Payment status is required"]);
                exit;
            }
            
            try {
                // Check if booking exists and belongs to current user
                writeLog("Checking if booking exists for user");
                $checkStmt = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id = ? AND user_id = ?");
                $checkStmt->bind_param("si", $bookingId, $userId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows === 0) {
                    writeLog("Booking not found or doesn't belong to user");
                    echo json_encode(["status" => "error", "message" => "Booking not found"]);
                    exit;
                }
                $checkStmt->close();
                
                // Update payment status
                writeLog("Updating payment status");
                $updateSql = "UPDATE bookings SET payment_status = ?, razorpay_payment_id = ? WHERE booking_id = ? AND user_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                
                if (!$updateStmt) {
                    throw new Exception("Failed to prepare update statement: " . $conn->error);
                }
                
                $updateStmt->bind_param("sssi", $paymentStatus, $razorpayPaymentId, $bookingId, $userId);
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update payment: " . $updateStmt->error);
                }
                
                $affectedRows = $updateStmt->affected_rows;
                writeLog("Payment update completed. Affected rows: $affectedRows");
                $updateStmt->close();
                
                if ($affectedRows > 0) {
                    echo json_encode([
                        "status" => "success",
                        "message" => "Payment updated successfully",
                        "booking_id" => $bookingId,
                        "payment_status" => $paymentStatus
                    ]);
                } else {
                    echo json_encode([
                        "status" => "warning",
                        "message" => "No rows updated - booking may already have this status"
                    ]);
                }
                
            } catch (Exception $e) {
                writeLog("Payment update error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to update payment: " . $e->getMessage()
                ]);
            }
        }
        
        else {
            writeLog("Unknown POST action requested: " . $action);
            echo json_encode(["status" => "error", "message" => "Unknown action: " . $action]);
        }
    }
    
    else {
        writeLog("Unsupported HTTP method: " . $_SERVER['REQUEST_METHOD']);
        echo json_encode(["status" => "error", "message" => "Method not supported"]);
    }
    
} catch (Exception $e) {
    writeLog("FATAL EXCEPTION: " . $e->getMessage());
    writeLog("Exception file: " . $e->getFile());
    writeLog("Exception line: " . $e->getLine());
    
    echo json_encode([
        "status" => "error",
        "message" => "Server exception: " . $e->getMessage(),
        "file" => basename($e->getFile()),
        "line" => $e->getLine()
    ]);
    
} catch (Error $e) {
    writeLog("FATAL PHP ERROR: " . $e->getMessage());
    writeLog("Error file: " . $e->getFile());
    writeLog("Error line: " . $e->getLine());
    
    echo json_encode([
        "status" => "error",
        "message" => "PHP Fatal Error: " . $e->getMessage(),
        "file" => basename($e->getFile()),
        "line" => $e->getLine()
    ]);
}

writeLog("=== SCRIPT COMPLETED ===");

if (isset($conn)) {
    $conn->close();
}
?>