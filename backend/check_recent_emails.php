<?php
// Check for recent service completion emails and match with completed bookings

header('Content-Type: text/plain; charset=utf-8');

echo "=== Recent Service Completion Emails Check ===\n\n";

try {
    require_once 'conn.php';
    
    echo "Checking recent email logs (last 24 hours)...\n\n";
    
    // Get recent email logs from the last 24 hours
    $query = "
        SELECT user_email, user_name, booking_id, service_name, total_amount, sent_at
        FROM email_logs 
        WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY sent_at DESC
    ";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "Recent Emails (Last 24 Hours):\n";
        echo "==============================\n";
        
        while ($row = $result->fetch_assoc()) {
            echo "📧 " . $row['sent_at'] . " - " . $row['booking_id'] . "\n";
            echo "   To: " . $row['user_email'] . "\n";
            echo "   Service: " . $row['service_name'] . "\n";
            echo "   Amount: ₹" . $row['total_amount'] . "\n";
            echo "   ---\n";
        }
    } else {
        echo "No recent emails found in the last 24 hours.\n";
    }
    
    echo "\nChecking completed bookings vs email logs...\n";
    echo "===========================================\n";
    
    // Get completed bookings and check if they have email logs
    $completedQuery = "
        SELECT b.booking_id, b.service_name, b.updated_at, 
               e.sent_at as email_sent_at, e.user_email
        FROM bookings b
        LEFT JOIN email_logs e ON b.booking_id = e.booking_id
        WHERE b.booking_status = 'completed'
        ORDER BY b.updated_at DESC
        LIMIT 10
    ";
    
    $completedResult = $conn->query($completedQuery);
    
    if ($completedResult && $completedResult->num_rows > 0) {
        echo "Completed Bookings vs Email Status:\n";
        while ($row = $completedResult->fetch_assoc()) {
            echo "📋 " . $row['booking_id'] . " - " . $row['service_name'] . "\n";
            echo "   Completed: " . $row['updated_at'] . "\n";
            
            if ($row['email_sent_at']) {
                echo "   ✅ Email sent: " . $row['email_sent_at'] . " to " . $row['user_email'] . "\n";
            } else {
                echo "   ❌ No email sent\n";
            }
            echo "   ---\n";
        }
    } else {
        echo "No completed bookings found.\n";
    }
    
    echo "\nChecking for duplicate emails...\n";
    echo "===============================\n";
    
    // Check for duplicate emails for the same booking
    $duplicateQuery = "
        SELECT booking_id, COUNT(*) as email_count, 
               MIN(sent_at) as first_email, MAX(sent_at) as last_email
        FROM email_logs 
        GROUP BY booking_id 
        HAVING COUNT(*) > 1
        ORDER BY last_email DESC
        LIMIT 5
    ";
    
    $duplicateResult = $conn->query($duplicateQuery);
    
    if ($duplicateResult && $duplicateResult->num_rows > 0) {
        echo "Bookings with Multiple Emails:\n";
        while ($row = $duplicateResult->fetch_assoc()) {
            echo "🔄 " . $row['booking_id'] . " - " . $row['email_count'] . " emails\n";
            echo "   First: " . $row['first_email'] . "\n";
            echo "   Last: " . $row['last_email'] . "\n";
            echo "   ---\n";
        }
    } else {
        echo "No duplicate emails found.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
