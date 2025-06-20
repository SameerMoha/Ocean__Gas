<?php 
session_start();

// Ensure sales staff is logged in.
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || ($_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin')) {
    header("Location: staff_login.php");
    exit;
}

if (!isset($_GET['order_id'])) {
    echo "No order ID provided.";
    exit;
}

$orderId = intval($_GET['order_id']);

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 250px;
      height: 100vh;
      background: #6a008a;
      color: #fff;
      padding: 20px;
      overflow-y: auto;
    }
    .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px; margin: 5px 0; }
    .sidebar a:hover { background: rgba(255,255,255,0.2); border-radius: 5px; }
    .sidebar a.active { background: rgba(255,255,255,0.3); font-weight: bold; }
    .dropdown-btn { padding: 10px; width: 100%; background: none; border: none; text-align: left; cursor: pointer; font-size: 16px; color: white; }
    .dropdown-container { display: none; background-color: #6a008a; padding-left: 20px; }
    .dropdown-btn.active + .dropdown-container { display: block; }
    .dropdown-container a { color: white; padding: 8px 0; display: block; text-decoration: none; }
    .dropdown-container a:hover { background-color: rgba(255,255,255,0.2); }
    .main-content { margin-left: 250px; padding: 20px; }
  </style>
</head>
<body class="bg-light">
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
      <h2>Sales Panel</h2>
      <a href="/OceanGas/staff/sales_staff_dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'sales_staff_dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Cockpit
      </a>
      <a href="/OceanGas/staff/sales_invoice.php"><i class="fas fa-file-invoice"></i> Sales Invoice</a>
      <a href="/OceanGas/staff/stock_sales.php"><i class="fas fa-box"></i> Stock/Inventory</a>
      <a href="/OceanGas/staff/reports.php"><i class="fas fa-clipboard-list"></i> Reports</a>
      <div class="dropdown mt-3">
        <button class="dropdown-btn">
          <i class="fas fa-truck"></i> Deliveries <i class="fas fa-caret-down ms-auto"></i>
        </button>
        <div class="dropdown-container">
          <a href="add_delivery_sales.php">Add Delivery</a>
          <a href="view_deliveries_sales.php">View Deliveries</a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 main-content">
      <h1 class="mb-4">Order Details</h1>
      <!-- Order Basic Information -->
      <div class="card mb-4">
        <div class="card-body">
          <h3 class="card-title">Order #: <?php echo htmlspecialchars($order['order_number']); ?></h3>
          <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
          <p><strong>Total Amount:</strong> Ksh <?php echo number_format($order['total_amount'], 2); ?></p>
        </div>
      </div>
      <!-- Billing Details -->
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
            <p><strong>Apartment/House No:</strong> <?php echo htmlspecialchars($delivery_info['apartment'] ?? 'N/A'); ?></p>
          <?php else: ?>
            <p>No delivery details available.</p>
          <?php endif; ?>
        </div>
      </div>
      <!-- Invoice & Items -->
      <div class="card mb-4">
        <div class="card-header">Invoice Summary</div>
        <div class="card-body">
          <p><?php echo htmlspecialchars($order['invoice_summary']); ?></p>
          <?php if (count($orderItems) > 0): ?>
            <h5 class="mt-4">Order Items</h5>
            <table class="table table-bordered">
              <thead>
                <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
              </thead>
              <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                  <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                  <td>Ksh <?php echo number_format($item['unit_price'],2); ?></td>
                  <td>Ksh <?php echo number_format($item['unit_price']*$item['quantity'],2); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
      <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
    </div>
  </div>
  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.dropdown-btn').forEach(btn=>btn.addEventListener('click',()=>{btn.classList.toggle('active');let c=btn.nextElementSibling;c.style.display=c.style.display==='block'?'none':'block';}));
  </script>
</body>
</html>
