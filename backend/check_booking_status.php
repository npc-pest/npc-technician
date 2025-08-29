<?php
// Check current booking statuses to see which ones are completed

header('Content-Type: text/plain; charset=utf-8');

echo "=== Booking Status Check ===\n\n";

try {
    require_once 'conn.php';
    
    echo "Checking recent bookings and their statuses...\n\n";
    
    // Get recent bookings with their statuses
    $query = "
        SELECT booking_id, service_name, booking_status, assigned_technician, service_date, created_at
        FROM bookings 
        WHERE booking_id IN ('BK202508113768', 'BK202508149683', 'BK202508145624', 'BK202508147705')
        ORDER BY created_at DESC
    ";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "Recent Bookings Status:\n";
        echo "======================\n";
        
        while ($row = $result->fetch_assoc()) {
            echo "📋 Booking: " . $row['booking_id'] . "\n";
            echo "   Service: " . $row['service_name'] . "\n";
            echo "   Status: " . $row['booking_status'] . "\n";
            echo "   Technician: " . $row['assigned_technician'] . "\n";
            echo "   Service Date: " . $row['service_date'] . "\n";
            echo "   Created: " . $row['created_at'] . "\n";
            echo "   ---\n";
        }
    } else {
        echo "No recent bookings found.\n";
    }
    
    echo "\nChecking all completed bookings...\n";
    echo "===============================\n";
    
    // Get all completed bookings
    $completedQuery = "
        SELECT booking_id, service_name, assigned_technician, service_date, updated_at
        FROM bookings 
        WHERE booking_status = 'completed'
        ORDER BY updated_at DESC
        LIMIT 10
    ";
    
    $completedResult = $conn->query($completedQuery);
    
    if ($completedResult && $completedResult->num_rows > 0) {
        echo "Completed Bookings:\n";
        while ($row = $completedResult->fetch_assoc()) {
            echo "✅ " . $row['booking_id'] . " - " . $row['service_name'] . " (Updated: " . $row['updated_at'] . ")\n";
        }
    } else {
        echo "❌ No completed bookings found.\n";
    }
    
    echo "\nChecking booking status log...\n";
    echo "============================\n";
    
    // Check booking status log for recent changes
    $logQuery = "
        SELECT booking_id, status, notes, updated_at
        FROM booking_status_log 
        WHERE booking_id IN ('BK202508113768', 'BK202508149683', 'BK202508145624', 'BK202508147705')
        ORDER BY updated_at DESC
        LIMIT 10
    ";
    
    $logResult = $conn->query($logQuery);
    
    if ($logResult && $logResult->num_rows > 0) {
        echo "Recent Status Changes:\n";
        while ($row = $logResult->fetch_assoc()) {
            echo "📝 " . $row['booking_id'] . " -> " . $row['status'] . " (" . $row['updated_at'] . ")\n";
            echo "    Note: " . $row['notes'] . "\n";
        }
    } else {
        echo "No recent status changes found.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
