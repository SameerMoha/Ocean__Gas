<?php
// File: staff/new_orders.php
session_start();

// Ensure sales staff is logged in
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin') {
    header("Location: staff_login.php");
    exit;
}

require_once('../includes/db.php');

// Query new orders (orders still marked as new)
$query = "SELECT order_id, order_number, order_date, invoice_summary, total_amount FROM orders WHERE is_new = 1 OR order_status = 'new'";
$result = $conn->query($query);
$new_orders_list = [];
if ($result) {
    while($row = $result->fetch_assoc()){
        $new_orders_list[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>New Orders - OceanGas Sales</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Prevent browser caching -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
</head>
<body class="bg-light">
  <div class="container my-5">
      <h1 class="mb-4">New Orders</h1>
      <?php if(empty($new_orders_list)): ?>
          <div class="alert alert-info">There are no new orders at this time.</div>
          <p>Please check your <a href="pending_sales.php">Pending Orders</a> page for orders awaiting confirmation.</p>
          <a href="sales_staff_dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
      <?php else: ?>
          <div class="alert alert-warning">
              These orders are new. If no action is taken within 30 seconds, they will automatically be moved to Pending Sales.
          </div>
          <table id="newOrdersTable" class="table table-striped">
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
                  <?php foreach ($new_orders_list as $order): ?>
                  <tr>
                      <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                      <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                      <td><?php echo htmlspecialchars($order['invoice_summary']); ?></td>
                      <td>Ksh <?php echo number_format($order['total_amount'], 2); ?></td>
                      <td>
                          <a href="view_orders.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn btn-info btn-sm">View Order</a>
                          <form action="confrim_sale.php" method="post" style="display:inline;">
                              <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                              <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to confirm this sale?');">Confirm Sale</button>
                          </form>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
          <a href="sales_staff_dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
      <?php endif; ?>
  </div>
  
  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery (for DataTables) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#newOrdersTable').DataTable();
    });
    
    // If there are new orders, set a 30-second timer to automatically mark them as pending.
    <?php if(!empty($new_orders_list)): ?>
    setTimeout(function(){
        // Make an AJAX request to mark new orders as pending.
        fetch('mark_pending.php')
          .then(response => response.json())
          .then(data => {
              if(data.success){
                  window.location.href = 'pending_sales.php';
              } else {
                  alert("Auto-update failed. Please refresh the page.");
              }
          })
          .catch(error => {
              console.error(error);
              window.location.href = 'pending_sales.php';
          });
    }, 30000); // 30 seconds
    <?php endif; ?>
  </script>
</body>
</html>
