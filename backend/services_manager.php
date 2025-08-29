<?php
//services_manager.php
include 'conn.php';
include 'functions.php';

//added for CROS (hari)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Get session ID from request headers
$headers = getallheaders();
$sessionId = '';
foreach ($headers as $name => $value) {
    if (strtolower($name) === 'session-id') {
        $sessionId = $value;
        break;
    }
}

error_log("Services Manager - Headers received: " . print_r($headers, true));
error_log("Services Manager - Session ID found: " . ($sessionId ? $sessionId : 'NOT FOUND'));

// Validate session ID
error_log("Services Manager - About to validate session. Session ID: " . ($sessionId ? $sessionId : 'EMPTY'));
$sessionValid = validateSession($conn, $sessionId);
error_log("Services Manager - Session validation result: " . ($sessionValid ? 'TRUE' : 'FALSE'));

if (empty($sessionId) || !$sessionValid) {
    error_log("Services Manager - Session validation failed. Session ID: " . ($sessionId ? $sessionId : 'EMPTY'));
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access. Invalid or expired session."
    ]);
    exit;
}

error_log("Services Manager - Session validation passed");

// Get the action from POST data
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Debug: Log the received data
error_log("Services Manager - POST data: " . print_r($_POST, true));
error_log("Services Manager - Raw input: " . file_get_contents('php://input'));
error_log("Services Manager - Action received: " . $action);

// Handle JSON input if POST is empty
if (empty($_POST)) {
    $jsonInput = file_get_contents('php://input');
    if (!empty($jsonInput)) {
        $jsonData = json_decode($jsonInput, true);
        if ($jsonData) {
            $_POST = $jsonData;
            $action = isset($_POST['action']) ? $_POST['action'] : '';
            error_log("Services Manager - JSON data processed: " . print_r($_POST, true));
            error_log("Services Manager - Action after JSON processing: " . $action);
        }
    }
}

error_log("Services Manager - Switch statement - Action: '" . $action . "'");

switch ($action) {
    case 'fetch_services':
        error_log("Services Manager - Executing fetch_services");
        fetchServices();
        break;
    case 'add_service':
        error_log("Services Manager - Executing add_service");
        addService();
        break;
    case 'update_service':
        error_log("Services Manager - Executing update_service");
        updateService();
        break;
    case 'delete_service':
        error_log("Services Manager - Executing delete_service");
        deleteService();
        break;
    case 'fetch_locations':
        error_log("Services Manager - Executing fetch_locations");
        fetchLocations();
        break;
    case 'add_location':
        error_log("Services Manager - Executing add_location");
        addLocation();
        break;
    case 'delete_location':
        error_log("Services Manager - Executing delete_location");
        deleteLocation();
        break;
    case 'toggle_service_status':
        error_log("Services Manager - Executing toggle_service_status");
        toggleServiceStatus();
        break;
    default:
        error_log("Services Manager - Invalid action: '" . $action . "'");
        echo json_encode([
            "status" => "error",
            "message" => "Invalid action: " . $action
        ]);
        break;
}

function fetchServices() {
    global $conn;
    
    try {
        // Admin should see ALL services (both active and inactive)
        $query = "SELECT service_id, service_name, description, image_path, locations, has_session2, created_at, COALESCE(status, 'active') as status FROM services ORDER BY created_at DESC";
        $result = $conn->query($query);
        
        if ($result) {
            $services = [];
            
            while ($row = $result->fetch_assoc()) {
                // Format the created_at date
                $created_date = new DateTime($row['created_at']);
                $formatted_date = $created_date->format('M d, Y');
                
                $services[] = [
                    'service_id' => $row['service_id'],
                    'service_name' => $row['service_name'],
                    'description' => $row['description'],
                    'image_path' => $row['image_path'],
                    'locations' => $row['locations'],
                    'created_at' => $formatted_date,
                    'status' => $row['status'] ?? 'active'
                ];
            }
            
            echo json_encode([
                "status" => "success",
                "services" => $services,
                "count" => count($services)
            ]);
            
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to fetch services: " . $conn->error
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $e->getMessage()
        ]);
    }
}

