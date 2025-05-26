<?php
// File: staff/sales_statement.php
session_start();

// Ensure sales staff is logged in
if (!isset($_SESSION['staff_username'], $_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin') {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}

// DB Connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filters & pagination
$filter       = $_GET['payment_method'] ?? '';
$from_date    = $_GET['from_date']     ?? '';
$to_date      = $_GET['to_date']       ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 10;
$offset       = ($page - 1) * $limit;

// Fetch distinct payment methods for dropdown
$methods = [];
$res = $conn->query("SELECT DISTINCT payment_method FROM sales_record");
while ($r = $res->fetch_assoc()) {
    $methods[] = $r['payment_method'];
}

// Build WHERE conditions dynamically
$conds  = [];
$params = [];
$types  = '';

if ($filter) {
    $conds[]   = "payment_method = ?";
    $types    .= 's';
    $params[]  = $filter;
}
if ($from_date) {
    $conds[]   = "sale_date >= ?";
    $types    .= 's';
    $params[]  = $from_date . ' 00:00:00';
}
if ($to_date) {
    $conds[]   = "sale_date <= ?";
    $types    .= 's';
    $params[]  = $to_date . ' 23:59:59';
}

$whereClause = $conds ? "WHERE " . implode(' AND ', $conds) : '';

// Main query with grouping, filters, pagination
$sql = "
    SELECT 
      order_id       AS sale_id,
      order_number,
      sale_date,
      payment_method,
      SUM(total_amount) AS total_amount,
      product_name
    FROM sales_record
    $whereClause
    GROUP BY order_id, order_number, sale_date, payment_method, product_name
    ORDER BY sale_date DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

// Bind filter params + limit + offset
if ($conds) {
    $bindTypes = $types . 'ii';
    $stmt->bind_param($bindTypes, ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$salesRecords = [];
if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $salesRecords[] = $row;
    }
}

// Count total for pagination
$countSql = "
    SELECT COUNT(DISTINCT order_id) AS total
    FROM sales_record
    $whereClause
";
$countStmt = $conn->prepare($countSql);
if ($conds) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<style>
    body { 
      background: #eef2f7; 
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }
    /* Sidebar Styling */
    .sidebar {
      min-height: 100vh;
      background: #6a008a;
      padding: 20px;
      width: 250px;
    }
    
    .sidebar .nav-link {
      color: #fff;
      margin: 0.5px 0;
    }
    .sidebar .nav-link:hover {
      background-color: rgba(255,255,255,0.2);
      border-radius: 5px; 
    }
    .sidebar .nav-link.active {
      background: rgba(255,255,255,0.3);
      font-weight: bold;
    }
    /* Main content area */
    .main-content {
      padding: 2rem;
    }
    /* Invoice container and watermark (unchanged) */
    .invoice-container { 
      position: relative; 
      padding: 2rem; 
      background: #fff; 
      border-radius: 0.5rem; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
    }
    .watermark::after {
      content: '<?php echo addslashes($salesName); ?>';
      position: absolute;
      bottom: 1rem;
      right: 1rem;
      font-size: 5rem;
      color: rgba(0,0,0,0.05);
      transform: rotate(-30deg);
      pointer-events: none;
    }
    .header-title {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      color: #4a4a4a;
    }
    .greeting {
      font-style: italic;
      color: #6c757d;
    }
    .table thead {
      background: #007bff;
      color: #fff;
    }
    .btn-add {
      background: #28a745;
      color: #fff;
    }
    .btn-remove {
      background: #dc3545;
      color: #fff;
    }
    .action-buttons {
      margin-top: 2rem;
    }
    #invoiceSummary {
      margin-top: 2rem;
    }
  </style>
  <meta charset="UTF-8">
  <title>Sales Statement - OceanGas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
  <h1 class="mb-4">Sales Statement (Bank Ledger)</h1>

  <!-- Filters -->
  <form method="GET" class="row g-3 align-items-end mb-4">
    <div class="col-md-3">
      <label for="payment_method" class="form-label">Payment Method</label>
      <select name="payment_method" id="payment_method" class="form-select">
        <option value="">All</option>
        <?php foreach ($methods as $method): ?>
          <option value="<?=htmlspecialchars($method)?>" <?= $filter=== $method ? 'selected' : ''?>>
            <?=htmlspecialchars($method)?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label for="from_date" class="form-label">From Date</label>
      <input type="date" name="from_date" id="from_date" class="form-control"
             value="<?=htmlspecialchars($from_date)?>">
    </div>
    <div class="col-md-3">
      <label for="to_date" class="form-label">To Date</label>
      <input type="date" name="to_date" id="to_date" class="form-control"
             value="<?=htmlspecialchars($to_date)?>">
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
    </div>
  </form>

  <!-- Sales Table -->
  <table class="table table-striped">
    <thead class="table-dark">
      <tr>
        <th>Sale ID</th>
        <th>Order Number</th>
        <th>Sale Date</th>
        <th>Total Amount (Ksh)</th>
        <th>Payment Method</th>
        <th>Product</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($salesRecords)): ?>
        <tr>
          <td colspan="5" class="text-center">No records found.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($salesRecords as $sale): ?>
        <tr>
          <td><?=htmlspecialchars($sale['sale_id'])?></td>
          <td><?=htmlspecialchars($sale['order_number'])?></td>
          <td><?=htmlspecialchars($sale['sale_date'])?></td>
          <td><?=number_format($sale['total_amount'], 2)?></td>
          <td><?=htmlspecialchars($sale['payment_method'])?></td>
          <td><?=htmlspecialchars($sale['product_name'])?></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?=$page-1?>
          &payment_method=<?=urlencode($filter)?>
          &from_date=<?=urlencode($from_date)?>
          &to_date=<?=urlencode($to_date)?>">
          Previous
        </a>
      </li>
      <?php endif; ?>

      <?php if ($page < $totalPages): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?=$page+1?>
          &payment_method=<?=urlencode($filter)?>
          &from_date=<?=urlencode($from_date)?>
          &to_date=<?=urlencode($to_date)?>">
          Next
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </nav>

  <a href="sales_staff_dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
