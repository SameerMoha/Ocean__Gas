<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Validate email input
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    exit("Invalid email address.");
}

// Use $pdo from the included db.php file
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Generate a secure token and expiration time (1 hour)
    $token = bin2hex(random_bytes(16));
    $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

    // Save the token and expiration to the database (modify query as needed)
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $user['id']]);

    // Create the reset link (update your domain accordingly)
    $resetLink = "http://localhost/OceanGas/reset_password.php?token=" . $token;


    // Send the reset link to the user's email address
    $subject = "Password Reset Request";
    $message = "Click the following link to reset your password: " . $resetLink;
    $headers = "From: no-reply@yourdomain.com";
    mail($email, $subject, $message, $headers);
}

// Always show a generic message to prevent email enumeration
echo "If the email is registered, a reset link has been sent.";
?>
