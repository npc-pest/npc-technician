<?php
// Web-accessible database update script for service status field
// Access this file through your browser: https://yourdomain.com/backend/update_service_status_web.php

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Service Status Field Update</h1>";

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
    echo "<p>📝 Status field does not exist. Adding it now...</p>";
    
    // Field doesn't exist, add it
    $addFieldQuery = "ALTER TABLE services ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
    
    if ($conn->query($addFieldQuery) === TRUE) {
        echo "<p style='color: green;'>✅ Status field added successfully to services table</p>";
        
        // Update existing services to have 'active' status
        $updateQuery = "UPDATE services SET status = 'active' WHERE status IS NULL";
        if ($conn->query($updateQuery) === TRUE) {
            echo "<p style='color: green;'>✅ Existing services updated to active status</p>";
        } else {
            echo "<p style='color: red;'>❌ Error updating existing services: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Error adding status field: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Status field already exists in services table</p>";
}

// Show current services with their status
echo "<h2>Current Services Status</h2>";
$servicesQuery = "SELECT service_id, service_name, status FROM services ORDER BY service_id LIMIT 10";
$servicesResult = $conn->query($servicesQuery);

if ($servicesResult && $servicesResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Service ID</th><th>Service Name</th><th>Status</th></tr>";
    
    while ($row = $servicesResult->fetch_assoc()) {
        $statusColor = $row['status'] === 'active' ? 'green' : 'red';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['service_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No services found in the database.</p>";
}

$conn->close();

echo "<hr>";
echo "<p><strong>Update completed!</strong> You can now use the service toggle functionality in the admin panel.</p>";
echo "<p><a href='../npc-website/src/pages/AdminDashboard.tsx' target='_blank'>Go to Admin Dashboard</a></p>";
?>
