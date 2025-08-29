<?php
// Simple script to check service status
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Service Status Check</h1>";

require 'conn.php';

if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

echo "<p>✅ Database connected successfully</p>";

// Check if status field exists
$checkFieldQuery = "SHOW COLUMNS FROM services LIKE 'status'";
$result = $conn->query($checkFieldQuery);

if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Status field does not exist!</p>";
    echo "<p>Please run the database update script first.</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Status field exists</p>";
}

// Show all services with their status
echo "<h2>All Services Status</h2>";
$servicesQuery = "SELECT service_id, service_name, status FROM services ORDER BY service_id";
$servicesResult = $conn->query($servicesQuery);

if ($servicesResult && $servicesResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Service ID</th><th>Service Name</th><th>Status</th><th>Action</th></tr>";
    
    while ($row = $servicesResult->fetch_assoc()) {
        $statusColor = ($row['status'] === 'active' || $row['status'] === null) ? 'green' : 'red';
        $statusText = ($row['status'] === 'active' || $row['status'] === null) ? 'active' : 'inactive';
        $actionText = ($row['status'] === 'active' || $row['status'] === null) ? 'Hide' : 'Show';
        $actionColor = ($row['status'] === 'active' || $row['status'] === null) ? 'red' : 'green';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['service_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>" . htmlspecialchars($statusText) . "</td>";
        echo "<td style='color: $actionColor; font-weight: bold;'>$actionText</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No services found in the database.</p>";
}

// Test the toggle functionality
echo "<h2>Test Toggle Functionality</h2>";
echo "<p>Click the links below to test the toggle functionality:</p>";

$testServicesQuery = "SELECT service_id, service_name, status FROM services ORDER BY service_id LIMIT 3";
$testResult = $conn->query($testServicesQuery);

if ($testResult && $testResult->num_rows > 0) {
    while ($row = $testResult->fetch_assoc()) {
        $currentStatus = $row['status'] ?? 'active';
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
        $actionText = ($currentStatus === 'active') ? 'Hide' : 'Show';
        
        echo "<p>";
        echo "<strong>" . htmlspecialchars($row['service_name']) . "</strong> (ID: " . $row['service_id'] . ") - ";
        echo "Current Status: <span style='color: " . ($currentStatus === 'active' ? 'green' : 'red') . "; font-weight: bold;'>$currentStatus</span> - ";
        echo "<a href='test_toggle.php?service_id=" . $row['service_id'] . "&action=toggle' target='_blank'>Test Toggle to $newStatus</a>";
        echo "</p>";
    }
}

$conn->close();
?>
