<?php
require 'conn.php';

// Add status field to services table if it doesn't exist
$checkFieldQuery = "SHOW COLUMNS FROM services LIKE 'status'";
$result = $conn->query($checkFieldQuery);

if ($result->num_rows == 0) {
    // Field doesn't exist, add it
    $addFieldQuery = "ALTER TABLE services ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
    
    if ($conn->query($addFieldQuery) === TRUE) {
        echo "Status field added successfully to services table\n";
        
        // Update existing services to have 'active' status
        $updateQuery = "UPDATE services SET status = 'active' WHERE status IS NULL";
        if ($conn->query($updateQuery) === TRUE) {
            echo "Existing services updated to active status\n";
        } else {
            echo "Error updating existing services: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding status field: " . $conn->error . "\n";
    }
} else {
    echo "Status field already exists in services table\n";
}

$conn->close();
?>
