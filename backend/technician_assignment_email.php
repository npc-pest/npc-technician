<?php
// Technician Assignment Email Service for NPC Services
// This sends email notifications to users when a technician is assigned to their booking

// Mark this file as included to prevent duplicate execution
define('EMAIL_SERVICE_INCLUDED', true);

// Function to send technician assignment email
function sendTechnicianAssignmentEmail($booking_id, $technician_id) {
    try {
        // Include database connection
        include 'conn.php';
        
        // Get booking details with user information
        $bookingQuery = "
            SELECT b.*, u.customer_name, u.email_id, u.mobile_number
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_id = ?
        ";
        $bookingStmt = $conn->prepare($bookingQuery);
        
        if (!$bookingStmt) {
            throw new Exception("Failed to prepare booking query: " . $conn->error);
        }
        
        $bookingStmt->bind_param("s", $booking_id);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        $booking = $bookingResult->fetch_assoc();
        
        if (!$booking) {
            return ['status' => 'error', 'message' => 'Booking not found'];
        }
        
        // Get technician details
        $technicianQuery = "SELECT employee_name, phone_number, service_type FROM technicians WHERE technician_id = ?";
        $technicianStmt = $conn->prepare($technicianQuery);
        
        if (!$technicianStmt) {
            throw new Exception("Failed to prepare technician query: " . $conn->error);
        }
        
        $technicianStmt->bind_param("i", $technician_id);
        $technicianStmt->execute();
        $technicianResult = $technicianStmt->get_result();
        $technician = $technicianResult->fetch_assoc();
        
        if (!$technician) {
            return ['status' => 'error', 'message' => 'Technician not found'];
        }
        
        // Extract data
        $user_email = $booking['email_id'];
        $user_name = $booking['customer_name'];
        $user_phone = $booking['mobile_number'];
        $service_name = $booking['service_name'];
        $service_date = $booking['service_date'];
        $time_slot = $booking['time_slot'];
        $service_address = $booking['service_address'];
        $total_amount = $booking['total_amount'];
        $payment_status = $booking['payment_status'];
        
        $technician_name = $technician['employee_name'];
        $technician_phone = $technician['phone_number'];
        $technician_service_type = $technician['service_type'];
        
        // Validate email format
        if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Invalid user email format'];
        }
        
        // Email subject and content
        $subject = "Technician Assigned - NPC Services";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                .header { background: linear-gradient(135deg, #0d9488, #0891b2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: white; padding: 20px; border: 1px solid #ddd; border-top: none; }
                .booking-details { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .technician-details { background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #0d9488; }
                .amount { font-size: 24px; font-weight: bold; color: #0d9488; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
                .contact-info { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>NPC Services</h1>
                <p>Professional Pest Control & Home Services</p>
            </div>
            
            <div class='content'>
                <h2>Dear $user_name,</h2>
                
                <p>Great news! A technician has been assigned to your booking and will be arriving at your scheduled time.</p>
                
                <div class='technician-details'>
                    <h3>👨‍🔧 Your Assigned Technician:</h3>
                    <p><strong>Name:</strong> $technician_name</p>
                    <p><strong>Phone Number:</strong> <a href='tel:$technician_phone'>$technician_phone</a></p>
                    <p><strong>Service Type:</strong> $technician_service_type</p>
                    <p><strong>Experience:</strong> Professional and certified technician</p>
                </div>
                
                <div class='booking-details'>
                    <h3>📋 Booking Details:</h3>
                    <p><strong>Booking ID:</strong> #$booking_id</p>
                    <p><strong>Service:</strong> $service_name</p>
                    <p><strong>Service Date:</strong> $service_date</p>
                    <p><strong>Service Time:</strong> $time_slot</p>
                    <p><strong>Service Address:</strong> $service_address</p>
                    <p><strong>Total Amount:</strong> <span class='amount'>₹$total_amount</span></p>
                    <p><strong>Payment Status:</strong> $payment_status</p>
                </div>
                
                <div class='contact-info'>
                    <h4>📞 Important Contact Information:</h4>
                    <p>If you need to contact your technician directly, you can call them at <strong>$technician_phone</strong></p>
                    <p>For any urgent issues or changes, please contact our support team immediately.</p>
                </div>
                
                <h3>What to expect:</h3>
                <ul>
                    <li>Your technician will arrive within the scheduled time slot</li>
                    <li>They will introduce themselves and show their ID</li>
                    <li>They will explain the service process before starting</li>
                    <li>Feel free to ask any questions about the service</li>
                </ul>
                
                <p><strong>Please ensure someone is available at the service address during the scheduled time.</strong></p>
                
                <p>You can track your booking status in your account dashboard.</p>
                
                <p>Thank you for choosing NPC Services!</p>
                
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
        
        // Method 1: Try PHP mail() first
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
                error_log("Technician assignment email - Email sent successfully via PHP mail() to: $user_email");
            } else {
                error_log("Technician assignment email - PHP mail() failed, trying Hostinger SMTP");
            }
        } catch (Exception $mail_error) {
            error_log("Technician assignment email - PHP mail() error: " . $mail_error->getMessage());
        }
        
        // Method 2: Try Hostinger SMTP if PHP mail() failed
        if (!$email_sent) {
            try {
                // Include Hostinger SMTP service
                require_once 'hostinger_smtp_service.php';
                
                $smtpService = new HostingerSMTPService();
                $email_sent = $smtpService->sendEmail($user_email, $subject, $message);
                
                if ($email_sent) {
                    error_log("Technician assignment email - Email sent successfully via Hostinger SMTP to: $user_email");
                } else {
                    error_log("Technician assignment email - Hostinger SMTP also failed");
                }
            } catch (Exception $smtp_error) {
                error_log("Technician assignment email - SMTP error: " . $smtp_error->getMessage());
            }
        }
        
        // Log the email sending in database
        if ($email_sent) {
            try {
                $logSql = "INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param("sssss", $user_email, $user_name, $booking_id, $service_name, $total_amount);
                $logStmt->execute();
                error_log("Technician assignment email - Email log saved to database");
            } catch (Exception $dbError) {
                error_log("Technician assignment email - Database logging error: " . $dbError->getMessage());
            }
        }
        
        if ($email_sent) {
            return [
                'status' => 'success',
                'message' => 'Technician assignment email sent successfully',
                'data' => [
                    'booking_id' => $booking_id,
                    'technician_name' => $technician_name,
                    'technician_phone' => $technician_phone,
                    'user_email' => $user_email,
                    'user_name' => $user_name
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to send technician assignment email. Please check server mail configuration.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Technician assignment email - Error: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'An error occurred while sending the technician assignment email: ' . $e->getMessage()
        ];
    }
}

// Handle direct HTTP requests to this file (only when accessed directly, not when included)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['SCRIPT_NAME']) === 'technician_assignment_email.php' && !defined('EMAIL_SERVICE_INCLUDED')) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Session-ID, session-id, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 hours

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', 'email_errors.log');

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Log the received input
        error_log("Technician assignment email service - Received input: " . json_encode($input));
        
        if (!$input) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
            exit;
        }
        
        // Validate required fields
        $required_fields = ['booking_id', 'technician_id'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
                exit;
            }
        }
        
        $booking_id = $input['booking_id'];
        $technician_id = $input['technician_id'];
        
        // Call the email function
        $result = sendTechnicianAssignmentEmail($booking_id, $technician_id);
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Technician assignment email - Error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred while sending the technician assignment email: ' . $e->getMessage()
        ]);
    }
}
?>
