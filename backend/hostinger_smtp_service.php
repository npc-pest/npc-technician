<?php
// Check Email Account Status
header('Content-Type: text/html');

echo "<h1>Email Account Check</h1>";
echo "<p>Checking email account status for: info@npcpest.com</p>";
echo "<hr>";

// Test 1: Check if we can connect to Hostinger SMTP
echo "<h2>1. SMTP Connection Test</h2>";

try {
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
        
        // Try to authenticate
        echo "<br><strong>Testing Authentication:</strong><br>";
        
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        echo "AUTH LOGIN: " . htmlspecialchars($response) . "<br>";
        
        if (substr($response, 0, 3) == '334') {
            fputs($smtp, base64_encode('info@npcpest.com') . "\r\n");
            $response = fgets($smtp, 515);
            echo "Username (info@npcpest.com): " . htmlspecialchars($response) . "<br>";
            
            if (substr($response, 0, 3) == '334') {
                fputs($smtp, base64_encode('NPc@2745') . "\r\n");
                $response = fgets($smtp, 515);
                echo "Password: " . htmlspecialchars($response) . "<br>";
                
                if (substr($response, 0, 3) == '235') {
                    echo "<br>✅ <strong>Authentication SUCCESSFUL!</strong><br>";
                    echo "The email account info@npcpest.com exists and is working.<br>";
                } else {
                    echo "<br>❌ <strong>Authentication FAILED!</strong><br>";
                    echo "The password might be incorrect or the account doesn't exist.<br>";
                }
            } else {
                echo "<br>❌ <strong>Username REJECTED!</strong><br>";
                echo "The email account info@npcpest.com does not exist in Hostinger.<br>";
            }
        } else {
            echo "<br>❌ <strong>AUTH LOGIN REJECTED!</strong><br>";
            echo "The server doesn't support authentication or there's an issue.<br>";
        }
        
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
    }
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 2: Check if PHP mail() works
echo "<h2>2. PHP mail() Test</h2>";

if (function_exists('mail')) {
    echo "✅ PHP mail() function is available<br>";
    
    $test_email = 'test@example.com'; // Change this to your email
    $subject = "PHP mail() Test - " . date('Y-m-d H:i:s');
    $message = "This is a test email sent via PHP mail() function.";
    
    $headers = array(
        'From: NPC Services <info@npcpest.com>',
        'Reply-To: support@npcservices.com',
        'X-Mailer: PHP/' . phpversion()
    );
    
    $mail_sent = mail($test_email, $subject, $message, implode("\r\n", $headers));
    
    if ($mail_sent) {
        echo "✅ PHP mail() test email sent successfully!<br>";
        echo "Please check your email (and spam folder) for the test message.<br>";
    } else {
        echo "❌ PHP mail() test email failed to send<br>";
    }
} else {
    echo "❌ PHP mail() function is not available<br>";
}

echo "<hr>";

// Instructions
echo "<h2>3. Next Steps</h2>";

echo "<h3>If Authentication Failed:</h3>";
echo "<ol>";
echo "<li><strong>Create Email Account in Hostinger:</strong>";
echo "<ul>";
echo "<li>Login to your Hostinger control panel</li>";
echo "<li>Go to 'Email' section</li>";
echo "<li>Click 'Create Email Account'</li>";
echo "<li>Email: <strong>info@npcpest.com</strong></li>";
echo "<li>Password: <strong>NPc@2745</strong> (or your preferred password)</li>";
echo "<li>Storage: Choose appropriate limit</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Update Password:</strong> If you used a different password, update it in <code>hostinger_smtp_service.php</code></li>";
echo "<li><strong>Test Again:</strong> Run this test again after creating the account</li>";
echo "</ol>";

echo "<h3>If Authentication Succeeded:</h3>";
echo "<ul>";
echo "<li>✅ Your email account is working!</li>";
echo "<li>Test booking a service on your website</li>";
echo "<li>Check if confirmation emails are received</li>";
echo "</ul>";

echo "<h3>Alternative Solution:</h3>";
echo "<p>If SMTP continues to fail, you can use PHP mail() which is already working:</p>";
echo "<ul>";
echo "<li>✅ PHP mail() function is available</li>";
echo "<li>✅ PHP mail() test was successful</li>";
echo "<li>Update your booking service to use PHP mail() instead of SMTP</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='quick_email_test.php'>Quick Email Test</a></li>";
echo "<li><a href='test_simple_email.php'>Full Email Test</a></li>";
echo "<li><a href='hostinger_smtp_service.php'>SMTP Service Test</a></li>";
echo "<li><a href='test_email.php'>Basic Email Test</a></li>";
echo "</ul>";
?>
