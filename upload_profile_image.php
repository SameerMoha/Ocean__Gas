<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Database connection
$host = 'localhost'; 
$user = 'root'; 
$password = ''; 
$dbname = 'oceangas';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $custId = $_SESSION['user_id'];
    $file = $_FILES['profile_image'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size too large (max 5MB)']);
        exit;
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
        exit;
    }
    
    // Read file content
    $imageData = file_get_contents($file['tmp_name']);
    
    if ($imageData === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to read image file']);
        exit;
    }
    
    // Update database
    $sql = "UPDATE customers SET profile_image = ? WHERE cust_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('bi', $imageData, $custId);
    $stmt->send_long_data(0, $imageData);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile image updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}

$conn->close();
?>