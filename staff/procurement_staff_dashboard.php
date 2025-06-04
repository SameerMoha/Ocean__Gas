<?php 
session_start();
// Ensure the procurement staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$staffName    = $_SESSION['staff_username'];
$current_page = basename($_SERVER['PHP_SELF']);

// Database connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * Retrieve user details from the users table.
 */
$sql = "SELECT username, email, role FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $staffName);
$stmt->execute();
$result = $stmt->get_result();

$displayName  = $staffName; // fallback
$email        = '';
$role         = '';
$profileImage = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

if ($row = $result->fetch_assoc()) {
    $displayName = $row['username'];
    $email       = $row['email'];
    $role        = $row['role'];
}
$stmt->close();

/**
 * Query current inventory from `products` table
 */
$stock_sql    = "SELECT product_id, product_name, quantity, created_at FROM products";
$stock_result = $conn->query($stock_sql);
$stocks             = [];
$supplierData       = [];
$supplierNames      = [];
$supplierQuantities = [];

if ($stock_result->num_rows > 0) {
    while ($row = $stock_result->fetch_assoc()) {
        $stocks[] = $row;
        $pName = trim($row['product_name']);
        $supplierData[$pName]      = intval($row['quantity']);
        $supplierNames[]           = $pName;
        $supplierQuantities[]      = intval($row['quantity']);
    }
}

/**
 * Build low‐stock notifications
 */
$low_stock_notifications = [];
$thresholds = [
    'Shell Afrigas 12kg' => 50,
    'K-Gas 12kg'         => 40,
    'Total Gas 12kg'     => 25,
    'Shell Afrigas 6kg'  => 45,
    'K-Gas 6kg'          => 60,
    'Total Gas 6kg'      => 40,
    'ProGas 6kg'         => 20,
    'ProGas 12kg'        => 15,
    'Hashi Gas 6kg'      => 35,
    'Hashi Gas 12kg'     => 20,
    'Luqman Gas 6kg'     => 25,
    'Luqman Gas 12kg'    => 15
];

foreach ($stocks as $stock) {
    $pName    = trim($stock['product_name']);
    $quantity = intval($stock['quantity']);
    if (isset($thresholds[$pName]) && $quantity < $thresholds[$pName]) {
        $low_stock_notifications[] = 
            "Low stock alert: {$pName} Cylinders are below threshold (Current: {$quantity}, Threshold: {$thresholds[$pName]}).";
    }
}

/**
 * === NEW: Handle month selection via GET ===
 * If ?month=MM is provided (e.g. month=05), use that. Otherwise, default to current month.
 * We only allow values "01" through "12".
 */
$allowedMonths = [
    '01'=>'January','02'=>'February','03'=>'March','04'=>'April',
    '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
    '09'=>'September','10'=>'October','11'=>'November','12'=>'December'
];

if (isset($_GET['month']) && array_key_exists($_GET['month'], $allowedMonths)) {
    $selectedMonth = $_GET['month'];
} else {
    $selectedMonth = date('m');  // e.g., "06" for June
}
$selectedYear  = date('Y');      // always current year

/**
 * === Procurement Trend for SELECTED MONTH ===
 * Group by DATE(purchase_date) for the chosen year & month.
 */
$trendDays   = [];
$trendTotals = [];