function addService() {
    global $conn;
    
    // Check if form data exists
    if (!isset($_POST['service_name']) || !isset($_POST['description'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing required fields"
        ]);
        return;
    }

    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $image_path = null;

    // Handle locations (JSON format)
    $locations_json = isset($_POST['locations']) ? $_POST['locations'] : '[]';

    // Validate inputs
    if (empty($service_name)) {
        echo json_encode([
            "status" => "error",
            "message" => "Service name is required"
        ]);
        return;
    }

    // Handle image upload if an image was provided
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        $image_path = handleImageUpload($_FILES['service_image']);
        if ($image_path === false) {
            return; // Error already echoed in handleImageUpload
        }
    }

    // Get warranty period from POST data
    $warranty_period = isset($_POST['warranty_period']) ? trim($_POST['warranty_period']) : '90 days';
    
    // Handle session 2 checkbox
    $has_session2 = isset($_POST['has_session2']) && ($_POST['has_session2'] === 'true' || $_POST['has_session2'] === 'on' || $_POST['has_session2'] === '1') ? 1 : 0;
    
    // Debug log
    error_log("Services Manager - Add Service - Warranty period received: " . $warranty_period);
    error_log("Services Manager - Add Service - has_session2 received: " . $has_session2);
    error_log("Services Manager - Add Service - POST data: " . print_r($_POST, true));

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO services (service_name, description, image_path, locations, warranty_period, has_session2) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $service_name, $description, $image_path, $locations_json, $warranty_period, $has_session2);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Service added successfully",
            "service_id" => $conn->insert_id
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to add service",
            "error" => $stmt->error
        ]);
    }
    
    $stmt->close();
}

function updateService() {
    global $conn;
    
    // Check if required data exists
    if (!isset($_POST['service_id']) || !isset($_POST['service_name']) || !isset($_POST['description'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing required fields"
        ]);
        return;
    }

    $service_id = trim($_POST['service_id']);
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $locations_json = isset($_POST['locations']) ? $_POST['locations'] : '[]';

    // Validate inputs
    if (empty($service_id) || empty($service_name)) {
        echo json_encode([
            "status" => "error",
            "message" => "Service ID and name are required"
        ]);
        return;
    }

    // First, get the current service data
    $stmt = $conn->prepare("SELECT image_path, video_path, media_type FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Service not found"
        ]);
        return;
    }

    $current_service = $result->fetch_assoc();
    $current_image_path = $current_service['image_path'];
    $current_video_path = $current_service['video_path'];
    $new_image_path = $current_image_path; // Keep current image by default
    $new_video_path = $current_video_path; // Keep current video by default

    // Handle image upload if a new image was provided
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        $new_image_path = handleImageUpload($_FILES['service_image']);
        if ($new_image_path === false) {
            return; // Error already echoed in handleImageUpload
        }
        
        // Delete old image if it exists and new image uploaded successfully
        if ($current_image_path && file_exists($current_image_path)) {
            unlink($current_image_path);
        }
    }

    // Handle video upload if a new video was provided
    if (isset($_FILES['service_video']) && $_FILES['service_video']['error'] == 0) {
        $new_video_path = handleVideoUpload($_FILES['service_video']);
        if ($new_video_path === false) {
            return; // Error already echoed in handleVideoUpload
        }
        
        // Delete old video if it exists and new video uploaded successfully
        if ($current_video_path && file_exists($current_video_path)) {
            unlink($current_video_path);
        }
    }

    // Determine media type based on what was uploaded
    $media_type = 'none';
    if ($new_image_path && $new_video_path) {
        $media_type = 'both';
    } elseif ($new_image_path) {
        $media_type = 'image';
    } elseif ($new_video_path) {
        $media_type = 'video';
    }

    // Get warranty period from POST data
    $warranty_period = isset($_POST['warranty_period']) ? trim($_POST['warranty_period']) : '90 days';
    
    // Handle session 2 checkbox
    $has_session2 = isset($_POST['has_session2']) && ($_POST['has_session2'] === 'true' || $_POST['has_session2'] === 'on' || $_POST['has_session2'] === '1') ? 1 : 0;
    
    // Debug log
    error_log("Services Manager - Update Service - Warranty period received: " . $warranty_period);
    error_log("Services Manager - Update Service - has_session2 received: " . $has_session2);
    error_log("Services Manager - Update Service - POST data: " . print_r($_POST, true));

    // Update the service in database
    $stmt = $conn->prepare("UPDATE services SET service_name = ?, description = ?, image_path = ?, video_path = ?, media_type = ?, locations = ?, warranty_period = ?, has_session2 = ? WHERE service_id = ?");
    $stmt->bind_param("sssssssii", $service_name, $description, $new_image_path, $new_video_path, $media_type, $locations_json, $warranty_period, $has_session2, $service_id);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Service updated successfully",
            "service_id" => $service_id,
            "image_path" => $new_image_path,
            "video_path" => $new_video_path,
            "media_type" => $media_type
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to update service",
            "error" => $stmt->error
        ]);
    }

    $stmt->close();
}

