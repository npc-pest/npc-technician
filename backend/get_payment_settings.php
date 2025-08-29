<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Session-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conn.php';

try {
    // Check if session ID is provided (for admin access)
    $headers = getallheaders();
    $sessionId = isset($headers['Session-ID']) ? $headers['Session-ID'] : '';
    
    // If session ID is provided, validate admin access
    if (!empty($sessionId)) {
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
    }
    
    // Get payment settings - this should be publicly accessible
    $query = "SELECT cash_on_delivery_enabled, online_payment_enabled FROM payment_settings WHERE id = 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        
        echo json_encode([
            'status' => 'success',
            'payment_settings' => [
                'cash_on_delivery_enabled' => (bool)$settings['cash_on_delivery_enabled'],
                'online_payment_enabled' => (bool)$settings['online_payment_enabled']
            ]
        ]);
    } else {
        // If no settings found, create default settings
        $insertQuery = "INSERT INTO payment_settings (cash_on_delivery_enabled, online_payment_enabled) VALUES (1, 1)";
        $conn->query($insertQuery);
        
        echo json_encode([
            'status' => 'success',
            'payment_settings' => [
                'cash_on_delivery_enabled' => true,
                'online_payment_enabled' => true
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch payment settings: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 