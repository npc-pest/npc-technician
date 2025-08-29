<?php
// Simple Email Service for NPC Services
// This provides a reliable email sending service for booking confirmations

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Session-ID, session-id, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Test endpoint for CORS verification
if (isset($_GET['test_cors'])) {
    echo json_encode([
        'status' => 'success',
        'message' => 'CORS headers working correctly',
        'timestamp' => date('Y-m-d H:i:s'),
        'headers' => [
            'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'not set',
            'method' => $_SERVER['REQUEST_METHOD'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'not set'
        ]
    ]);
    exit;
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'email_errors.log');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log the received input
    error_log("Simple email service - Received input: " . json_encode($input));
    
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['user_email', 'user_name', 'booking_id', 'service_name', 'total_amount'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    $user_email = $input['user_email'];
    $user_name = $input['user_name'];
    $booking_id = $input['booking_id'];
    $service_name = $input['service_name'];
    $total_amount = $input['total_amount'];
    $service_date = $input['service_date'] ?? '';
    $service_time = $input['service_time'] ?? '';
    $address = $input['address'] ?? '';
    $payment_status = $input['payment_status'] ?? 'Pending';
    
    // Log the processed data
    error_log("Simple email service - Processing email for: $user_email, Booking: $booking_id, Service: $service_name");
    
    // Validate email format
    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }
    
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
            <h2>Dear $user_name,</h2>
            
            <p>Thank you for choosing NPC Services! Your booking has been confirmed successfully.</p>
            
            <div class='booking-details'>
                <h3>Booking Details:</h3>
                <p><strong>Booking ID:</strong> #$booking_id</p>
                <p><strong>Service:</strong> $service_name</p>
                <p><strong>Service Date:</strong> $service_date</p>
                <p><strong>Service Time:</strong> $service_time</p>
                <p><strong>Service Address:</strong> $address</p>
                <p><strong>Total Amount:</strong> <span class='amount'>₹$total_amount</span></p>
                <p><strong>Payment Status:</strong> $payment_status</p>
            </div>
            
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
    
    // Try multiple email sending methods
    
    // Method 1: Try PHP mail() first (since it's working)
    $email_sent = false;
    
    try {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: NPC Services <info@npcpest.com>',
            'Reply-To: support@npcservices.com',
            'X-Mailer: PHP/' . phpversion()
        );
        
        $email_sent = mail($user_email, $subject, $message, implode("\r\n", $headers));
        
        if ($email_sent) {
            error_log("Simple email service - Email sent successfully via PHP mail() to: $user_email");
        } else {
            error_log("Simple email service - PHP mail() failed, trying Hostinger SMTP");
        }
    } catch (Exception $mail_error) {
        error_log("Simple email service - PHP mail() error: " . $mail_error->getMessage());
    }
    
    // Method 2: Try Hostinger SMTP if PHP mail() failed
    if (!$email_sent) {
        try {
            // Include Hostinger SMTP service
            require_once 'hostinger_smtp_service.php';
            
            $smtpService = new HostingerSMTPService();
            $email_sent = $smtpService->sendEmail($user_email, $subject, $message);
            
            if ($email_sent) {
                error_log("Simple email service - Email sent successfully via Hostinger SMTP to: $user_email");
            } else {
                error_log("Simple email service - Hostinger SMTP also failed");
            }
        } catch (Exception $smtp_error) {
            error_log("Simple email service - SMTP error: " . $smtp_error->getMessage());
        }
    }
    
    // Log the email sending in database
    if ($email_sent) {
        try {
            include 'conn.php';
            
            $logSql = "INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("sssss", $user_email, $user_name, $booking_id, $service_name, $total_amount);
            $logStmt->execute();
            error_log("Simple email service - Email log saved to database");
        } catch (Exception $dbError) {
            error_log("Simple email service - Database logging error: " . $dbError->getMessage());
        }
    }
    
    if ($email_sent) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Booking confirmation email sent successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to send email. Please check server mail configuration.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Simple email service - Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while sending the email: ' . $e->getMessage()
    ]);
}
?>
