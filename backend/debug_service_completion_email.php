<?php
// Debug file for Service Completion Email
// This will help us identify where the issue is

header('Content-Type: text/plain; charset=utf-8');

echo "=== Service Completion Email Debug ===\n\n";

// Test data
$booking_id = 'BK202508113768';
$technician_id = 10;

echo "Test Data:\n";
echo "- Booking ID: $booking_id\n";
echo "- Technician ID: $technician_id\n\n";

try {
    echo "Step 1: Testing database connection...\n";
    require_once 'conn.php';
    echo "✅ Database connection successful\n\n";
    
    echo "Step 2: Testing booking query...\n";
    $bookingQuery = "
        SELECT b.*, u.customer_name, u.email_id, u.mobile_number, b.service_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = ?
    ";
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->bind_param("s", $booking_id);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    $booking = $bookingResult->fetch_assoc();
    
    if ($booking) {
        echo "✅ Booking found:\n";
        echo "   - Customer: " . $booking['customer_name'] . "\n";
        echo "   - Email: " . $booking['email_id'] . "\n";
        echo "   - Service: " . $booking['service_name'] . "\n";
        echo "   - Date: " . $booking['service_date'] . "\n";
    } else {
        echo "❌ Booking not found\n";
        exit;
    }
    
    echo "\nStep 3: Testing technician query...\n";
    $technicianQuery = "SELECT employee_name, phone_number FROM technicians WHERE technician_id = ?";
    $technicianStmt = $conn->prepare($technicianQuery);
    $technicianStmt->bind_param("i", $technician_id);
    $technicianStmt->execute();
    $technicianResult = $technicianStmt->get_result();
    $technician = $technicianResult->fetch_assoc();
    
    if ($technician) {
        echo "✅ Technician found:\n";
        echo "   - Name: " . $technician['employee_name'] . "\n";
        echo "   - Phone: " . $technician['phone_number'] . "\n";
    } else {
        echo "❌ Technician not found\n";
        exit;
    }
    
    echo "\nStep 4: Testing email sending...\n";
    
    // Extract data
    $user_name = $booking['customer_name'];
    $user_email = $booking['email_id'];
    $service_name = $booking['service_name'];
    $service_date = $booking['service_date'];
    $time_slot = $booking['time_slot'];
    $technician_name = $technician['employee_name'];
    $technician_phone = $technician['phone_number'];
    $completion_date = date('Y-m-d H:i:s');
    
    // Email subject
    $subject = "Service Completed - Booking #$booking_id";
    
    // Simple email body for testing
    $emailBody = "
    <html>
    <body>
        <h2>Service Completed</h2>
        <p>Dear $user_name,</p>
        <p>Your service has been completed successfully!</p>
        <p><strong>Booking ID:</strong> $booking_id</p>
        <p><strong>Service:</strong> $service_name</p>
        <p><strong>Technician:</strong> $technician_name</p>
        <p><strong>Completion Date:</strong> $completion_date</p>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: NPC Services <noreply@npcpest.com>',
        'Reply-To: support@npcpest.com',
        'X-Mailer: PHP/' . phpversion()
    );
    
    echo "Sending email to: $user_email\n";
    echo "Subject: $subject\n";
    
    // Send email
    $email_sent = mail($user_email, $subject, $emailBody, implode("\r\n", $headers));
    
    if ($email_sent) {
        echo "✅ Email sent successfully!\n";
        
        // Log email attempt
        $logQuery = "
            INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("ssssd", $user_email, $user_name, $booking_id, $service_name, $booking['total_amount']);
        $logStmt->execute();
        echo "✅ Email logged to database\n";
        
    } else {
        echo "❌ Email sending failed\n";
        
        // Log failed attempt
        $logQuery = "
            INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("ssssd", $user_email, $user_name, $booking_id, $service_name, $booking['total_amount']);
        $logStmt->execute();
        echo "❌ Failed email logged to database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
