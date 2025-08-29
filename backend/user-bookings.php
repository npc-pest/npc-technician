<?php
require_once 'conn.php';
// user-bookings.php - Updated for your actual bookings table structure

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}



// GET - Fetch user bookings by session ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['booking_id'])) {
    try {
        $sessionId = null;
if (isset($_GET['session_id'])) {
    $sessionId = trim($_GET['session_id']);
} elseif (isset($_SERVER['HTTP_SESSION_ID'])) {
    $sessionId = trim($_SERVER['HTTP_SESSION_ID']);
}
        
        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Session ID is required']);
            exit;
        }
        
        // First, get user details from session
        $userQuery = "SELECT user_id, customer_name, email_id, mobile_number, address1, address2 FROM users WHERE sessionid = ?";
        $userStmt = $conn->prepare($userQuery);
        
        if (!$userStmt) {
            throw new Exception("Failed to prepare user query: " . $conn->error);
        }
        
        $userStmt->bind_param("s", $sessionId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();
        
        if (!$userData) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found with provided session ID']);
            exit;
        }
        
        // Format user data for response
        $user = [
            'id' => $userData['user_id'],
            'name' => $userData['customer_name'],
            'email' => $userData['email_id'],
            'phone' => $userData['mobile_number'],
            'address' => trim(($userData['address1'] ?? '') . ' ' . ($userData['address2'] ?? ''))
        ];
        
        // Check if session2 columns exist in the table
        $checkColumns = $conn->query("SHOW COLUMNS FROM bookings LIKE 'session2_status'");
        $hasSession2Columns = $checkColumns->num_rows > 0;
        
        // Build query with JOIN to get has_session2 from services table
        $baseFields = "
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
                b.created_at,
                b.updated_at";
        
        $session2Fields = $hasSession2Columns ? ",
                b.session2_status,
                b.session2_date,
                b.session2_completed_date" : "";
        
        $bookingsQuery = "
            SELECT 
                $baseFields$session2Fields,
                s.has_session2,
                s.image_path as service_image
            FROM bookings b
            LEFT JOIN services s ON b.service_name = s.service_name
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ";
        
        $bookingsStmt = $conn->prepare($bookingsQuery);
        
        if (!$bookingsStmt) {
            throw new Exception("Failed to prepare bookings query: " . $conn->error);
        }
        
        $bookingsStmt->bind_param("i", $userData['user_id']);
        $bookingsStmt->execute();
        $bookingsResult = $bookingsStmt->get_result();
        $bookings = $bookingsResult->fetch_all(MYSQLI_ASSOC);
        
        // Process and categorize bookings
        $activeBookings = [];
        $completedBookings = [];
        $cancelledBookings = [];
        
        foreach ($bookings as $booking) {
            // Format the booking data to match expected response structure
            $formattedBooking = [
                'booking_id' => $booking['booking_id'],
                'service_id' => null, // Not available in your schema
                'service_name' => $booking['service_name'] ?? 'Unknown Service',
                'service_description' => $booking['special_notes'] ?? 'Professional service as per your requirements',
                'service_image' => $booking['service_image'] ?? null, // Get from services table
                'category' => 'Home Service', // Default category
                'space_type' => 'Standard', // Not available in your schema, using default
                'item_total' => (float)$booking['subtotal'],
                'taxes' => (float)$booking['discount_amount'], // Using discount as taxes for now
                'total_amount' => (float)$booking['total_amount'],
                'booking_date' => $booking['created_at'] ? date('Y-m-d', strtotime($booking['created_at'])) : null,
                'service_date' => $booking['service_date'],
                'service_time' => $booking['time_slot'] ?? 'N/A',
                'status' => $booking['booking_status'],
                'address' => $booking['service_address'],
                'special_instructions' => $booking['special_notes'],
                'payment_mode' => $booking['payment_method'],
                'payment_status' => $booking['payment_status'],
                'payment_id' => $booking['razorpay_payment_id'],
                'session2_status' => $hasSession2Columns ? $booking['session2_status'] : null,
                'session2_date' => $hasSession2Columns ? $booking['session2_date'] : null,
                'session2_completed_date' => $hasSession2Columns ? $booking['session2_completed_date'] : null,
                'created_at' => $booking['created_at'],
                'updated_at' => $booking['updated_at']
            ];
            
            // Add has_session2 and session2_info fields
            $formattedBooking['has_session2'] = $booking['has_session2'] ?? null;
            $formattedBooking['session2_info'] = null;
            
            // Add session2 information if service has session2
            if ($booking['has_session2'] == 1) {
                $formattedBooking['session2_info'] = '2nd Session after 15 days';
            }
            
            // Debug log for has_session2 value
            error_log("Processing booking {$booking['booking_id']}: has_session2 = " . ($booking['has_session2'] ?? 'NULL'));
            
            // Format dates safely
            $formattedBooking['booking_date_formatted'] = $formattedBooking['booking_date'] ? date('d M Y', strtotime($formattedBooking['booking_date'])) : 'N/A';
            $formattedBooking['service_date_formatted'] = $formattedBooking['service_date'] ? date('d M Y', strtotime($formattedBooking['service_date'])) : 'N/A';
            $formattedBooking['created_at_formatted'] = $formattedBooking['created_at'] ? date('d M Y, h:i A', strtotime($formattedBooking['created_at'])) : 'N/A';
            
                         // Check 3-hour cancellation policy for active bookings
             $canCancel = false;
             if (in_array(strtolower($booking['booking_status']), ['pending', 'confirmed', 'in_progress'])) {
                 $serviceDateTime = $booking['service_date'];
                 $timeSlot = $booking['time_slot'] ?? '';
                 
                 // Extract start time from time slot
                 $startTime = $timeSlot;
                 if (strpos($timeSlot, ' - ') !== false) {
                     $startTime = trim(explode(' - ', $timeSlot)[0]);
                 }
                 
                 // Combine service date and start time
                 if ($serviceDateTime && $startTime) {
                     try {
                         $serviceTimestamp = strtotime($serviceDateTime . ' ' . $startTime);
                         if ($serviceTimestamp) {
                             $currentTimestamp = time();
                             $timeDifference = $serviceTimestamp - $currentTimestamp;
                             $hoursUntilService = $timeDifference / 3600;
                             
                             // Allow cancellation if service is more than 3 hours away
                             // Example: Service at 11 AM, can cancel until 8 AM (3 hours before)
                             $canCancel = $hoursUntilService > 3;
                             
                             // Debug log for cancellation logic
                             error_log("Cancellation check for booking {$booking['booking_id']}: Service at {$serviceDateTime} {$startTime}, Current time: " . date('Y-m-d H:i:s') . ", Hours until service: {$hoursUntilService}, Can cancel: " . ($canCancel ? 'Yes' : 'No'));
                         }
                     } catch (Exception $e) {
                         $canCancel = false;
                     }
                 }
             }
            
            $formattedBooking['can_cancel'] = $canCancel;
            
            // Add status color and icon based on booking_status field
            switch (strtolower($formattedBooking['status'])) {
                case 'completed':
                    $formattedBooking['status_color'] = '#4CAF50';
                    $formattedBooking['status_icon'] = 'check_circle';
                    $formattedBooking['status_display'] = 'Completed';
                    $completedBookings[] = $formattedBooking;
                    break;
                    
                case 'cancelled':
                    $formattedBooking['status_color'] = '#f44336';
                    $formattedBooking['status_icon'] = 'cancel';
                    $formattedBooking['status_display'] = 'Cancelled';
                    $cancelledBookings[] = $formattedBooking;
                    break;
                    
                    
                    
                case 'pending':
                    $formattedBooking['status_color'] = '#FFC107';
                    $formattedBooking['status_icon'] = 'hourglass_empty';
                    $formattedBooking['status_display'] = 'Pending';
                    $activeBookings[] = $formattedBooking;
                    break;
                    
                case 'confirmed':
                    $formattedBooking['status_color'] = '#2196F3';
                    $formattedBooking['status_icon'] = 'check';
                    $formattedBooking['status_display'] = 'Confirmed';
                    $activeBookings[] = $formattedBooking;
                    break;
                    
                case 'in_progress':
                    $formattedBooking['status_color'] = '#FF9800';
                    $formattedBooking['status_icon'] = 'build';
                    $formattedBooking['status_display'] = 'In Progress';
                    $activeBookings[] = $formattedBooking;
                    break;
                    
                default:
                    // Fallback for any other status
                    $formattedBooking['status_color'] = '#9E9E9E';
                    $formattedBooking['status_icon'] = 'help';
                    $formattedBooking['status_display'] = ucfirst($formattedBooking['status']);
                    $activeBookings[] = $formattedBooking;
                    break;
            }
            
            // Add session2 information if service has session2
            if ($booking['has_session2'] == 1) {
                $formattedBooking['session2_info'] = '2nd Session after 15 days';
            }
            
            // Add has_session2 field to response for debugging
            $formattedBooking['has_session2'] = $booking['has_session2'];
            
            // Debug logging
            error_log("Booking {$booking['booking_id']} - Service: {$booking['service_name']} - has_session2: " . ($booking['has_session2'] ?? 'NULL'));
        }
        
        // Calculate summary statistics
        $totalBookings = count($bookings);
        $totalSpent = array_sum(array_column($bookings, 'total_amount'));
        $activeCount = count($activeBookings);
        $completedCount = count($completedBookings);
        
        $response = [
            'user' => $user,
            'summary' => [
                'total_bookings' => $totalBookings,
                'total_spent' => $totalSpent,
                'active_bookings' => $activeCount,
                'completed_bookings' => $completedCount
            ],
            'bookings' => [
                'active' => $activeBookings,
                'completed' => $completedBookings,
                'cancelled' => $cancelledBookings
            ]
        ];
        
        http_response_code(200);
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("Booking fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error occurred',
            'message' => $e->getMessage(),
            'debug_info' => [
                'session_id' => isset($sessionId) ? $sessionId : 'not provided',
                'user_found' => isset($userData) ? 'yes' : 'no'
            ]
        ]);
    }
}

