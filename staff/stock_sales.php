<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$query = "SELECT * FROM products ORDER BY product_id";
$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}

$baseUrl  = '/OceanGas/';
$fsPrefix = $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Stock Sales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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
    .sidebar h2 {
      margin-top: 0;
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
    .product-img {
      width: 100px;
      height: 100px;
      object-fit: contain;
    }
    .low-stock {
      color: red;
      font-weight: bold;
    }
    .dropdown-btn {
      padding: 10px;
      width: 100%;
      background: none;
      border: none;
      text-align: left;
      cursor: pointer;
      font-size: 16px;
      color: white;
    }
    .dropdown-container {
      display: none;
      background-color:#6a008a;
      padding-left: 20px;
    }
    .dropdown-container a {
      color: white;
      padding: 8px 0;
      display: block;
      text-decoration: none;
    }
    .dropdown-container a:hover {
      background-color:rgba(255,255,255,0.2);
    }
  </style>
</head>
<body>
  <div class="d-flex">
    <div class="sidebar">
      <h2>Sales Panel</h2>
      <a href="sales_staff_dashboard.php" class="<?= $current_page === 'sales_staff_dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i> Cockpit
      </a>
      <a href="sales_invoice.php" class="<?= $current_page === 'sales_invoice.php' ? 'active' : '' ?>">
        <i class="fas fa-file-invoice"></i> Sales Invoice
      </a>
      <a href="stock_sales.php" class="<?= $current_page === 'stock_sales.php' ? 'active' : '' ?>">
        <i class="fas fa-box"></i> Stock/Inventory
      </a>
      <a href="reports.php" class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> Reports
      </a>
      <div class="dropdown">
        <button class="dropdown-btn">
          <i class="fas fa-truck"></i>
          <span>Deliveries</span>
          <i class="fas fa-caret-down ms-auto"></i>
        </button>
        <div class="dropdown-container">
          <a href="add_delivery_sales.php">Add Delivery</a>
          <a href="view_deliveries_sales.php">View Deliveries</a>
        </div>
      </div>
    </div>

    <div class="content-wrapper">
      <div class="container">
        <h1 class="mb-4">Inventory Stock</h1>
        <div class="table-responsive">
          <table id="productTable" class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Image</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php $counter = 1; while($row = $result->fetch_assoc()): ?>
                <?php 
                  $fsPath = $fsPrefix . ltrim($row['image_path'], '/');
                  $image_url = (!empty($row['image_path']) && file_exists($fsPath))
                    ? $baseUrl . ltrim($row['image_path'], '/')
                    : $baseUrl . 'assets/images/default.jpg';
                  $isLowStock = $row['quantity'] < 5;
                ?>
                <tr>
                  <td><?= $counter++ ?></td>
                  <td>
                    <img src="<?= htmlspecialchars($image_url) ?>" 
                         alt="<?= htmlspecialchars($row['product_name']) ?>" 
                         class="product-img">
                  </td>
                  <td><?= htmlspecialchars($row['product_name']) ?></td>
                  <td class="<?= $isLowStock ? 'low-stock' : '' ?>">
                    <?= htmlspecialchars($row['quantity']) ?>
                  </td>
                  <td>High quality LPG cylinder. Check back regularly for updated stock levels.</td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#productTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
          'excelHtml5',
          'pdfHtml5'
        ]
      });

      document.querySelectorAll('.dropdown-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          btn.classList.toggle('active');
          const container = btn.nextElementSibling;
          container.style.display = container.style.display === 'block'
            ? 'none'
            : 'block';
        });
      });
    });
  </script>
</body>
</html>
