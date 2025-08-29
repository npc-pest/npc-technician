<?php
// Database migration to add warranty_days field to services table
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include 'conn.php';

try {
    // Check if warranty_days column already exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM services LIKE 'warranty_days'");
    
    if ($checkColumn->num_rows == 0) {
        // Add warranty_days column
        $sql = "ALTER TABLE services ADD COLUMN warranty_days INT DEFAULT 90 AFTER description";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode([
                "status" => "success",
                "message" => "warranty_days column added successfully to services table"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Error adding warranty_days column: " . $conn->error
            ]);
        }
    } else {
        echo json_encode([
            "status" => "info",
            "message" => "warranty_days column already exists in services table"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Exception: " . $e->getMessage()
    ]);
}

$conn->close();
?>
