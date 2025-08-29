<?php
//added for CROS (hari)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Suppress warnings and notices to prevent them from breaking JSON output
error_reporting(0);
// Include files with @ to suppress any potential warnings
@include 'conn.php';  // Include database connection
@include 'functions.php';  // Include session validation function

// Check if conn.php was successfully included
if (!isset($conn)) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

// Get session ID from request headers
$headers = getallheaders();
$sessionId = '';
foreach ($headers as $name => $value) {
    if (strtolower($name) === 'session-id') {
        $sessionId = $value;
        break;
    }
}

// Validate session
if (!validateSession($sessionId)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or expired session"
    ]);
    exit;
}

function fetchOffers() {
    global $conn;
    
    try {
        // Base query - only show active offers
        $sql = "SELECT offer_id, offer_name, description, discount_percentage, discount_amount, offer_code, valid_from, valid_until, offer_banner_location, created_at FROM offers WHERE status = 'active' AND valid_until >= CURDATE()";
        
        $sql .= " ORDER BY created_at DESC";
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Database query error: " . $conn->error
            ]);
            exit;
        }
        
        $offers = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format the discount display
                $discount = '';
                if (!empty($row['discount_percentage'])) {
                    $discount = $row['discount_percentage'] . '% OFF';
                } elseif (!empty($row['discount_amount'])) {
                    $discount = '₹' . $row['discount_amount'] . ' OFF';
                }
                
                $offers[] = [
                    'offer_id' => $row['offer_id'],
                    'offer_name' => $row['offer_name'],
                    'description' => $row['description'],
                    'discount' => $discount,
                    'code' => $row['offer_code'],
                    'valid_until' => $row['valid_until'],
                    'banner_image' => $row['offer_banner_location'],
                    'created_at' => $row['created_at']
                ];
            }
        }
        
        echo json_encode([
            "status" => "success",
            "offers" => $offers,
            "count" => count($offers)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred: " . $e->getMessage()
        ]);
    }
}

// Execute the function
fetchOffers();
?>
