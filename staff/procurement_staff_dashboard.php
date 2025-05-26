<?php
session_start();
// Ensure the procurement staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}


$staffName = $_SESSION['staff_username'];

// Define the current page file name
$current_page = basename($_SERVER['PHP_SELF']);

// Database connection details
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

$displayName = $staffName; // fallback to username
$email = '';
$role = '';
// Use a default avatar image URL
$profileImage = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

if ($row = $result->fetch_assoc()) {
    $displayName = $row['username'];
    $email       = $row['email'];
    $role        = $row['role'];
}
$stmt->close();

// Query the stock table for current inventory data
$stock_sql = "SELECT product_id, product_name, quantity, created_at FROM products";
$stock_result = $conn->query($stock_sql);
$stocks = [];
$supplierData = [];
$supplierNames = [];
$supplierQuantities = [];

if ($stock_result->num_rows > 0) {
    while ($row = $stock_result->fetch_assoc()) {
        $stocks[] = $row;
        $productName = trim($row['product_name']);
        $supplierData[$productName] = $row['quantity'];
        $supplierNames[] = $productName;
        $supplierQuantities[] = $row['quantity'];
    }
}

// Build low stock notifications based on supplier-specific thresholds
$low_stock_notifications = [];

// These thresholds can be adjusted as needed for each supplier and cylinder size
$thresholds = [
    'Shell Afrigas 12kg' => 60,
    'K-Gas 12kg' => 40, 
    'Total Gas 12kg' => 25,
    'Shell Afrigas 6kg' => 150,
    'K-Gas 6kg' => 60,
    'Total Gas 6kg' => 40
];

foreach ($stocks as $stock) {
    $productName = trim($stock['product_name']);
    
    // Check if we have a threshold defined for this product
    if (isset($thresholds[$productName]) && $stock['quantity'] < $thresholds[$productName]) {
        $low_stock_notifications[] = "Low stock alert: {$productName} Cylinders are below threshold (Current: {$stock['quantity']}, Threshold: {$thresholds[$productName]}).";
    }
}

// Query for the Budget vs Actual data from the procurement_funds table
$queryBudget = "SELECT DATE_FORMAT(allocated_date, '%b') AS month,
                       SUM(budget) AS total_budget,
                       SUM(actual) AS total_actual
                FROM procurement_funds
                GROUP BY MONTH(allocated_date)
                ORDER BY MONTH(allocated_date)";
$resultBudget = $conn->query($queryBudget);

$months = [];
$budgets = [];
$actuals = [];

if ($resultBudget && $resultBudget->num_rows > 0) {
    while($row = $resultBudget->fetch_assoc()){
        $months[] = $row['month'];
        $budgets[] = $row['total_budget'];
        $actuals[] = $row['total_actual'];
    }
} else {
    // Fallback dummy data if no records are found
    $months = ['Jan', 'Feb', 'Mar', 'Apr'];
    $budgets = [25000, 30000, 28000, 32000];
    $actuals = [24000, 29000, 27000, 31000];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Procurement Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
      body {
          display: flex;
          min-height: 100vh;
          background: #f8f9fa;
          font-family: Arial, sans-serif;
      }
      /* Sidebar Styles */
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
      /* Highlight the active page */
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
      /* Top Bar Styles */
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
      /* Bell icon badge */
      .position-relative {
          position: relative;
      }
      .badge-notification {
          position: absolute;
          top: -4px;
          right: -4px;
          font-size: 0.5rem; /* Smaller font size */
          padding: 2px 4px;  /* Adjust padding */
      }
      /* Dropdown Overrides */
      .dropdown-header img {
          object-fit: cover;
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
                  <?php if(count($low_stock_notifications) > 0): ?>
                    <span class="badge rounded-pill bg-danger badge-notification">
                      <?php echo count($low_stock_notifications); ?>
                    </span>
                  <?php endif; ?>
                </i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bellDropdown" style="min-width: 250px; ">
                <?php if(!empty($low_stock_notifications)): ?>
                  <?php foreach($low_stock_notifications as $note): ?>
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
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="card">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0">Budget vs Actual</h5>
                </div>
                <div class="card-body">
                  <canvas id="barChart" width="400" height="300"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card">
                <div class="card-header bg-success text-white">
                  <h5 class="mb-0">Procurement Trend</h5>
                </div>
                <div class="card-body">
                  <canvas id="lineChart" width="400" height="300"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
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
    
    <!-- Profile Edit Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form action="update_profile.php" method="POST" id="profileForm">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <!-- Username (read-only) -->
              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($displayName); ?>" readonly>
              </div>
              <!-- Email -->
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
              </div>
              <!-- New Password -->
              <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank if not changing">
              </div>
              <!-- Confirm New Password -->
              <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Leave blank if not changing">
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
    // Budget vs Actual Bar Chart
    const months = <?php echo json_encode($months); ?>;
    const budgetData = <?php echo json_encode($budgets); ?>;
    const actualData = <?php echo json_encode($actuals); ?>;
    
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Budget',
                    data: budgetData,
                    backgroundColor: '#3498db'
                },
                {
                    label: 'Actual',
                    data: actualData,
                    backgroundColor: '#e74c3c'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    // Updated Procurement Trend Line Chart with supplier-specific data
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [
                {
                    label: 'Shell Afrigas 6kg',
                    data: [110, 115, 118, 120],
                    borderColor: '#2ecc71',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'K-Gas 6kg',
                    data: [45, 48, 52, 50],
                    borderColor: '#3498db',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Total Gas 6kg',
                    data: [25, 28, 30, 31],
                    borderColor: '#e74c3c',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Shell Afrigas 12kg',
                    data: [42, 45, 48, 50],
                    borderColor: '#9b59b6',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'K-Gas 12kg',
                    data: [30, 32, 35, 35],
                    borderColor: '#f39c12',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Total Gas 12kg',
                    data: [15, 17, 18, 19],
                    borderColor: '#16a085',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    // Updated Procurement Trend Pie Chart with supplier-specific data
    const procurementData = {
        labels: <?php echo json_encode($supplierNames); ?>,
        datasets: [{
            label: 'Inventory Volumes',
            data: <?php echo json_encode($supplierQuantities); ?>,
            backgroundColor: [
                '#9b59b6', // Shell Afrigas 12kg
                '#f39c12', // K-Gas 12kg
                '#16a085', // Total Gas 12kg
                '#2ecc71', // Shell Afrigas 6kg
                '#3498db', // K-Gas 6kg
                '#e74c3c'  // Total Gas 6kg
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
                legend: {
                    position: 'top',
                },
                tooltip: {
                    enabled: true,
                }
            }
        }
    });
});
    </script>
</body>
</html>