<?php
// Debug version of technician_status_update.php to identify the issue

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Session-ID, session-id');
header('Access-Control-Max-Age: 86400');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conn.php';

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Function to log errors
function logError($message) {
    error_log("Technician Status Update Debug Error: " . $message);
}

// POST - Update technician status for a booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("=== DEBUG: Starting technician status update ===");
        
        // Get Technician-ID from headers
        $headers = getallheaders();
        $technicianIdFromHeader = null;
        
        if ($headers) {
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'technician-id') {
                    $technicianIdFromHeader = trim($value);
                    break;
                }
            }
        }

        error_log("DEBUG: Technician ID from header: " . ($technicianIdFromHeader ?? 'null'));

        if (!$technicianIdFromHeader) {
            error_log("DEBUG: No technician ID found in headers");
            sendJsonResponse([
                'status' => 'error', 
                'message' => 'Technician ID is required'
            ], 401);
        }

        // Check database connection
        if (!$conn) {
            logError("Database connection failed");
            sendJsonResponse([
                'status' => 'error', 
                'message' => 'Database connection failed'
            ], 500);
        }

        error_log("DEBUG: Database connection successful");

        // First try session-based lookup
        $sessionQuery = "SELECT technician_id, employee_name FROM technicians WHERE sessionid = ? AND session_expiry > NOW()";
        $sessionStmt = $conn->prepare($sessionQuery);
        
        if (!$sessionStmt) {
            logError("Failed to prepare session query: " . $conn->error);
            sendJsonResponse([
                'status' => 'error', 
                'message' => 'Database query preparation failed'
            ], 500);
        }
        
        $sessionStmt->bind_param("s", $technicianIdFromHeader);
        $sessionStmt->execute();
        $sessionResult = $sessionStmt->get_result();
        $technician = $sessionResult->fetch_assoc();
        $sessionStmt->close();
        
        // If session lookup fails, try direct technician ID lookup
        if (!$technician && is_numeric($technicianIdFromHeader)) {
            error_log("DEBUG: Session lookup failed, trying direct lookup for ID: $technicianIdFromHeader");
            $directQuery = "SELECT technician_id, employee_name FROM technicians WHERE technician_id = ?";
            $directStmt = $conn->prepare($directQuery);
            
            if (!$directStmt) {
                logError("Failed to prepare direct query: " . $conn->error);
                sendJsonResponse([
                    'status' => 'error', 
                    'message' => 'Database query preparation failed'
                ], 500);
            }
            
            $directStmt->bind_param("i", $technicianIdFromHeader);
            $directStmt->execute();
            $directResult = $directStmt->get_result();
            $technician = $directResult->fetch_assoc();
            $directStmt->close();
        }
        
        if (!$technician) {
            logError("Technician not found for ID: $technicianIdFromHeader");
            sendJsonResponse([
                'status' => 'error', 
                'message' => 'Technician not found or session expired'
            ], 404);
        }

        error_log("DEBUG: Technician found - ID: " . $technician['technician_id'] . ", Name: " . $technician['employee_name']);
        
        // Use the actual technician_id from the database
        $actualTechnicianId = $technician['technician_id'];

        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        error_log("DEBUG: Raw input: " . $input);
        error_log("DEBUG: Decoded data: " . json_encode($data));
        
        if (!$data) {
            sendJsonResponse([
                'status' => 'error', 
                'message' => 'Invalid JSON data'
            ], 400);
        }
        
        $bookingId = isset($data['booking_id']) ? trim($data['booking_id']) : null;
        $status = isset($data['status']) ? trim($data['status']) : null;
        $notes = isset($data['notes']) ? trim($data['notes']) : null;
        
        error_log("DEBUG: Extracted data - Booking ID: $bookingId, Status: $status, Notes: $notes");
        
        if (!$bookingId || !$status) {
            sendJsonResponse([
                'status' => 'error', 
                'message' => 'Booking ID and status are required'
            ], 400);
        }

        // Validate status
        $validStatuses = ['assigned', 'reached', 'started', 'completed'];
        if (!in_array($status, $validStatuses)) {
            sendJsonResponse([
                'status' => 'error', 
                'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses)
            ], 400);
        }

        error_log("DEBUG: Status validation passed");

        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if booking exists and is assigned to this technician
            $bookingQuery = "
                SELECT booking_id, assigned_technician, booking_status, user_id, service_name,
                       payment_method, payment_status
                FROM bookings 
                WHERE booking_id = ? AND assigned_technician = ?
            ";
            $bookingStmt = $conn->prepare($bookingQuery);
            
            if (!$bookingStmt) {
                throw new Exception("Failed to prepare booking query: " . $conn->error);
            }
            
            $bookingStmt->bind_param("si", $bookingId, $actualTechnicianId);
            $bookingStmt->execute();
            $bookingResult = $bookingStmt->get_result();
            $booking = $bookingResult->fetch_assoc();
            $bookingStmt->close();
            
            if (!$booking) {
                $conn->rollback();
                logError("Booking not found for booking_id: $bookingId, technician_id: $actualTechnicianId");
                sendJsonResponse([
                    'status' => 'error', 
                    'message' => 'Booking not found or not assigned to this technician'
                ], 404);
            }

            error_log("DEBUG: Booking found - Status: " . $booking['booking_status'] . ", Service: " . $booking['service_name']);

            // Update booking status
            $updateQuery = "UPDATE bookings SET booking_status = ? WHERE booking_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ss", $status, $bookingId);
            $updateStmt->execute();
            $updateStmt->close();

            error_log("DEBUG: Booking status updated to: $status");

            // Log the status change
            $logQuery = "INSERT INTO technician_status_log (booking_id, technician_id, status, notes, timestamp) VALUES (?, ?, ?, ?, NOW())";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->bind_param("siss", $bookingId, $actualTechnicianId, $status, $notes);
            $logStmt->execute();
            $logStmt->close();

            error_log("DEBUG: Status change logged");

            // Commit transaction
            $conn->commit();

            error_log("DEBUG: Transaction committed");

            // Prepare response
            $response = [
                'status' => 'success',
                'message' => 'Status updated successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'technician_id' => $actualTechnicianId,
                    'technician_name' => $technician['employee_name'],
                    'previous_status' => $booking['booking_status'],
                    'new_status' => $status,
                    'booking_status' => $status,
                    'payment_method' => $booking['payment_method'] ?? null,
                    'payment_status' => $booking['payment_status'] ?? null,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'notes' => $notes
                ]
            ];

            error_log("DEBUG: Response prepared");

            // Send service completion email if status is 'completed'
            if ($status === 'completed') {
                error_log("DEBUG: Status is 'completed', attempting to send email...");
                
                try {
                    error_log("Starting service completion email process for booking: $bookingId");
                    
                    // Check if email file exists before including
                    if (file_exists('service_completion_email.php')) {
                        error_log("DEBUG: service_completion_email.php file exists");
                        
                        // Include and call the email service directly
                        require_once 'service_completion_email.php';
                        
                        if (function_exists('sendServiceCompletionEmail')) {
                            error_log("DEBUG: sendServiceCompletionEmail function exists, calling it...");
                            $emailResult = sendServiceCompletionEmail($bookingId, $actualTechnicianId);
                            
                            error_log("DEBUG: Email function result: " . json_encode($emailResult));
                            
                            if ($emailResult && $emailResult['status'] === 'success') {
                                error_log("Service completion email sent successfully for booking: $bookingId");
                            } else {
                                error_log("Failed to send service completion email: " . ($emailResult['message'] ?? 'Unknown error'));
                            }
                        } else {
                            error_log("sendServiceCompletionEmail function not found");
                        }
                    } else {
                        error_log("service_completion_email.php file not found");
                    }
                } catch (Exception $emailError) {
                    error_log("Error sending service completion email: " . $emailError->getMessage());
                    error_log("DEBUG: Email error stack trace: " . $emailError->getTraceAsString());
                }
            } else {
                error_log("DEBUG: Status is not 'completed', skipping email");
            }
            
            error_log("DEBUG: About to send JSON response");
            
            // Send response to frontend
            sendJsonResponse($response, 200);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction) {
            $conn->rollback();
        }
        
        logError("Status update error: " . $e->getMessage());
        error_log("DEBUG: Exception stack trace: " . $e->getTraceAsString());
        sendJsonResponse([
            'status' => 'error',
            'message' => 'An error occurred while updating status',
            'debug' => $e->getMessage()
        ], 500);
    }
}

// GET - Get technician's assigned bookings (simplified for debug)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'GET method not implemented in debug version'
    ], 405);
}

sendJsonResponse([
    'status' => 'error',
    'message' => 'Invalid request method'
], 405);
?>
