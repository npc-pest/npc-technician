<?php
// Final version - No dependency on technician_status_log table

// Start output buffering
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Technician-ID");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit;
}

// Clear output buffer
ob_clean();

// Database connection
require_once 'conn.php';

// Response function
function sendResponse($data, $statusCode = 200) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// POST request handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get headers
        $headers = getallheaders();
        $technicianId = null;
        
        if ($headers) {
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'technician-id') {
                    $technicianId = trim($value);
                    break;
                }
            }
        }

        if (!$technicianId) {
            sendResponse([
                'status' => 'error',
                'message' => 'Technician ID required'
            ], 401);
        }

        // Check database connection
        if (!$conn) {
            sendResponse([
                'status' => 'error',
                'message' => 'Database connection failed'
            ], 500);
        }

        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            sendResponse([
                'status' => 'error',
                'message' => 'Invalid JSON data'
            ], 400);
        }
        
        $bookingId = isset($data['booking_id']) ? trim($data['booking_id']) : null;
        $status = isset($data['status']) ? trim($data['status']) : null;
        $notes = isset($data['notes']) ? trim($data['notes']) : null;
        
        if (!$bookingId || !$status) {
            sendResponse([
                'status' => 'error',
                'message' => 'Booking ID and status required'
            ], 400);
        }

        // Validate status
        $validStatuses = ['assigned', 'reached', 'started', 'completed'];
        if (!in_array($status, $validStatuses)) {
            sendResponse([
                'status' => 'error',
                'message' => 'Invalid status'
            ], 400);
        }

        // Find technician
        $techQuery = "SELECT technician_id, employee_name FROM technicians WHERE sessionid = ? AND session_expiry > NOW()";
        $techStmt = $conn->prepare($techQuery);
        $techStmt->bind_param("s", $technicianId);
        $techStmt->execute();
        $technician = $techStmt->get_result()->fetch_assoc();
        $techStmt->close();
        
        // Fallback to direct ID lookup
        if (!$technician && is_numeric($technicianId)) {
            $techQuery = "SELECT technician_id, employee_name FROM technicians WHERE technician_id = ?";
            $techStmt = $conn->prepare($techQuery);
            $techStmt->bind_param("i", $technicianId);
            $techStmt->execute();
            $technician = $techStmt->get_result()->fetch_assoc();
            $techStmt->close();
        }
        
        if (!$technician) {
            sendResponse([
                'status' => 'error',
                'message' => 'Technician not found'
            ], 404);
        }

        $actualTechnicianId = $technician['technician_id'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // Check booking
            $bookingQuery = "SELECT booking_id, assigned_technician, booking_status, user_id, service_name FROM bookings WHERE booking_id = ? AND assigned_technician = ?";
            $bookingStmt = $conn->prepare($bookingQuery);
            $bookingStmt->bind_param("si", $bookingId, $actualTechnicianId);
            $bookingStmt->execute();
            $booking = $bookingStmt->get_result()->fetch_assoc();
            $bookingStmt->close();
            
            if (!$booking) {
                $conn->rollback();
                sendResponse([
                    'status' => 'error',
                    'message' => 'Booking not found or not assigned'
                ], 404);
            }

            // Update booking status
            $updateQuery = "UPDATE bookings SET booking_status = ? WHERE booking_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ss", $status, $bookingId);
            $updateStmt->execute();
            $updateStmt->close();

            // Try to log status change (optional - won't break if table doesn't exist)
            try {
                $logQuery = "INSERT INTO technician_status_log (booking_id, technician_id, status, notes, timestamp) VALUES (?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("siss", $bookingId, $actualTechnicianId, $status, $notes);
                $logStmt->execute();
                $logStmt->close();
            } catch (Exception $logError) {
                // Ignore logging errors - they don't affect core functionality
                error_log("Status log error (non-critical): " . $logError->getMessage());
            }

            // Commit transaction
            $conn->commit();

            // Send email if completed
            if ($status === 'completed') {
                try {
                    // Get user email
                    $userQuery = "SELECT u.customer_name, u.email_id, b.service_name FROM bookings b JOIN users u ON b.user_id = u.user_id WHERE b.booking_id = ?";
                    $userStmt = $conn->prepare($userQuery);
                    $userStmt->bind_param("s", $bookingId);
                    $userStmt->execute();
                    $user = $userStmt->get_result()->fetch_assoc();
                    $userStmt->close();
                    
                    if ($user && $user['email_id']) {
                        // Get technician details
                        $techQuery = "SELECT employee_name, phone_number FROM technicians WHERE technician_id = ?";
                        $techStmt = $conn->prepare($techQuery);
                        $techStmt->bind_param("i", $actualTechnicianId);
                        $techStmt->execute();
                        $tech = $techStmt->get_result()->fetch_assoc();
                        $techStmt->close();
                        
                        if ($tech) {
                            // Send email
                            $to = $user['email_id'];
                            $subject = "Service Completed - Booking #$bookingId";
                            
                            $message = "
                            <html>
                            <body>
                                <h2>Service Completed Successfully!</h2>
                                <p>Dear {$user['customer_name']},</p>
                                <p>Your service has been completed successfully.</p>
                                <br>
                                <h3>Service Details:</h3>
                                <ul>
                                    <li><strong>Booking ID:</strong> $bookingId</li>
                                    <li><strong>Service:</strong> {$user['service_name']}</li>
                                    <li><strong>Technician:</strong> {$tech['employee_name']}</li>
                                    <li><strong>Technician Phone:</strong> {$tech['phone_number']}</li>
                                    <li><strong>Completion Date:</strong> " . date('Y-m-d H:i:s') . "</li>
                                </ul>
                                <br>
                                <p>Thank you for choosing our services!</p>
                                <p>Best regards,<br>NPC Services Team</p>
                            </body>
                            </html>
                            ";
                            
                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                            $headers .= "From: NPC Services <noreply@npcpest.com>" . "\r\n";
                            
                            $mailSent = mail($to, $subject, $message, $headers);
                            
                            if ($mailSent) {
                                // Try to log email (optional)
                                try {
                                    $logQuery = "INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) VALUES (?, ?, ?, ?, ?, NOW())";
                                    $logStmt = $conn->prepare($logQuery);
                                    $logStmt->bind_param("sssss", $to, $user['customer_name'], $bookingId, $user['service_name'], '0');
                                    $logStmt->execute();
                                    $logStmt->close();
                                } catch (Exception $emailLogError) {
                                    // Ignore email logging errors
                                    error_log("Email log error (non-critical): " . $emailLogError->getMessage());
                                }
                            }
                        }
                    }
                } catch (Exception $emailError) {
                    error_log("Email error: " . $emailError->getMessage());
                }
            }

            // Send success response
            sendResponse([
                'status' => 'success',
                'message' => 'Status updated successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'technician_id' => $actualTechnicianId,
                    'technician_name' => $technician['employee_name'],
                    'previous_status' => $booking['booking_status'],
                    'new_status' => $status,
                    'booking_status' => $status,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'notes' => $notes
                ]
            ], 200);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction) {
            $conn->rollback();
        }
        
        error_log("Status update error: " . $e->getMessage());
        sendResponse([
            'status' => 'error',
            'message' => 'An error occurred while updating status'
        ], 500);
    }
}

// GET request (simplified)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendResponse([
        'status' => 'error',
        'message' => 'GET method not supported'
    ], 405);
}

// Default response
sendResponse([
    'status' => 'error',
    'message' => 'Invalid request method'
], 405);
?>
