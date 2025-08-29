<?php
// Service Completion Email Service for NPC Services
// This sends email notifications to users when a technician completes their service

// Mark this file as included to prevent duplicate execution
define('SERVICE_COMPLETION_EMAIL_INCLUDED', true);

// Function to send service completion email
function sendServiceCompletionEmail($booking_id, $technician_id) {
    try {
        // Use absolute path to ensure conn.php is found
        $connPath = __DIR__ . '/conn.php';
        if (file_exists($connPath)) {
            require_once $connPath;
        } else {
            // Fallback to relative path
            require_once 'conn.php';
        }
        
        // Verify connection
        if (!isset($conn) || !$conn) {
            error_log("Service completion email - Database connection failed");
            return [
                'status' => 'error',
                'message' => 'Database connection failed'
            ];
        }
        
        // Get booking details first
        $bookingQuery = "
            SELECT b.*, u.customer_name, u.email_id, u.mobile_number, b.service_name
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_id = ?
        ";
        $bookingStmt = $conn->prepare($bookingQuery);
        $bookingStmt->bind_param("s", $booking_id);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        $booking = $bookingResult->fetch_assoc();
        
        if (!$booking) {
            error_log("Service completion email - Booking not found: $booking_id");
            return [
                'status' => 'error',
                'message' => 'Booking not found'
            ];
        }
        
        // Extract service name for duplicate check
        $service_name = $booking['service_name'];
        
        // Check if service completion email was already sent recently for this specific booking (within last 5 minutes)
        $recentEmailQuery = "
            SELECT COUNT(*) as recent_count 
            FROM email_logs 
            WHERE booking_id = ? 
            AND service_name = ? 
            AND sent_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ";
        $recentEmailStmt = $conn->prepare($recentEmailQuery);
        $recentEmailStmt->bind_param("ss", $booking_id, $service_name);
        $recentEmailStmt->execute();
        $recentEmailResult = $recentEmailStmt->get_result();
        $recentEmail = $recentEmailResult->fetch_assoc();
        
        if ($recentEmail['recent_count'] > 0) {
            error_log("Service completion email - Duplicate prevention: Service completion email already sent recently for booking: $booking_id");
            return [
                'status' => 'success',
                'message' => 'Service completion email already sent recently for this booking',
                'data' => [
                    'booking_id' => $booking_id,
                    'service_name' => $service_name,
                    'duplicate_prevented' => true
                ]
            ];
        }
        
        // Get technician details
        $technicianQuery = "SELECT employee_name, phone_number FROM technicians WHERE technician_id = ?";
        $technicianStmt = $conn->prepare($technicianQuery);
        $technicianStmt->bind_param("i", $technician_id);
        $technicianStmt->execute();
        $technicianResult = $technicianStmt->get_result();
        $technician = $technicianResult->fetch_assoc();
        
        if (!$technician) {
            error_log("Service completion email - Technician not found: $technician_id");
            return [
                'status' => 'error',
                'message' => 'Technician not found'
            ];
        }
        
        // Extract data
        $user_name = $booking['customer_name'];
        $user_email = $booking['email_id'];
        $user_phone = $booking['mobile_number'];
        $service_date = $booking['service_date'];
        $time_slot = $booking['time_slot'];
        $technician_name = $technician['employee_name'];
        $technician_phone = $technician['phone_number'];
        $completion_date = date('Y-m-d H:i:s');
        
        // Email subject
        $subject = "Service Completed - Booking #$booking_id";
        
        // Email body
        $emailBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Service Completed</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                .success-icon { font-size: 48px; margin-bottom: 10px; }
                .booking-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #4CAF50; }
                .technician-info { background: #e8f5e8; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .highlight { color: #4CAF50; font-weight: bold; }
                .contact-info { background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='success-icon'>✅</div>
                    <h1>Service Completed Successfully!</h1>
                    <p>Your service has been completed by our technician</p>
                </div>
                
                <div class='content'>
                    <h2>Dear $user_name,</h2>
                    
                    <p>We're pleased to inform you that your service has been completed successfully!</p>
                    
                    <div class='booking-details'>
                        <h3>📋 Service Details</h3>
                        <p><strong>Booking ID:</strong> <span class='highlight'>$booking_id</span></p>
                        <p><strong>Service:</strong> $service_name</p>
                        <p><strong>Service Date:</strong> $service_date</p>
                        <p><strong>Time Slot:</strong> $time_slot</p>
                        <p><strong>Completion Date:</strong> $completion_date</p>
                    </div>
                    
                    <div class='technician-info'>
                        <h3>👨‍🔧 Technician Information</h3>
                        <p><strong>Technician Name:</strong> $technician_name</p>
                        <p><strong>Contact Number:</strong> $technician_phone</p>
                    </div>
                    
                    <h3>📞 Need Help?</h3>
                    <p>If you have any questions about the service or need further assistance, please don't hesitate to contact us:</p>
                    
                    <div class='contact-info'>
                        <p><strong>Customer Support:</strong> +91 1234567890</p>
                        <p><strong>Email:</strong> support@npcpest.com</p>
                        <p><strong>Website:</strong> www.npcpest.com</p>
                    </div>
                    
                    <h3>⭐ Rate Your Experience</h3>
                    <p>We value your feedback! Please take a moment to rate your experience with our service. Your feedback helps us improve and serve you better.</p>
                    
                    <p><strong>Thank you for choosing NPC Services!</strong></p>
                    
                    <p>Best regards,<br>
                    <strong>NPC Services Team</strong></p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>&copy; 2024 NPC Services. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Email headers
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: NPC Services <noreply@npcpest.com>',
            'Reply-To: support@npcpest.com',
            'X-Mailer: PHP/' . phpversion()
        );
        
        // Send email
        $email_sent = mail($user_email, $subject, $emailBody, implode("\r\n", $headers));
        
        // Log email attempt
        $logQuery = "
            INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("ssssd", $user_email, $user_name, $booking_id, $service_name, $booking['total_amount']);
        $logStmt->execute();
        
        if ($email_sent) {
            return [
                'status' => 'success',
                'message' => 'Service completion email sent successfully',
                'data' => [
                    'booking_id' => $booking_id,
                    'technician_name' => $technician_name,
                    'technician_phone' => $technician_phone,
                    'user_email' => $user_email,
                    'user_name' => $user_name,
                    'service_name' => $service_name,
                    'completion_date' => $completion_date
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to send service completion email. Please check server mail configuration.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Service completion email - Error: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'An error occurred while sending the service completion email: ' . $e->getMessage()
        ];
    }
}

// Handle direct HTTP requests to this file (only when accessed directly, not when included)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['SCRIPT_NAME']) === 'service_completion_email.php' && !defined('SERVICE_COMPLETION_EMAIL_INCLUDED')) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    try {
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
            exit;
        }
        
        $booking_id = isset($data['booking_id']) ? trim($data['booking_id']) : null;
        $technician_id = isset($data['technician_id']) ? trim($data['technician_id']) : null;
        
        if (!$booking_id || !$technician_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Booking ID and Technician ID are required']);
            exit;
        }
        
        $result = sendServiceCompletionEmail($booking_id, $technician_id);
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Service completion email - Error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred while sending the service completion email: ' . $e->getMessage()
        ]);
    }
}
?>
