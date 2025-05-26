<?php
// File: staff/pending_sales.php
session_start();

// Ensure sales staff is logged in
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin') {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
$salesName = $_SESSION['staff_username'];

// Database Connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query pending orders from the orders table (orders pending confirmation)
$pending_orders = [];
$query = "SELECT order_id, order_number, order_date, invoice_summary, total_amount FROM orders WHERE order_status = 'pending'";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()){
        $pending_orders[] = $row;
    }
} else {
    die("Query failed: " . $conn->error);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pending Sales - OceanGas Sales</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light">
  <div class="container my-5">
    <h1 class="mb-4">Pending Sales</h1>
    <?php if(empty($pending_orders)): ?>
      <div class="alert alert-info">There are no pending sales at this time.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table id="pendingSalesTable" class="table table-striped">
          <thead class="table-dark">
            <tr>
              <th>Order Number</th>
              <th>Order Date/Time</th>
              <th>Invoice Summary</th>
              <th>Total Amount</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pending_orders as $order): ?>
            <tr>
              <td><?php echo htmlspecialchars($order['order_number']); ?></td>
              <td><?php echo htmlspecialchars($order['order_date']); ?></td>
              <td><?php echo htmlspecialchars($order['invoice_summary']); ?></td>
              <td>Ksh <?php echo number_format($order['total_amount'], 2); ?></td>
              <td>
                <a href="/OceanGas/staff/view_orders.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn btn-info btn-sm">View Order</a>
                <form action="/OceanGas/staff/confrim_sale.php" method="post" style="display:inline;">
                  <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                  <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to confirm this sale?');">Confirm Sale</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <a href="/OceanGas/staff/sales_staff_dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
  </div>
  
  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#pendingSalesTable').DataTable();
    });
  </script>
</body>
</html>
