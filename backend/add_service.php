<?php
//add_service.php
include 'conn.php';  // Include database connection
include 'functions.php';  // Include session validation function

//added for CROS (hari)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Get session ID from request headers
$headers = getallheaders();
error_log(print_r($headers, true));

// Try different header case variations as header names can be case-insensitive
$sessionId = '';
foreach ($headers as $name => $value) {
    if (strtolower($name) === 'session-id') {
        $sessionId = $value;
        break;
    }
}

// Validate session ID
if (empty($sessionId) || !validateSession($conn, $sessionId)) {
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access. Invalid or expired session."
    ]);
    exit;
}

// Check if form data exists
if (!isset($_POST['service_name']) || !isset($_POST['description'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

$service_name = trim($_POST['service_name']);
$description = trim($_POST['description']);
$image_path = null;
$video_path = null;
$media_type = 'none';

// Handle locations (JSON format)
$locations_json = isset($_POST['locations']) ? $_POST['locations'] : '[]';

// Handle session 2 checkbox
$has_session2 = isset($_POST['has_session2']) && ($_POST['has_session2'] === 'true' || $_POST['has_session2'] === 'on' || $_POST['has_session2'] === '1') ? 1 : 0;

// Validate inputs
if (empty($service_name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Service name is required"
    ]);
    exit;
}

// Handle media upload (image and/or video)
$has_image = isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0;
$has_video = isset($_FILES['service_video']) && $_FILES['service_video']['error'] == 0;

// Set media type based on what was uploaded
if ($has_image && $has_video) {
    $media_type = 'both';
} elseif ($has_image) {
    $media_type = 'image';
} elseif ($has_video) {
    $media_type = 'video';
} else {
    $media_type = 'none';
}

// Handle image upload
if ($has_image) {
    // Create directory if it doesn't exist
    $upload_dir = 'ServiceImages/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate a unique filename
    $file_extension = pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION);
    $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed."
        ]);
        exit;
    }
    
    // Check file size (5MB limit)
    if ($_FILES['service_image']['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            "status" => "error",
            "message" => "Image file size must be less than 5MB."
        ]);
        exit;
    }
    
    // Move the uploaded file to our directory
    if (move_uploaded_file($_FILES['service_image']['tmp_name'], $target_path)) {
        $image_path = $target_path;
        // Don't overwrite media_type if both image and video are uploaded
        if (!$has_video) {
            $media_type = 'image';
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to upload image."
        ]);
        exit;
    }
}

// Handle video upload
if ($has_video) {
    // Create directory if it doesn't exist
    $upload_dir = 'ServiceVideos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate a unique filename
    $file_extension = pathinfo($_FILES['service_video']['name'], PATHINFO_EXTENSION);
    $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    // Check file type
    $allowed_types = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid file type. Only MP4, AVI, MOV, WMV, FLV, and WEBM files are allowed."
        ]);
        exit;
    }
    
    // Check file size (50MB limit for videos)
    if ($_FILES['service_video']['size'] > 50 * 1024 * 1024) {
        echo json_encode([
            "status" => "error",
            "message" => "Video file size must be less than 50MB."
        ]);
        exit;
    }
    
    // Move the uploaded file to our directory
    if (move_uploaded_file($_FILES['service_video']['tmp_name'], $target_path)) {
        $video_path = $target_path;
        // Don't overwrite media_type if both image and video are uploaded
        if (!$has_image) {
            $media_type = 'video';
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to upload video."
        ]);
        exit;
    }
}

    // Prepare the SQL statement with image_path, video_path, media_type, locations, and has_session2
    $stmt = $conn->prepare("INSERT INTO services (service_name, description, image_path, video_path, media_type, locations, has_session2) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $service_name, $description, $image_path, $video_path, $media_type, $locations_json, $has_session2);

// Execute the query and check if successful
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

// Close the statement and connection
$stmt->close();
$conn->close();
?>