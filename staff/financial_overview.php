<?php
// Define the current page file name
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
// Ensure the procurement staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$staffName = $_SESSION['staff_username'];

// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve procurement staff details (for example, to show their name)
$sql = "SELECT username FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $staffName);
$stmt->execute();
$stmt->bind_result($p_Name);
$stmt->fetch();
$stmt->close();

// Query the stock table for current inventory data
$stock_sql = "SELECT id, product, quantity FROM stock";
$stock_result = $conn->query($stock_sql);
$stocks = [];
if ($stock_result->num_rows > 0) {
    while ($row = $stock_result->fetch_assoc()) {
        $stocks[] = $row;
    }
}

// Build low stock notifications
$low_stock_notifications = [];
foreach ($stocks as $stock) {
    $productName = strtolower(trim($stock['product']));
    if ($productName == '6kg' && $stock['quantity'] < 149) {
        $low_stock_notifications[] = "Low stock alert: 6kg Gas Cylinders are below threshold (Current: " . $stock['quantity'] . ").";
    }
    if ($productName == '12kg' && $stock['quantity'] < 100) {
        $low_stock_notifications[] = "Low stock alert: 12kg Gas Cylinders are below threshold (Current: " . $stock['quantity'] . ").";
    }
}

// Query purchase history joining suppliers and users for detailed report,
// including a computed total_cost column.
$history_sql = "SELECT ph.purchase_date, ph.product, ph.quantity, s.name AS supplier, 
                       u.username AS purchased_by,
                       (CASE 
                          WHEN ph.product = '6kg' THEN s.cost_6kg 
                          ELSE s.cost_12kg 
                        END * ph.quantity) AS total_cost
                FROM purchase_history ph
                JOIN suppliers s ON ph.supplier_id = s.id
                JOIN users u ON ph.purchased_by = u.id
                ORDER BY ph.purchase_date DESC";
$history_result = $conn->query($history_sql);
$purchase_history = [];
if ($history_result && $history_result->num_rows > 0) {
    while ($row = $history_result->fetch_assoc()) {
        $purchase_history[] = $row;
    }
}

// Query financial summary using separate queries for allocated funds and funds deductions
$sql_allocated = "SELECT IFNULL(SUM(allocated_amount),0) AS total_allocated FROM procurement_funds";
$allocated_result = $conn->query($sql_allocated);
$allocated_data = $allocated_result->fetch_assoc();
$total_allocated = $allocated_data['total_allocated'];

$sql_used = "SELECT IFNULL(SUM(amount),0) AS total_used FROM funds_deductions";
$used_result = $conn->query($sql_used);
$used_data = $used_result->fetch_assoc();
$total_used = $used_data['total_used'];

$balance = $total_allocated - $total_used;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Procurement Dashboard</title>
  <!-- Bootstrap CSS -->
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
    /* SIDEBAR STYLES */
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      height: 100vh;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 10px;
      margin: 5px 0;
    }
    .sidebar a:hover {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 5px;
    }
     /* Highlight the active page */
     .sidebar a.active {
          background: rgba(255,255,255,0.3);
          font-weight: bold;
     }


    /* MAIN CONTENT WRAPPER */
    .content-wrapper {
      flex: 1; /* Grow to fill remaining space */
      padding: 20px;
      background: #f8f9fa;
    }

    /* FINANCIAL SECTION STYLES */
    .financial-section {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }
    .financial-section h3 {
      margin-top: 0;
    }
    body{
        font-family: Arial, sans-serif;
    }
  </style>
</head>

<body class="m-0">
  <!-- FLEX CONTAINER FOR SIDEBAR AND MAIN CONTENT -->
  <div class="d-flex flex-nowrap" style="min-height:100vh;">
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
      <section class="financial-section">
        <h2>Financial Overview (KES)</h2>
        <div class="row text-center">
          <div class="col-md-4">
            <h3>Allocated</h3>
            <p class="value">
              KES <?php echo number_format($total_allocated, 2); ?>
            </p>
          </div>
          <div class="col-md-4">
            <h3>Used</h3>
            <p class="value">
              KES <?php echo number_format($total_used, 2); ?>
            </p>
          </div>
          <div class="col-md-4">
            <h3>Balance</h3>
            <p class="value">
              KES <?php echo number_format($balance, 2); ?>
            </p>
          </div>
        </div>
      </section>
      <!-- Add more sections or content here -->
    </div>
  </div>

  <!-- Bootstrap JS (Optional) -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>
