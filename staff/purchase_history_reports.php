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

// pagination parameters
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = in_array(intval($_GET['per_page'] ?? 25), [10, 25, 50]) ? intval($_GET['per_page'] ?? 25) : 25;
$offset = ($page - 1) * $per_page;

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

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
$count_stmt = $conn->prepare($count_sql);
if ($types) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_records / $per_page);

// Add pagination to main query
$sql .= " ORDER BY ph.purchase_date DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $per_page;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$purchase_history = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// Build pagination URL
function buildPaginationUrl($params, $page, $per_page) {
    $params['page'] = $page;
    $params['per_page'] = $per_page;
    return '?' . http_build_query($params);
}
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
    .pagination-info { margin: 15px 0; color: #6c757d; }
    .pagination-controls { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
    .per-page-selector { display: flex; align-items: center; gap: 10px; }
  </style>
</head>
<body>
  <div class="d-flex" style="min-height:100vh">
    <script>
  // If we're inside an iframe, window.self !== window.top
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

      <!-- Pagination Info -->
      <div class="pagination-info">
        Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_records) ?> of <?= $total_records ?> records
      </div>

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

      <!-- Pagination Controls -->
      <?php if ($total_pages > 1): ?>
        <div class="pagination-controls">
          <div class="per-page-selector">
            <label for="per_page">Show:</label>
            <select id="per_page" class="form-select" style="width: auto;" onchange="changePerPage(this.value)">
              <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
              <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
              <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
            </select>
            <span>records per page</span>
          </div>
          
          <nav aria-label="Purchase history pagination">
            <ul class="pagination mb-0">
              <!-- Previous button -->
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= buildPaginationUrl($_GET, $page - 1, $per_page) ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                  </a>
                </li>
              <?php else: ?>
                <li class="page-item disabled">
                  <span class="page-link">
                    <i class="fas fa-chevron-left"></i> Previous
                  </span>
                </li>
              <?php endif; ?>

              <!-- Page numbers -->
              <?php
              $start_page = max(1, $page - 2);
              $end_page = min($total_pages, $page + 2);
              
              if ($start_page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= buildPaginationUrl($_GET, 1, $per_page) ?>">1</a>
                </li>
                <?php if ($start_page > 2): ?>
                  <li class="page-item disabled">
                    <span class="page-link">...</span>
                  </li>
                <?php endif; ?>
              <?php endif; ?>

              <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="<?= buildPaginationUrl($_GET, $i, $per_page) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                  <li class="page-item disabled">
                    <span class="page-link">...</span>
                  </li>
                <?php endif; ?>
                <li class="page-item">
                  <a class="page-link" href="<?= buildPaginationUrl($_GET, $total_pages, $per_page) ?>"><?= $total_pages ?></a>
                </li>
              <?php endif; ?>

              <!-- Next button -->
              <?php if ($page < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= buildPaginationUrl($_GET, $page + 1, $per_page) ?>">
                    Next <i class="fas fa-chevron-right"></i>
                  </a>
                </li>
              <?php else: ?>
                <li class="page-item disabled">
                  <span class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                  </span>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script>
    function changePerPage(value) {
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.set('per_page', value);
      urlParams.set('page', '1'); // Reset to first page when changing per_page
      window.location.href = window.location.pathname + '?' + urlParams.toString();
    }
  </script>
</body>
</html>
