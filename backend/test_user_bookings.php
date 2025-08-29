<?php
require_once 'conn.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => true, 'data' => []];

try {
    // Test the exact query from user-bookings.php
    $testQuery = "
        SELECT 
            b.booking_id,
            b.user_id,
            b.service_name,
            b.service_date,
            b.time_slot,
            b.service_address,
            b.special_notes,
            b.subtotal,
            b.discount_amount,
            b.coupon_code,
            b.coupon_discount,
            b.total_amount,
            b.payment_method,
            b.payment_status,
            b.razorpay_order_id,
            b.razorpay_payment_id,
            b.booking_status,
            b.session2_status,
            b.session2_date,
            b.session2_completed_date,
            b.created_at,
            b.updated_at,
            s.has_session2
        FROM bookings b
        LEFT JOIN services s ON b.service_name = s.service_name
        WHERE b.user_id = 22
        ORDER BY b.created_at DESC
        LIMIT 5
    ";
    
    $result = $conn->query($testQuery);
    $bookings = [];
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    $response['data']['raw_bookings'] = $bookings;
    
    // Test the processing logic
    $processedBookings = [];
    foreach ($bookings as $booking) {
        $formattedBooking = [
            'booking_id' => $booking['booking_id'],
            'service_name' => $booking['service_name'],
            'has_session2' => $booking['has_session2'],
            'session2_info' => null
        ];
        
        // Add session2 information if service has session2
        if ($booking['has_session2'] == 1) {
            $formattedBooking['session2_info'] = '2nd Session after 15 days';
        }
        
        $processedBookings[] = $formattedBooking;
    }
    
    $response['data']['processed_bookings'] = $processedBookings;
    
    // Check if has_session2 column exists in the query result
    if (count($bookings) > 0) {
        $firstBooking = $bookings[0];
        $response['data']['has_session2_in_result'] = array_key_exists('has_session2', $firstBooking);
        $response['data']['sample_has_session2_value'] = $firstBooking['has_session2'] ?? 'NOT_FOUND';
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

$conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
