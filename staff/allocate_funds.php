<?php
session_start();
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if ($amount <= 0) {
    die("Please enter a valid amount.");
}

$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("INSERT INTO procurement_funds (allocated_amount, note) VALUES (?, ?)");
$stmt->bind_param("ds", $amount, $note);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: /OceanGas/staff/procurement_dashboard.php?message=Funds+allocated+successfully");
    exit();
} else {
    die("Error allocating funds: " . $conn->error);
}
?>
