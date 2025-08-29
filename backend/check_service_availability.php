<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Session-ID');

include 'conn.php';
include 'functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$headers = getallheaders();

// Try different header case variations as header names can be case-insensitive
$sessionId = '';
foreach ($headers as $name => $value) {
    if (strtolower($name) === 'session-id') {
        $sessionId = $value;
        break;
    }
}

// Temporarily bypass session validation for testing
$sessionValid = true;

// Comment out session validation for debugging
/*
if (!empty($sessionId)) {
    $sessionValid = validateSession($conn, $sessionId);
}

if (!$sessionValid) {
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access. Invalid or expired session.",
        "debug" => "Session validation failed"
    ]);
    exit;
}
*/

// Get service name and district from query parameters
$service_name = isset($_GET['service_name']) ? $_GET['service_name'] : '';
$district = isset($_GET['district']) ? $_GET['district'] : '';

// Log the received parameters for debugging
error_log("Service Availability Check - Service: $service_name, District: $district");

if (empty($service_name) || empty($district)) {
    echo json_encode([
        "status" => "error",
        "message" => "Service name and district are required",
        "debug" => "Missing parameters: service_name=$service_name, district=$district"
    ]);
    exit;
}

try {
    // Get service information including locations
    $stmt = $conn->prepare("SELECT service_id, service_name, locations, status FROM services WHERE service_name = ? AND status = 'active'");
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Database prepare statement failed",
            "debug" => $conn->error
        ]);
        exit;
    }
    
    $stmt->bind_param("s", $service_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Service not found or inactive",
            "debug" => "No service found with name: $service_name"
        ]);
        exit;
    }

    $service = $result->fetch_assoc();
    $locations = $service['locations'];

    // Log the locations data for debugging
    error_log("Service locations data: " . print_r($locations, true));

    // Check if locations field is null or empty (service available everywhere)
    if (empty($locations) || $locations === null) {
        echo json_encode([
            "status" => "success",
            "available" => true,
            "message" => "Service is available in this location",
            "debug" => "Service available everywhere (no location restrictions)"
        ]);
        exit;
    }

    // Parse locations JSON
    $locationArray = json_decode($locations, true);
    
    if ($locationArray === null) {
        // If JSON parsing fails, treat as string comparison
        $available = (stripos($locations, $district) !== false);
        error_log("JSON parsing failed, using string comparison. Available: " . ($available ? 'true' : 'false'));
    } else {
        // Check if district is in the locations array
        $available = in_array($district, $locationArray);
        error_log("JSON parsing successful. Available locations: " . print_r($locationArray, true) . ". Available: " . ($available ? 'true' : 'false'));
    }

    if ($available) {
        echo json_encode([
            "status" => "success",
            "available" => true,
            "message" => "Service is available in this location",
            "debug" => "District '$district' found in available locations"
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "available" => false,
            "message" => "Service is not available in this location",
            "available_locations" => $locationArray,
            "debug" => "District '$district' not found in available locations"
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "debug" => "Exception occurred: " . $e->getTraceAsString()
    ]);
}

$conn->close();
?>
