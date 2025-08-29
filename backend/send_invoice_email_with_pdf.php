<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, session-id, Authorization, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, session-id, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    exit(0);
}

require_once 'conn.php';

// Function to send email with PDF attachment
function sendEmailWithPDF($to, $subject, $message, $pdfData, $filename) {
    $boundary = md5(time());
    
            $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: NPC <info@npcpest.com>',
            'Reply-To: info@npcpest.com',
            'X-Mailer: PHP/' . phpversion()
        );
    
    $body = "--" . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n\r\n";
    
    // Add PDF attachment
    $body .= "--" . $boundary . "\r\n";
    $body .= "Content-Type: application/pdf; name=\"" . $filename . "\"\r\n";
    $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($pdfData)) . "\r\n";
    
    $body .= "--" . $boundary . "--\r\n";
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $userEmail = $input['user_email'] ?? '';
        $userName = $input['user_name'] ?? 'Customer';
        $bookingId = $input['booking_id'] ?? '';
        $serviceName = $input['service_name'] ?? '';
        $totalAmount = $input['total_amount'] ?? 0;
        $serviceDate = $input['service_date'] ?? '';
        $serviceTime = $input['service_time'] ?? '';
        $address = $input['address'] ?? '';
        $cartItems = $input['cart_items'] ?? [];
        $discount = $input['discount'] ?? 0;
        $taxes = $input['taxes'] ?? 0;
        $paymentStatus = $input['payment_status'] ?? 'Pending';
        $pdfData = $input['pdf_data'] ?? '';
        
        if (empty($userEmail)) {
            throw new Exception('User email is required');
        }
        
        if (empty($pdfData)) {
            throw new Exception('PDF data is required');
        }
        
        // Decode base64 PDF data
        $pdfBinary = base64_decode($pdfData);
        
        if ($pdfBinary === false) {
            throw new Exception('Invalid PDF data format');
        }
        
        // Create email subject and message
        $subject = "Invoice for Booking #$bookingId - NPC Services";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: linear-gradient(135deg, #0d9488, #0891b2); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: bold; }
                .header p { margin: 10px 0 0 0; font-size: 16px; opacity: 0.9; }
                .content { padding: 30px; }
                .booking-details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .booking-details h3 { margin: 0 0 15px 0; color: #0d9488; font-size: 18px; }
                .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                .detail-label { font-weight: 600; color: #374151; }
                .detail-value { color: #111827; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; }
                .contact-info { margin-top: 15px; }
                .contact-info a { color: #0d9488; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>NPC Pest Control</h1>
                    <p>Professional Services</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>$userName</strong>,</p>
                    <p>Thank you for choosing NPC Pest Control! Your booking has been confirmed and your professional invoice is attached to this email.</p>
                    
                    <div class='booking-details'>
                        <h3>📋 Booking Summary</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span>
                            <span class='detail-value'>$bookingId</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Service:</span>
                            <span class='detail-value'>$serviceName</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Date:</span>
                            <span class='detail-value'>$serviceDate</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Time:</span>
                            <span class='detail-value'>$serviceTime</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Total Amount:</span>
                            <span class='detail-value'><strong>₹$totalAmount</strong></span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Payment Status:</span>
                            <span class='detail-value'>$paymentStatus</span>
                        </div>
                    </div>
                    
                    <p>Your professional invoice is attached to this email. Please review the details and keep it for your records.</p>
                    
                    <p>If you have any questions about your booking or need to make changes, please don't hesitate to contact us.</p>
                </div>
                <div class='footer'>
                    <p><strong>NPC Pest Control</strong></p>
                    <div class='contact-info'>
                        <p>📧 <a href='mailto:info@npcpest.com'>info@npcpest.com</a></p>
                        <p>📞 +91 8111043335</p>
                        <p>🌐 <a href='https://npcpest.com'>www.npcpest.com</a></p>
                    </div>
                    <p style='margin-top: 20px; font-style: italic;'>Thank you for trusting us with your pest control needs!</p>
                </div>
            </div>
        </body>
        </html>";
        
        $filename = "Invoice_$bookingId.pdf";
        
        // Send email with PDF attachment
        $emailSent = sendEmailWithPDF($userEmail, $subject, $message, $pdfBinary, $filename);
        
        if ($emailSent) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Invoice email sent successfully with PDF attachment'
            ]);
        } else {
            throw new Exception('Failed to send email');
        }
        
    } catch (Exception $e) {
        error_log("Invoice email error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method is allowed'
    ]);
}
?> 