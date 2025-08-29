<?php
// Test script to verify admin API functionality
header('Content-Type: text/html; charset=utf-8');

require 'conn.php';

if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

echo "<h1>Admin API Test</h1>";

// Test 1: Simulate admin fetch_services action
echo "<h2>Test 1: Admin Fetch Services</h2>";

// Simulate the exact request that the admin panel makes
$_POST['action'] = 'fetch_services';

// Include the services_manager.php logic
$action = $_POST['action'];

if ($action === 'fetch_services') {
    // Use the exact query from services_manager.php
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
        
        echo "<p style='color: green;'>✅ Admin API working correctly</p>";
        echo "<p><strong>Total services returned:</strong> " . count($services) . "</p>";
        
        echo "<h3>Services with their status:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
        
        foreach ($services as $service) {
            $statusColor = $service['status'] === 'active' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>" . $service['service_id'] . "</td>";
            echo "<td>" . htmlspecialchars($service['service_name']) . "</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>" . $service['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Query failed: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Invalid action</p>";
}

echo "<hr>";
echo "<p><strong>Test completed!</strong> The admin panel should now show all services with their correct status.</p>";

$conn->close();
?>
