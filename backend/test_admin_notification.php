<?php
// Test file for admin notification email functionality
// This file tests the sendAdminNotificationEmail function

require_once 'conn.php';

// Test data
$testBookingId = 'BK' . date('Ymd') . 'TEST';
$testUserId = 1; // Assuming user ID 1 exists
$testServiceName = 'Pest Control Service';
$testServiceDate = date('Y-m-d', strtotime('+2 days'));
$testTimeSlot = '10:00 AM - 12:00 PM';
$testTotalAmount = 1500;
$testServiceAddress = '123 Test Street, Test City, Tamil Nadu';
$testUserEmail = 'test@example.com';

echo "<h2>Testing Admin Notification Email</h2>";
echo "<p>This test will send an admin notification email to info@npcpest.com</p>";

// Include the booking service file to get the function
require_once 'book_service_v2.php';

try {
    echo "<p>Attempting to send admin notification email...</p>";
    
    // Call the admin notification function
    $result = sendAdminNotificationEmail(
        $conn, 
        $testBookingId, 
        $testUserId, 
        $testServiceName, 
        $testServiceDate, 
        $testTimeSlot, 
        $testTotalAmount, 
        $testServiceAddress, 
        $testUserEmail
    );
    
    if ($result) {
        echo "<p style='color: green;'><strong>✅ SUCCESS:</strong> Admin notification email sent successfully!</p>";
        echo "<p>Email sent to: info@npcpest.com</p>";
        echo "<p>Test Booking ID: $testBookingId</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ FAILED:</strong> Failed to send admin notification email.</p>";
        echo "<p>Check the server logs for more details.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Test Details:</strong></p>";
echo "<ul>";
echo "<li>Booking ID: $testBookingId</li>";
echo "<li>User ID: $testUserId</li>";
echo "<li>Service: $testServiceName</li>";
echo "<li>Date: $testServiceDate</li>";
echo "<li>Time: $testTimeSlot</li>";
echo "<li>Amount: ₹$testTotalAmount</li>";
echo "<li>Address: $testServiceAddress</li>";
echo "<li>Email: $testUserEmail</li>";
echo "</ul>";

echo "<p><em>Note: This is a test email. The booking ID and other details are for testing purposes only.</em></p>";
?>
