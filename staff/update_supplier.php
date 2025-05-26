<?php  
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the procurement staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Database connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission to update supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplier_id'])) {
    $supplier_id   = $_POST['supplier_id'];
    $supplier_name = $_POST['supplier_name'];
    $address       = $_POST['address'];
    $phone         = $_POST['phone'];
    $email         = $_POST['email'];
    $details       = $_POST['details'];
    $cost_6kg      = $_POST['cost_6kg'];
    $cost_12kg     = $_POST['cost_12kg'];
    $sell_6kg      = $_POST['sell_6kg'];
    $sell_12kg     = $_POST['sell_12kg'];

    // Update supplier info
    $updateSupplier = $conn->prepare("UPDATE suppliers SET name=?, address=?, phone=?, email=?, details=? WHERE id=?");
    $updateSupplier->bind_param("sssssi", $supplier_name, $address, $phone, $email, $details, $supplier_id);
    $updateSupplier->execute();
    $updateSupplier->close();

    // Update 6kg price
    $update6kg = $conn->prepare("
        UPDATE price pr
        JOIN products p ON pr.product_id = p.product_id
        SET pr.buying_price = ?
        WHERE pr.supplier_id = ? AND p.product_name LIKE '%6kg%'
    ");
    $update6kg->bind_param("di", $cost_6kg, $supplier_id);
    $update6kg->execute();
    $update6kg->close();

    // Update 12kg price
    $update12kg = $conn->prepare("
        UPDATE price pr
        JOIN products p ON pr.product_id = p.product_id
        SET pr.buying_price = ?
        WHERE pr.supplier_id = ? AND p.product_name LIKE '%12kg%'
    ");
    $update12kg->bind_param("di", $cost_12kg, $supplier_id);
    $update12kg->execute();
    $update12kg->close();

    $updatesell6kg = $conn->prepare("
        UPDATE price pr
        JOIN products p ON pr.product_id = p.product_id
        SET pr.selling_price = ?
        WHERE pr.supplier_id = ? AND p.product_name LIKE '%6kg%'
    ");
    $updatesell6kg->bind_param("di", $sell_6kg, $supplier_id);
    $updatesell6kg->execute();
    $updatesell6kg->close();

    $updatesell12kg = $conn->prepare("
        UPDATE price pr
        JOIN products p ON pr.product_id = p.product_id
        SET pr.selling_price = ?
        WHERE pr.supplier_id = ? AND p.product_name LIKE '%12kg%'
    ");
    $updatesell12kg->bind_param("di", $sell_12kg, $supplier_id);
    $updatesell12kg->execute();
    $updatesell12kg->close();


    // Redirect to avoid resubmission
    header("Location: suppliers.php");
    exit();
}


$conn->close();
?>
