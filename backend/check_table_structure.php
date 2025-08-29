<?php
require_once 'conn.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => true, 'data' => []];

try {
    // Check bookings table structure
    $result = $conn->query("DESCRIBE bookings");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $response['data']['bookings_columns'] = $columns;
    $response['data']['has_session2_status'] = in_array('session2_status', $columns);
    $response['data']['has_session2_date'] = in_array('session2_date', $columns);
    $response['data']['has_session2_completed_date'] = in_array('session2_completed_date', $columns);
    
    // Check services table structure
    $result = $conn->query("DESCRIBE services");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $response['data']['services_columns'] = $columns;
    $response['data']['has_has_session2'] = in_array('has_session2', $columns);
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

$conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
