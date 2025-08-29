<?php
// Test the simplified email approach

header('Content-Type: text/plain; charset=utf-8');

echo "=== Simple Email Test ===\n\n";

try {
    require_once 'conn.php';
    
    $bookingId = 'BK202508141226';
    $technicianId = 1;
    
    echo "Testing simplified email for:\n";
    echo "- Booking ID: $bookingId\n";
    echo "- Technician ID: $technicianId\n\n";
    
    // Get user email directly
    $userQuery = "SELECT u.customer_name, u.email_id, b.service_name FROM bookings b JOIN users u ON b.user_id = u.user_id WHERE b.booking_id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("s", $bookingId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    
    if ($user && $user['email_id']) {
        echo "✅ User found:\n";
        echo "   - Name: " . $user['customer_name'] . "\n";
        echo "   - Email: " . $user['email_id'] . "\n";
        echo "   - Service: " . $user['service_name'] . "\n\n";
        
        // Get technician details
        $techQuery = "SELECT employee_name, phone_number FROM technicians WHERE technician_id = ?";
        $techStmt = $conn->prepare($techQuery);
        $techStmt->bind_param("i", $technicianId);
        $techStmt->execute();
        $techResult = $techStmt->get_result();
        $technician = $techResult->fetch_assoc();
        $techStmt->close();
        
        if ($technician) {
            echo "✅ Technician found:\n";
            echo "   - Name: " . $technician['employee_name'] . "\n";
            echo "   - Phone: " . $technician['phone_number'] . "\n\n";
            
            // Prepare email
            $to = $user['email_id'];
            $subject = "Service Completed - Booking #$bookingId";
            
            $message = "
            <html>
            <body>
                <h2>Service Completed Successfully!</h2>
                <p>Dear {$user['customer_name']},</p>
                <p>Your service has been completed successfully.</p>
                <br>
                <h3>Service Details:</h3>
                <ul>
                    <li><strong>Booking ID:</strong> $bookingId</li>
                    <li><strong>Service:</strong> {$user['service_name']}</li>
                    <li><strong>Technician:</strong> {$technician['employee_name']}</li>
                    <li><strong>Technician Phone:</strong> {$technician['phone_number']}</li>
                    <li><strong>Completion Date:</strong> " . date('Y-m-d H:i:s') . "</li>
                </ul>
                <br>
                <p>Thank you for choosing our services!</p>
                <p>Best regards,<br>NPC Services Team</p>
            </body>
            </html>
            ";
            
            // Email headers
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: NPC Services <noreply@npcpest.com>" . "\r\n";
            
            echo "Sending email...\n";
            
            // Send email
            $mailSent = mail($to, $subject, $message, $headers);
            
            if ($mailSent) {
                echo "✅ Email sent successfully to: $to\n";
                
                // Log the email
                $logQuery = "INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("sssss", $to, $user['customer_name'], $bookingId, $user['service_name'], '0');
                $logStmt->execute();
                $logStmt->close();
                
                echo "✅ Email logged to database\n";
            } else {
                echo "❌ Failed to send email to: $to\n";
            }
        } else {
            echo "❌ Technician not found\n";
        }
    } else {
        echo "❌ User email not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\n🎯 Now try completing a service from the technician app!\n";
echo "The simplified email should work immediately.\n";
?>
