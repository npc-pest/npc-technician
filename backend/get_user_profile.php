<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Session-ID');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_connection.php';

function getUserProfile($sessionId) {
    global $conn;
    
    // Get user ID from session
    $stmt = $conn->prepare("SELECT user_id FROM user_sessions WHERE session_id = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['status' => 'error', 'message' => 'Invalid or expired session'];
    }
    
    $session = $result->fetch_assoc();
    $userId = $session['user_id'];
    
    // Get user profile data
    $stmt = $conn->prepare("
        SELECT 
            customer_name,
            email_id,
            mobile_number,
            address1,
            address2,
            gender,
            country,
            profile_pic
        FROM customers 
        WHERE customer_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['status' => 'error', 'message' => 'User profile not found'];
    }
    
    $profile = $result->fetch_assoc();
    
    return [
        'status' => 'success',
        'data' => $profile
    ];
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sessionId = $_SERVER['HTTP_SESSION_ID'] ?? null;
    
    if (!$sessionId) {
        echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
        exit;
    }
    
    $result = getUserProfile($sessionId);
    echo json_encode($result);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
?>

