<?php
// File: staff/new_orders.php
session_start();

// Ensure sales staff is logged in
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || ($_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin')) {
    header("Location: staff_login.php");
    exit;
}

require_once('../includes/db.php');

// Query new orders (orders still marked as new)
$query = "SELECT order_id, order_number, order_date, invoice_summary, total_amount FROM orders WHERE is_new = 1 OR order_status = 'new'";
$result = $conn->query($query);
$new_orders_list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Prevent browser caching -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: #fff;
      padding: 20px;
      height: 100vh;
    }
    .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px; margin: 5px 0; }
    .sidebar a:hover { background: rgba(255,255,255,0.2); border-radius: 5px; }
    .sidebar a.active { background: rgba(255,255,255,0.3); font-weight: bold; }
    .dropdown-btn { padding: 10px; width: 100%; background: none; border: none; text-align: left; cursor: pointer; font-size: 16px; color: white; }
    .dropdown-container { display: none; background-color: #6a008a; padding-left: 20px; }
    .dropdown-btn.active + .dropdown-container { display: block; }
    .dropdown-container a { color: white; padding: 8px 0; display: block; text-decoration: none; }
    .dropdown-container a:hover { background-color: rgba(255,255,255,0.2); }
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
    <div class="flex-grow-1 p-4">
      <h1 class="mb-4">New Orders</h1>
      <?php if(empty($new_orders_list)): ?>
        <div class="alert alert-info">There are no new orders at this time.</div>
        <a href="sales_staff_dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
      <?php else: ?>
        <div class="table-responsive">
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
              <tr id="order-row-<?php echo $order['order_id']; ?>">
                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                <td><?php echo htmlspecialchars($order['invoice_summary']); ?></td>
                <td>Ksh <?php echo number_format($order['total_amount'], 2); ?></td>
                <td>
                  <a href="view_orders.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn btn-info btn-sm">View Order</a>
                  <button type="button" class="btn btn-success btn-sm confirm-sale-btn" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">Confirm Sale</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <a href="sales_staff_dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
      <?php endif; ?>
    </div>
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
      if ($.fn.DataTable) {
        $('#newOrdersTable').DataTable();
      }

      $('.confirm-sale-btn').on('click', function() {
        var orderId = $(this).data('order-id');
        var $row = $('#order-row-' + orderId);
        Swal.fire({
          title: 'Confirm Sale',
          text: 'Are you sure you want to confirm this sale?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, confirm it!',
          cancelButtonText: 'Cancel',
          showLoaderOnConfirm: true,
          preConfirm: () => {
            return $.ajax({
              url: 'confrim_sale.php',
              method: 'POST',
              data: { order_id: orderId },
              dataType: 'html',
            }).then(function(response) {
              return response;
            }).catch(function(xhr) {
              Swal.showValidationMessage('Request failed: ' + xhr.statusText);
            });
          },
          allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
          if (result.isConfirmed) {
            Swal.fire({
              title: 'Sale Confirmed!',
              text: 'The sale has been successfully confirmed.',
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            });
            $row.fadeOut(600, function() { $(this).remove(); });
          }
        });
      });

      document.querySelectorAll('.dropdown-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          btn.classList.toggle('active');
          const container = btn.nextElementSibling;
          container.style.display = container.style.display === 'block' ? 'none' : 'block';
        });
      });
    });
  </script>
</body>
</html>
