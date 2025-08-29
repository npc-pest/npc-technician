<?php
// Test toggle functionality
header('Content-Type: text/html; charset=utf-8');

require 'conn.php';

if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($service_id == 0) {
    echo "<p style='color: red;'>❌ Invalid service ID</p>";
    exit;
}

if ($action !== 'toggle') {
    echo "<p style='color: red;'>❌ Invalid action</p>";
    exit;
}

// Get current status
$stmt = $conn->prepare("SELECT service_name, status FROM services WHERE service_id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color: red;'>❌ Service not found</p>";
    exit;
}

$service = $result->fetch_assoc();
$current_status = $service['status'] ?? 'active';
$new_status = ($current_status === 'active') ? 'inactive' : 'active';

echo "<h1>Toggle Service Status</h1>";
echo "<p><strong>Service:</strong> " . htmlspecialchars($service['service_name']) . "</p>";
echo "<p><strong>Current Status:</strong> <span style='color: " . ($current_status === 'active' ? 'green' : 'red') . "; font-weight: bold;'>$current_status</span></p>";
echo "<p><strong>New Status:</strong> <span style='color: " . ($new_status === 'active' ? 'green' : 'red') . "; font-weight: bold;'>$new_status</span></p>";

// Update status
$update_stmt = $conn->prepare("UPDATE services SET status = ? WHERE service_id = ?");
$update_stmt->bind_param("si", $new_status, $service_id);

if ($update_stmt->execute()) {
    echo "<p style='color: green;'>✅ Status updated successfully!</p>";
    echo "<p>Service is now <strong>" . ($new_status === 'active' ? 'ACTIVE' : 'INACTIVE') . "</strong></p>";
    
    if ($new_status === 'active') {
        echo "<p>✅ This service will now be visible to users</p>";
    } else {
        echo "<p>❌ This service will now be hidden from users</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Failed to update status: " . $update_stmt->error . "</p>";
}

echo "<hr>";
echo "<p><a href='check_service_status.php'>← Back to Service Status Check</a></p>";

$stmt->close();
$update_stmt->close();
$conn->close();
?>