function deleteService() {
    global $conn;
    
    // Debug logging
    error_log("DeleteService - POST data: " . print_r($_POST, true));
    error_log("DeleteService - Service ID: " . (isset($_POST['service_id']) ? $_POST['service_id'] : 'NOT SET'));
    
    if (!isset($_POST['service_id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Service ID is required"
        ]);
        return;
    }

    $service_id = trim($_POST['service_id']);
    error_log("DeleteService - Trimmed Service ID: " . $service_id);

    if (empty($service_id)) {
        echo json_encode([
            "status" => "error",
            "message" => "Service ID is required"
        ]);
        return;
    }

    // First, get the service data to delete associated image
    error_log("DeleteService - Checking if service exists with ID: " . $service_id);
    $stmt = $conn->prepare("SELECT image_path FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    error_log("DeleteService - Found " . $result->num_rows . " rows for service ID: " . $service_id);

    if ($result->num_rows == 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Service not found"
        ]);
        return;
    }

    $service = $result->fetch_assoc();
    $image_path = $service['image_path'];

    // Delete the service from database
    error_log("DeleteService - Attempting to delete service with ID: " . $service_id);
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);

    if ($stmt->execute()) {
        error_log("DeleteService - Successfully deleted service with ID: " . $service_id);
        // Delete associated image file if it exists
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        
        echo json_encode([
            "status" => "success",
            "message" => "Service deleted successfully"
        ]);
    } else {
        error_log("DeleteService - Failed to delete service with ID: " . $service_id . ". Error: " . $stmt->error);
        echo json_encode([
            "status" => "error",
            "message" => "Failed to delete service",
            "error" => $stmt->error
        ]);
    }

    $stmt->close();
}

function fetchLocations() {
    global $conn;
    
    try {
        $query = "SELECT id, location_name FROM locations ORDER BY location_name ASC";
        $result = $conn->query($query);
        
        if ($result) {
            $locations = [];
            while ($row = $result->fetch_assoc()) {
                $locations[] = [
                    'id' => $row['id'],
                    'location_name' => $row['location_name']
                ];
            }
            
            echo json_encode([
                "status" => "success",
                "locations" => $locations
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to fetch locations: " . $conn->error
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $e->getMessage()
        ]);
    }
}

function addLocation() {
    global $conn;
    
    if (!isset($_POST['location_name']) || empty(trim($_POST['location_name']))) {
        echo json_encode([
            "status" => "error",
            "message" => "Location name is required"
        ]);
        return;
    }
    
    $location_name = trim($_POST['location_name']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO locations (location_name) VALUES (?)");
        $stmt->bind_param("s", $location_name);
        
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Location added successfully",
                "location_id" => $conn->insert_id,
                "location_name" => $location_name
            ]);
        } else {
            if ($conn->errno == 1062) { // Duplicate entry error
                echo json_encode([
                    "status" => "error",
                    "message" => "Location already exists"
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to add location: " . $stmt->error
                ]);
            }
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $e->getMessage()
        ]);
    }
}

function deleteLocation() {
    global $conn;
    
    if (!isset($_POST['location_id']) || empty($_POST['location_id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Location ID is required"
        ]);
        return;
    }
    
    $location_id = intval($_POST['location_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
        $stmt->bind_param("i", $location_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Location deleted successfully"
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Location not found"
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to delete location: " . $stmt->error
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $e->getMessage()
        ]);
    }
}

function handleImageUpload($file) {
    // Create directory if it doesn't exist
    $upload_dir = 'ServiceImages/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate a unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed."
        ]);
        return false;
    }
    
    // Check file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            "status" => "error",
            "message" => "File size too large. Maximum 5MB allowed."
        ]);
        return false;
    }
    
    // Move the uploaded file to our directory
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $target_path;
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to upload image."
        ]);
        return false;
    }
}

function handleVideoUpload($file) {
    // Create directory if it doesn't exist
    $upload_dir = 'ServiceVideos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate a unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    // Check file type
    $allowed_types = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid file type. Only MP4, AVI, MOV, WMV, FLV, and WEBM files are allowed."
        ]);
        return false;
    }
    
    // Check file size (50MB limit)
    if ($file['size'] > 50 * 1024 * 1024) {
        echo json_encode([
            "status" => "error",
            "message" => "File size too large. Maximum 50MB allowed."
        ]);
        return false;
    }
    
    // Move the uploaded file to our directory
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $target_path;
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to upload video."
        ]);
        return false;
    }
}

function toggleServiceStatus() {
    global $conn;
    
    // Check if service_id is provided
    if (!isset($_POST['service_id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Service ID is required"
        ]);
        return;
    }
    
    $service_id = intval($_POST['service_id']);
    
    try {
        // Get current status
        $stmt = $conn->prepare("SELECT status FROM services WHERE service_id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Service not found"
            ]);
            return;
        }
        
        $service = $result->fetch_assoc();
        $current_status = $service['status'] ?? 'active';
        
        // Toggle status - if current is active, make inactive; if inactive, make active
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
        
        // Update status
        $update_stmt = $conn->prepare("UPDATE services SET status = ? WHERE service_id = ?");
        $update_stmt->bind_param("si", $new_status, $service_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Service status updated successfully",
                "new_status" => $new_status
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to update service status"
            ]);
        }
        
        $update_stmt->close();
        
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $e->getMessage()
        ]);
    }
}

$conn->close();
?>