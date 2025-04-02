<?php
session_start();
// Ensure the procurement staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if required POST data exists
if (isset($_POST['id'], $_POST['name'], $_POST['address'], $_POST['phone'], $_POST['email'], $_POST['details'], $_POST['cost_6kg'], $_POST['cost_12kg'])) {
    $id         = $_POST['id'];
    $name       = $_POST['name'];
    $address    = $_POST['address'];
    $phone      = $_POST['phone'];
    $email      = $_POST['email'];
    $details    = $_POST['details'];
    $cost_6kg   = $_POST['cost_6kg'];
    $cost_12kg  = $_POST['cost_12kg'];
    
    // Prepare update statement
    $sql = "UPDATE suppliers 
            SET name = ?, address = ?, phone = ?, email = ?, details = ?, cost_6kg = ?, cost_12kg = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters: s = string, d = double, i = integer.
    $stmt->bind_param("sssssddi", $name, $address, $phone, $email, $details, $cost_6kg, $cost_12kg, $id);
    
    if ($stmt->execute()) {
        // Redirect back with a success indicator
        header("Location: /OceanGas/staff/suppliers.php?update=success");
        exit();
    } else {
        die("Update failed: " . $stmt->error);
    }
    
    // No code here because exit() stops execution
} else {
    // Redirect if required fields are missing
    header("Location: /OceanGas/staff/suppliers.php?update=missing");
    exit();
}
?>
