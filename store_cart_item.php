<?php
session_start();
// Database credentials (adjust as needed)
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'oceangas';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if product and price are set
if (isset($_POST['product']) && isset($_POST['price'])) {
    $product = $conn->real_escape_string($_POST['product']);
    $price = (int)$_POST['price'];
    $session_id = session_id();

    // Insert the cart item into the 'cart' table
    $sql = "INSERT INTO cart (session_id, product, price, quantity) VALUES ('$session_id', '$product', $price, 1)";
    if ($conn->query($sql) === TRUE) {
        echo "Item added to cart in database.";
    } else {
        echo "Error: " . $conn->error;
    }
}
$conn->close();
?>
