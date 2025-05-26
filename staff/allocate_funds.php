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
$note   = isset($_POST['note'])   ? trim($_POST['note'])   : '';

if ($amount <= 0) {
    die("Please enter a valid amount.");
}

// DB connect
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert into unified funds table as an allocation
$stmt = $conn->prepare("
    INSERT INTO funds 
      (source_type,  funds_in, funds_out, transaction_date, purchased_by, note)
    VALUES
      ('allocation',  ?, 0.00, NOW(), NULL, ?)
");
$stmt->bind_param("ds", $amount, $note);

if (! $stmt->execute()) {
    die("Error allocating funds: " . $stmt->error);
}

$stmt->close();
$conn->close();

// Redirect back with success message
header("Location: /OceanGas/staff/finance.php?message=Funds+allocated+successfully");
exit();
