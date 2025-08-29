<?php
require 'conn.php';
require 'functions.php';

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Get session ID from request headers
$headers = getallheaders();
$sessionId = $headers['Session-ID'] ?? '';

// Validate session ID
if (empty($sessionId) || !validateSession($conn, $sessionId)) {
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access. Invalid or expired session."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['booking_id']) || empty($input['status'])) {
        echo json_encode(['status' => 'error', 'message' => 'Booking ID and status are required.']);
        exit;
    }

    $booking_id = $input['booking_id'];
    $status = $input['status'];
    $notes = $input['notes'] ?? '';

    // Fetch booking details
    $stmt = $conn->prepare("
        SELECT b.*, s.has_session2, s.service_name 
        FROM bookings b 
        LEFT JOIN services s ON b.service_name = s.service_name 
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found.']);
        exit;
    }

    $has_session2 = $booking['has_session2'] ?? 0;
    $current_status = $booking['booking_status'];
    $current_session2_status = $booking['session2_status'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Handle status updates based on whether service has session 2
        if ($has_session2 && $status === 'completed' && $current_session2_status === null) {
            // First session completed, move to session 2
            $session2_date = date('Y-m-d', strtotime('+15 days'));
            
            $updateStmt = $conn->prepare("
                UPDATE bookings 
                SET booking_status = 'session2_pending', 
                    session2_status = 'pending',
                    session2_date = ?,
                    updated_at = NOW() 
                WHERE booking_id = ?
            ");
            $updateStmt->bind_param("ss", $session2_date, $booking_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update booking status");
            }
            
            $status_message = "First session completed. Session 2 scheduled for " . date('d M Y', strtotime($session2_date));
            
        } elseif ($has_session2 && $status === 'completed' && $current_session2_status === 'pending') {
            // Session 2 completed, move to final completed status
            $updateStmt = $conn->prepare("
                UPDATE bookings 
                SET booking_status = 'completed', 
                    session2_status = 'completed',
                    session2_completed_date = NOW(),
                    updated_at = NOW() 
                WHERE booking_id = ?
            ");
            $updateStmt->bind_param("s", $booking_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update booking status");
            }
            
            $status_message = "Service completed (both sessions)";
            
        } else {
            // Regular status update (no session 2 or not a completion status)
            $updateStmt = $conn->prepare("
                UPDATE bookings 
                SET booking_status = ?, 
                    updated_at = NOW() 
                WHERE booking_id = ?
            ");
            $updateStmt->bind_param("ss", $status, $booking_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update booking status");
            }
            
            $status_message = "Status updated to " . ucfirst($status);
        }

        // Log the status update
        $logStmt = $conn->prepare("
            INSERT INTO booking_status_log (booking_id, status, notes, updated_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $logStmt->bind_param("sss", $booking_id, $status, $notes);
        $logStmt->execute();

        $conn->commit();
        
        echo json_encode([
            'status' => 'success', 
            'message' => $status_message,
            'booking_status' => $status
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

$conn->close();
?>
