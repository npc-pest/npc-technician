<?php
// Alternative email sending using PDFShift (third-party PDF service)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'email_errors.log');

require_once 'conn.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log the received input
    error_log("PDFShift email - Received input: " . json_encode($input));
    
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['user_email', 'user_name', 'booking_id', 'service_name', 'total_amount'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    $user_email = $input['user_email'];
    $user_name = $input['user_name'];
    $booking_id = $input['booking_id'];
    $service_name = $input['service_name'];
    $total_amount = $input['total_amount'];
    $service_date = $input['service_date'] ?? '';
    $service_time = $input['service_time'] ?? '';
    $address = $input['address'] ?? '';
    $cart_items = $input['cart_items'] ?? [];
    $discount = $input['discount'] ?? 0;
    $taxes = $input['taxes'] ?? 0;
    $payment_status = $input['payment_status'] ?? 'Pending';
    
    // Log the processed data
    error_log("PDFShift email - Processing email for: $user_email, Booking: $booking_id, Service: $service_name");
    
    // Validate email format
    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }
    
    // Generate HTML content for PDF
    $html_content = generateInvoiceHTML($user_name, $booking_id, $service_name, $total_amount, $service_date, $service_time, $address, $cart_items, $discount, $taxes, $payment_status);
    
    // Try to generate PDF using PDFShift (free tier available)
    $pdf_content = generatePDFWithPDFShift($html_content);
    
    $has_pdf = !empty($pdf_content);
    error_log("PDFShift PDF generation result: " . ($has_pdf ? "SUCCESS - PDF size: " . strlen($pdf_content) . " bytes" : "FAILED - will send HTML email"));
    
    // Email content
    $subject = "NPC Services - Invoice for Booking #$booking_id";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
            .header { background: linear-gradient(135deg, #0d9488, #0891b2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: white; padding: 20px; border: 1px solid #ddd; border-top: none; }
            .invoice-details { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
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
            
            <p>Thank you for choosing NPC Services! Your booking has been confirmed and your invoice is " . ($has_pdf ? "attached to this email." : "included below.") . "</p>
            
            <div class='invoice-details'>
                <h3>Booking Details:</h3>
                <p><strong>Booking ID:</strong> #$booking_id</p>
                <p><strong>Service:</strong> $service_name</p>
                " . ($service_date ? "<p><strong>Service Date:</strong> $service_date</p>" : "") . "
                " . ($service_time ? "<p><strong>Service Time:</strong> $service_time</p>" : "") . "
                " . ($address ? "<p><strong>Address:</strong> $address</p>" : "") . "
                <p><strong>Total Amount:</strong> <span class='amount'>₹$total_amount</span></p>
            </div>
            
            " . (!$has_pdf ? $html_content : "") . "
            
            <p>" . ($has_pdf ? "Please find your detailed invoice attached to this email." : "Your invoice is displayed above.") . " You can also view your booking details in your account dashboard.</p>
            
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
    </html>
    ";
    
    // Create email headers and body based on whether we have PDF
    if ($has_pdf) {
        // Create multipart email with PDF attachment
        $boundary = md5(time());
        
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: NPC Services <info@npcpest.com>',
            'Reply-To: info@npcpest.com',
            'X-Mailer: PHP/' . phpversion()
        );
        
        // Build multipart message
        $email_body = "--" . $boundary . "\r\n";
        $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $email_body .= $message . "\r\n\r\n";
        
        // Add PDF attachment
        $email_body .= "--" . $boundary . "\r\n";
        $email_body .= "Content-Type: application/pdf; name=\"Invoice_$booking_id.pdf\"\r\n";
        $email_body .= "Content-Disposition: attachment; filename=\"Invoice_$booking_id.pdf\"\r\n";
        $email_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $email_body .= chunk_split(base64_encode($pdf_content)) . "\r\n";
        
        $email_body .= "--" . $boundary . "--\r\n";
    } else {
        // Send HTML email without PDF attachment
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: NPC Services <info@npcpest.com>',
            'Reply-To: support@npcservices.com',
            'X-Mailer: PHP/' . phpversion()
        );
        
        $email_body = $message;
    }
    
    // Log email attempt
    $email_type = $has_pdf ? "with PDF attachment" : "HTML only";
    error_log("PDFShift email - Attempting to send email $email_type to: $user_email");
    
    // Send email
    $mail_sent = mail($user_email, $subject, $email_body, implode("\r\n", $headers));
    
    // Log the result
    error_log("PDFShift email - Mail send result: " . ($mail_sent ? "SUCCESS" : "FAILED"));
    
    if ($mail_sent) {
        // Log the email sending
        try {
            $log_sql = "INSERT INTO email_logs (user_email, user_name, booking_id, service_name, total_amount, sent_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("sssss", $user_email, $user_name, $booking_id, $service_name, $total_amount);
            $log_result = $log_stmt->execute();
            error_log("PDFShift email - Database log result: " . ($log_result ? "SUCCESS" : "FAILED"));
        } catch (Exception $db_error) {
            error_log("PDFShift email - Database logging error: " . $db_error->getMessage());
        }
        
        echo json_encode([
            'status' => 'success', 
            'message' => $has_pdf ? 'Invoice email with PDF attachment sent successfully' : 'Invoice email sent successfully',
            'email_sent_to' => $user_email,
            'method' => $has_pdf ? 'pdfshift_with_pdf' : 'pdfshift_html'
        ]);
    } else {
        // Get the last error from PHP mail function
        $error = error_get_last();
        error_log("PDFShift email - Mail function error: " . json_encode($error));
        
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to send email. Please check server mail configuration.',
            'debug_info' => [
                'mail_function_available' => function_exists('mail'),
                'sendmail_path' => ini_get('sendmail_path'),
                'smtp_host' => ini_get('SMTP'),
                'last_error' => $error,
                'pdf_generation' => $has_pdf ? 'success' : 'failed'
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("PDFShift email - Send invoice email error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}

// Function to generate HTML content for invoice
function generateInvoiceHTML($user_name, $booking_id, $service_name, $total_amount, $service_date, $service_time, $address, $cart_items, $discount, $taxes, $payment_status) {
    // Calculate item total (sum of all cart items)
    $item_total = 0;
    if (!empty($cart_items) && is_array($cart_items)) {
        foreach ($cart_items as $item) {
            if (isset($item['price']) && isset($item['quantity'])) {
                $item_total += $item['price'] * $item['quantity'];
            }
        }
    }
    
    // Calculate final total
    $final_total = $item_total - $discount + $taxes;
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice #' . $booking_id . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 32px; border: 1px solid #ddd; }
            .header { margin-bottom: 30px; }
            .header-top { overflow: hidden; margin-bottom: 20px; }
            .logo-section { float: left; }
            .logo-box { width: 60px; height: 60px; background: #0d9488; border-radius: 8px; text-align: center; line-height: 60px; margin-bottom: 10px; }
            .logo-text { font-size: 24px; font-weight: bold; color: white; }
            .company-name { font-size: 24px; font-weight: bold; color: #111827; margin: 0 0 5px 0; }
            .company-tagline { color: #6b7280; margin: 0 0 10px 0; }
            .company-info { font-size: 14px; color: #6b7280; line-height: 1.4; }
            .invoice-title { float: right; text-align: right; }
            .invoice-title h2 { font-size: 32px; font-weight: bold; color: #111827; margin: 0 0 10px 0; }
            .invoice-details { font-size: 14px; color: #6b7280; line-height: 1.4; }
            .clear { clear: both; }
            .customer-section { margin-bottom: 30px; }
            .customer-left { float: left; width: 45%; }
            .customer-right { float: right; width: 45%; }
            .section-title { font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 12px 0; border-bottom: 2px solid #0d9488; padding-bottom: 5px; }
            .customer-info { font-size: 14px; color: #374151; line-height: 1.4; }
            .service-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .service-table th { background: #f9fafb; padding: 12px; text-align: left; font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase; border: 1px solid #e5e7eb; }
            .service-table td { padding: 12px; font-size: 14px; border: 1px solid #e5e7eb; }
            .payment-summary { text-align: right; }
            .payment-box { display: inline-block; width: 320px; background: #f9fafb; border: 1px solid #e5e7eb; padding: 20px; }
            .payment-row { margin-bottom: 10px; }
            .payment-row span:first-child { float: left; color: #6b7280; }
            .payment-row span:last-child { float: right; color: #111827; }
            .payment-total { border-top: 2px solid #0d9488; padding-top: 10px; margin-top: 15px; font-size: 18px; font-weight: 600; }
            .payment-total span:first-child { float: left; color: #111827; }
            .payment-total span:last-child { float: right; color: #0d9488; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
            .footer-section { float: left; width: 30%; margin-right: 2%; }
            .footer-title { font-weight: 600; color: #111827; margin: 0 0 8px 0; }
            .footer-text { font-size: 12px; margin: 0 0 4px 0; line-height: 1.3; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <div class="header-top">
                    <div class="logo-section">
                        <div class="logo-box">
                            <div class="logo-text">NPC</div>
                        </div>
                        <div class="company-name">NPC Pest Control</div>
                        <div class="company-tagline">Professional Services</div>
                        <div class="company-info">
                            <p style="margin: 0 0 4px 0;">NPC, NO. 158, Murugan Kovil Street</p>
                            <p style="margin: 0 0 4px 0;">Vanashakthi Nagar, Kolather, Chennai - 99</p>
                            <p style="margin: 0 0 4px 0;">Phone: +91 86374 54428</p>
                            <p style="margin: 0;">Email: info@npcpest.com</p>
                        </div>
                    </div>
                    
                    <div class="invoice-title">
                        <h2>TAX INVOICE</h2>
                        <div class="invoice-details">
                            <p style="margin: 0 0 4px 0;"><strong>Invoice Date:</strong> ' . date('d/m/Y') . '</p>
                            <p style="margin: 0 0 4px 0;"><strong>Invoice #:</strong> ' . $booking_id . '</p>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>

            <!-- Customer Information -->
            <div class="customer-section">
                <div class="customer-left">
                    <h3 class="section-title">Bill To:</h3>
                    <div class="customer-info">
                        <p style="font-weight: 600; margin: 0 0 4px 0;">' . htmlspecialchars($user_name) . '</p>
                        <p style="margin: 0 0 4px 0;">' . htmlspecialchars($address) . '</p>
                    </div>
                </div>
                
                <div class="customer-right">
                    <h3 class="section-title">Service Details:</h3>
                    <div class="customer-info">
                        <div style="margin-bottom: 4px; font-family: Arial, sans-serif; color: #374151;"><strong>Service Date:</strong> ' . ($service_date ? date('d/m/Y', strtotime($service_date)) : 'To be scheduled') . '</div>
                        <div style="margin-bottom: 4px; font-family: Arial, sans-serif; color: #374151;"><strong>Time Slot:</strong> ' . ($service_time ? $service_time : 'To be scheduled') . '</div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>

            <!-- Service Items -->
            <div style="margin-bottom: 32px;">
                <h3 class="section-title">Service Details</h3>
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Main Service -->
                            <tr>
                                <td style="font-weight: 500; color: #111827;">' . htmlspecialchars($service_name) . '</td>
                                <td style="color: #6b7280;">' . ($service_name . ' - Professional pest control service') . '</td>
                                <td style="text-align: right; color: #111827;">-</td>
                            </tr>';
    
    // Add sub-services if available
    if (!empty($cart_items) && is_array($cart_items)) {
        foreach ($cart_items as $item) {
            if (isset($item['service_type_name']) && isset($item['price']) && isset($item['quantity'])) {
                // Fix common misspellings
                $service_name_fixed = str_replace('Appartment', 'Apartment', $item['service_type_name']);
                $html .= '
                            <tr style="background: #f9fafb;">
                                <td style="padding-left: 32px; font-weight: 500; color: #111827;">• ' . htmlspecialchars($service_name_fixed) . ' - ' . (isset($item['room_size']) ? $item['room_size'] : '') . '</td>
                                <td style="color: #6b7280;">Quantity: ' . $item['quantity'] . ' × ₹' . number_format($item['price'], 2) . '</td>
                                <td style="text-align: right; color: #111827;">₹' . number_format($item['price'] * $item['quantity'], 2) . '</td>
                            </tr>';
            }
        }
    } else {
        // If no cart items, show main service with total
        $html .= '
                            <tr style="background: #f9fafb;">
                                <td style="padding-left: 32px; font-weight: 500; color: #111827;">• ' . htmlspecialchars($service_name) . '</td>
                                <td style="color: #6b7280;">Professional pest control service</td>
                                <td style="text-align: right; color: #111827;">₹' . number_format($item_total, 2) . '</td>
                            </tr>';
    }
    
    $html .= '
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="payment-summary">
                <div class="payment-box">
                    <h3 class="section-title">Payment Summary</h3>
                    <div class="payment-row">
                        <span>Item Total:</span>
                        <span>₹' . number_format($item_total, 2) . '</span>
                    </div>';
    
    if ($discount > 0) {
        $html .= '
                    <div class="payment-row">
                        <span>Discount:</span>
                        <span>-₹' . number_format($discount, 2) . '</span>
                    </div>';
    }
    
    if ($taxes > 0) {
        $html .= '
                    <div class="payment-row">
                        <span>Taxes:</span>
                        <span>₹' . number_format($taxes, 2) . '</span>
                    </div>';
    }
    
    $html .= '
                    <div class="payment-total">
                        <span>Total Amount:</span>
                        <span>₹' . number_format($final_total, 2) . '</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-section">
                    <h4 class="footer-title">Terms & Conditions</h4>
                    <div class="footer-text">• Payment is due upon receipt of this invoice</div>
                    <div class="footer-text">• Late payments may incur additional charges</div>
                    <div class="footer-text">• Service satisfaction is guaranteed</div>
                </div>
                <div class="footer-section">
                    <h4 class="footer-title">Contact Information</h4>
                    <div class="footer-text">Phone: +91 86374 54428</div>
                    <div class="footer-text">Email: info@npcpest.com</div>
                    <div class="footer-text">Website: www.npcservices.com</div>
                </div>
                <div class="footer-section">
                    <h4 class="footer-title">Thank You</h4>
                    <div class="footer-text">
                        Thank you for choosing NPC Pest Control for your service needs. 
                        We appreciate your business and look forward to serving you again.
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Function to generate PDF using PDFShift
function generatePDFWithPDFShift($html_content) {
    try {
        // PDFShift API endpoint
        $api_url = 'https://api.pdfshift.io/v3/convert/pdf';
        
        // You need to sign up for a free account at https://pdfshift.io/
        // Replace 'YOUR_API_KEY' with your actual API key
        $api_key = 'sk_7914256c228ea275f14f3959dca8a4392165aa47'; // PDFShift API key
        
        // Prepare the request data
        $data = array(
            'source' => $html_content,
            'landscape' => false,
            'use_print' => true,
            'margin_top' => '10mm',
            'margin_right' => '10mm',
            'margin_bottom' => '10mm',
            'margin_left' => '10mm'
        );
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));
        
        // Execute the request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            error_log("PDFShift: PDF generated successfully");
            return $response; // This is the PDF content
        } else {
            error_log("PDFShift: Failed to generate PDF. HTTP Code: $http_code, Response: $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("PDFShift error: " . $e->getMessage());
        return false;
    }
}
?> 