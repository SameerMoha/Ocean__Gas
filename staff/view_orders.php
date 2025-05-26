<?php
session_start();

// Ensure sales staff is logged in.
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin') {
    header("Location: staff_login.php");
    exit;
}

if (!isset($_GET['order_id'])) {
    echo "No order ID provided.";
    exit;
}

$orderId = intval($_GET['order_id']);

// Use the relative path to your database connection file.
require_once('../includes/db.php');

// Retrieve order details (including billing_info and delivery_info).
$orderQuery = "SELECT order_number, order_date, invoice_summary, total_amount, billing_info, delivery_info FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Order not found.";
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

// Decode billing_info and delivery_info; adjust keys as needed.
$billing_info = json_decode($order['billing_info'], true);
$delivery_info = json_decode($order['delivery_info'], true);

// Retrieve order items.
$orderItemsQuery = "SELECT product_name, quantity, unit_price FROM order_items WHERE order_id = ?";
$stmt2 = $conn->prepare($orderItemsQuery);
$stmt2->bind_param("i", $orderId);
$stmt2->execute();
$result2 = $stmt2->get_result();

$orderItems = [];
while ($row = $result2->fetch_assoc()) {
    $orderItems[] = $row;
}
$stmt2->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Order - OceanGas Sales</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container my-5">
    <h1 class="mb-4">Order Details</h1>
    
    <!-- Order Basic Information -->
    <div class="card mb-4">
      <div class="card-body">
        <h3 class="card-title">Order #: <?php echo htmlspecialchars($order['order_number']); ?></h3>
        <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
        <p><strong>Total Amount:</strong> Ksh <?php echo number_format($order['total_amount'], 2); ?></p>
      </div>
    </div>
    
    <!-- Customer Billing Details -->
    <div class="card mb-4">
      <div class="card-header">Customer Billing Details</div>
      <div class="card-body">
        <?php if ($billing_info): ?>
          <p><strong>Name:</strong> <?php echo htmlspecialchars($billing_info['name'] ?? 'N/A'); ?></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($billing_info['email'] ?? 'N/A'); ?></p>
          <p><strong>Contact:</strong> <?php echo htmlspecialchars($billing_info['contact'] ?? 'N/A'); ?></p>
        <?php else: ?>
          <p>No billing details available.</p>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Delivery Details -->
    <div class="card mb-4">
      <div class="card-header">Delivery Details</div>
      <div class="card-body">
        <?php if ($delivery_info): ?>
          <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($delivery_info['address'] ?? 'N/A'); ?></p>
          <p><strong>Apartment/House Number:</strong> <?php echo htmlspecialchars($delivery_info['apartment'] ?? 'N/A'); ?></p>
        <?php else: ?>
          <p>No delivery details available.</p>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Invoice Summary & Order Items -->
    <div class="card mb-4">
      <div class="card-header">Invoice Summary</div>
      <div class="card-body">
        <p><?php echo htmlspecialchars($order['invoice_summary']); ?></p>
        <?php if(count($orderItems) > 0): ?>
          <h5 class="mt-4">Order Items</h5>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price (Ksh)</th>
                <th>Total (Ksh)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($orderItems as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                  <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                  <td><?php echo number_format($item['unit_price'], 2); ?></td>
                  <td><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
    
    <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
  </div>
  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