$trend_stmt = $conn->prepare("
    SELECT 
      DATE(purchase_date) AS day, 
      SUM(quantity)      AS total 
    FROM purchase_history
    WHERE 
      YEAR(purchase_date)  = ?
      AND MONTH(purchase_date) = ?
    GROUP BY DATE(purchase_date)
    ORDER BY DATE(purchase_date)
");
$trend_stmt->bind_param("ii", $selectedYear, $selectedMonth);
$trend_stmt->execute();
$trend_result = $trend_stmt->get_result();

if ($trend_result && $trend_result->num_rows > 0) {
    while ($row = $trend_result->fetch_assoc()) {
        // e.g. "2025-06-03"
        $trendDays[]   = $row['day'];
        $trendTotals[] = intval($row['total']);
    }
}
$trend_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Procurement Dashboard</title>
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
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      display: flex;
      min-height: 100vh;
      background: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    /* Sidebar */
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      position: fixed;
      height: 100vh;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 10px;
      margin: 5px 0;
      border-radius: 5px;
      transition: background 0.2s;
    }
    .sidebar a:hover {
      background: rgba(255,255,255,0.2);
    }
    .sidebar a.active {
      background: rgba(255,255,255,0.3);
      font-weight: bold;
    }
    /* Content Area */
    .content {
      margin-left: 260px;
      padding: 20px;
      flex-grow: 1;
    }
    /* Top Bar */
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      padding: 10px 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .topbar-icons {
      display: flex;
      align-items: center;
    }
    .topbar-icons i {
      font-size: 16px;
      cursor: pointer;
      color: black;
    }
    .position-relative {
      position: relative;
    }
    .badge-notification {
      position: absolute;
      top: -4px;
      right: -4px;
      font-size: 0.5rem;
      padding: 2px 4px;
    }
    /* Card Styles */
    .card {
      border: none;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .history-table {
      width: 100%;
      border-collapse: collapse;
    }
    .history-table th, .history-table td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: left;
    }
    .sticky-alert {
      position: sticky;
      top: 0;
      z-index: 1050;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
<script>
  // If inside an iframe, remove sidebar & topbar
  if (window.self !== window.top) {
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) sidebar.remove();
      
      const topbar = document.querySelector('.topbar');
      if (topbar) topbar.remove();
      
      const content = document.querySelector('.content');
      if (content) {
        content.style.marginLeft = '0';
        content.style.width      = '100%';
        content.style.padding    = '20px';
      }
    });
  }
</script>

<!-- Sidebar -->
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

