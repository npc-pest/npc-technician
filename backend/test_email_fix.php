<?php
// Test the email fix

header('Content-Type: text/plain; charset=utf-8');

echo "=== Email Fix Test ===\n\n";

try {
    echo "Step 1: Testing email function with database fix...\n";
    echo "==================================================\n";
    
    require_once 'service_completion_email.php';
    
    if (function_exists('sendServiceCompletionEmail')) {
        echo "✅ Function exists\n";
        
        $result = sendServiceCompletionEmail('BK202508141226', 1);
        echo "✅ Function call successful\n";
        echo "Result: " . json_encode($result) . "\n";
        
        if ($result['status'] === 'success') {
            if (isset($result['data']['duplicate_prevented'])) {
                echo "✅ Duplicate prevented (expected)\n";
            } else {
                echo "✅ Email sent successfully!\n";
            }
        } else {
            echo "❌ Email failed: " . $result['message'] . "\n";
        }
    } else {
        echo "❌ Function not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\n🎯 Now the email should work in the main API!\n";
?>
