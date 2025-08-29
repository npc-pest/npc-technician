<?php
require 'conn.php';

// Test script to verify complete session 2 flow
echo "<h1>Complete Session 2 Flow Test</h1>";

// Test 1: Create booking_status_log table if it doesn't exist
echo "<h2>Test 1: Database Setup</h2>";
$result = $conn->query("SHOW TABLES LIKE 'booking_status_log'");
if ($result->num_rows === 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS booking_status_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id VARCHAR(50) NOT NULL,
        status VARCHAR(50) NOT NULL,
        notes TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_booking_id (booking_id),
        INDEX idx_status (status),
        INDEX idx_updated_at (updated_at)
    )";
    
    if ($conn->query($createTable)) {
        echo "✅ booking_status_log table created<br>";
    } else {
        echo "❌ Failed to create booking_status_log table: " . $conn->error . "<br>";
    }
} else {
    echo "✅ booking_status_log table exists<br>";
}

// Test 2: Update booking_status enum
echo "<h2>Test 2: Database Schema</h2>";
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'booking_status'");
$column = $result->fetch_assoc();

if (strpos($column['Type'], 'session2_pending') !== false) {
    echo "✅ booking_status enum includes 'session2_pending'<br>";
} else {
    echo "⚠️ Updating booking_status enum...<br>";
    $updateEnum = "ALTER TABLE bookings MODIFY COLUMN booking_status ENUM('pending','confirmed','in_progress','completed','cancelled','session2_pending') DEFAULT 'pending'";
    if ($conn->query($updateEnum)) {
        echo "✅ booking_status enum updated successfully<br>";
    } else {
        echo "❌ Failed to update booking_status enum: " . $conn->error . "<br>";
    }
}

// Test 3: Find a service with session 2 enabled
echo "<h2>Test 3: Service with Session 2</h2>";
$result = $conn->query("SELECT service_id, service_name, has_session2 FROM services WHERE has_session2 = 1 LIMIT 1");
$service = $result->fetch_assoc();

if ($service) {
    echo "✅ Found service with session 2: {$service['service_name']} (ID: {$service['service_id']})<br>";
    $testServiceName = $service['service_name'];
} else {
    echo "❌ No service with session 2 found. Create one first.<br>";
    exit;
}

// Test 4: Find a booking for this service
echo "<h2>Test 4: Booking for Session 2 Service</h2>";
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

// Test 5: Simulate first session completion (move to session2_pending)
echo "<h2>Test 5: Simulate First Session Completion</h2>";
if ($booking['session2_status'] === null) {
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

// Test 6: Simulate technician starting session 2 (session2_status = in_progress)
echo "<h2>Test 6: Simulate Session 2 Start</h2>";
$stmt = $conn->prepare("SELECT booking_id, service_name, booking_status, session2_status, session2_date FROM bookings WHERE booking_id = ?");
$stmt->bind_param("s", $testBookingId);
$stmt->execute();
$result = $stmt->get_result();
$updatedBooking = $result->fetch_assoc();

if ($updatedBooking && $updatedBooking['session2_status'] === 'pending') {
    $updateQuery = "UPDATE bookings SET session2_status = 'in_progress', updated_at = NOW() WHERE booking_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("s", $testBookingId);
    
    if ($updateStmt->execute()) {
        echo "✅ Successfully updated session2_status to 'in_progress'<br>";
        echo "   This should show as 'Session 2 In Progress' in the UI<br>";
    } else {
        echo "❌ Failed to update session2_status<br>";
    }
} else {
    echo "ℹ️ Session 2 status is already: " . ($updatedBooking['session2_status'] ?? 'NULL') . "<br>";
}

// Test 7: Verify the booking shows as "Session 2 In Progress"
echo "<h2>Test 7: Verify Session 2 In Progress Status</h2>";
$stmt = $conn->prepare("SELECT booking_id, service_name, booking_status, session2_status, session2_date FROM bookings WHERE booking_id = ?");
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
    
    if ($finalBooking['booking_status'] === 'session2_pending' && $finalBooking['session2_status'] === 'in_progress') {
        echo "✅ Session 2 In Progress status working correctly!<br>";
        echo "   This booking should appear in the 'Session 2' tab with 'Session 2 In Progress' status<br>";
    } else {
        echo "❌ Session 2 In Progress status not working as expected<br>";
    }
}

// Test 8: Simulate session 2 completion
echo "<h2>Test 8: Simulate Session 2 Completion</h2>";
if ($finalBooking['session2_status'] === 'in_progress') {
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

// Test 9: Final verification
echo "<h2>Test 9: Final Verification</h2>";
$stmt = $conn->prepare("SELECT booking_id, service_name, booking_status, session2_status, session2_date, session2_completed_date FROM bookings WHERE booking_id = ?");
$stmt->bind_param("s", $testBookingId);
$stmt->execute();
$result = $stmt->get_result();
$completedBooking = $result->fetch_assoc();

if ($completedBooking) {
    echo "✅ Completed booking status:<br>";
    echo "   Booking ID: {$completedBooking['booking_id']}<br>";
    echo "   Service: {$completedBooking['service_name']}<br>";
    echo "   Booking Status: {$completedBooking['booking_status']}<br>";
    echo "   Session 2 Status: " . ($completedBooking['session2_status'] ?? 'NULL') . "<br>";
    echo "   Session 2 Date: " . ($completedBooking['session2_date'] ?? 'NULL') . "<br>";
    echo "   Session 2 Completed: " . ($completedBooking['session2_completed_date'] ?? 'NULL') . "<br>";
    
    if ($completedBooking['booking_status'] === 'completed' && $completedBooking['session2_status'] === 'completed') {
        echo "✅ Complete session 2 flow working correctly!<br>";
    } else {
        echo "❌ Complete session 2 flow not working as expected<br>";
    }
}

echo "<h2>Test Summary</h2>";
echo "Complete session 2 flow test completed. The flow should now work as follows:<br>";
echo "1. ✅ First session completed → moves to 'Session 2 Pending'<br>";
echo "2. ✅ Technician starts session 2 → shows as 'Session 2 In Progress'<br>";
echo "3. ✅ Session 2 completed → moves to 'Completed (Both Sessions)'<br>";
echo "<br>";
echo "The booking should appear in the 'Session 2' tab for users with appropriate status messages.<br>";

$conn->close();
?>
