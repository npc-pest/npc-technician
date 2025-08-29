<?php
// Test script to verify user creation
header('Content-Type: application/json');

require 'conn.php';

// Test mobile number
$test_mobile = '8825723688';

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, customer_name FROM users WHERE mobile_number = ?");
    $stmt->bind_param("s", $test_mobile);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        echo json_encode([
            'status' => 'success',
            'message' => 'User already exists',
            'user_id' => $user['user_id'],
            'customer_name' => $user['customer_name']
        ]);
    } else {
        // Try to create a new user
        $customer_name = "User_" . substr($test_mobile, -4);
        $email_id = $test_mobile . "@npc.com";
        $default_password = password_hash($test_mobile, PASSWORD_DEFAULT);
        $default_address = "Address to be updated";
        
        $createStmt = $conn->prepare("INSERT INTO users (mobile_number, customer_name, email_id, password, address1) VALUES (?, ?, ?, ?, ?)");
        $createStmt->bind_param("sssss", $test_mobile, $customer_name, $email_id, $default_password, $default_address);
        
        if ($createStmt->execute()) {
            $user_id = $conn->insert_id;
            echo json_encode([
                'status' => 'success',
                'message' => 'User created successfully',
                'user_id' => $user_id,
                'customer_name' => $customer_name
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create user: ' . $conn->error
            ]);
        }
        $createStmt->close();
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
