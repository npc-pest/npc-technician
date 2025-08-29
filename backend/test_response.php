<?php
// Test the sendJsonResponse function

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

echo "Testing sendJsonResponse function...\n";

$testResponse = [
    'status' => 'success',
    'message' => 'Test response working',
    'data' => [
        'test' => 'value',
        'timestamp' => date('Y-m-d H:i:s')
    ]
];

try {
    sendJsonResponse($testResponse, 200);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "This should not appear if sendJsonResponse works correctly.\n";
?>
