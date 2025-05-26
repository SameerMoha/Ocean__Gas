<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// 1) Auth check
if (!isset($_SESSION['staff_username'])) {
    die("Access denied. Please log in.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// 2) Sanitize POST data
$supplier_id = intval($_POST['supplier_id']);
$product_id  = intval($_POST['product_id']);
$quantity    = intval($_POST['quantity']);

// 3) Lookup staff ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['staff_username']);
$stmt->execute();
$stmt->bind_result($purchased_by);
if (!$stmt->fetch()) {
    die("User not found.");
}
$stmt->close();

// 4) Lookup product name
$stmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($product_name);
if (!$stmt->fetch()) {
    die("Product not found.");
}
$stmt->close();

// 5) Lookup unit cost from price table
$stmt = $conn->prepare("
    SELECT buying_price
      FROM price
     WHERE supplier_id = ?
       AND product_id  = ?
    LIMIT 1
");
$stmt->bind_param("ii", $supplier_id, $product_id);
$stmt->execute();
$stmt->bind_result($unit_cost);
if (!$stmt->fetch()) {
    die("Price not set for this product/supplier.");
}
$stmt->close();

$total_amount = $unit_cost * $quantity;

// 6) Check procurement balance using unified funds table
$row = $conn->query(
    "SELECT IFNULL(SUM(funds_in),0) AS sum_alloc, IFNULL(SUM(funds_out),0) AS sum_used FROM funds"
)->fetch_assoc();
$allocated = (float) $row['sum_alloc'];
$used      = (float) $row['sum_used'];

$balance = $allocated - $used;
if ($balance < $total_amount) {
    die("Error: Insufficient funds. Balance is KES " 
        . number_format($balance,2) 
        . " but this purchase costs KES " 
        . number_format($total_amount,2));
}

// 7) Update stock
$stmt = $conn->prepare(" 
    UPDATE products 
       SET quantity = quantity + ? 
     WHERE product_id = ?
");
$stmt->bind_param("ii", $quantity, $product_id);
if (!$stmt->execute()) {
    die("Stock update failed: " . $stmt->error);
}
$stmt->close();

// 8) Insert into purchase_history
$status = 'completed';
$stmt = $conn->prepare(" 
    INSERT INTO purchase_history 
      (supplier_id, product, quantity, purchased_by, purchase_date, status, total)
    VALUES (?, ?, ?, ?, NOW(), ?, ?)
");
$stmt->bind_param(
    "isissi", 
    $supplier_id, 
    $product_name, 
    $quantity, 
    $purchased_by, 
    $status,
    $total_amount
);
if (!$stmt->execute()) {
    die("Purchase history insert failed: " . $stmt->error);
}
$purchase_id = $conn->insert_id;
$stmt->close();

// 9) Insert deduction into unified funds table
$note = "Purchase of {$quantity}×{$product_name}";
$stmt = $conn->prepare(" 
    INSERT INTO funds 
      (source_type, funds_in, funds_out, transaction_date, purchased_by, note)
    VALUES ('deduction', 0.00, ?, NOW(), ?, ?)
");
$stmt->bind_param("dss", $total_amount, $purchased_by, $note);
if (!$stmt->execute()) {
    die("Funds deduction failed: " . $stmt->error);
}
$stmt->close();

$conn->close();

// 10) Redirect back to dashboard
header("Location: purchase_history_reports.php?id=" . $supplier_id);
exit();
