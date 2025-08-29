<?php
// Test to identify the 500 error cause

header('Content-Type: text/plain; charset=utf-8');

echo "=== 500 Error Debug ===\n\n";

try {
    echo "Step 1: Testing database connection...\n";
    require_once 'conn.php';
    echo "✅ Database connection successful\n\n";
    
    echo "Step 2: Testing service_completion_email.php inclusion...\n";
    if (file_exists('service_completion_email.php')) {
        echo "✅ service_completion_email.php file exists\n";
        
        // Test if the file can be included without errors
        try {
            require_once 'service_completion_email.php';
            echo "✅ service_completion_email.php included successfully\n";
            
            // Test if the function exists
            if (function_exists('sendServiceCompletionEmail')) {
                echo "✅ sendServiceCompletionEmail function exists\n";
            } else {
                echo "❌ sendServiceCompletionEmail function does not exist\n";
            }
        } catch (Exception $e) {
            echo "❌ Error including service_completion_email.php: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ service_completion_email.php file does not exist\n";
    }
    
    echo "\nStep 3: Testing with sample data...\n";
    $testBookingId = 'BK202508141226';
    $testTechnicianId = 1;
    
    echo "Test booking ID: $testBookingId\n";
    echo "Test technician ID: $testTechnicianId\n";
    
    // Test the email function call
    if (function_exists('sendServiceCompletionEmail')) {
        try {
            echo "Calling sendServiceCompletionEmail...\n";
            $result = sendServiceCompletionEmail($testBookingId, $testTechnicianId);
            echo "✅ Function call successful\n";
            echo "Result: " . json_encode($result) . "\n";
        } catch (Exception $e) {
            echo "❌ Error calling sendServiceCompletionEmail: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
    echo "Error trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
