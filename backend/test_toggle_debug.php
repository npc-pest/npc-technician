<?php
// Debug script to test toggle functionality
header('Content-Type: text/html; charset=utf-8');

require 'conn.php';

if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

echo "<h1>Toggle Debug Test</h1>";

// Get a service to test with
$testQuery = "SELECT service_id, service_name, status FROM services ORDER BY service_id LIMIT 1";
$testResult = $conn->query($testQuery);

if ($testResult && $testResult->num_rows > 0) {
    $testService = $testResult->fetch_assoc();
    $service_id = $testService['service_id'];
    $service_name = $testService['service_name'];
    $current_status = $testService['status'] ?? 'active';
    
    echo "<h2>Testing with Service: $service_name (ID: $service_id)</h2>";
    echo "<p><strong>Current Status:</strong> <span style='color: " . ($current_status === 'active' ? 'green' : 'red') . "; font-weight: bold;'>$current_status</span></p>";
    
    // Test 1: Check current status
    echo "<h3>Step 1: Check Current Status</h3>";
    $checkStmt = $conn->prepare("SELECT status FROM services WHERE service_id = ?");
    $checkStmt->bind_param("i", $service_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    echo "<p>Database status: <strong>" . ($checkRow['status'] ?? 'NULL') . "</strong></p>";
    
    // Test 2: Toggle status
    echo "<h3>Step 2: Toggle Status</h3>";
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';
    echo "<p>Will toggle to: <strong>$new_status</strong></p>";
    
    $updateStmt = $conn->prepare("UPDATE services SET status = ? WHERE service_id = ?");
    $updateStmt->bind_param("si", $new_status, $service_id);
    
    if ($updateStmt->execute()) {
        echo "<p style='color: green;'>✅ Status updated successfully</p>";
        
        // Test 3: Verify the change
        echo "<h3>Step 3: Verify Change</h3>";
        $verifyStmt = $conn->prepare("SELECT status FROM services WHERE service_id = ?");
        $verifyStmt->bind_param("i", $service_id);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $verifyRow = $verifyResult->fetch_assoc();
        
        echo "<p>New database status: <strong>" . ($verifyRow['status'] ?? 'NULL') . "</strong></p>";
        
        if ($verifyRow['status'] === $new_status) {
            echo "<p style='color: green;'>✅ Status change verified!</p>";
        } else {
            echo "<p style='color: red;'>❌ Status change verification failed!</p>";
        }
        
        // Test 4: Check admin query
        echo "<h3>Step 4: Test Admin Query</h3>";
        $adminQuery = "SELECT service_id, service_name, COALESCE(status, 'active') as status FROM services WHERE service_id = ?";
        $adminStmt = $conn->prepare($adminQuery);
        $adminStmt->bind_param("i", $service_id);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        $adminRow = $adminResult->fetch_assoc();
        
        echo "<p>Admin query result: <strong>" . ($adminRow['status'] ?? 'NULL') . "</strong></p>";
        
        // Test 5: Show all services
        echo "<h3>Step 5: All Services After Toggle</h3>";
        $allQuery = "SELECT service_id, service_name, status FROM services ORDER BY service_id";
        $allResult = $conn->query($allQuery);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
        while ($row = $allResult->fetch_assoc()) {
            $statusColor = ($row['status'] === 'active' || $row['status'] === null) ? 'green' : 'red';
            $statusText = ($row['status'] === 'active' || $row['status'] === null) ? 'active' : 'inactive';
            echo "<tr>";
            echo "<td>" . $row['service_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>$statusText</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to update status: " . $updateStmt->error . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ No services found to test with</p>";
}

echo "<hr>";
echo "<p><a href='check_service_status.php'>← Back to Service Status Check</a></p>";

$conn->close();
?>
