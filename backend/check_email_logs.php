<?php
// Check email logs to see if service completion emails are being sent

header('Content-Type: text/plain; charset=utf-8');

echo "=== Email Logs Check ===\n\n";

try {
    require_once 'conn.php';
    
    echo "Checking email_logs table...\n\n";
    
    // Get recent email logs
    $query = "
        SELECT user_email, user_name, booking_id, service_name, total_amount, sent_at
        FROM email_logs 
        ORDER BY sent_at DESC 
        LIMIT 10
    ";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "Recent email logs:\n";
        echo "==================\n";
        
        while ($row = $result->fetch_assoc()) {
            echo "📧 Email sent to: " . $row['user_email'] . "\n";
            echo "   Customer: " . $row['user_name'] . "\n";
            echo "   Booking: " . $row['booking_id'] . "\n";
            echo "   Service: " . $row['service_name'] . "\n";
            echo "   Amount: ₹" . $row['total_amount'] . "\n";
            echo "   Sent: " . $row['sent_at'] . "\n";
            echo "   ---\n";
        }
    } else {
        echo "No email logs found.\n";
    }
    
    echo "\nChecking for service completion emails specifically...\n";
    echo "==================================================\n";
    
    // Check for service completion emails (look for specific booking IDs)
    $completionQuery = "
        SELECT user_email, user_name, booking_id, service_name, sent_at
        FROM email_logs 
        WHERE booking_id IN ('BK202508113768', 'BK202508112445', 'BK202508111373', 'BK202508114280')
        ORDER BY sent_at DESC
    ";
    
    $completionResult = $conn->query($completionQuery);
    
    if ($completionResult && $completionResult->num_rows > 0) {
        echo "Service completion emails found:\n";
        while ($row = $completionResult->fetch_assoc()) {
            echo "✅ " . $row['booking_id'] . " - " . $row['user_email'] . " (" . $row['sent_at'] . ")\n";
        }
    } else {
        echo "❌ No service completion emails found for test bookings.\n";
    }
    
    echo "\nChecking PHP error logs for email-related messages...\n";
    echo "==================================================\n";
    
    // Check if there are any error log entries related to service completion emails
    $errorQuery = "
        SELECT * FROM (
            SELECT 'Recent PHP errors' as source, '' as message, NOW() as timestamp
            UNION ALL
            SELECT 'Check server error logs manually' as source, 'Look for service completion email errors' as message, NOW() as timestamp
        ) as dummy
        LIMIT 2
    ";
    
    $errorResult = $conn->query($errorQuery);
    
    echo "To check for email errors, look in your server's error log for:\n";
    echo "- 'Starting service completion email process'\n";
    echo "- 'Service completion email sent successfully'\n";
    echo "- 'Failed to send service completion email'\n";
    echo "- 'Error sending service completion email'\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
