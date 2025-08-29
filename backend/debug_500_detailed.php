<?php
// Detailed debug to identify 500 error cause

header('Content-Type: text/plain; charset=utf-8');

echo "=== Detailed 500 Error Debug ===\n\n";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Step 1: Testing basic PHP...\n";
    echo "==========================\n";
    echo "✅ PHP is working\n\n";
    
    echo "Step 2: Testing database connection...\n";
    echo "=====================================\n";
    require_once 'conn.php';
    echo "✅ Database connection successful\n\n";
    
    echo "Step 3: Testing service_completion_email.php...\n";
    echo "==============================================\n";
    
    if (file_exists('service_completion_email.php')) {
        echo "✅ File exists\n";
        
        // Test inclusion
        try {
            require_once 'service_completion_email.php';
            echo "✅ File included successfully\n";
            
            if (function_exists('sendServiceCompletionEmail')) {
                echo "✅ Function exists\n";
            } else {
                echo "❌ Function does not exist\n";
            }
        } catch (ParseError $e) {
            echo "❌ Parse error: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ File does not exist\n";
    }
    
    echo "\nStep 4: Testing email function call...\n";
    echo "=====================================\n";
    
    if (function_exists('sendServiceCompletionEmail')) {
        try {
            $result = sendServiceCompletionEmail('BK202508141226', 1);
            echo "✅ Function call successful\n";
            echo "Result: " . json_encode($result) . "\n";
        } catch (Exception $e) {
            echo "❌ Function call failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
    
    echo "\nStep 5: Testing the exact code from technician_status_update.php...\n";
    echo "================================================================\n";
    
    $bookingId = 'BK202508141226';
    $actualTechnicianId = 1;
    $status = 'completed';
    
    echo "Variables:\n";
    echo "- bookingId: $bookingId\n";
    echo "- actualTechnicianId: $actualTechnicianId\n";
    echo "- status: $status\n\n";
    
    if ($status === 'completed') {
        echo "✅ Status is 'completed', proceeding with email...\n";
        
        try {
            error_log("Starting service completion email process for booking: $bookingId");
            
            // Check if email file exists before including
            if (file_exists('service_completion_email.php')) {
                echo "✅ Email file exists\n";
                
                // Include and call the email service directly
                require_once 'service_completion_email.php';
                
                if (function_exists('sendServiceCompletionEmail')) {
                    echo "✅ Function exists, calling it...\n";
                    $emailResult = sendServiceCompletionEmail($bookingId, $actualTechnicianId);
                    
                    if ($emailResult && $emailResult['status'] === 'success') {
                        echo "✅ Email sent successfully\n";
                        error_log("Service completion email sent successfully for booking: $bookingId");
                    } else {
                        echo "❌ Email failed: " . ($emailResult['message'] ?? 'Unknown error') . "\n";
                        error_log("Failed to send service completion email: " . ($emailResult['message'] ?? 'Unknown error'));
                    }
                } else {
                    echo "❌ Function not found\n";
                    error_log("sendServiceCompletionEmail function not found");
                }
            } else {
                echo "❌ Email file not found\n";
                error_log("service_completion_email.php file not found");
            }
        } catch (Exception $emailError) {
            echo "❌ Email error: " . $emailError->getMessage() . "\n";
            error_log("Error sending service completion email: " . $emailError->getMessage());
        }
    } else {
        echo "❌ Status is not 'completed'\n";
    }
    
    echo "\nStep 6: Testing response sending...\n";
    echo "================================\n";
    
    $response = [
        'status' => 'success',
        'message' => 'Status updated successfully',
        'data' => [
            'booking_id' => $bookingId,
            'technician_id' => $actualTechnicianId,
            'new_status' => $status
        ]
    ];
    
    echo "Response data: " . json_encode($response) . "\n";
    echo "✅ Response prepared successfully\n";
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
    echo "Error trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
