<?php
session_start();
// Ensure the user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$staffName = $_SESSION['staff_username'];

// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get posted values
$username         = $_POST['username'] ?? '';
$email            = $_POST['email'] ?? '';
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($email)) {
    die("Email is required.");
}

if (!empty($password)) {
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
} 

// Prepare update query: update email and, if provided, the password
if (!empty($password)) {
    $sql = "UPDATE users SET email = ?, password = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $hashedPassword, $username);
} else {
    $sql = "UPDATE users SET email = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $username);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    // Optionally update session email if stored there
    $_SESSION['staff_email'] = $email;
    // Redirect back to dashboard with a success message (you can use a GET parameter or flash message)
    header("Location: procurement_staff_dashboard.php?msg=Profile updated successfully");
    exit();
} else {
    echo "Error updating profile: " . $conn->error;
}
?>
