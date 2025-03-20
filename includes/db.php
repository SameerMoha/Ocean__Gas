<?php
// Database connection settings
$servername = "localhost";
$username = "root";      // Change if needed
$password = "";          // Change if needed
$database = "oceangas";  // Your database name

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
