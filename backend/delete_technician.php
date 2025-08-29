<?php
// Enable error logging first
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Delete technician request received");

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Session-ID, session-id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conn.php';

// Debug: Check if $conn is initialized
if (!isset($conn)) {
    error_log("Database connection is not initialized");
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit();
}

// Check if connection is valid
if ($conn->connect_error) {
    error_log("Database connection error: " . $conn->connect_error);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection error"
    ]);
    exit();
}

error_log("Database connection successful");

// Test query to check if table exists and has data
$test_query = $conn->query("SELECT COUNT(*) as count FROM technicians");
if ($test_query) {
    $test_result = $test_query->fetch_assoc();
    error_log("Technicians table has " . $test_result['count'] . " records");
} else {
    error_log("Error testing technicians table: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
error_log("Received input: " . print_r($input, true));

if (!isset($input['technician_id']) || empty($input['technician_id'])) {
    error_log("Technician ID is missing or empty");
    echo json_encode([
        'status' => 'error',
        'message' => 'Technician ID is required'
    ]);
    exit;
}

$technician_id = intval($input['technician_id']);
error_log("Processing technician ID: " . $technician_id . " (type: " . gettype($technician_id) . ")");

try {
    // Check if technicians table exists (try both cases)
    $tableName = 'technicians'; // default lowercase
    $tableCheck = $conn->query("SHOW TABLES LIKE 'technicians'");
    
    if ($tableCheck->num_rows === 0) {
        // Try uppercase
        $tableCheck = $conn->query("SHOW TABLES LIKE 'Technicians'");
        if ($tableCheck->num_rows > 0) {
            $tableName = 'Technicians';
        } else {
            error_log("Neither 'technicians' nor 'Technicians' table exists");
            echo json_encode([
                "status" => "error",
                "message" => "Technicians table not found"
            ]);
            exit();
        }
    }
    
    error_log("Using table name: " . $tableName);
    
    // First, check if the technician exists
    error_log("Checking if technician exists with ID: " . $technician_id);
    
    // Try different possible column names
    $possible_columns = ['technician_id', 'id', 'tech_id'];
    $found_column = null;
    
    foreach ($possible_columns as $column) {
        $test_stmt = $conn->prepare("SELECT COUNT(*) as count FROM $tableName WHERE $column = ?");
        if ($test_stmt) {
            $test_stmt->bind_param("i", $technician_id);
            $test_stmt->execute();
            $test_result = $test_stmt->get_result();
            if ($test_result->fetch_assoc()['count'] > 0) {
                $found_column = $column;
                error_log("Found column name: " . $column);
                break;
            }
            $test_stmt->close();
        }
    }
    
    if (!$found_column) {
        error_log("No valid column found for technician ID");
        echo json_encode([
            'status' => 'error',
            'message' => 'Technician not found'
        ]);
        exit;
    }
    
    $check_stmt = $conn->prepare("SELECT $found_column, employee_name, id_proof FROM $tableName WHERE $found_column = ?");
    $check_stmt->bind_param("i", $technician_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    error_log("Found " . $result->num_rows . " technicians with ID: " . $technician_id);
    
    if ($result->num_rows === 0) {
        error_log("Technician not found with ID: " . $technician_id);
        echo json_encode([
            'status' => 'error',
            'message' => 'Technician not found'
        ]);
        exit;
    }
    
    $technician = $result->fetch_assoc();
    $id_proof_path = $technician['id_proof'];
    
    // Check if technician has any active bookings
    error_log("Checking for active bookings for technician ID: " . $technician_id);
    
    // Check both bookings and orders tables
    $booking_check_stmt = $conn->prepare("
        SELECT COUNT(*) as active_bookings FROM (
            SELECT assigned_technician FROM bookings WHERE assigned_technician = ? AND booking_status IN ('pending', 'confirmed', 'in_progress')
            UNION ALL
            SELECT assigned_technician FROM orders WHERE assigned_technician = ? AND status IN ('Pending', 'Accepted')
        ) as combined_bookings
    ");
    $booking_check_stmt->bind_param("ii", $technician_id, $technician_id);
    $booking_check_stmt->execute();
    $booking_result = $booking_check_stmt->get_result();
    $booking_count = $booking_result->fetch_assoc()['active_bookings'];
    
    error_log("Found " . $booking_count . " active bookings for technician ID: " . $technician_id);
    
    if ($booking_count > 0) {
        error_log("Cannot delete technician with active bookings");
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot delete technician with active bookings. Please reassign or complete the bookings first.'
        ]);
        exit;
    }
    
    // Delete the technician
    error_log("Attempting to delete technician with ID: " . $technician_id);
    $delete_stmt = $conn->prepare("DELETE FROM $tableName WHERE $found_column = ?");
    $delete_stmt->bind_param("i", $technician_id);
    
    if ($delete_stmt->execute()) {
        error_log("Successfully deleted technician with ID: " . $technician_id);
        // Delete the ID proof file if it exists
        if ($id_proof_path && file_exists($id_proof_path)) {
            unlink($id_proof_path);
            error_log("Deleted ID proof file: " . $id_proof_path);
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Technician deleted successfully'
        ]);
    } else {
        error_log("Failed to delete technician. Error: " . $delete_stmt->error);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete technician: ' . $delete_stmt->error
        ]);
    }
    
    $check_stmt->close();
    $booking_check_stmt->close();
    $delete_stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 