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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $custId = $_SESSION['user_id'];
    $deliveryLocation = trim($_POST['delivery_location'] ?? '');
    $apartmentNumber = trim($_POST['apartment_number'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    
    // Validate required fields
    if (empty($deliveryLocation)) {
        echo json_encode(['success' => false, 'message' => 'Delivery location is required']);
        exit;
    }
    
    if (empty($phoneNumber)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required']);
        exit;
    }
    
    // Validate phone number format (basic validation)
    if (!preg_match('/^[0-9+\-\s()]+$/', $phoneNumber)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
        exit;
    }
    
    // Update database
    $sql = "UPDATE customers SET delivery_location = ?, apartment_number = ?, phone_number = ? WHERE cust_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('sssi', $deliveryLocation, $apartmentNumber, $phoneNumber, $custId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Address updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>