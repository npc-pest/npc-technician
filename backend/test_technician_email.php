<?php
// Test file for technician assignment email service
// This file can be used to test the email functionality

// Set content type to text/plain for better readability in browser
header('Content-Type: text/plain; charset=utf-8');

echo "=== Technician Assignment Email Service Test ===\n\n";

// Test data - using actual booking and technician IDs from your database
$testData = [
    'booking_id' => 'BK202508113768', // This booking exists and has technician 10 assigned
    'technician_id' => 10 // Technician ID 10 exists (Sathick Batscha)
];

echo "Test Data:\n";
echo "- Booking ID: " . $testData['booking_id'] . "\n";
echo "- Technician ID: " . $testData['technician_id'] . "\n\n";

echo "Calling email service function directly...\n";
echo "----------------------------------------\n";

// Include and call the email service directly
require_once 'technician_assignment_email.php';

$result = sendTechnicianAssignmentEmail($testData['booking_id'], $testData['technician_id']);

echo "\nResult:\n";
echo "Status: " . ($result['status'] ?? 'Unknown') . "\n";
echo "Message: " . ($result['message'] ?? 'No message') . "\n";

if ($result && $result['status'] === 'success') {
    echo "\n✅ Email service test successful!\n";
    echo "Email sent to: " . ($result['data']['user_email'] ?? 'Unknown') . "\n";
    echo "Technician: " . ($result['data']['technician_name'] ?? 'Unknown') . "\n";
    echo "Technician Phone: " . ($result['data']['technician_phone'] ?? 'Unknown') . "\n";
} else {
    echo "\n❌ Email service test failed!\n";
    echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
}

echo "\n=== Test Complete ===\n";
?>
