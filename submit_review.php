<?php
header('Content-Type: application/json');
session_start();

// Ensure required POST data is provided
if (!isset($_POST['order_id']) || !isset($_POST['cust_id']) || !isset($_POST['rating'])) {
    echo json_encode(['message' => 'Missing required fields.']);
    exit;
}

$order_id = $_POST['order_id'];
$cust_id  = $_POST['cust_id'];
$rating   = $_POST['rating'];
$comments = isset($_POST['comments']) ? $_POST['comments'] : '';

// Connect to the database
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed.']);
    exit;
}

// Insert review into reviews table; adjust table/columns as needed
$query = "INSERT INTO reviews (order_id, cust_name, cust_id, rating, comments) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("isiss", $order_id, $cust_name, $cust_id, $rating, $comments);
if ($stmt->execute()) {
    echo json_encode(['message' => 'Thank you for your review!']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Review submission failed.']);
}
$stmt->close();
$conn->close();
?>
