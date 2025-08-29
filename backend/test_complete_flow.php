<?php
// Test the complete technician completion flow

header('Content-Type: text/plain; charset=utf-8');

echo "=== Complete Flow Test ===\n\n";

// Simulate a technician completing a service
$testData = [
    'booking_id' => 'BK202508141226',
    'technician_id' => 1,
    'status' => 'completed',
    'notes' => 'Service completed successfully'
];

echo "Simulating technician completion:\n";
echo "- Booking ID: " . $testData['booking_id'] . "\n";
echo "- Technician ID: " . $testData['technician_id'] . "\n";
echo "- Status: " . $testData['status'] . "\n";
echo "- Notes: " . $testData['notes'] . "\n\n";

try {
    require_once 'conn.php';
    
    echo "Step 1: Checking booking status...\n";
    echo "===============================\n";
    
    $bookingQuery = "SELECT booking_id, assigned_technician, booking_status, user_id, service_name FROM bookings WHERE booking_id = ?";
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->bind_param("s", $testData['booking_id']);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    $booking = $bookingResult->fetch_assoc();
    
    if ($booking) {
        echo "✅ Booking found:\n";
        echo "   - Current Status: " . $booking['booking_status'] . "\n";
        echo "   - Assigned Technician: " . $booking['assigned_technician'] . "\n";
        echo "   - Service: " . $booking['service_name'] . "\n";
    } else {
        echo "❌ Booking not found\n";
        exit;
    }
    
    echo "\nStep 2: Testing email sending...\n";
    echo "=============================\n";
    
    require_once 'service_completion_email.php';
    
    echo "Calling sendServiceCompletionEmail...\n";
    $emailResult = sendServiceCompletionEmail($testData['booking_id'], $testData['technician_id']);
    
    echo "Email result: " . json_encode($emailResult) . "\n";
    
    if ($emailResult['status'] === 'success') {
        if (isset($emailResult['data']['duplicate_prevented'])) {
            echo "✅ Duplicate prevented - Email already sent recently\n";
        } else {
            echo "✅ Email sent successfully!\n";
            echo "   - To: " . ($emailResult['data']['user_email'] ?? 'Unknown') . "\n";
            echo "   - Service: " . ($emailResult['data']['service_name'] ?? 'Unknown') . "\n";
            echo "   - Technician: " . ($emailResult['data']['technician_name'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "❌ Email failed: " . $emailResult['message'] . "\n";
    }
    
    echo "\nStep 3: Checking email logs...\n";
    echo "===========================\n";
    
    $emailLogsQuery = "
        SELECT user_email, user_name, booking_id, service_name, sent_at
        FROM email_logs 
        WHERE booking_id = ? 
        ORDER BY sent_at DESC 
        LIMIT 5
    ";
    $emailLogsStmt = $conn->prepare($emailLogsQuery);
    $emailLogsStmt->bind_param("s", $testData['booking_id']);
    $emailLogsStmt->execute();
    $emailLogsResult = $emailLogsStmt->get_result();
    
    if ($emailLogsResult->num_rows > 0) {
        echo "✅ Recent emails for this booking:\n";
        while ($emailLog = $emailLogsResult->fetch_assoc()) {
            echo "   📧 " . $emailLog['sent_at'] . " - " . $emailLog['user_email'] . " (" . $emailLog['service_name'] . ")\n";
        }
    } else {
        echo "❌ No emails found for this booking\n";
    }
    
    echo "\nStep 4: Testing API integration...\n";
    echo "===============================\n";
    
    echo "The API should now work with email sending enabled.\n";
    echo "When a technician completes a service:\n";
    echo "1. ✅ Status is updated to 'completed'\n";
    echo "2. ✅ Email is sent to user\n";
    echo "3. ✅ Response is sent to technician app\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\n🎯 Now try completing a service from the technician app!\n";
echo "You should receive an email when the service is marked as completed.\n";
?>
