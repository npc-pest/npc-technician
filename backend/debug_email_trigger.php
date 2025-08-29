<?php
// Debug email trigger logic to see why emails aren't being sent

header('Content-Type: text/plain; charset=utf-8');

echo "=== Email Trigger Debug ===\n\n";

// Simulate the exact data from the successful API call
$testData = [
    'booking_id' => 'BK202508141226', // From the successful API call
    'technician_id' => 1, // From the successful API call
    'status' => 'completed', // From the successful API call
    'notes' => ''
];

echo "Test Data (from successful API call):\n";
echo "- Booking ID: " . $testData['booking_id'] . "\n";
echo "- Technician ID: " . $testData['technician_id'] . "\n";
echo "- Status: " . $testData['status'] . "\n";
echo "- Notes: " . $testData['notes'] . "\n\n";

try {
    require_once 'conn.php';
    
    echo "Step 1: Checking if status is 'completed'...\n";
    echo "==========================================\n";
    
    $status = $testData['status'];
    echo "Status value: '$status'\n";
    echo "Status type: " . gettype($status) . "\n";
    echo "Status length: " . strlen($status) . "\n";
    echo "Status comparison: " . ($status === 'completed' ? 'TRUE' : 'FALSE') . "\n";
    
    if ($status === 'completed') {
        echo "✅ Status is 'completed' - Email should be triggered\n\n";
    } else {
        echo "❌ Status is NOT 'completed' - Email will NOT be triggered\n\n";
        exit;
    }
    
    echo "Step 2: Checking booking details...\n";
    echo "==================================\n";
    
    $bookingQuery = "
        SELECT booking_id, assigned_technician, booking_status, user_id, service_name
        FROM bookings 
        WHERE booking_id = ?
    ";
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->bind_param("s", $testData['booking_id']);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    $booking = $bookingResult->fetch_assoc();
    
    if ($booking) {
        echo "✅ Booking found:\n";
        echo "   - Booking ID: " . $booking['booking_id'] . "\n";
        echo "   - Assigned Technician: " . $booking['assigned_technician'] . "\n";
        echo "   - Booking Status: " . $booking['booking_status'] . "\n";
        echo "   - User ID: " . $booking['user_id'] . "\n";
        echo "   - Service Name: " . $booking['service_name'] . "\n";
    } else {
        echo "❌ Booking not found\n";
        exit;
    }
    
    echo "\nStep 3: Checking user email...\n";
    echo "=============================\n";
    
    $userQuery = "SELECT customer_name, email_id FROM users WHERE user_id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("i", $booking['user_id']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    
    if ($user) {
        echo "✅ User found:\n";
        echo "   - Customer Name: " . $user['customer_name'] . "\n";
        echo "   - Email: " . $user['email_id'] . "\n";
    } else {
        echo "❌ User not found\n";
        exit;
    }
    
    echo "\nStep 4: Checking technician details...\n";
    echo "=====================================\n";
    
    $technicianQuery = "SELECT employee_name, phone_number FROM technicians WHERE technician_id = ?";
    $technicianStmt = $conn->prepare($technicianQuery);
    $technicianStmt->bind_param("i", $testData['technician_id']);
    $technicianStmt->execute();
    $technicianResult = $technicianStmt->get_result();
    $technician = $technicianResult->fetch_assoc();
    
    if ($technician) {
        echo "✅ Technician found:\n";
        echo "   - Name: " . $technician['employee_name'] . "\n";
        echo "   - Phone: " . $technician['phone_number'] . "\n";
    } else {
        echo "❌ Technician not found\n";
        exit;
    }
    
    echo "\nStep 5: Testing email function directly...\n";
    echo "=========================================\n";
    
    require_once 'service_completion_email.php';
    
    echo "Calling sendServiceCompletionEmail...\n";
    $emailResult = sendServiceCompletionEmail($testData['booking_id'], $testData['technician_id']);
    
    echo "Email function result: " . json_encode($emailResult) . "\n";
    
    if ($emailResult['status'] === 'success') {
        if (isset($emailResult['data']['duplicate_prevented'])) {
            echo "✅ Duplicate prevented - Email already sent recently\n";
        } else {
            echo "✅ Email sent successfully!\n";
            echo "   - Email sent to: " . ($emailResult['data']['user_email'] ?? 'Unknown') . "\n";
            echo "   - Technician: " . ($emailResult['data']['technician_name'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "❌ Email failed: " . $emailResult['message'] . "\n";
    }
    
    echo "\nStep 6: Checking recent email logs...\n";
    echo "===================================\n";
    
    $emailLogsQuery = "
        SELECT user_email, user_name, booking_id, service_name, sent_at
        FROM email_logs 
        WHERE booking_id = ? 
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY sent_at DESC
    ";
    $emailLogsStmt = $conn->prepare($emailLogsQuery);
    $emailLogsStmt->bind_param("s", $testData['booking_id']);
    $emailLogsStmt->execute();
    $emailLogsResult = $emailLogsStmt->get_result();
    
    if ($emailLogsResult->num_rows > 0) {
        echo "✅ Recent emails found for this booking:\n";
        while ($emailLog = $emailLogsResult->fetch_assoc()) {
            echo "   📧 " . $emailLog['sent_at'] . " - " . $emailLog['user_email'] . "\n";
        }
    } else {
        echo "❌ No recent emails found for this booking\n";
    }
    
    echo "\nStep 7: Checking server error logs...\n";
    echo "===================================\n";
    
    echo "Look for these messages in your server's error log:\n";
    echo "- 'Starting service completion email process'\n";
    echo "- 'Service completion email sent successfully'\n";
    echo "- 'Failed to send service completion email'\n";
    echo "- 'Error sending service completion email'\n";
    echo "- 'Duplicate prevention'\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
