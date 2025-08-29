<?php
// Simple endpoint checker to see which API the technician app is calling

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Technician-ID, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log the request
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'endpoint' => $_SERVER['SCRIPT_NAME'],
    'headers' => getallheaders(),
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
];

// Log to file
$logFile = 'technician_api_calls.log';
file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);

// Return success response
echo json_encode([
    'status' => 'success',
    'message' => 'Endpoint called successfully',
    'data' => [
        'endpoint' => basename($_SERVER['SCRIPT_NAME']),
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s'),
        'headers_received' => array_keys(getallheaders()),
        'post_data_received' => !empty($_POST),
        'raw_data_received' => !empty(file_get_contents('php://input'))
    ]
]);
?>
