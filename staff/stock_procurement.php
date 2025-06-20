<?php
// Define the current page file name
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// Check that a staff user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Query the stock table for inventory details + the image_path
$query = "SELECT * FROM products ORDER BY product_id";
$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}

// Base URL & filesystem prefix for image checks
$baseUrl  = '/OceanGas/';
$fsPrefix = $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock Procurement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Arial', sans-serif;
      margin: 0;
    }
    .d-flex {
      min-height: 100vh;
    }
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      height: 100vh;
      position: sticky;
      top: 0;
      overflow-y: auto;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 10px;
      margin: 5px 0;
      border-radius: 5px;
    }
    .sidebar a:hover {
      background: rgba(255, 255, 255, 0.2);
    }
    .sidebar a.active {
      background: rgba(255,255,255,0.3);
      font-weight: bold;
    }
    .content-wrapper {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }
    .low-stock {
      color: red;
      font-weight: bold;
    }
    .product-img {
      width: 100px;
      height: auto;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <div class="sidebar">
    <h2>Procurement Panel</h2>
    <a href="/OceanGas/staff/procurement_staff_dashboard.php" class="<?php echo ($current_page === 'procurement_staff_dashboard.php') ? 'active' : ''; ?>">
      <i class="fas fa-truck"></i> Dashboard
    </a>
    <a href="/OceanGas/staff/stock_procurement.php" class="<?php echo ($current_page === 'stock_procurement.php') ? 'active' : ''; ?>">
      <i class="fas fa-box"></i> Stock/Inventory
    </a>
    <a href="/OceanGas/staff/purchase_history_reports.php" class="<?php echo ($current_page === 'purchase_history_reports.php') ? 'active' : ''; ?>">
      <i class="fas fa-receipt"></i> Purchase History
    </a>
    <a href="/OceanGas/staff/suppliers.php" class="<?php echo ($current_page === 'suppliers.php') ? 'active' : ''; ?>">
      <i class="fas fa-industry"></i> Suppliers
    </a>
    <a href="/OceanGas/staff/financial_overview.php" class="<?php echo ($current_page === 'financial_overview.php') ? 'active' : ''; ?>">
      <i class="fas fa-credit-card"></i> Financial Overview
    </a>
  </div>

  <div class="content-wrapper">
    <div class="container">
      <h1 class="mb-4">Inventory Stock</h1>
      <table id="stockTable" class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Image</th>
            <th>Quantity</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
            <?php 
              $fsPath = $fsPrefix . ltrim($row['image_path'], '/');
              $image_url = (!empty($row['image_path']) && file_exists($fsPath))
                           ? $baseUrl . ltrim($row['image_path'], '/')
                           : $baseUrl . 'assets/images/default.jpg';
              $lowStockClass = ($row['quantity'] < 5) ? 'low-stock' : '';
            ?>
            <tr>
              <td><?php echo htmlspecialchars($row['product_id']); ?></td>
              <td><?php echo htmlspecialchars($row['product_name']); ?></td>
              <td><img src="<?php echo htmlspecialchars($image_url); ?>" class="product-img" alt="Product Image"></td>
              <td class="<?php echo $lowStockClass; ?>"><?php echo htmlspecialchars($row['quantity']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function() {
    $('#stockTable').DataTable({
      pageLength: 10,
      dom: 'Bfrtip',
      buttons: ['excel', 'pdf']
    });
  });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
</body>
</html>
