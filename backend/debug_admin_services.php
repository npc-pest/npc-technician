<?php
// Debug script to check what admin services API returns
header('Content-Type: application/json');

require 'conn.php';

if (!$conn) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

// Simulate the exact query that services_manager.php uses
$query = "SELECT service_id, service_name, description, image_path, locations, has_session2, created_at, COALESCE(status, 'active') as status FROM services ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result) {
    $services = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format the created_at date like the original function
        $created_date = new DateTime($row['created_at']);
        $formatted_date = $created_date->format('M d, Y');
        
        $services[] = [
            'service_id' => $row['service_id'],
            'service_name' => $row['service_name'],
            'description' => $row['description'],
            'image_path' => $row['image_path'],
            'locations' => $row['locations'],
            'created_at' => $formatted_date,
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        "status" => "success",
        "services" => $services,
        "count" => count($services),
        "debug_info" => [
            "total_services_in_db" => $result->num_rows,
            "query_used" => $query
        ]
    ]);
    
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch services: " . $conn->error
    ]);
}

$conn->close();
?>
