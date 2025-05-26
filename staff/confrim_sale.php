<?php 
session_start();

// Ensure only authorized staff can confirm the sale.
if (!isset($_SESSION['staff_username']) || ( $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' )) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}

if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    die("Order ID missing.");
}

$order_id = intval($_POST['order_id']);

// DB Connection configuration
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Confirm the order
$updateQuery = "UPDATE orders SET order_status = 'confirmed', is_new = 0 WHERE order_id = ?";
$stmt = $conn->prepare($updateQuery);
if (!$stmt) {
    die("Prepare failed (status update): " . $conn->error);
}
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->close();

// Step 2: Retrieve the order details from orders table
$orderQuery = "SELECT order_number, order_date, billing_info, invoice_summary, total_amount, cust_id FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($orderQuery);
if (!$stmt) {
    die("Prepare failed (order fetch): " . $conn->error);
}
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->bind_result($order_number, $order_date, $billing_info_json, $invoice_summary, $total_amount, $cust_id);
if (!$stmt->fetch()) {
    die("Order not found.");
}
$stmt->close();

// Decode billing_info JSON to extract customer information
$billing_info = json_decode($billing_info_json, true);
$customer_name = isset($billing_info['name']) ? $billing_info['name'] : 'Unknown';

$payment_method = 'MPESA'; // Default payment method
date_default_timezone_set('Africa/Nairobi');
$sale_date = date("Y-m-d H:i:s");

// Step 3: Fetch each order item from order_items table
$orderItemsQuery = "SELECT product_name, quantity, unit_price, product_id FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($orderItemsQuery);
if (!$stmt) {
    die("Prepare failed (fetching order items): " . $conn->error);
}
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$orderItems = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Initialize a computed total for the entire order.
$computed_order_total = 0;

// Step 4: Process each order item
foreach ($orderItems as $item) {
    $quantity      = intval($item['quantity']);
    $product_name  = $item['product_name'];
    $unit_price    = floatval($item['unit_price']);
    $line_total    = $quantity * $unit_price;
    $line_product_id = intval($item['product_id']); 

    $computed_order_total += $line_total;

    // Insert sales record for this order item.
    $insertQuery = "INSERT INTO sales_record 
        (order_id, order_number, customer_name, quantity, sale_date, payment_method, product_name, total_amount, cust_id, product_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    if (!$stmt) {
        die("Prepare failed (sales insert): " . $conn->error);
    }
    $stmt->bind_param(
        "ississsdii", 
        $order_id, 
        $order_number, 
        $customer_name, 
        $quantity, 
        $sale_date, 
        $payment_method, 
        $product_name, 
        $line_total, 
        $cust_id, 
        $line_product_id
    );
    $stmt->execute();
    if ($stmt->error) {
        die("Error inserting sales record: " . $stmt->error);
    }
    $stmt->close();

    $stockUpdateQuery = "UPDATE products SET quantity = quantity - ? WHERE product_name = ?";
    $stmt = $conn->prepare($stockUpdateQuery);
    if (!$stmt) {
        die("Prepare failed (stock update): " . $conn->error);
    }
    $stmt->bind_param("is", $quantity, $product_name);
    $stmt->execute();
    $stmt->close();
}

// Step 5: Update the orders table total_amount with the computed total.
$updateOrderTotalQuery = "UPDATE orders SET total_amount = ? WHERE order_id = ?";
$stmt = $conn->prepare($updateOrderTotalQuery);
if (!$stmt) {
    die("Prepare failed (order total update): " . $conn->error);
}
$stmt->bind_param("di", $computed_order_total, $order_id);
$stmt->execute();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sale Confirmed</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .container { margin-top: 50px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card shadow">
      <div class="card-header bg-success text-white">
        <h2>Sale Confirmed</h2>
      </div>
      <div class="card-body">
        <p class="lead">Sale confirmed by <strong><?php echo htmlspecialchars($_SESSION['staff_username']); ?></strong>.</p>
        <hr>
        <h5>Order Summary</h5>
        <ul class="list-group mb-3">
          <li class="list-group-item"><strong>Order Number:</strong> <?php echo htmlspecialchars($order_number); ?></li>
          <li class="list-group-item"><strong>Order ID:</strong> <?php echo $order_id; ?></li>
          <li class="list-group-item"><strong>Customer:</strong> <?php echo htmlspecialchars($customer_name); ?></li>
          <?php foreach ($orderItems as $item): ?>
            <li class="list-group-item">
              <strong>Product:</strong> <?php echo htmlspecialchars($item['product_name']); ?>
              &mdash; <strong>Quantity:</strong> <?php echo htmlspecialchars($item['quantity']); ?>
            </li>
          <?php endforeach; ?>
          <li class="list-group-item"><strong>Total Amount:</strong> Ksh <?php echo number_format($computed_order_total, 2); ?></li>
          <li class="list-group-item"><strong>Payment Method:</strong> <?php echo $payment_method; ?></li>
        </ul>
        <a href="new_orders.php" class="btn btn-primary">Back to Orders</a>
        <a href="sales_statement.php" class="btn btn-secondary">Sales Statement</a>
      </div>
    </div>
  </div>
</body>
</html>
