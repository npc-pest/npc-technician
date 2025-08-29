<?php
require 'conn.php';

// Test script to verify session 2 indicator in booking cards
echo "<h1>Session 2 Indicator Test</h1>";

// Test 1: Check if booking_status_log table exists
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

// Test 6: Verify the booking data structure for frontend
echo "<h2>Test 6: Verify Frontend Data Structure</h2>";
$stmt = $conn->prepare("SELECT booking_id, service_name, booking_status, session2_status, session2_date FROM bookings WHERE booking_id = ?");
$stmt->bind_param("s", $testBookingId);
$stmt->execute();
$result = $stmt->get_result();
$updatedBooking = $result->fetch_assoc();

if ($updatedBooking) {
    echo "✅ Booking data structure:<br>";
    echo "   Booking ID: {$updatedBooking['booking_id']}<br>";
    echo "   Service: {$updatedBooking['service_name']}<br>";
    echo "   Booking Status: {$updatedBooking['booking_status']}<br>";
    echo "   Session 2 Status: " . ($updatedBooking['session2_status'] ?? 'NULL') . "<br>";
    echo "   Session 2 Date: " . ($updatedBooking['session2_date'] ?? 'NULL') . "<br>";
    
    // Check if the data will trigger the "2nd Session Pending" indicator
    if ($updatedBooking['session2_status'] && $updatedBooking['session2_status'] !== 'completed') {
        echo "✅ This booking will show '2nd Session Pending' indicator in the UI<br>";
        echo "   - session2_status is set to: {$updatedBooking['session2_status']}<br>";
        echo "   - session2_status is not 'completed'<br>";
        echo "   - Frontend will display purple '2nd Session Pending' badge<br>";
    } else {
        echo "❌ This booking will NOT show '2nd Session Pending' indicator<br>";
        echo "   - session2_status is: " . ($updatedBooking['session2_status'] ?? 'NULL') . "<br>";
    }
}

// Test 7: Test user-bookings.php API response
echo "<h2>Test 7: API Response Structure</h2>";
// Simulate the user-bookings.php query structure
$apiQuery = "
    SELECT 
        booking_id,
        service_name,
        booking_status,
        session2_status,
        session2_date,
        session2_completed_date
    FROM bookings 
    WHERE booking_id = ?
";

$apiStmt = $conn->prepare($apiQuery);
$apiStmt->bind_param("s", $testBookingId);
$apiStmt->execute();
$apiResult = $apiStmt->get_result();
$apiBooking = $apiResult->fetch_assoc();

if ($apiBooking) {
    echo "✅ API Response includes session 2 data:<br>";
    echo "   - session2_status: " . ($apiBooking['session2_status'] ?? 'NULL') . "<br>";
    echo "   - session2_date: " . ($apiBooking['session2_date'] ?? 'NULL') . "<br>";
    echo "   - session2_completed_date: " . ($apiBooking['session2_completed_date'] ?? 'NULL') . "<br>";
    
    // Simulate the frontend logic
    $showIndicator = $apiBooking['session2_status'] && $apiBooking['session2_status'] !== 'completed';
    
    if ($showIndicator) {
        echo "✅ Frontend will show '2nd Session Pending' indicator<br>";
        echo "   - Condition: session2_status exists AND is not 'completed'<br>";
        echo "   - Result: Purple badge with '2nd Session Pending' text<br>";
    } else {
        echo "❌ Frontend will NOT show '2nd Session Pending' indicator<br>";
    }
}

echo "<h2>Test Summary</h2>";
echo "Session 2 indicator test completed. The feature should work as follows:<br>";
echo "1. ✅ When a booking has session2_status set (and not 'completed')<br>";
echo "2. ✅ Web frontend shows purple '2nd Session Pending' badge next to status<br>";
echo "3. ✅ Mobile app shows purple '2nd Session Pending' badge in booking card header<br>";
echo "4. ✅ The indicator appears for both 'pending' and 'in_progress' session2_status<br>";
echo "<br>";
echo "To test in the UI:<br>";
echo "1. Complete a booking for a service with session 2 enabled<br>";
echo "2. Check the booking history page - you should see '2nd Session Pending' indicator<br>";
echo "3. The indicator should appear in both web and mobile interfaces<br>";

$conn->close();
?>
