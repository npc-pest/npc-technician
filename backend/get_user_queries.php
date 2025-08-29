<?php
// get_user_queries.php - Fetch user's contact queries/tickets

// Disable error display to prevent breaking JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Session-ID");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get Session-ID from headers
        $headers = getallheaders();
        $sessionId = null;
        
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'session-id') {
                $sessionId = trim($value);
                break;
            }
        }

        if (!$sessionId) {
            echo json_encode(['status' => 'error', 'message' => 'Session ID is required']);
            exit;
        }

        // Verify user session
        $userStmt = $conn->prepare("SELECT user_id, customer_name FROM users WHERE sessionid = ? AND session_expiry > NOW()");
        $userStmt->bind_param("s", $sessionId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        $userStmt->close();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
            exit;
        }

        // Check if contact_queries table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'contact_queries'");
        if ($tableCheck->num_rows == 0) {
            echo json_encode([
                'status' => 'success',
                'queries' => [],
                'message' => 'No queries found'
            ]);
            exit;
        }

        // Fetch user's queries
        $queryStmt = $conn->prepare("
            SELECT 
                id,
                first_name,
                last_name,
                email,
                phone,
                subject,
                message,
                status,
                admin_response,
                response_date,
                created_at,
                updated_at
            FROM contact_queries 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        
        $queryStmt->bind_param("i", $user['user_id']);
        $queryStmt->execute();
        $queryResult = $queryStmt->get_result();
        
        $queries = array();
        
        while ($row = $queryResult->fetch_assoc()) {
            // Format dates
            $createdAt = new DateTime($row['created_at']);
            $updatedAt = new DateTime($row['updated_at']);
            
            $query = array(
                'id' => $row['id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'subject' => $row['subject'],
                'message' => $row['message'],
                'status' => $row['status'],
                'admin_response' => $row['admin_response'],
                'response_date' => $row['response_date'],
                'created_at' => $row['created_at'],
                'created_at_formatted' => $createdAt->format('M d, Y \a\t g:i A'),
                'updated_at' => $row['updated_at'],
                'updated_at_formatted' => $updatedAt->format('M d, Y \a\t g:i A'),
                'has_response' => !empty($row['admin_response'])
            );
            
            $queries[] = $query;
        }
        
        $queryStmt->close();
        
        echo json_encode([
            'status' => 'success',
            'queries' => $queries,
            'total_count' => count($queries)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Only GET method allowed']);
}

$conn->close();
?>
