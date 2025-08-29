<?php
// Direct Email Test - Bypasses URL routing issues
header('Content-Type: text/html');

echo "<h1>Direct Email Test</h1>";
echo "<p>Testing email service directly without URL routing</p>";
echo "<hr>";

// Test 1: Check if simple_email_service.php exists
echo "<h2>1. File Check</h2>";
if (file_exists('simple_email_service.php')) {
    echo "✅ simple_email_service.php exists<br>";
} else {
    echo "❌ simple_email_service.php does not exist<br>";
    exit;
}

// Test 2: Test PHP mail() directly
echo "<h2>2. Direct PHP mail() Test</h2>";

$test_email = 'hariprabhu571@gmail.com';
$subject = "Direct PHP mail() Test - " . date('Y-m-d H:i:s');
$message = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #0d9488; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>NPC Services</h1>
        <p>Direct Email Test</p>
    </div>
    <div class='content'>
        <h2>Test Email</h2>
        <p>This is a direct test email sent via PHP mail() function.</p>
        <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Server:</strong> " . $_SERVER['HTTP_HOST'] . "</p>
    </div>
</body>
</html>";

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: NPC Services <info@npcpest.com>',
    'Reply-To: support@npcservices.com',
    'X-Mailer: PHP/' . phpversion()
);

$mail_sent = mail($test_email, $subject, $message, implode("\r\n", $headers));

if ($mail_sent) {
    echo "✅ Direct PHP mail() test successful!<br>";
    echo "Email sent to: $test_email<br>";
    echo "Please check your email (and spam folder) for the test message.<br>";
} else {
    echo "❌ Direct PHP mail() test failed<br>";
}

// Test 3: Test the email service logic directly
echo "<h2>3. Direct Email Service Logic Test</h2>";

// Simulate the email service logic
$user_email = 'hariprabhu571@gmail.com';
$user_name = 'Test User';
$booking_id = 'DIRECT123';
$service_name = 'Direct Test Service';
$total_amount = '750';
$service_date = date('Y-m-d');
$service_time = '3:00 PM';
$address = 'Test Address';
$payment_status = 'Paid';

$subject = "Booking Confirmation - NPC Services";

$message = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .header { background: linear-gradient(135deg, #0d9488, #0891b2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .booking-details { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .amount { font-size: 24px; font-weight: bold; color: #0d9488; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>NPC Services</h1>
        <p>Professional Pest Control & Home Services</p>
    </div>
    
    <div class='content'>
        <h2>Dear $user_name,</h2>
        
        <p>Thank you for choosing NPC Services! Your booking has been confirmed successfully.</p>
        
        <div class='booking-details'>
            <h3>Booking Details:</h3>
            <p><strong>Booking ID:</strong> #$booking_id</p>
            <p><strong>Service:</strong> $service_name</p>
            <p><strong>Service Date:</strong> $service_date</p>
            <p><strong>Service Time:</strong> $service_time</p>
            <p><strong>Service Address:</strong> $address</p>
            <p><strong>Total Amount:</strong> <span class='amount'>₹$total_amount</span></p>
            <p><strong>Payment Status:</strong> $payment_status</p>
        </div>
        
        <p>Our team will contact you shortly to confirm the details and provide any additional information.</p>
        
        <p>You can track your booking status in your account dashboard.</p>
        
        <p>If you have any questions or need to make changes to your booking, please don't hesitate to contact our support team.</p>
        
        <p>Thank you for trusting NPC Services!</p>
        
        <p>Best regards,<br>
        NPC Services Team</p>
    </div>
    
    <div class='footer'>
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>For support, contact us at support@npcservices.com</p>
    </div>
</body>
</html>";

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: NPC Services <info@npcpest.com>',
    'Reply-To: support@npcservices.com',
    'X-Mailer: PHP/' . phpversion()
);

$email_sent = mail($user_email, $subject, $message, implode("\r\n", $headers));

if ($email_sent) {
    echo "✅ Direct email service logic test successful!<br>";
    echo "Booking confirmation email sent to: $user_email<br>";
    echo "Please check your email (and spam folder) for the confirmation message.<br>";
} else {
    echo "❌ Direct email service logic test failed<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>PHP mail() Status:</strong> " . (function_exists('mail') ? '✅ Available' : '❌ Not Available') . "</p>";
echo "<p><strong>Direct Test Result:</strong> " . ($mail_sent ? '✅ Working' : '❌ Failed') . "</p>";

if ($mail_sent) {
    echo "<p><strong>✅ Your email system is working!</strong></p>";
    echo "<p>You can now test booking a service on your website.</p>";
} else {
    echo "<p><strong>❌ Email system needs configuration</strong></p>";
    echo "<p>Please check your server mail configuration.</p>";
}

echo "<hr>";
echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='quick_email_test.php'>Quick Email Test</a></li>";
echo "<li><a href='test_simple_email.php'>Full Email Test</a></li>";
echo "<li><a href='check_email_account.php'>Email Account Check</a></li>";
echo "<li><a href='test_email.php'>Basic Email Test</a></li>";
echo "</ul>";
?>
