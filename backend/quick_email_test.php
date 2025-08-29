<?php
// Quick Email Test for Hostinger
header('Content-Type: text/html');

echo "<h1>Quick Email Test - Hostinger Configuration</h1>";
echo "<p>Testing email functionality on: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<hr>";

// Test 1: Check current configuration
echo "<h2>1. Current Configuration</h2>";
echo "SMTP Host: smtp.hostinger.com<br>";
echo "SMTP Port: 465 (SSL)<br>";
echo "Username: info@npcpest.com<br>";
echo "Password: [Configured]<br>";
echo "<br>";

// Test 2: Test SMTP connection
echo "<h2>2. SMTP Connection Test</h2>";

try {
    // Test basic connection
    $smtp = fsockopen('ssl://smtp.hostinger.com', 465, $errno, $errstr, 30);
    
    if (!$smtp) {
        echo "❌ Connection failed: $errstr ($errno)<br>";
    } else {
        echo "✅ Connection successful to smtp.hostinger.com:465<br>";
        
        // Read server greeting
        $response = fgets($smtp, 515);
        echo "Server greeting: " . htmlspecialchars($response) . "<br>";
        
        // Send EHLO
        fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $response = fgets($smtp, 515);
        echo "EHLO response: " . htmlspecialchars($response) . "<br>";
        
        fclose($smtp);
    }
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "<br>";
}

// Test 3: Test the simple email service
echo "<h2>3. Simple Email Service Test</h2>";

$test_data = array(
    'user_email' => 'hariprabhu571@gmail.com', // Your actual email
    'user_name' => 'Test User',
    'booking_id' => 'QUICK123',
    'service_name' => 'Quick Test Service',
    'total_amount' => '500',
    'service_date' => date('Y-m-d'),
    'service_time' => '2:00 PM',
    'address' => 'Test Address',
    'payment_status' => 'Paid'
);

echo "Testing with data: " . json_encode($test_data) . "<br><br>";

// Make a POST request to the simple email service
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://' . $_SERVER['HTTP_HOST'] . '/npc/simple_email_service.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($test_data))
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
if ($curl_error) {
    echo "CURL Error: $curl_error<br>";
}

if ($response) {
    echo "Response: " . htmlspecialchars($response) . "<br>";
    $response_data = json_decode($response, true);
    if ($response_data && $response_data['status'] === 'success') {
        echo "<br>✅ <strong>Email service test successful!</strong><br>";
        echo "Please check your email (and spam folder) for the test message.<br>";
    } else {
        echo "<br>❌ <strong>Email service test failed</strong><br>";
        if ($response_data) {
            echo "Error: " . ($response_data['message'] ?? 'Unknown error') . "<br>";
        }
    }
} else {
    echo "<br>❌ <strong>No response from email service</strong><br>";
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

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li><strong>Change the test email address</strong> in this file to your actual email</li>";
echo "<li>Make sure you have created the email account <strong>info@npcpest.com</strong> in Hostinger</li>";
echo "<li>Verify the password in <strong>hostinger_smtp_service.php</strong> is correct</li>";
echo "<li>Test booking a service on your website</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> If the test fails, check the error logs and make sure your Hostinger email account is properly configured.</p>";

echo "<hr>";
echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='test_simple_email.php'>Full Email Test</a></li>";
echo "<li><a href='hostinger_smtp_service.php'>SMTP Service Test</a></li>";
echo "<li><a href='test_email.php'>Basic Email Test</a></li>";
echo "</ul>";
?>
