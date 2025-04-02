<?php
// Define the current page file name
$current_page = basename($_SERVER['PHP_SELF']);
// db_connect.php
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
// Ensure the user is logged in (adjust as needed)
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Retrieve filter parameters from GET
$start_date      = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date        = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$supplier_filter = isset($_GET['supplier']) ? $_GET['supplier'] : '';
$product_filter  = isset($_GET['product']) ? $_GET['product'] : '';

// Build dynamic SQL query with filters
$query = "SELECT ph.purchase_date, ph.product, ph.quantity, s.name AS supplier, 
                 u.username AS purchased_by,
                 (CASE 
                    WHEN ph.product = '6kg' THEN s.cost_6kg 
                    ELSE s.cost_12kg 
                  END * ph.quantity) AS total_cost
          FROM purchase_history ph
          JOIN suppliers s ON ph.supplier_id = s.id
          JOIN users u ON ph.purchased_by = u.id
          WHERE 1=1 ";
$params = [];
$types  = "";

// Filter by purchase date range
if (!empty($start_date)) {
    $query .= " AND ph.purchase_date >= ? ";
    $params[] = $start_date;
    $types .= "s";
}
if (!empty($end_date)) {
    $query .= " AND ph.purchase_date <= ? ";
    $params[] = $end_date;
    $types .= "s";
}

// Filter by supplier name
if (!empty($supplier_filter)) {
    $query .= " AND s.name LIKE ? ";
    $params[] = "%" . $supplier_filter . "%";
    $types .= "s";
}

// Filter by product
if (!empty($product_filter)) {
    $query .= " AND ph.product LIKE ? ";
    $params[] = "%" . $product_filter . "%";
    $types .= "s";
}

$query .= " ORDER BY ph.purchase_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$purchase_history = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $purchase_history[] = $row;
    }
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase History & Reports</title>
  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <!-- Font Awesome -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
  />
  <style>
    /* === SIDEBAR STYLES === */
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      height: relative;
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
     /* Highlight the active page */
     .sidebar a.active {
          background: rgba(255,255,255,0.3);
          font-weight: bold;
     }
    /* === MAIN CONTENT WRAPPER === */
    .content-wrapper {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background: #f8f9fa;
    }

    /* === PURCHASE HISTORY TABLE & FORM === */
    .history-table {
      width: 100%;
      border-collapse: collapse;
    }
    .history-table th,
    .history-table td {
      border: 1px solid #dee2e6;
      padding: 10px;
      text-align: left;
    }
    .history-table th {
      background-color: #f8f9fa;
    }
    .filter-form .form-control {
      max-width: 100%;
    }
    .filter-form .btn {
      min-width: 120px;
    }
    .export-btns a {
      margin-right: 10px;
    }
    body{
      font-family: Arial, sans-serif;
    }
  </style>
</head>
<body style="margin: 0;">
  <!-- FLEX CONTAINER FOR SIDEBAR AND CONTENT -->
  <div class="d-flex" style="min-height: 100vh;">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Procurement Panel</h2>
        <a href="/OceanGas/staff/procurement_staff_dashboard.php"class="<?php echo ($current_page === 'procurement_staff_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Dashboard</a>
        <a href="/OceanGas/staff/stock_procurement.php"class="<?php echo ($current_page === 'stock_procurement.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Stock/Inventory</a>
        <a href="/OceanGas/staff/purchase_history_reports.php"class="<?php echo ($current_page === 'purchase_history_reports.php') ? 'active' : ''; ?>"><i class="fas fa-receipt"></i> Purchase History</a>
        <a href="/OceanGas/staff/suppliers.php"class="<?php echo ($current_page === 'suppliers.php') ? 'active' : ''; ?>"><i class="fas fa-industry"></i> Suppliers</a>
        <a href="/OceanGas/staff/financial_overview.php"class="<?php echo ($current_page === 'financial_overview.php') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Financial Overview</a>
    </div>

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
      <h2 class="mb-4">Purchase History & Reports</h2>

      <!-- Filter Form -->
      <form method="GET" action="" class="row g-3 filter-form mb-4">
        <div class="col-md-3">
          <input
            type="date"
            name="start_date"
            class="form-control"
            placeholder="Start Date"
            value="<?php echo htmlspecialchars($start_date); ?>"
          />
        </div>
        <div class="col-md-3">
          <input
            type="date"
            name="end_date"
            class="form-control"
            placeholder="End Date"
            value="<?php echo htmlspecialchars($end_date); ?>"
          />
        </div>
        <div class="col-md-3">
          <input
            type="text"
            name="supplier"
            class="form-control"
            placeholder="Supplier Name"
            value="<?php echo htmlspecialchars($supplier_filter); ?>"
          />
        </div>
        <div class="col-md-3">
          <input
            type="text"
            name="product"
            class="form-control"
            placeholder="Product"
            value="<?php echo htmlspecialchars($product_filter); ?>"
          />
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <div class="col-md-6 export-btns">
          <!-- Export Buttons: Passing along current filters -->
          <a
            href="export_excel.php?<?php echo http_build_query($_GET); ?>"
            class="btn btn-success"
          >
            <i class="fas fa-file-excel"></i> Export Excel
          </a>
          <a
            href="export_pdf.php?<?php echo http_build_query($_GET); ?>"
            class="btn btn-danger"
          >
            <i class="fas fa-file-pdf"></i> Export PDF
          </a>
        </div>
      </form>

      <!-- Purchase History Table -->
      <table class="history-table table table-striped">
        <thead>
          <tr>
            <th>Purchase Date</th>
            <th>Supplier Name</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Total Cost (KES)</th>
            <th>Procurement Staff</th>
          </tr>
        </thead>
        <tbody>
          <?php if (isset($purchase_history) && count($purchase_history) > 0): ?>
            <?php foreach($purchase_history as $history): ?>
              <tr>
                <td><?php echo htmlspecialchars($history['purchase_date']); ?></td>
                <td><?php echo htmlspecialchars($history['supplier']); ?></td>
                <td><?php echo htmlspecialchars($history['product']); ?></td>
                <td><?php echo htmlspecialchars($history['quantity']); ?></td>
                <td>KES <?php echo number_format($history['total_cost'], 2); ?></td>
                <td><?php echo htmlspecialchars($history['purchased_by']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6">No purchase history found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle (Optional) -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>
