<?php
require 'conn.php';

// Test script for Session 2 functionality
echo "<h1>Session 2 Feature Test</h1>";

// Test 1: Check if database fields exist
echo "<h2>Test 1: Database Structure</h2>";
$result = $conn->query("DESCRIBE services");
$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'];
}

if (in_array('has_session2', $fields)) {
    echo "✅ has_session2 field exists in services table<br>";
} else {
    echo "❌ has_session2 field missing in services table<br>";
}

$result = $conn->query("DESCRIBE bookings");
$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'];
}

$required_fields = ['session2_status', 'session2_date', 'session2_completed_date'];
foreach ($required_fields as $field) {
    if (in_array($field, $fields)) {
        echo "✅ $field field exists in bookings table<br>";
    } else {
        echo "❌ $field field missing in bookings table<br>";
    }
}

// Test 2: Check services with session 2
echo "<h2>Test 2: Services with Session 2</h2>";
$result = $conn->query("SELECT service_name, has_session2 FROM services WHERE has_session2 = 1");
$services_with_session2 = $result->fetch_all(MYSQLI_ASSOC);

if (count($services_with_session2) > 0) {
    echo "✅ Found " . count($services_with_session2) . " services with session 2:<br>";
    foreach ($services_with_session2 as $service) {
        echo "- " . $service['service_name'] . "<br>";
    }
} else {
    echo "ℹ️ No services with session 2 found. You can create one through the admin panel.<br>";
}

// Test 3: Check bookings with session 2 status
echo "<h2>Test 3: Bookings with Session 2 Status</h2>";
$result = $conn->query("
    SELECT b.booking_id, b.service_name, b.booking_status, b.session2_status, b.session2_date
    FROM bookings b
    WHERE b.session2_status IS NOT NULL OR b.booking_status = 'session2_pending'
    ORDER BY b.created_at DESC
    LIMIT 5
");
$session2_bookings = $result->fetch_all(MYSQLI_ASSOC);

if (count($session2_bookings) > 0) {
    echo "✅ Found " . count($session2_bookings) . " bookings with session 2 status:<br>";
    foreach ($session2_bookings as $booking) {
        echo "- " . $booking['booking_id'] . " (" . $booking['service_name'] . "): " . 
             $booking['booking_status'] . " / " . ($booking['session2_status'] ?? 'N/A') . 
             ($booking['session2_date'] ? " (Due: " . $booking['session2_date'] . ")" : "") . "<br>";
    }
} else {
    echo "ℹ️ No bookings with session 2 status found.<br>";
}

// Test 4: Test auto-completion logic
echo "<h2>Test 4: Auto-Completion Logic</h2>";
$result = $conn->query("
    SELECT booking_id, service_name, session2_date
    FROM bookings 
    WHERE booking_status = 'session2_pending' 
    AND session2_status = 'pending'
    AND session2_date <= CURDATE()
    AND session2_date IS NOT NULL
");
$ready_for_completion = $result->fetch_all(MYSQLI_ASSOC);

if (count($ready_for_completion) > 0) {
    echo "✅ Found " . count($ready_for_completion) . " bookings ready for auto-completion:<br>";
    foreach ($ready_for_completion as $booking) {
        echo "- " . $booking['booking_id'] . " (" . $booking['service_name'] . ") - Due: " . $booking['session2_date'] . "<br>";
    }
} else {
    echo "ℹ️ No bookings ready for auto-completion.<br>";
}

// Test 5: Check if booking_status_log table exists
echo "<h2>Test 5: Status Log Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'booking_status_log'");
if ($result->num_rows > 0) {
    echo "✅ booking_status_log table exists<br>";
} else {
    echo "⚠️ booking_status_log table missing. Creating it...<br>";
    
    $create_table = "
    CREATE TABLE booking_status_log (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id VARCHAR(50) NOT NULL,
        status VARCHAR(50) NOT NULL,
        notes TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_booking_id (booking_id),
        INDEX idx_updated_at (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    
    if ($conn->query($create_table)) {
        echo "✅ booking_status_log table created successfully<br>";
    } else {
        echo "❌ Failed to create booking_status_log table: " . $conn->error . "<br>";
    }
}

echo "<h2>Test Summary</h2>";
echo "Session 2 feature is ready for testing. To test the complete flow:<br>";
echo "1. Create a service with session 2 enabled<br>";
echo "2. Book the service as a user<br>";
echo "3. Update the booking status to 'completed' (first session)<br>";
echo "4. Verify the booking moves to 'session2_pending' status<br>";
echo "5. Test session 2 completion or auto-completion after 15 days<br>";

$conn->close();
?>
