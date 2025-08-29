<?php
// Debug technician completion flow to see why emails aren't being sent
// This will trace the exact path when technician marks service as completed

header('Content-Type: text/plain; charset=utf-8');

echo "=== Technician Completion Flow Debug ===\n\n";

// Simulate the exact data that comes from technician app
$testData = [
    'booking_id' => 'BK202508113768',
    'technician_id' => 10,
    'status' => 'completed',
    'notes' => 'Service completed successfully'
];

echo "Simulating technician completion request:\n";
echo "- Booking ID: " . $testData['booking_id'] . "\n";
echo "- Technician ID: " . $testData['technician_id'] . "\n";
echo "- Status: " . $testData['status'] . "\n";
echo "- Notes: " . $testData['notes'] . "\n\n";

try {
    require_once 'conn.php';
    
    echo "Step 1: Checking current booking status...\n";
    $statusQuery = "SELECT booking_id, booking_status, assigned_technician FROM bookings WHERE booking_id = ?";
    $statusStmt = $conn->prepare($statusQuery);
    $statusStmt->bind_param("s", $testData['booking_id']);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    $booking = $statusResult->fetch_assoc();
    
    if ($booking) {
        echo "✅ Booking found:\n";
        echo "   - Current Status: " . $booking['booking_status'] . "\n";
        echo "   - Assigned Technician: " . $booking['assigned_technician'] . "\n";
    } else {
        echo "❌ Booking not found\n";
        exit;
    }
    
    echo "\nStep 2: Simulating technician status update API call...\n";
    echo "==================================================\n";
    
    // Test multiple possible API endpoints
    $possibleEndpoints = [
        'https://npcpest.com/npc/backend/technician_status_update.php',
        'https://npcpest.com/npc/technician_status_update.php',
        'https://npcpest.com/backend/technician_status_update.php',
        'https://npcpest.com/technician_status_update.php',
        'https://npcpest.com/npc/backend/technician_api.php',
        'https://npcpest.com/npc/backend/technician_orders.php'
    ];
    
    // Prepare the data that would be sent
    $postData = [
        'booking_id' => $testData['booking_id'],
        'status' => $testData['status'],
        'notes' => $testData['notes']
    ];
    
    echo "POST Data: " . json_encode($postData) . "\n\n";
    
    echo "Step 3: Testing different API endpoints...\n";
    echo "=========================================\n";
    
    foreach ($possibleEndpoints as $apiUrl) {
        echo "Testing: $apiUrl\n";
        
        // Make actual HTTP request to the API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Technician-ID: ' . $testData['technician_id']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "  HTTP Response Code: $httpCode\n";
        if ($error) {
            echo "  cURL Error: $error\n";
        } else {
            if ($httpCode === 200) {
                echo "  ✅ Endpoint accessible!\n";
                echo "  Response: " . substr($response, 0, 200) . "...\n";
            } else {
                echo "  ❌ Endpoint not accessible\n";
            }
        }
        echo "  ---\n";
    }
    
    echo "Step 4: Checking if email was triggered...\n";
    echo "=========================================\n";
    
    // Check recent email logs
    $emailQuery = "
        SELECT user_email, user_name, booking_id, service_name, sent_at
        FROM email_logs 
        WHERE booking_id = ? 
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
        ORDER BY sent_at DESC
    ";
    $emailStmt = $conn->prepare($emailQuery);
    $emailStmt->bind_param("s", $testData['booking_id']);
    $emailStmt->execute();
    $emailResult = $emailStmt->get_result();
    
    if ($emailResult->num_rows > 0) {
        echo "✅ Recent emails found for this booking:\n";
        while ($email = $emailResult->fetch_assoc()) {
            echo "   📧 " . $email['sent_at'] . " - " . $email['user_email'] . "\n";
        }
    } else {
        echo "❌ No recent emails found for this booking\n";
    }
    
    echo "\nStep 5: Checking server error logs...\n";
    echo "===================================\n";
    
    echo "To check for errors, look in your server's error log for:\n";
    echo "- 'Starting service completion email process'\n";
    echo "- 'Service completion email sent successfully'\n";
    echo "- 'Failed to send service completion email'\n";
    echo "- 'Error sending service completion email'\n";
    echo "- 'Duplicate prevention'\n";
    
    echo "\nStep 6: Testing email function directly...\n";
    echo "========================================\n";
    
    // Test the email function directly
    require_once 'service_completion_email.php';
    
    echo "Calling sendServiceCompletionEmail directly...\n";
    $emailResult = sendServiceCompletionEmail($testData['booking_id'], $testData['technician_id']);
    
    echo "Direct email function result: " . json_encode($emailResult) . "\n";
    
    if ($emailResult['status'] === 'success') {
        if (isset($emailResult['data']['duplicate_prevented'])) {
            echo "✅ Duplicate prevented - Email already sent recently\n";
        } else {
            echo "✅ Email sent successfully via direct function call\n";
        }
    } else {
        echo "❌ Email failed via direct function call: " . $emailResult['message'] . "\n";
    }
    
    echo "\nStep 7: Checking which API endpoint is actually used...\n";
    echo "=====================================================\n";
    
    echo "Possible API endpoints that technician app might be using:\n";
    echo "1. technician_status_update.php\n";
    echo "2. technician_api.php\n";
    echo "3. technician_orders.php\n";
    echo "4. update_booking_status.php\n";
    
    echo "\nCheck which endpoint your technician app is actually calling!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
