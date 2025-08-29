<?php
require_once 'conn.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => true, 'data' => []];

try {
    // 1. Check if has_session2 column exists in services table
    $result = $conn->query("SHOW COLUMNS FROM services LIKE 'has_session2'");
    $hasColumn = $result->num_rows > 0;
    $response['data']['has_session2_column_exists'] = $hasColumn;
    
    if ($hasColumn) {
        // 2. Check services with has_session2 = 1
        $result = $conn->query("SELECT service_id, service_name, has_session2 FROM services WHERE has_session2 = 1");
        $servicesWithSession2 = [];
        while ($row = $result->fetch_assoc()) {
            $servicesWithSession2[] = $row;
        }
        $response['data']['services_with_session2'] = $servicesWithSession2;
        
        // 3. Check all services
        $result = $conn->query("SELECT service_id, service_name, has_session2 FROM services ORDER BY service_id DESC LIMIT 10");
        $allServices = [];
        while ($row = $result->fetch_assoc()) {
            $allServices[] = $row;
        }
        $response['data']['recent_services'] = $allServices;
        
        // 4. Test the JOIN query with a specific service name
        $testServiceName = "Resume Review"; // From your API response
        $stmt = $conn->prepare("
            SELECT 
                b.booking_id,
                b.service_name,
                s.has_session2
            FROM bookings b
            LEFT JOIN services s ON b.service_name = s.service_name
            WHERE b.service_name = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $testServiceName);
        $stmt->execute();
        $result = $stmt->get_result();
        $testBooking = $result->fetch_assoc();
        
        $response['data']['test_join_query'] = [
            'service_name' => $testServiceName,
            'result' => $testBooking
        ];
        
        // 5. Check if there are any services with the exact names from bookings
        $bookingServiceNames = ["aasdfgjqwwqwqwqw", "sdfsdfs", "WALL PAINTING SERVICE", "dssdffd", "Resume Review", "as"];
        $serviceMatches = [];
        
        foreach ($bookingServiceNames as $serviceName) {
            $stmt = $conn->prepare("SELECT service_id, service_name, has_session2 FROM services WHERE service_name = ?");
            $stmt->bind_param("s", $serviceName);
            $stmt->execute();
            $result = $stmt->get_result();
            $service = $result->fetch_assoc();
            $serviceMatches[$serviceName] = $service ?: 'NOT_FOUND';
        }
        
        $response['data']['service_matches'] = $serviceMatches;
        
    } else {
        $response['data']['message'] = 'has_session2 column does not exist in services table';
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

$conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
