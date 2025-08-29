<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Session-ID');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_connection.php';

function updateUserProfile($sessionId, $profileData) {
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
    
    // Update user profile data
    $stmt = $conn->prepare("
        UPDATE customers 
        SET 
            customer_name = ?,
            email_id = ?,
            mobile_number = ?,
            address1 = ?,
            address2 = ?,
            gender = ?,
            country = ?
        WHERE customer_id = ?
    ");
    
    $stmt->bind_param("sssssssi", 
        $profileData['customer_name'],
        $profileData['email_id'],
        $profileData['mobile_number'],
        $profileData['address1'],
        $profileData['address2'],
        $profileData['gender'],
        $profileData['country'],
        $userId
    );
    
    if ($stmt->execute()) {
        return ['status' => 'success', 'message' => 'Profile updated successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to update profile'];
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionId = $_SERVER['HTTP_SESSION_ID'] ?? null;
    
    if (!$sessionId) {
        echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
        exit;
    }
    
    $result = updateUserProfile($sessionId, $input);
    echo json_encode($result);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
?>