<!-- Main Content -->
<div class="content">
  <!-- Top Bar -->
  <div class="topbar">
    <h1>Welcome, <?php echo htmlspecialchars($staffName); ?></h1>
    <div class="topbar-icons">
      <i class="fas fa-envelope me-3"></i>

      <!-- Bell Icon Dropdown -->
      <div class="dropdown me-3">
        <a href="#" class="dropdown-toggle" id="bellDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: black;">
          <i class="fas fa-bell position-relative">
            <?php if (count($low_stock_notifications) > 0): ?>
              <span class="badge rounded-pill bg-danger badge-notification">
                <?php echo count($low_stock_notifications); ?>
              </span>
            <?php endif; ?>
          </i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bellDropdown" style="min-width: 250px;">
          <?php if (!empty($low_stock_notifications)): ?>
            <?php foreach ($low_stock_notifications as $note): ?>
              <li>
                <a class="dropdown-item" href="/OceanGas/staff/suppliers.php">
                  <?php echo htmlspecialchars($note); ?>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><span class="dropdown-item-text">No notifications</span></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Profile Icon Dropdown -->
      <div class="dropdown">
        <a href="#" class="dropdown-toggle d-flex align-items-center" 
           id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
           style="text-decoration: none; color:black">
          <img src="<?php echo htmlspecialchars($profileImage); ?>" 
               alt="Profile" class="rounded-circle" width="23" height="23">
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style="min-width: 250px;">
          <li class="dropdown-header text-center">
            <img src="<?php echo htmlspecialchars($profileImage); ?>" 
                 alt="Profile" class="rounded-circle mb-2" width="60" height="60">
            <p class="m-0 fw-bold"><?php echo htmlspecialchars($displayName); ?></p>
            <small class="text-muted"><?php echo htmlspecialchars($email); ?></small><br>
            <?php if (!empty($role)): ?>
              <small class="text-muted"><?php echo htmlspecialchars($role); ?></small>
            <?php endif; ?>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal">Profile</a></li>
          <li><a class="dropdown-item" href="#">Dashboard</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="/OceanGas/logout.php">
              <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Procurement Analytics Section -->
  <section class="section">
    <h2>Procurement Analytics</h2>

    <div class="row mb-3">
      <!-- Month Selector Dropdown -->
      <div class="col-md-4">
        <form method="GET" id="monthForm">
          <label for="monthSelect" class="form-label">Select Month:</label>
          <select 
            id="monthSelect" 
            name="month" 
            class="form-select"
            onchange="document.getElementById('monthForm').submit();"
          >
            <?php foreach ($allowedMonths as $num => $name): ?>
              <option 
                value="<?php echo $num; ?>" 
                <?php echo ($num === $selectedMonth) ? 'selected' : ''; ?>
              >
                <?php echo $name; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>

    <div class="row">
      <!-- Procurement Trend: driven by data for the selected month -->
      <div class="col-md-12 mb-3">
        <div class="card">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Procurement Trend (<?php echo $allowedMonths[$selectedMonth] . ", " . $selectedYear; ?>)</h5>
            <!-- You could also put the dropdown here if you prefer it in the card header -->
          </div>
          <div class="card-body">
            <canvas id="lineChart" width="400" height="100"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Inventory Distribution -->
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header bg-warning text-white">
            <h5 class="mb-0">Inventory Distribution</h5>
          </div>
          <div class="card-body">
            <canvas id="pieChart" width="400" height="300"></canvas>
          </div>
        </div>
      </div>
      <!-- Current Inventory Table -->
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">Current Inventory</h5>
          </div>
          <div class="card-body">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity</th>
                  <th>Status</th>
                  <th>Threshold</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($supplierData as $product => $quantity): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($product); ?></td>
                    <td><?php echo htmlspecialchars($quantity); ?></td>
                    <td>
                      <?php if (isset($thresholds[$product]) && $quantity < $thresholds[$product]): ?>
                        <span class="badge bg-danger">Low Stock</span>
                      <?php else: ?>
                        <span class="badge bg-success">In Stock</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($thresholds[$product]); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Profile Edit Modal (unchanged) -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="update_profile.php" method="POST" id="profileForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Username (read‐only) -->
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input 
              type="text" 
              name="username" 
              id="username" 
              class="form-control" 
              value="<?php echo htmlspecialchars($displayName); ?>" 
              readonly
            >
          </div>
          <!-- Email -->
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input 
              type="email" 
              name="email" 
              id="email" 
              class="form-control" 
              value="<?php echo htmlspecialchars($email); ?>" 
              required
            >
          </div>
          <!-- New Password -->
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input 
              type="password" 
              name="password" 
              id="password" 
              class="form-control" 
              placeholder="Leave blank if not changing"
            >
          </div>
          <!-- Confirm New Password -->
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input 
              type="password" 
              name="confirm_password" 
              id="confirm_password" 
              class="form-control" 
              placeholder="Leave blank if not changing"
            >
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    /**
     * === Line Chart: Procurement Trend for SELECTED MONTH ===
     * We use the PHP arrays trendDays and trendTotals, built above.
     */
    const trendDays   = <?php echo json_encode($trendDays); ?>;    // e.g. ["2025-06-01", "2025-06-02", …]
    const trendTotals = <?php echo json_encode($trendTotals); ?>;  // e.g. [15, 42, 23, …]

    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: trendDays,
            datasets: [{
                label: 'Quantity Purchased',
                data: trendTotals,
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.2)',
                fill: true,
                tension: 0.1,
            }]
        },
        options: {
            responsive: true,
            plugins: {
              legend: { display: false }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantity Purchased'
                    }
                }
            }
        }
    });

    /**
     * === Pie Chart: Inventory Distribution (unchanged) ===
     */
    const procurementData = {
        labels: <?php echo json_encode($supplierNames); ?>,
        datasets: [{
            label: 'Inventory Volumes',
            data: <?php echo json_encode($supplierQuantities); ?>,
            backgroundColor: [
                '#9b59b6', '#f4ff07', '#16a085', '#2ecc71',
                '#3498db', '#e74c3c', '#9f675b', '#0c0aa6',
                '#07fbff', '#ff9e00', '#ff00e8', '#086a9e'
            ],
            hoverOffset: 4
        }]
    };

    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: procurementData,
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'right' },
                tooltip: { enabled: true }
            }
        }
    });
});
</script>
</body>
</html>
