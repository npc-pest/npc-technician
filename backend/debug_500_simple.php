<?php
// Simple debug version to find the 500 error

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== Simple Debug Test ===\n\n";

try {
    echo "Step 1: Including conn.php...\n";
    require_once 'conn.php';
    echo "✅ Database connection successful\n\n";
    
    echo "Step 2: Testing technician lookup...\n";
    $technicianId = 'c0e24a60702832b71e9678ae6d836d74143133e03c8a39c444e13b5e9046c877';
    
    $techQuery = "SELECT technician_id, employee_name FROM technicians WHERE sessionid = ? AND session_expiry > NOW()";
    $techStmt = $conn->prepare($techQuery);
    $techStmt->bind_param("s", $technicianId);
    $techStmt->execute();
    $technician = $techStmt->get_result()->fetch_assoc();
    $techStmt->close();
    
    if ($technician) {
        echo "✅ Technician found: " . $technician['employee_name'] . "\n";
    } else {
        echo "❌ Technician not found\n";
    }
    
    echo "\nStep 3: Testing booking lookup...\n";
    $bookingId = 'BK202508123634';
    $actualTechnicianId = $technician ? $technician['technician_id'] : 1;
    
    $bookingQuery = "SELECT booking_id, assigned_technician, booking_status, user_id, service_name FROM bookings WHERE booking_id = ? AND assigned_technician = ?";
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->bind_param("si", $bookingId, $actualTechnicianId);
    $bookingStmt->execute();
    $booking = $bookingStmt->get_result()->fetch_assoc();
    $bookingStmt->close();
    
    if ($booking) {
        echo "✅ Booking found: " . $booking['booking_id'] . " (Status: " . $booking['booking_status'] . ")\n";
    } else {
        echo "❌ Booking not found\n";
    }
    
    echo "\nStep 4: Testing status update...\n";
    $status = 'completed';
    
    $updateQuery = "UPDATE bookings SET booking_status = ? WHERE booking_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ss", $status, $bookingId);
    $result = $updateStmt->execute();
    $updateStmt->close();
    
    if ($result) {
        echo "✅ Status update successful\n";
    } else {
        echo "❌ Status update failed\n";
    }
    
    echo "\nStep 5: Testing log insert...\n";
    $notes = 'Test completion';
    
    $logQuery = "INSERT INTO technician_status_log (booking_id, technician_id, status, notes, timestamp) VALUES (?, ?, ?, ?, NOW())";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bind_param("siss", $bookingId, $actualTechnicianId, $status, $notes);
    $result = $logStmt->execute();
    $logStmt->close();
    
    if ($result) {
        echo "✅ Log insert successful\n";
    } else {
        echo "❌ Log insert failed\n";
    }
    
    echo "\nStep 6: Testing JSON response...\n";
    $response = [
        'status' => 'success',
        'message' => 'Status updated successfully',
        'data' => [
            'booking_id' => $bookingId,
            'technician_id' => $actualTechnicianId,
            'technician_name' => $technician ? $technician['employee_name'] : 'Unknown',
            'previous_status' => $booking ? $booking['booking_status'] : 'unknown',
            'new_status' => $status,
            'booking_status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'notes' => $notes
        ]
    ];
    
    $jsonResponse = json_encode($response);
    if ($jsonResponse !== false) {
        echo "✅ JSON encoding successful\n";
        echo "Response: $jsonResponse\n";
    } else {
        echo "❌ JSON encoding failed: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
