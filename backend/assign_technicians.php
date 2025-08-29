<?php
// assign_technician.php - Assign technician to booking

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Session-ID");
header("Content-Type: application/json; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'technician_assignment.log');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conn.php';

// Function to send technician notification email
function sendTechnicianNotificationEmail($conn, $bookingId, $technicianId) {
    try {
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
        
        $bookingStmt->bind_param("s", $bookingId);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        $booking = $bookingResult->fetch_assoc();
        
        if (!$booking) {
            error_log("Technician notification email - Booking not found: $bookingId");
            return false;
        }
        
        // Get technician details
        $technicianQuery = "SELECT employee_name, phone_number, service_type, email FROM technicians WHERE technician_id = ?";
        $technicianStmt = $conn->prepare($technicianQuery);
        
        if (!$technicianStmt) {
            throw new Exception("Failed to prepare technician query: " . $conn->error);
        }
        
        $technicianStmt->bind_param("i", $technicianId);
        $technicianStmt->execute();
        $technicianResult = $technicianStmt->get_result();
        $technician = $technicianResult->fetch_assoc();
        
        if (!$technician) {
            error_log("Technician notification email - Technician not found: $technicianId");
            return false;
        }
        
        // Check if technician has email
        if (empty($technician['email'])) {
            error_log("Technician notification email - Technician has no email: $technicianId");
            return false;
        }
        
        // Extract data
        $technician_email = $technician['email'];
        $technician_name = $technician['employee_name'];
        $technician_phone = $technician['phone_number'];
        $technician_service_type = $technician['service_type'];
        
        $customer_name = $booking['customer_name'];
        $customer_phone = $booking['mobile_number'];
        $service_name = $booking['service_name'];
        $service_date = $booking['service_date'];
        $time_slot = $booking['time_slot'];
        $service_address = $booking['service_address'];
        $total_amount = $booking['total_amount'];
        $payment_status = $booking['payment_status'];
        
        error_log("Sending technician notification email to: $technician_email for booking: $bookingId");
        
        // Email subject and content
        $subject = "New Service Assigned - Booking ID: #$bookingId";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .job-details { background: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #FF9800; }
                .customer-details { background: #fff3e0; padding: 15px; margin: 20px 0; border-left: 4px solid #FF9800; }
                .amount { color: #FF9800; font-weight: bold; font-size: 18px; }
                .footer { background: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .urgent { color: #f44336; font-weight: bold; }
                .action-required { background: #ffebee; padding: 10px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Service Assigned</h1>
                    <p>NPC Services - Technician Portal</p>
                </div>
                
                <div class='content'>
                    <h2>Hello $technician_name,</h2>
                    
                    <p class='urgent'>You have been assigned to a new service. Please review the details below.</p>
                    
                    <div class='action-required'>
                        <h3>⚠️ Action Required:</h3>
                        <ul>
                            <li>Contact the customer to confirm the appointment</li>
                            <li>Arrive at the scheduled time</li>
                            <li>Update job status in your portal</li>
                        </ul>
                    </div>
                    
                    <div class='job-details'>
                        <h3>📋 Service Details:</h3>
                        <p><strong>Booking ID:</strong> #$bookingId</p>
                        <p><strong>Service:</strong> $service_name</p>
                        <p><strong>Service Date:</strong> $service_date</p>
                        <p><strong>Service Time:</strong> $time_slot</p>
                        <p><strong>Service Address:</strong> $service_address</p>
                        <p><strong>Total Amount:</strong> <span class='amount'>₹$total_amount</span></p>
                        <p><strong>Payment Status:</strong> $payment_status</p>
                    </div>
                    
                    <div class='customer-details'>
                        <h3>👤 Customer Information:</h3>
                        <p><strong>Customer Name:</strong> $customer_name</p>
                        <p><strong>Phone Number:</strong> <a href='tel:$customer_phone'>$customer_phone</a></p>
                        <p><strong>Service Type:</strong> $technician_service_type</p>
                    </div>
                    
                    <h3>📞 Important Instructions:</h3>
                    <ul>
                        <li><strong>Contact the customer</strong> at least 1 hour before the scheduled time</li>
                        <li><strong>Introduce yourself</strong> and show your ID when you arrive</li>
                        <li><strong>Explain the service process</strong> before starting</li>
                        <li><strong>Update job status</strong> in your technician portal</li>
                        <li><strong>Report any issues</strong> immediately to the admin</li>
                    </ul>
                    
                    <p><strong>Please ensure you arrive on time and provide excellent service.</strong></p>
                    
                    <p>You can manage this service from your technician dashboard.</p>
                    
                    <p>Best regards,<br>
                    NPC Services Management</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from NPC Services technician portal.</p>
                    <p>Booking ID: #$bookingId | Service Assigned at: " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send HTML email to technician
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: NPC Services <info@npcpest.com>',
            'Reply-To: support@npcservices.com',
            'X-Mailer: PHP/' . phpversion()
        );
        
        // Send email to technician
        $mailSent = mail($technician_email, $subject, $message, implode("\r\n", $headers));
        
        if ($mailSent) {
            error_log("Technician notification email sent successfully to: $technician_email");
            return true;
        } else {
            error_log("Failed to send technician notification email to: $technician_email");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Technician notification email error: " . $e->getMessage());
        return false;
    }
}

// POST - Assign technician to booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get Session-ID from headers for admin authentication
        $headers = getallheaders();
        $sessionId = null;
        
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'session-id') {
                $sessionId = $value;
                break;
            }
        }

        if (!$sessionId) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Admin session required']);
            exit;
        }

        // Verify admin session using admin_login table
        $adminQuery = "SELECT id FROM admin_login WHERE sessionid = ? AND session_expiry > NOW()";
        $adminStmt = $conn->prepare($adminQuery);
        
        if (!$adminStmt) {
            throw new Exception("Failed to prepare admin query: " . $conn->error);
        }
        
        $adminStmt->bind_param("s", $sessionId);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        $admin = $adminResult->fetch_assoc();
        
        if (!$admin) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid admin session']);
            exit;
        }

        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
            exit;
        }
        
        $bookingId = isset($data['booking_id']) ? trim($data['booking_id']) : null;
        $technicianId = isset($data['technician_id']) ? trim($data['technician_id']) : null;
        
        if (!$bookingId || !$technicianId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Booking ID and Technician ID are required']);
            exit;
        }

        // Start transaction
        $conn->begin_transaction();

        // Check if booking exists and is in pending status
        $bookingQuery = "SELECT booking_id, booking_status, service_name, service_date, time_slot, user_id FROM bookings WHERE booking_id = ? AND booking_status = 'pending'";
        $bookingStmt = $conn->prepare($bookingQuery);
        
        if (!$bookingStmt) {
            throw new Exception("Failed to prepare booking query: " . $conn->error);
        }
        
        $bookingStmt->bind_param("s", $bookingId);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        $booking = $bookingResult->fetch_assoc();
        
        if (!$booking) {
            $conn->rollback();
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Booking not found or not in pending status']);
            exit;
        }

        // Check if technician exists and is active
        $technicianQuery = "SELECT technician_id, employee_name, phone_number FROM technicians WHERE technician_id = ?";
        $technicianStmt = $conn->prepare($technicianQuery);
        
        if (!$technicianStmt) {
            throw new Exception("Failed to prepare technician query: " . $conn->error);
        }
        
        $technicianStmt->bind_param("i", $technicianId);
        $technicianStmt->execute();
        $technicianResult = $technicianStmt->get_result();
        $technician = $technicianResult->fetch_assoc();
        
        if (!$technician) {
            $conn->rollback();
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Technician not found or not active']);
            exit;
        }

        // Update booking with assigned technician
        $updateBookingQuery = "
            UPDATE bookings 
            SET assigned_technician = ?, 
                booking_status = 'confirmed',
                updated_at = NOW() 
            WHERE booking_id = ?
        ";
        $updateBookingStmt = $conn->prepare($updateBookingQuery);
        
        if (!$updateBookingStmt) {
            throw new Exception("Failed to prepare update booking query: " . $conn->error);
        }
        
        $updateBookingStmt->bind_param("is", $technicianId, $bookingId);
        $updateBookingResult = $updateBookingStmt->execute();
        
        if (!$updateBookingResult) {
            $conn->rollback();
            throw new Exception("Failed to update booking: " . $conn->error);
        }

        // Log the technician status assignment
        $logQuery = "
            INSERT INTO technician_status_log (booking_id, technician_id, status, timestamp, notes) 
            VALUES (?, ?, 'assigned', NOW(), 'Technician assigned by admin')
        ";
        $logStmt = $conn->prepare($logQuery);
        
        if (!$logStmt) {
            throw new Exception("Failed to prepare log query: " . $conn->error);
        }
        
        $logStmt->bind_param("si", $bookingId, $technicianId);
        $logResult = $logStmt->execute();
        
        if (!$logResult) {
            $conn->rollback();
            throw new Exception("Failed to log status: " . $conn->error);
        }

        // Get customer details for notification
        $customerQuery = "SELECT customer_name, mobile_number FROM users WHERE user_id = ?";
        $customerStmt = $conn->prepare($customerQuery);
        
        if (!$customerStmt) {
            throw new Exception("Failed to prepare customer query: " . $conn->error);
        }
        
        $customerStmt->bind_param("i", $booking['user_id']);
        $customerStmt->execute();
        $customerResult = $customerStmt->get_result();
        $customer = $customerResult->fetch_assoc();

        // Create notification for technician
        $technicianNotificationTitle = "New Job Assignment";
        $technicianNotificationMessage = "You have been assigned to a new job. Booking ID: {$bookingId}. Service: {$booking['service_name']}. Date: {$booking['service_date']} at {$booking['time_slot']}.";
        
        $technicianNotificationQuery = "
            INSERT INTO notifications (user_type, user_id, title, message, type, related_booking_id, created_at) 
            VALUES ('technician', ?, ?, ?, 'booking_assigned', ?, NOW())
        ";
        $technicianNotificationStmt = $conn->prepare($technicianNotificationQuery);
        
        if (!$technicianNotificationStmt) {
            throw new Exception("Failed to prepare technician notification query: " . $conn->error);
        }
        
        $technicianNotificationStmt->bind_param("isss", $technicianId, $technicianNotificationTitle, $technicianNotificationMessage, $bookingId);
        $technicianNotificationResult = $technicianNotificationStmt->execute();
        
        if (!$technicianNotificationResult) {
            $conn->rollback();
            throw new Exception("Failed to create technician notification: " . $conn->error);
        }

        // Create notification for customer
        if ($customer) {
            $customerNotificationTitle = "Technician Assigned";
            $customerNotificationMessage = "Good news! Technician {$technician['employee_name']} has been assigned to your booking #{$bookingId}. They will contact you soon.";
            
            $customerNotificationQuery = "
                INSERT INTO notifications (user_type, user_id, title, message, type, related_booking_id, created_at) 
                VALUES ('customer', ?, ?, ?, 'status_update', ?, NOW())
            ";
            $customerNotificationStmt = $conn->prepare($customerNotificationQuery);
            
            if (!$customerNotificationStmt) {
                throw new Exception("Failed to prepare customer notification query: " . $conn->error);
            }
            
            $customerNotificationStmt->bind_param("isss", $booking['user_id'], $customerNotificationTitle, $customerNotificationMessage, $bookingId);
            $customerNotificationResult = $customerNotificationStmt->execute();
            
            if (!$customerNotificationResult) {
                $conn->rollback();
                throw new Exception("Failed to create customer notification: " . $conn->error);
            }
        }

        // Commit transaction
        $conn->commit();
        
        // Prepare response first
        $response = [
            'status' => 'success',
            'message' => 'Technician assigned successfully',
            'data' => [
                'booking_id' => $bookingId,
                'technician_id' => $technicianId,
                'technician_name' => $technician['employee_name'],
                'technician_phone' => $technician['phone_number'],
                'booking_status' => 'confirmed',
                'technician_status' => 'assigned',
                'assigned_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        // Send response to frontend immediately
        http_response_code(200);
        echo json_encode($response);
        
        // Log the response sent to frontend
        error_log("Response sent to frontend: " . json_encode($response));
        
        // Send email notification to user about technician assignment (asynchronous)
        try {
            error_log("Starting email sending process for booking: $bookingId");
            // Include and call the email service directly
            require_once 'technician_assignment_email.php';
            $emailResult = sendTechnicianAssignmentEmail($bookingId, $technicianId);
            
            if ($emailResult && $emailResult['status'] === 'success') {
                error_log("Technician assignment email sent successfully for booking: $bookingId");
                
                // Send technician notification email after user email is sent successfully
                try {
                    $technicianEmailSent = sendTechnicianNotificationEmail($conn, $bookingId, $technicianId);
                    if ($technicianEmailSent) {
                        error_log("Technician notification email sent successfully for booking: $bookingId");
                    } else {
                        error_log("Failed to send technician notification email for booking: $bookingId");
                    }
                } catch (Exception $technicianEmailError) {
                    error_log("Error sending technician notification email: " . $technicianEmailError->getMessage());
                }
            } else {
                error_log("Failed to send technician assignment email: " . ($emailResult['message'] ?? 'Unknown error'));
            }
        } catch (Exception $emailError) {
            error_log("Error sending technician assignment email: " . $emailError->getMessage());
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction) {
            $conn->rollback();
        }
        
        error_log("Technician assignment error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error occurred',
            'error' => $e->getMessage()
        ]);
    }
}

$conn->close();
?>