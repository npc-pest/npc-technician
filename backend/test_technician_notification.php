<?php
// Test file for technician notification email functionality
// This file tests the sendTechnicianNotificationEmail function

require_once 'conn.php';

// Test data
$testBookingId = 'BK' . date('Ymd') . 'TEST';
$testTechnicianId = 1; // Assuming technician ID 1 exists

echo "<h2>Testing Technician Notification Email</h2>";
echo "<p>This test will send a technician notification email to the assigned technician</p>";

// Include the assign technicians file to get the function
require_once 'assign_technicians.php';

try {
    echo "<p>Attempting to send technician notification email...</p>";
    
    // Call the technician notification function
    $result = sendTechnicianNotificationEmail($conn, $testBookingId, $testTechnicianId);
    
    if ($result) {
        echo "<p style='color: green;'><strong>✅ SUCCESS:</strong> Technician notification email sent successfully!</p>";
        echo "<p>Email sent to the assigned technician</p>";
        echo "<p>Test Booking ID: $testBookingId</p>";
        echo "<p>Test Technician ID: $testTechnicianId</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ FAILED:</strong> Failed to send technician notification email.</p>";
        echo "<p>Possible reasons:</p>";
        echo "<ul>";
        echo "<li>Technician ID $testTechnicianId doesn't exist</li>";
        echo "<li>Technician doesn't have an email address</li>";
        echo "<li>Booking ID $testBookingId doesn't exist</li>";
        echo "<li>Server mail configuration issue</li>";
        echo "</ul>";
        echo "<p>Check the server logs for more details.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Test Details:</strong></p>";
echo "<ul>";
echo "<li>Booking ID: $testBookingId</li>";
echo "<li>Technician ID: $testTechnicianId</li>";
echo "<li>Test Date: " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";

echo "<p><strong>Email Content Preview:</strong></p>";
echo "<ul>";
echo "<li>Subject: New Service Assigned - Booking ID: #$testBookingId</li>";
echo "<li>Recipient: Technician's email address</li>";
echo "<li>Content: Job details, customer information, and action items</li>";
echo "<li>Theme: Orange color scheme for technician notifications</li>";
echo "</ul>";

echo "<p><em>Note: This is a test email. The booking ID and other details are for testing purposes only.</em></p>";

// Show technician details if available
try {
    $techQuery = "SELECT employee_name, email, phone_number, service_type FROM technicians WHERE technician_id = ?";
    $techStmt = $conn->prepare($techQuery);
    $techStmt->bind_param("i", $testTechnicianId);
    $techStmt->execute();
    $techResult = $techStmt->get_result();
    $technician = $techResult->fetch_assoc();
    
    if ($technician) {
        echo "<hr>";
        echo "<p><strong>Technician Details (ID: $testTechnicianId):</strong></p>";
        echo "<ul>";
        echo "<li>Name: " . ($technician['employee_name'] ?? 'N/A') . "</li>";
        echo "<li>Email: " . ($technician['email'] ?? 'N/A') . "</li>";
        echo "<li>Phone: " . ($technician['phone_number'] ?? 'N/A') . "</li>";
        echo "<li>Service Type: " . ($technician['service_type'] ?? 'N/A') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ WARNING:</strong> Technician ID $testTechnicianId not found in database.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> Could not fetch technician details: " . $e->getMessage() . "</p>";
}
?>
