<?php
session_start();

// Remove only the user's login info
unset($_SESSION['user_id']); // or whatever key you used when they logged in

// Optionally, redirect them
header("Location: ../index.html");
exit();
?>