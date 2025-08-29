 <?php
// Check if technician_status_log table exists

header('Content-Type: text/plain; charset=utf-8');

echo "=== Checking Missing Tables ===\n\n";

try {
    require_once 'conn.php';
    
    echo "Step 1: Checking technician_status_log table...\n";
    echo "=============================================\n";
    
    $checkTableQuery = "SHOW TABLES LIKE 'technician_status_log'";
    $checkTableStmt = $conn->prepare($checkTableQuery);
    $checkTableStmt->execute();
    $tableExists = $checkTableStmt->get_result()->num_rows > 0;
    $checkTableStmt->close();
    
    if ($tableExists) {
        echo "✅ technician_status_log table exists\n";
        
        // Check table structure
        $structureQuery = "DESCRIBE technician_status_log";
        $structureStmt = $conn->prepare($structureQuery);
        $structureStmt->execute();
        $structure = $structureStmt->get_result();
        $structureStmt->close();
        
        echo "Table structure:\n";
        while ($row = $structure->fetch_assoc()) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "❌ technician_status_log table does NOT exist\n";
        echo "This is likely causing the 500 error!\n";
        
        echo "\nStep 2: Creating technician_status_log table...\n";
        echo "=============================================\n";
        
        $createTableQuery = "
        CREATE TABLE technician_status_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id VARCHAR(50) NOT NULL,
            technician_id INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            notes TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_booking_id (booking_id),
            INDEX idx_technician_id (technician_id),
            INDEX idx_timestamp (timestamp)
        )
        ";
        
        if ($conn->query($createTableQuery)) {
            echo "✅ technician_status_log table created successfully\n";
        } else {
            echo "❌ Failed to create table: " . $conn->error . "\n";
        }
    }
    
    echo "\nStep 3: Checking email_logs table...\n";
    echo "==================================\n";
    
    $checkEmailTableQuery = "SHOW TABLES LIKE 'email_logs'";
    $checkEmailTableStmt = $conn->prepare($checkEmailTableQuery);
    $checkEmailTableStmt->execute();
    $emailTableExists = $checkEmailTableStmt->get_result()->num_rows > 0;
    $checkEmailTableStmt->close();
    
    if ($emailTableExists) {
        echo "✅ email_logs table exists\n";
    } else {
        echo "❌ email_logs table does NOT exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
