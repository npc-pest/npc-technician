<?php
require 'conn.php';

// This script should be run daily via cron job
// Example cron job: 0 2 * * * /usr/bin/php /path/to/auto_complete_session2.php

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Log file for tracking
$log_file = 'session2_auto_complete.log';

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

writeLog("Starting session 2 auto-completion check");

try {
    // Find bookings that are in session 2 pending status and have passed the 15-day mark
    $stmt = $conn->prepare("
        SELECT booking_id, service_name, session2_date, created_at
        FROM bookings 
        WHERE booking_status = 'session2_pending' 
        AND session2_status = 'pending'
        AND session2_date <= CURDATE()
        AND session2_date IS NOT NULL
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings_to_complete = $result->fetch_all(MYSQLI_ASSOC);
    
    writeLog("Found " . count($bookings_to_complete) . " bookings ready for session 2 completion");
    
    if (count($bookings_to_complete) > 0) {
        // Start transaction
        $conn->begin_transaction();
        
        $completed_count = 0;
        
        foreach ($bookings_to_complete as $booking) {
            // Update booking to completed status
            $updateStmt = $conn->prepare("
                UPDATE bookings 
                SET booking_status = 'completed',
                    session2_status = 'completed',
                    session2_completed_date = NOW(),
                    updated_at = NOW()
                WHERE booking_id = ?
            ");
            
            $updateStmt->bind_param("s", $booking['booking_id']);
            
            if ($updateStmt->execute()) {
                // Log the status update
                $logStmt = $conn->prepare("
                    INSERT INTO booking_status_log (booking_id, status, notes, updated_at) 
                    VALUES (?, 'completed', 'Auto-completed session 2 after 15 days', NOW())
                ");
                $logStmt->bind_param("s", $booking['booking_id']);
                $logStmt->execute();
                
                $completed_count++;
                writeLog("Auto-completed booking: " . $booking['booking_id'] . " for service: " . $booking['service_name']);
            } else {
                writeLog("Failed to auto-complete booking: " . $booking['booking_id']);
            }
        }
        
        $conn->commit();
        writeLog("Successfully auto-completed $completed_count bookings");
        
    } else {
        writeLog("No bookings found that need auto-completion");
    }
    
} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    writeLog("Error during auto-completion: " . $e->getMessage());
}

$conn->close();
writeLog("Session 2 auto-completion check completed");
?>