// GET - Fetch specific booking details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['booking_id'])) {
    try {
        $bookingId = $_GET['booking_id'];
        $sessionId = null;
if (isset($_GET['session_id'])) {
    $sessionId = trim($_GET['session_id']);
} elseif (isset($_SERVER['HTTP_SESSION_ID'])) {
    $sessionId = trim($_SERVER['HTTP_SESSION_ID']);
}
        
        if (!$sessionId || !$bookingId) {
            http_response_code(400);
            echo json_encode(['error' => 'Session ID and Booking ID are required']);
            exit;
        }
        
        // Get user ID from session
        $userStmt = $conn->prepare("SELECT user_id FROM users WHERE sessionid = ?");
        if (!$userStmt) {
            throw new Exception("Failed to prepare user query: " . $conn->error);
        }
        
        $userStmt->bind_param("s", $sessionId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        // Check if session2 columns exist in the table
        $checkColumns = $conn->query("SHOW COLUMNS FROM bookings LIKE 'session2_status'");
        $hasSession2Columns = $checkColumns->num_rows > 0;
        
        // Build query based on column existence
        $baseFields = "
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
                b.created_at,
                b.updated_at,
                s.has_session2";
        
        $session2Fields = $hasSession2Columns ? ",
                b.session2_status,
                b.session2_date,
                b.session2_completed_date" : "";
        
        $bookingQuery = "
            SELECT 
                $baseFields$session2Fields
            FROM bookings b
            LEFT JOIN services s ON b.service_name = s.service_name
            WHERE b.booking_id = ? AND b.user_id = ?
        ";
        
        $bookingStmt = $conn->prepare($bookingQuery);
        if (!$bookingStmt) {
            throw new Exception("Failed to prepare booking query: " . $conn->error);
        }
        
        $bookingStmt->bind_param("si", $bookingId, $user['user_id']);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        $bookingData = $bookingResult->fetch_assoc();
        
        if (!$bookingData) {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
            exit;
        }
        
        // Format response
        $booking = [
            'booking_id' => $bookingData['booking_id'],
            'service_name' => $bookingData['service_name'] ?? 'Unknown Service',
            'service_description' => $bookingData['special_notes'] ?? 'Professional service as per your requirements',
            'service_image' => null,
            'category' => 'Home Service',
            'space_type' => 'Standard',
            'item_total' => (float)$bookingData['subtotal'],
            'taxes' => (float)$bookingData['discount_amount'],
            'total_amount' => (float)$bookingData['total_amount'],
            'booking_date' => $bookingData['created_at'] ? date('Y-m-d', strtotime($bookingData['created_at'])) : null,
            'service_date' => $bookingData['service_date'],
            'service_time' => $bookingData['time_slot'] ?? 'N/A',
            'status' => $bookingData['booking_status'],
            'address' => $bookingData['service_address'],
            'special_instructions' => $bookingData['special_notes'],
            'payment_mode' => $bookingData['payment_method'],
            'payment_status' => $bookingData['payment_status'],
            'payment_id' => $bookingData['razorpay_payment_id'],
            'session2_status' => $hasSession2Columns ? $bookingData['session2_status'] : null,
            'session2_date' => $hasSession2Columns ? $bookingData['session2_date'] : null,
            'session2_completed_date' => $hasSession2Columns ? $bookingData['session2_completed_date'] : null,
            'has_session2' => $bookingData['has_session2']
        ];
        
        // Format dates
        $booking['booking_date_formatted'] = $booking['booking_date'] ? date('d M Y', strtotime($booking['booking_date'])) : 'N/A';
        $booking['service_date_formatted'] = $booking['service_date'] ? date('d M Y', strtotime($booking['service_date'])) : 'N/A';
        $booking['created_at_formatted'] = $bookingData['created_at'] ? date('d M Y, h:i A', strtotime($bookingData['created_at'])) : 'N/A';
        
        // Add session2 information if service has session2
        if ($bookingData['has_session2'] == 1) {
            $booking['session2_info'] = '2nd Session after 15 days';
        }
        
        // Debug logging for single booking
        error_log("Single Booking {$bookingId} - Service: {$bookingData['service_name']} - has_session2: " . ($bookingData['has_session2'] ?? 'NULL'));
        
        http_response_code(200);
        echo json_encode($booking);
        
    } catch (Exception $e) {
        error_log("Single booking fetch error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// POST - Update booking status (for cancellation, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            exit;
        }
        
        $bookingId = isset($data['booking_id']) ? trim($data['booking_id']) : null;
        $sessionId = isset($data['session_id']) ? trim($data['session_id']) : null;
        $action = isset($data['action']) ? trim($data['action']) : null;
        
        if (!$bookingId || !$sessionId || !$action) {
            http_response_code(400);
            echo json_encode(['error' => 'Booking ID, Session ID, and action are required']);
            exit;
        }
        
        // Get user ID from session
        $userStmt = $conn->prepare("SELECT user_id FROM users WHERE sessionid = ?");
        if (!$userStmt) {
            throw new Exception("Failed to prepare user query: " . $conn->error);
        }
        
        $userStmt->bind_param("s", $sessionId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
                 // Check if booking belongs to user
         $checkStmt = $conn->prepare("SELECT booking_id, booking_status, service_date, time_slot FROM bookings WHERE booking_id = ? AND user_id = ?");
        if (!$checkStmt) {
            throw new Exception("Failed to prepare check query: " . $conn->error);
        }
        
        $checkStmt->bind_param("si", $bookingId, $user['user_id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $booking = $checkResult->fetch_assoc();
        
        if (!$booking) {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
            exit;
        }
        
        // Handle different actions
        switch ($action) {
            case 'cancel':
                // Only allow cancellation for pending, confirmed, and in_progress bookings
                if (in_array(strtolower($booking['booking_status']), ['completed', 'cancelled'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Cannot cancel this booking. Booking is already ' . $booking['booking_status']]);
                    exit;
                }
                
                                 // Check 3-hour cancellation policy
                 $serviceDateTime = $booking['service_date'];
                 $timeSlot = isset($booking['time_slot']) ? $booking['time_slot'] : '';
                 
                 // Debug log for cancellation attempt
                 error_log("Cancel attempt for booking {$bookingId}: Service date: {$serviceDateTime}, Time slot: {$timeSlot}");
                 
                 // Log the raw booking data for debugging
                 error_log("Raw booking data: " . json_encode($booking));
                 
                 // Extract start time from time slot (e.g., "9:00 AM - 11:00 AM" -> "9:00 AM")
                 $startTime = $timeSlot;
                 if (strpos($timeSlot, ' - ') !== false) {
                     $startTime = trim(explode(' - ', $timeSlot)[0]);
                 }
                 
                 // Combine service date and start time
                 $serviceTimestamp = null;
                 if ($serviceDateTime && $startTime) {
                     try {
                         $serviceTimestamp = strtotime($serviceDateTime . ' ' . $startTime);
                         error_log("Service timestamp: " . date('Y-m-d H:i:s', $serviceTimestamp));
                     } catch (Exception $e) {
                         // If time parsing fails, just use the date
                         $serviceTimestamp = strtotime($serviceDateTime);
                         error_log("Time parsing failed, using date only: " . date('Y-m-d H:i:s', $serviceTimestamp));
                     }
                 }
                 
                 if ($serviceTimestamp) {
                     $currentTimestamp = time();
                     $timeDifference = $serviceTimestamp - $currentTimestamp;
                     $hoursUntilService = $timeDifference / 3600; // Convert seconds to hours
                     
                     error_log("Current time: " . date('Y-m-d H:i:s', $currentTimestamp));
                     error_log("Hours until service: {$hoursUntilService}");
                     
                     // Test calculation for your example
                     $testServiceTime = strtotime('2025-08-11 10:00:00');
                     $testCurrentTime = strtotime('2025-08-10 14:46:00');
                     $testHours = ($testServiceTime - $testCurrentTime) / 3600;
                     error_log("Test calculation - Service: 2025-08-11 10:00, Current: 2025-08-10 14:46, Hours: {$testHours}");
                     
                     if ($hoursUntilService <= 3) {
                         $remainingHours = max(0, round($hoursUntilService, 1));
                         error_log("Cancellation denied: Service is in {$remainingHours} hours");
                         http_response_code(400);
                         echo json_encode([
                             'error' => 'Cannot cancel booking within 3 hours of service time',
                             'message' => "Service is in {$remainingHours} hours. Cancellation must be made at least 3 hours before the scheduled service.",
                             'service_date' => date('d M Y', $serviceTimestamp),
                             'service_time' => $timeSlot,
                             'hours_remaining' => $remainingHours
                         ]);
                         exit;
                     } else {
                         error_log("Cancellation allowed: Service is in {$hoursUntilService} hours");
                     }
                 } else {
                     error_log("Could not calculate service timestamp");
                 }
                
                $updateStmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled', updated_at = NOW() WHERE booking_id = ?");
                if (!$updateStmt) {
                    throw new Exception("Failed to prepare update query: " . $conn->error);
                }
                
                $updateStmt->bind_param("s", $bookingId);
                $updateStmt->execute();
                
                http_response_code(200);
                echo json_encode(['message' => 'Booking cancelled successfully']);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }
        
    } catch (Exception $e) {
        error_log("Booking update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>