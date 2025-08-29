<?php
require_once 'conn.php';

// Set headers for web response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ['success' => true, 'messages' => []];

try {
    // Add session2_status column
    $sql1 = "ALTER TABLE bookings ADD COLUMN session2_status ENUM('pending', 'in_progress', 'completed') DEFAULT NULL AFTER booking_status";
    if ($conn->query($sql1) === TRUE) {
        $response['messages'][] = "✅ Added session2_status column to bookings table";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            $response['messages'][] = "ℹ️ session2_status column already exists";
        } else {
            $response['messages'][] = "❌ Error adding session2_status: " . $conn->error;
            $response['success'] = false;
        }
    }
    
    // Add session2_date column
    $sql2 = "ALTER TABLE bookings ADD COLUMN session2_date DATE DEFAULT NULL AFTER session2_status";
    if ($conn->query($sql2) === TRUE) {
        $response['messages'][] = "✅ Added session2_date column to bookings table";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            $response['messages'][] = "ℹ️ session2_date column already exists";
        } else {
            $response['messages'][] = "❌ Error adding session2_date: " . $conn->error;
            $response['success'] = false;
        }
    }
    
    // Add session2_completed_date column
    $sql3 = "ALTER TABLE bookings ADD COLUMN session2_completed_date DATETIME DEFAULT NULL AFTER session2_date";
    if ($conn->query($sql3) === TRUE) {
        $response['messages'][] = "✅ Added session2_completed_date column to bookings table";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            $response['messages'][] = "ℹ️ session2_completed_date column already exists";
        } else {
            $response['messages'][] = "❌ Error adding session2_completed_date: " . $conn->error;
            $response['success'] = false;
        }
    }
    
    // Add has_session2 column to services table
    $sql4 = "ALTER TABLE services ADD COLUMN has_session2 TINYINT(1) DEFAULT 0 AFTER warranty_period";
    if ($conn->query($sql4) === TRUE) {
        $response['messages'][] = "✅ Added has_session2 column to services table";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            $response['messages'][] = "ℹ️ has_session2 column already exists";
        } else {
            $response['messages'][] = "❌ Error adding has_session2: " . $conn->error;
            $response['success'] = false;
        }
    }
    
    if ($response['success']) {
        $response['messages'][] = "🎉 Session2 fields added successfully!";
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'][] = "❌ Error: " . $e->getMessage();
}

$conn->close();

// Return JSON response
http_response_code($response['success'] ? 200 : 500);
echo json_encode($response, JSON_PRETTY_PRINT);
?>
