<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// Ensure the user is logged in
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_id'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die("Invalid request.");
}

// Get and sanitize form data
$supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
$product = isset($_POST['product']) ? trim($_POST['product']) : '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if ($supplier_id <= 0) {
    die("Invalid supplier.");
}
if (empty($product)) {
    die("Product is required.");
}
if ($quantity <= 0) {
    die("Quantity must be a positive number.");
}

// Validate product type: only allow "6kg" or "12kg"
$allowed_products = array('6kg', '12kg');
if (!in_array($product, $allowed_products)) {
    die("Invalid product type.");
}

// Validate that the supplier exists and retrieve cost info
$stmt = $conn->prepare("SELECT cost_6kg, cost_12kg FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$stmt->bind_result($cost_6kg, $cost_12kg);
if (!$stmt->fetch()) {
    die("Supplier not found.");
}
$stmt->close();

// Determine unit cost based on product type and calculate total cost
$unit_cost = ($product === '6kg') ? $cost_6kg : $cost_12kg;
$total_cost = $unit_cost * $quantity;

// Check available funds: allocated funds - funds deductions
$sql_allocated = "SELECT IFNULL(SUM(allocated_amount),0) AS total_allocated FROM procurement_funds";
$allocated_result = $conn->query($sql_allocated);
$allocated_data = $allocated_result->fetch_assoc();
$total_allocated = $allocated_data['total_allocated'];

$sql_used = "SELECT IFNULL(SUM(amount),0) AS total_used FROM funds_deductions";
$used_result = $conn->query($sql_used);
$used_data = $used_result->fetch_assoc();
$total_used = $used_data['total_used'];

$balance = $total_allocated - $total_used;
if ($balance < $total_cost) {
    die("Insufficient funds. Available balance: KES " . number_format($balance, 2));
}

// Optionally validate that the product exists in stock
$stmt = $conn->prepare("SELECT quantity FROM stock WHERE product = ?");
$stmt->bind_param("s", $product);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) {
    die("Product not found in stock.");
}
$stmt->close();

// Update the stock table (increase quantity)
$stmt = $conn->prepare("UPDATE stock SET quantity = quantity + ? WHERE product = ?");
$stmt->bind_param("is", $quantity, $product);
if (!$stmt->execute()) {
    die("Error updating stock: " . $conn->error);
}
$stmt->close();

// Record purchase history. Use the staff_id from the session.
$purchased_by = $_SESSION['staff_id'];
$stmt = $conn->prepare("INSERT INTO purchase_history (supplier_id, product, quantity, purchased_by) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isii", $supplier_id, $product, $quantity, $purchased_by);
if (!$stmt->execute()) {
    die("Error recording purchase history: " . $conn->error);
}
$purchase_history_id = $stmt->insert_id;
$stmt->close();

// Deduct funds by inserting a record in funds_deductions
$stmt = $conn->prepare("INSERT INTO funds_deductions (purchase_id, amount, note) VALUES (?, ?, ?)");
$note = "Deduction for purchasing $quantity units of $product at KES " . number_format($unit_cost, 2) . " each.";
$stmt->bind_param("ids", $purchase_history_id, $total_cost, $note);
if (!$stmt->execute()) {
    die("Error recording funds deduction: " . $conn->error);
}
$stmt->close();

$conn->close();

// Redirect back with a success message
header("Location: /OceanGas/staff/procurement_staff_dashboard.php?message=Purchase+successful");
exit();
?>
