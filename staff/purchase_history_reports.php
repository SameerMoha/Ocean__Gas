<?php
// db_connect.php (you can also move this into an include)
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// retrieve filter inputs
$start_date      = $_GET['start_date']      ?? '';
$end_date        = $_GET['end_date']        ?? '';
$supplier_filter = $_GET['supplier']        ?? '';
$product_filter  = $_GET['product']         ?? '';

// base query (joins to get buying_price)
$sql = "
  SELECT
    ph.purchase_date,
    s.name           AS supplier,
    ph.product,
    ph.quantity,
    u.username       AS purchased_by,
    (pr.buying_price * ph.quantity) AS total_cost
  FROM purchase_history ph
  JOIN suppliers s
    ON ph.supplier_id = s.id
  JOIN users u
    ON ph.purchased_by = u.id
  /* join price and products to get unit cost */
  JOIN products p
    ON p.product_name = ph.product
  JOIN price pr
    ON pr.supplier_id = ph.supplier_id
   AND pr.product_id  = p.product_id
  WHERE 1=1
";

$params = [];
$types  = '';

// date filters
if ($start_date !== '') {
    $sql .= " AND ph.purchase_date >= ? ";
    $types  .= 's';
    $params[] = $start_date;
}
if ($end_date !== '') {
    $sql .= " AND ph.purchase_date <= ? ";
    $types  .= 's';
    $params[] = $end_date;
}

// supplier name filter
if ($supplier_filter !== '') {
    $sql .= " AND s.name LIKE ? ";
    $types  .= 's';
    $params[] = "%{$supplier_filter}%";
}

// product filter
if ($product_filter !== '') {
    $sql .= " AND ph.product LIKE ? ";
    $types  .= 's';
    $params[] = "%{$product_filter}%";
}

$sql .= " ORDER BY ph.purchase_date DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$purchase_history = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase History & Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body { font-family: Arial, sans-serif; }
    .sidebar { width: 250px; background: #6a008a; color: white; padding: 20px; }
    .sidebar a { color: white; display: block; padding: 10px; border-radius: 5px; text-decoration: none; }
    .sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.2); }
    .content-wrapper { flex: 1; padding: 20px; background: #f8f9fa; }
    .history-table th, .history-table td { padding: 10px; border: 1px solid #dee2e6; }
    .history-table th { background: #f8f9fa; }
  </style>
</head>
<body>
  <div class="d-flex" style="min-height:100vh">
    <script>
  // If we’re inside an iframe, window.self !== window.top
  if (window.self !== window.top) {
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Remove the sidebar element entirely
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) sidebar.remove();
      
      const topbar = document.querySelector('.topbar');
      if (topbar) topbar.remove();
      // 2. Reset your main content to fill the viewport
      const content = document.querySelector('.content');
      if (content) {
        content.style.marginLeft = '0';
        content.style.width      = '100%';
        content.style.padding    = '20px';
      }

    });
  }
</script>
    <div class="sidebar">
      <h2>Procurement Panel</h2>
      <?php $cur = basename($_SERVER['PHP_SELF']); ?>
      <a href="procurement_staff_dashboard.php" class="<?= $cur==='procurement_staff_dashboard.php'?'active':'' ?>">
        <i class="fas fa-truck"></i> Dashboard
      </a>
      <a href="stock_procurement.php" class="<?= $cur==='stock_procurement.php'?'active':'' ?>">
        <i class="fas fa-box"></i> Stock/Inventory
      </a>
      <a href="purchase_history_reports.php" class="<?= $cur==='purchase_history_reports.php'?'active':'' ?>">
        <i class="fas fa-receipt"></i> Purchase History
      </a>
      <a href="suppliers.php" class="<?= $cur==='suppliers.php'?'active':'' ?>">
        <i class="fas fa-industry"></i> Suppliers
      </a>
      <a href="financial_overview.php" class="<?= $cur==='financial_overview.php'?'active':'' ?>">
        <i class="fas fa-credit-card"></i> Financial Overview
      </a>
    </div>
    <div class="content-wrapper">
      <h2>Purchase History & Reports</h2>
      <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
          <input type="date" name="start_date" class="form-control" value="<?=htmlspecialchars($start_date)?>" />
        </div>
        <div class="col-md-3">
          <input type="date" name="end_date" class="form-control" value="<?=htmlspecialchars($end_date)?>" />
        </div>
        <div class="col-md-3">
          <input type="text" name="supplier" class="form-control" placeholder="Supplier" value="<?=htmlspecialchars($supplier_filter)?>" />
        </div>
        <div class="col-md-3">
          <input type="text" name="product" class="form-control" placeholder="Product" value="<?=htmlspecialchars($product_filter)?>" />
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary">Filter</button>
        </div>
        <div class="col-md-6">
          <a href="export_excel.php?<?=http_build_query($_GET)?>" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export Excel
          </a>
          <a href="export_pdf.php?<?=http_build_query($_GET)?>" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> Export PDF
          </a>
        </div>
      </form>

      <table class="history-table table table-striped">
        <thead>
          <tr>
            <th>Date</th>
            <th>Supplier</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Total Cost (KES)</th>
            <th>Staff</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($purchase_history): ?>
            <?php foreach ($purchase_history as $h): ?>
              <tr>
                <td><?=htmlspecialchars($h['purchase_date'])?></td>
                <td><?=htmlspecialchars($h['supplier'])?></td>
                <td><?=htmlspecialchars($h['product'])?></td>
                <td><?=htmlspecialchars($h['quantity'])?></td>
                <td>KES <?=number_format($h['total_cost'],2)?></td>
                <td><?=htmlspecialchars($h['purchased_by'])?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6">No purchase history found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script>

  </script>
</body>
</html>
