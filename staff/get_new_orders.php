<?php
// File: staff/get_new_orders.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales') {
    http_response_code(403);
    exit;
}

$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    exit(json_encode(["error" => "Database connection failed."]));
}

$result = $conn->query("SELECT COUNT(*) AS new_orders FROM orders WHERE is_new = 1");
$data = ($result && $row = $result->fetch_assoc()) ? $row : ['new_orders' => 0];
$conn->close();
header('Content-Type: application/json');
echo json_encode($data);
?>
