<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Session-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conn.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// Validate required fields
if (!isset($input['cash_on_delivery_enabled']) || !isset($input['online_payment_enabled'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: cash_on_delivery_enabled, online_payment_enabled'
    ]);
    exit;
}

// Validate session for admin access
$headers = getallheaders();
$sessionId = '';

// Try different ways to get the Session-ID header
if (isset($headers['Session-ID'])) {
    $sessionId = $headers['Session-ID'];
} elseif (isset($headers['session-id'])) {
    $sessionId = $headers['session-id'];
} elseif (isset($headers['Session-id'])) {
    $sessionId = $headers['Session-id'];
} else {
    // Try to get from $_SERVER
    $sessionId = $_SERVER['HTTP_SESSION_ID'] ?? '';
}

if (empty($sessionId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Session ID is required'
    ]);
    exit;
}

// Check if admin session is valid
$adminQuery = "SELECT id FROM admin_login WHERE sessionid = ? AND session_expiry > NOW()";
$stmt = $conn->prepare($adminQuery);
$stmt->bind_param("s", $sessionId);
$stmt->execute();
$adminResult = $stmt->get_result();

if ($adminResult->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired admin session'
    ]);
    exit;
}

$adminData = $adminResult->fetch_assoc();
$adminId = $adminData['id'];

try {
    // Convert boolean values to integers
    $cashEnabled = $input['cash_on_delivery_enabled'] ? 1 : 0;
    $onlineEnabled = $input['online_payment_enabled'] ? 1 : 0;
    
    // Update payment settings
    $updateQuery = "UPDATE payment_settings SET 
                    cash_on_delivery_enabled = ?, 
                    online_payment_enabled = ?, 
                    updated_by = ? 
                    WHERE id = 1";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("iii", $cashEnabled, $onlineEnabled, $adminId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment settings updated successfully',
            'payment_settings' => [
                'cash_on_delivery_enabled' => (bool)$cashEnabled,
                'online_payment_enabled' => (bool)$onlineEnabled
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update payment settings'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 