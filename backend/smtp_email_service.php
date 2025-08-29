<?php
// SMTP Email Service for NPC Services
// This provides an alternative to PHP mail() function for better reliability

class SMTPEmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_secure; // 'tls' or 'ssl'
    
    public function __construct($host = null, $port = null, $username = null, $password = null, $secure = 'tls') {
        // Default configuration - you can override these
        $this->smtp_host = $host ?? 'smtp.gmail.com'; // Change to your SMTP server
        $this->smtp_port = $port ?? 587;
        $this->smtp_username = $username ?? 'your-email@gmail.com'; // Change to your email
        $this->smtp_password = $password ?? 'your-app-password'; // Change to your app password
        $this->smtp_secure = $secure;
    }
    
    public function sendEmail($to, $subject, $message, $headers = array()) {
        try {
            // Create SMTP connection
            $smtp = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
            
            if (!$smtp) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return false;
            }
            
            // Read server greeting
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '220') {
                error_log("SMTP Server greeting failed: $response");
                fclose($smtp);
                return false;
            }
            
            // Send EHLO
            fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $response = fgets($smtp, 515);
            
            // Start TLS if required
            if ($this->smtp_secure == 'tls') {
                fputs($smtp, "STARTTLS\r\n");
                $response = fgets($smtp, 515);
                if (substr($response, 0, 3) == '220') {
                    stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
                    $response = fgets($smtp, 515);
                }
            }
            
            // Authenticate
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) == '334') {
                fputs($smtp, base64_encode($this->smtp_username) . "\r\n");
                $response = fgets($smtp, 515);
                if (substr($response, 0, 3) == '334') {
                    fputs($smtp, base64_encode($this->smtp_password) . "\r\n");
                    $response = fgets($smtp, 515);
                    if (substr($response, 0, 3) != '235') {
                        error_log("SMTP Authentication failed: $response");
                        fclose($smtp);
                        return false;
                    }
                }
            }
            
            // Send MAIL FROM
            fputs($smtp, "MAIL FROM: <" . $this->smtp_username . ">\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '250') {
                error_log("SMTP MAIL FROM failed: $response");
                fclose($smtp);
                return false;
            }
            
            // Send RCPT TO
            fputs($smtp, "RCPT TO: <$to>\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '250') {
                error_log("SMTP RCPT TO failed: $response");
                fclose($smtp);
                return false;
            }
            
            // Send DATA
            fputs($smtp, "DATA\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '354') {
                error_log("SMTP DATA failed: $response");
                fclose($smtp);
                return false;
            }
            
            // Send email content
            $email_content = "Subject: $subject\r\n";
            $email_content .= "To: $to\r\n";
            $email_content .= "From: NPC Services <" . $this->smtp_username . ">\r\n";
            $email_content .= "Reply-To: support@npcservices.com\r\n";
            $email_content .= "MIME-Version: 1.0\r\n";
            $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email_content .= "X-Mailer: PHP/" . phpversion() . "\r\n\r\n";
            $email_content .= $message . "\r\n.\r\n";
            
            fputs($smtp, $email_content);
            $response = fgets($smtp, 515);
            
            // Quit
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);
            
            if (substr($response, 0, 3) == '250') {
                error_log("SMTP Email sent successfully to: $to");
                return true;
            } else {
                error_log("SMTP Email sending failed: $response");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SMTP Email error: " . $e->getMessage());
            return false;
        }
    }
}

// Function to send booking confirmation email using SMTP
function sendBookingConfirmationEmailSMTP($conn, $bookingId, $userId, $serviceName, $serviceDate, $timeSlot, $totalAmount, $serviceAddress, $userEmail = null) {
    try {
        // Get user details
        $userStmt = $conn->prepare("SELECT customer_name, email_id FROM users WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            error_log("User not found for email sending");
            return false;
        }
        
        $user = $userResult->fetch_assoc();
        $userName = $user['customer_name'];
        
        // Use provided email if available, otherwise use database email
        if (!empty($userEmail)) {
            $emailToUse = $userEmail;
            error_log("Using email from booking form: $emailToUse");
        } else {
            $emailToUse = $user['email_id'];
            error_log("Using email from database: $emailToUse");
        }
        
        if (empty($emailToUse)) {
            error_log("User email is empty, cannot send confirmation email");
            return false;
        }
        
        error_log("Sending booking confirmation email to: $emailToUse");
        
        // Email subject and content
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
                <h2>Dear $userName,</h2>
                
                <p>Thank you for choosing NPC Services! Your booking has been confirmed successfully.</p>
                
                <div class='booking-details'>
                    <h3>Booking Details:</h3>
                    <p><strong>Booking ID:</strong> #$bookingId</p>
                    <p><strong>Service:</strong> $serviceName</p>
                    <p><strong>Service Date:</strong> $serviceDate</p>
                    <p><strong>Service Time:</strong> $timeSlot</p>
                    <p><strong>Service Address:</strong> $serviceAddress</p>
                    <p><strong>Total Amount:</strong> <span class='amount'>₹$totalAmount</span></p>
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
        
        // Initialize SMTP service
        $smtpService = new SMTPEmailService();
        
        // Send email using SMTP
        $mailSent = $smtpService->sendEmail($emailToUse, $subject, $message);
        
        if ($mailSent) {
            error_log("Booking confirmation email sent successfully via SMTP to: $emailToUse");
            
            // Log the email sending in database
            try {
                $logSql = "INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param("sssss", $emailToUse, $userName, $bookingId, $serviceName, $totalAmount);
                $logStmt->execute();
                error_log("Email log saved to database");
            } catch (Exception $dbError) {
                error_log("Database logging error: " . $dbError->getMessage());
            }
            
            return true;
        } else {
            error_log("Failed to send booking confirmation email via SMTP to: $emailToUse");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("SMTP Email sending error: " . $e->getMessage());
        return false;
    }
}

// Test function to verify SMTP configuration
function testSMTPConfiguration() {
    echo "<h2>SMTP Configuration Test</h2>";
    
    $smtpService = new SMTPEmailService();
    
    // Test connection
    $test_email = "test@example.com"; // Change this to your email
    $subject = "SMTP Test Email - " . date('Y-m-d H:i:s');
    $message = "This is a test email sent via SMTP from your NPC Services website.";
    
    echo "Testing SMTP email sending to: $test_email<br>";
    
    $result = $smtpService->sendEmail($test_email, $subject, $message);
    
    if ($result) {
        echo "✅ SMTP email sent successfully!<br>";
        echo "Please check your email (and spam folder) for the test message.<br>";
    } else {
        echo "❌ SMTP email failed to send<br>";
        echo "Please check your SMTP configuration in the smtp_email_service.php file.<br>";
    }
}

// If this file is accessed directly, run the test
if (basename($_SERVER['PHP_SELF']) == 'smtp_email_service.php') {
    echo "<h1>SMTP Email Service Test</h1>";
    testSMTPConfiguration();
}
?>
