<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Retrieve the token and new password fields from POST data
$token = $_POST['token'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Basic validation: ensure fields are not empty
if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
    exit('Missing required data.');
}

// Ensure the passwords match
if ($newPassword !== $confirmPassword) {
    exit('Passwords do not match.');
}

// Validate the token from the database
$stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || strtotime($user['reset_expires']) < time()) {
    exit('Invalid or expired token.');
}

// Token is valid, update the user's password (hash the new password)
$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
$stmt->execute([$passwordHash, $user['id']]);

// Optionally, you can add a message to display on the login page using session or URL parameters

// Redirect the user to the login page
header("Location: login.php");
exit();
?>
