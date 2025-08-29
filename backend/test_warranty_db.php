<?php
include 'conn.php';

// Test if warranty_period column exists in services table
$sql = "SHOW COLUMNS FROM services LIKE 'warranty_period'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "✅ warranty_period column exists in services table\n";
    
    // Check current warranty periods
    $sql2 = "SELECT service_name, warranty_period FROM services ORDER BY service_name";
    $result2 = $conn->query($sql2);
    
    echo "\nCurrent warranty periods:\n";
    while ($row = $result2->fetch_assoc()) {
        echo "- {$row['service_name']}: {$row['warranty_period']}\n";
    }
} else {
    echo "❌ warranty_period column does NOT exist in services table\n";
    echo "You need to run the migration script first:\n";
    echo "php backend/add_service_warranty_field.sql\n";
}

$conn->close();
?>
