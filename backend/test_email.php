<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'email_errors.log');

echo "<h1>Email Configuration Test</h1>";
echo "<p>Testing email functionality on: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<hr>";

// Test 1: Check if mail function exists
echo "<h2>1. PHP mail() Function Test</h2>";
if (function_exists('mail')) {
    echo "✅ PHP mail() function is available<br>";
} else {
    echo "❌ PHP mail() function is NOT available<br>";
}

// Test 2: Check mail configuration
echo "<h2>2. Mail Configuration</h2>";
$mail_config = ini_get('sendmail_path');
echo "Sendmail path: " . ($mail_config ? $mail_config : 'Not set') . "<br>";

$smtp_host = ini_get('SMTP');
echo "SMTP host: " . ($smtp_host ? $smtp_host : 'Not set') . "<br>";

$smtp_port = ini_get('smtp_port');
echo "SMTP port: " . ($smtp_port ? $smtp_port : 'Not set') . "<br>";

// Test 3: Try to send a test email
echo "<h2>3. Test Email Sending</h2>";
$test_email = "test@example.com"; // Change this to your email
$subject = "Test Email from NPC Services - " . date('Y-m-d H:i:s');
$message = "This is a test email from your NPC Services website.\n\n";
$message .= "Server: " . $_SERVER['HTTP_HOST'] . "\n";
$message .= "Time: " . date('Y-m-d H:i:s') . "\n";
$message .= "PHP Version: " . phpversion() . "\n";

$headers = array(
    'From: NPC Services <info@npcpest.com>',
    'Reply-To: support@npcservices.com',
    'X-Mailer: PHP/' . phpversion()
);

echo "Attempting to send test email to: $test_email<br>";

try {
    $mail_sent = mail($test_email, $subject, $message, implode("\r\n", $headers));
    
    if ($mail_sent) {
        echo "✅ Test email sent successfully!<br>";
        echo "Please check your email (and spam folder) for the test message.<br>";
    } else {
        echo "❌ Test email failed to send<br>";
    }
} catch (Exception $e) {
    echo "❌ Error sending test email: " . $e->getMessage() . "<br>";
}

// Test 4: Check error logs
echo "<h2>4. Error Log Check</h2>";
$error_log = 'email_errors.log';
if (file_exists($error_log)) {
    echo "✅ Error log file exists<br>";
    $log_content = file_get_contents($error_log);
    if (!empty($log_content)) {
        echo "Recent error log entries:<br>";
        echo "<pre>" . htmlspecialchars(substr($log_content, -1000)) . "</pre>";
    } else {
        echo "Error log is empty<br>";
    }
} else {
    echo "❌ Error log file does not exist<br>";
}

// Test 5: Check server environment
echo "<h2>5. Server Environment</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Operating System: " . PHP_OS . "<br>";

// Test 6: Check if we can write to files
echo "<h2>6. File Write Permissions</h2>";
$test_file = 'test_write_' . time() . '.txt';
if (file_put_contents($test_file, 'Test write permission')) {
    echo "✅ Can write files to server<br>";
    unlink($test_file); // Clean up
} else {
    echo "❌ Cannot write files to server<br>";
}

echo "<hr>";
echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li>If mail() function is not available, contact your hosting provider</li>";
echo "<li>If test email fails, check with hosting provider about mail server configuration</li>";
echo "<li>Consider implementing SMTP email sending as an alternative</li>";
echo "<li>Check your email spam folder for test messages</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> Change the test email address in this file to your actual email address to receive the test message.</p>";
?>
