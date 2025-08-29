<?php
// Test file to simulate technician completing a service
// This will help us verify if the email integration is working

header('Content-Type: text/plain; charset=utf-8');

echo "=== Technician Service Completion Test ===\n\n";

// Simulate the data that would come from a technician app
$testData = [
    'booking_id' => 'BK202508113768',
    'technician_id' => 10,
    'status' => 'completed',
    'notes' => 'Service completed successfully'
];

echo "Simulating technician completion:\n";
echo "- Booking ID: " . $testData['booking_id'] . "\n";
echo "- Technician ID: " . $testData['technician_id'] . "\n";
echo "- Status: " . $testData['status'] . "\n";
echo "- Notes: " . $testData['notes'] . "\n\n";

try {
    echo "Step 1: Testing database connection...\n";
    require_once 'conn.php';
    echo "✅ Database connection successful\n\n";
    
    echo "Step 2: Simulating status update process...\n";
    
    // Check if booking exists and is assigned to this technician
    $bookingQuery = "
        SELECT booking_id, assigned_technician, booking_status, user_id, service_name 
        FROM bookings 
        WHERE booking_id = ? AND assigned_technician = ?
    ";
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->bind_param("si", $testData['booking_id'], $testData['technician_id']);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    $booking = $bookingResult->fetch_assoc();
    
    if ($booking) {
        echo "✅ Booking found and assigned to technician\n";
        echo "   - Current Status: " . $booking['booking_status'] . "\n";
        echo "   - Service: " . $booking['service_name'] . "\n";
    } else {
        echo "❌ Booking not found or not assigned to technician\n";
        exit;
    }
    
    echo "\nStep 3: Testing email function call...\n";
    
    // Simulate the exact same code that runs when technician completes service
    if ($testData['status'] === 'completed') {
        echo "Status is 'completed', calling email function...\n";
        
        try {
            error_log("Starting service completion email process for booking: " . $testData['booking_id']);
            
            // Include and call the email service directly
            require_once 'service_completion_email.php';
            $emailResult = sendServiceCompletionEmail($testData['booking_id'], $testData['technician_id']);
            
            echo "Email function returned: " . json_encode($emailResult) . "\n";
            
            if ($emailResult && $emailResult['status'] === 'success') {
                echo "✅ Service completion email sent successfully!\n";
                echo "   - Email sent to: " . ($emailResult['data']['user_email'] ?? 'Unknown') . "\n";
                echo "   - Technician: " . ($emailResult['data']['technician_name'] ?? 'Unknown') . "\n";
                error_log("Service completion email sent successfully for booking: " . $testData['booking_id']);
            } else {
                echo "❌ Failed to send service completion email\n";
                echo "   - Error: " . ($emailResult['message'] ?? 'Unknown error') . "\n";
                error_log("Failed to send service completion email: " . ($emailResult['message'] ?? 'Unknown error'));
            }
        } catch (Exception $emailError) {
            echo "❌ Exception in email function: " . $emailError->getMessage() . "\n";
            error_log("Error sending service completion email: " . $emailError->getMessage());
        }
    } else {
        echo "Status is not 'completed', skipping email\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
