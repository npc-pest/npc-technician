<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Session-ID');

include 'conn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    "status" => "success",
    "message" => "Service availability API is working",
    "timestamp" => date('Y-m-d H:i:s'),
    "test" => true
]);

$conn->close();
?>
