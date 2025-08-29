<?php
require 'conn.php';

// Test script to verify session 2 flow
echo "<h1>Session 2 Flow Test</h1>";

// Test 1: Check if database supports session2_pending status
echo "<h2>Test 1: Database Schema</h2>";
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'booking_status'");
$column = $result->fetch_assoc();

if (strpos($column['Type'], 'session2_pending') !== false) {
    echo "✅ booking_status enum includes 'session2_pending'<br>";
} else {
    echo "❌ booking_status enum missing 'session2_pending'. Run the migration script.<br>";
}

// Test 2: Find a service with session 2 enabled
echo "<h2>Test 2: Service with Session 2</h2>";
$result = $conn->query("SELECT service_id, service_name, has_session2 FROM services WHERE has_session2 = 1 LIMIT 1");
$service = $result->fetch_assoc();

if ($service) {
    echo "✅ Found service with session 2: {$service['service_name']} (ID: {$service['service_id']})<br>";
    $testServiceName = $service['service_name'];
} else {
    echo "❌ No service with session 2 found. Create one first.<br>";
    exit;
}

// Test 3: Find a booking for this service
echo "<h2>Test 3: Booking for Session 2 Service</h2>";
$stmt = $conn->prepare("SELECT booking_id, service_name, booking_status, session2_status, session2_date FROM bookings WHERE service_name = ? AND booking_status = 'completed' LIMIT 1");
$stmt->bind_param("s", $testServiceName);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if ($booking) {
    echo "✅ Found booking: {$booking['booking_id']} for service: {$booking['service_name']}<br>";
    echo "   Current status: {$booking['booking_status']}<br>";
    echo "   Session 2 status: " . ($booking['session2_status'] ?? 'NULL') . "<br>";
    echo "   Session 2 date: " . ($booking['session2_date'] ?? 'NULL') . "<br>";
    
    $testBookingId = $booking['booking_id'];
} else {
    echo "❌ No completed booking found for service: $testServiceName<br>";
    echo "   You need to complete a booking for this service first.<br>";
    exit;
}

// Test 4: Simulate technician completing the first session
echo "<h2>Test 4: Simulate First Session Completion</h2>";
if ($booking['session2_status'] === null) {
    // Simulate the session 2 logic
    $session2Date = date('Y-m-d', strtotime('+15 days'));
    
    $updateQuery = "UPDATE bookings SET booking_status = 'session2_pending', session2_status = 'pending', session2_date = ?, updated_at = NOW() WHERE booking_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ss", $session2Date, $testBookingId);
    
    if ($updateStmt->execute()) {
        echo "✅ Successfully moved booking to session2_pending<br>";
        echo "   Session 2 date set to: $session2Date<br>";
        
        // Log the status update
        $logQuery = "INSERT INTO booking_status_log (booking_id, status, notes, updated_at) VALUES (?, 'session2_pending', 'Test: First session completed, moved to session 2', NOW())";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("s", $testBookingId);
        $logStmt->execute();
        
        echo "✅ Status change logged<br>";
    } else {
        echo "❌ Failed to update booking status<br>";
    }
} else {
    echo "ℹ️ Booking already has session 2 status: {$booking['session2_status']}<br>";
}

// Test 5: Verify the booking is now in session 2
echo "<h2>Test 5: Verify Session 2 Status</h2>";
$stmt = $conn->prepare("SELECT booking_id, service_name, booking_status, session2_status, session2_date FROM bookings WHERE booking_id = ?");
$stmt->bind_param("s", $testBookingId);
$stmt->execute();
$result = $stmt->get_result();
$updatedBooking = $result->fetch_assoc();

if ($updatedBooking) {
    echo "✅ Booking status updated:<br>";
    echo "   Booking ID: {$updatedBooking['booking_id']}<br>";
    echo "   Service: {$updatedBooking['service_name']}<br>";
    echo "   Booking Status: {$updatedBooking['booking_status']}<br>";
    echo "   Session 2 Status: " . ($updatedBooking['session2_status'] ?? 'NULL') . "<br>";
    echo "   Session 2 Date: " . ($updatedBooking['session2_date'] ?? 'NULL') . "<br>";
    
    if ($updatedBooking['booking_status'] === 'session2_pending' && $updatedBooking['session2_status'] === 'pending') {
        echo "✅ Session 2 flow working correctly!<br>";
    } else {
        echo "❌ Session 2 flow not working as expected<br>";
    }
}

// Test 6: Simulate session 2 completion
echo "<h2>Test 6: Simulate Session 2 Completion</h2>";
if ($updatedBooking['session2_status'] === 'pending') {
    $finalUpdateQuery = "UPDATE bookings SET booking_status = 'completed', session2_status = 'completed', session2_completed_date = NOW(), updated_at = NOW() WHERE booking_id = ?";
    $finalUpdateStmt = $conn->prepare($finalUpdateQuery);
    $finalUpdateStmt->bind_param("s", $testBookingId);
    
    if ($finalUpdateStmt->execute()) {
        echo "✅ Successfully completed session 2<br>";
        
        // Log the final completion
        $finalLogQuery = "INSERT INTO booking_status_log (booking_id, status, notes, updated_at) VALUES (?, 'completed', 'Test: Session 2 completed, service fully completed', NOW())";
        $finalLogStmt = $conn->prepare($finalLogQuery);
        $finalLogStmt->bind_param("s", $testBookingId);
        $finalLogStmt->execute();
        
        echo "✅ Final completion logged<br>";
    } else {
        echo "❌ Failed to complete session 2<br>";
    }
}

// Test 7: Final verification
echo "<h2>Test 7: Final Verification</h2>";
$stmt = $conn->prepare("SELECT booking_id, service_name, booking_status, session2_status, session2_date, session2_completed_date FROM bookings WHERE booking_id = ?");
$stmt->bind_param("s", $testBookingId);
$stmt->execute();
$result = $stmt->get_result();
$finalBooking = $result->fetch_assoc();

if ($finalBooking) {
    echo "✅ Final booking status:<br>";
    echo "   Booking ID: {$finalBooking['booking_id']}<br>";
    echo "   Service: {$finalBooking['service_name']}<br>";
    echo "   Booking Status: {$finalBooking['booking_status']}<br>";
    echo "   Session 2 Status: " . ($finalBooking['session2_status'] ?? 'NULL') . "<br>";
    echo "   Session 2 Date: " . ($finalBooking['session2_date'] ?? 'NULL') . "<br>";
    echo "   Session 2 Completed: " . ($finalBooking['session2_completed_date'] ?? 'NULL') . "<br>";
    
    if ($finalBooking['booking_status'] === 'completed' && $finalBooking['session2_status'] === 'completed') {
        echo "✅ Complete session 2 flow working correctly!<br>";
    } else {
        echo "❌ Complete session 2 flow not working as expected<br>";
    }
}

echo "<h2>Test Summary</h2>";
echo "Session 2 flow test completed. Check the results above to verify everything is working correctly.<br>";
echo "To test with real technician actions, have a technician mark a booking as 'completed' for a service with session 2 enabled.<br>";

$conn->close();
?>
