<?php
require_once 'conn.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => true, 'messages' => []];

try {
    // Step 1: Add has_session2 column to services table if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM services LIKE 'has_session2'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE services ADD COLUMN has_session2 TINYINT(1) DEFAULT 0 AFTER warranty_period";
        if ($conn->query($sql) === TRUE) {
            $response['messages'][] = "✅ Added has_session2 column to services table";
        } else {
            $response['messages'][] = "❌ Error adding has_session2 column: " . $conn->error;
            $response['success'] = false;
        }
    } else {
        $response['messages'][] = "ℹ️ has_session2 column already exists";
    }
    
    // Step 2: Set has_session2 = 1 for some test services
    $testServices = ["Resume Review", "WALL PAINTING SERVICE", "aasdfgjqwwqwqwqw"];
    
    foreach ($testServices as $serviceName) {
        $stmt = $conn->prepare("UPDATE services SET has_session2 = 1 WHERE service_name = ?");
        $stmt->bind_param("s", $serviceName);
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            if ($affectedRows > 0) {
                $response['messages'][] = "✅ Set has_session2 = 1 for service: $serviceName";
            } else {
                $response['messages'][] = "ℹ️ Service not found: $serviceName";
            }
        } else {
            $response['messages'][] = "❌ Error updating service $serviceName: " . $stmt->error;
        }
    }
    
    // Step 3: Verify the changes
    $result = $conn->query("SELECT service_id, service_name, has_session2 FROM services WHERE has_session2 = 1");
    $servicesWithSession2 = [];
    while ($row = $result->fetch_assoc()) {
        $servicesWithSession2[] = $row;
    }
    $response['data']['services_with_session2'] = $servicesWithSession2;
    
    // Step 4: Test the JOIN query
    $testQuery = "
        SELECT 
            b.booking_id,
            b.service_name,
            s.has_session2
        FROM bookings b
        LEFT JOIN services s ON b.service_name = s.service_name
        WHERE b.service_name IN ('Resume Review', 'WALL PAINTING SERVICE', 'aasdfgjqwwqwqwqw')
        LIMIT 5
    ";
    
    $result = $conn->query($testQuery);
    $testResults = [];
    while ($row = $result->fetch_assoc()) {
        $testResults[] = $row;
    }
    $response['data']['test_join_results'] = $testResults;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'][] = "❌ Error: " . $e->getMessage();
}

$conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
