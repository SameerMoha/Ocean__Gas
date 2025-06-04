<?php  
$current_page = basename($_SERVER['PHP_SELF']);
session_start();

// Ensure sales staff is logged in
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}

$salesName = $_SESSION['staff_username'];

// Database Connection Details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve Email from users table using a prepared statement
$stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
$stmt->bind_param("s", $salesName);
$stmt->execute();
$stmt->bind_result($emailFromDB);
$stmt->fetch();
$stmt->close();

$email = !empty($emailFromDB) 
         ? $emailFromDB 
         : (isset($_SESSION['staff_email']) 
            ? $_SESSION['staff_email'] 
            : 'user@example.com');
$_SESSION['staff_email'] = $email;

$displayName  = $salesName;
$role         = isset($_SESSION['staff_role']) ? $_SESSION['staff_role'] : '';
$profileImage = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

// --- Cockpit Queries --- //

// New Orders Count
$result      = $conn->query("SELECT COUNT(*) AS new_orders FROM orders WHERE is_new = 1 OR order_status = 'new'");
$new_orders  = ($result && $row = $result->fetch_assoc()) ? $row['new_orders'] : 0;

// Total Products in Stock
$result      = $conn->query("SELECT IFNULL(SUM(quantity), 0) AS total_stock FROM products");
$total_stock = ($result && $row = $result->fetch_assoc()) ? $row['total_stock'] : 0;

// Pending Sales Count
$result        = $conn->query("SELECT COUNT(*) AS pending_sales FROM orders WHERE order_status = 'pending'");
$pending_sales = ($result && $row = $result->fetch_assoc()) ? $row['pending_sales'] : 0;

/**
 * === NEW: Month Dropdown & Sales Data Queries ===
 */
$allowedMonths = [
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
];

if (isset($_GET['month']) && array_key_exists($_GET['month'], $allowedMonths)) {
    $selectedMonth = $_GET['month'];
} else {
    $selectedMonth = date('m');
}
$selectedYear = date('Y');

$salesDays   = [];
$salesTotals = [];
$orderDays   = [];
$orderCounts = [];

// Total sales amount per day for the selected month
$sales_stmt = $conn->prepare("
    SELECT 
      DATE(sale_date) AS day, 
      SUM(total_amount) AS total_sales 
    FROM sales_record
    WHERE 
      YEAR(sale_date)  = ? 
      AND MONTH(sale_date) = ?
    GROUP BY DATE(sale_date)
    ORDER BY DATE(sale_date)
");
$sales_stmt->bind_param("ii", $selectedYear, $selectedMonth);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();
if ($sales_result && $sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $salesDays[]   = $row['day'];
        $salesTotals[] = floatval($row['total_sales']);
    }
}
$sales_stmt->close();

// Total number of orders per day for the selected month
$orders_stmt = $conn->prepare("
    SELECT 
      DATE(sale_date) AS day, 
      COUNT(*)         AS order_count 
    FROM sales_record
    WHERE 
      YEAR(sale_date)  = ? 
      AND MONTH(sale_date) = ?
    GROUP BY DATE(sale_date)
    ORDER BY DATE(sale_date)
");
$orders_stmt->bind_param("ii", $selectedYear, $selectedMonth);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $orderDays[]   = $row['day'];
        $orderCounts[] = intval($row['order_count']);
    }
}
$orders_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Cockpit - Sales Dashboard | OceanGas Enterprise</title>
  <!-- Bootstrap 5 CSS -->
  <link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
    rel="stylesheet"
  >
  <!-- Font Awesome -->
  <link 
    rel="stylesheet" 
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
  >
  <!-- DataTables CSS -->
  <link 
    rel="stylesheet" 
    href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css"
  >
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- jQuery (for DataTables) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script 
    src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"
  ></script>
  <script 
    src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"
  ></script>

  <style>
      body { 
          font-family: Arial, sans-serif; 
          background: #f8f9fa; 
      }
      .sidebar { 
          width: 250px; 
          background: #6a008a; 
          color: #fff; 
          padding: 20px; 
          position: fixed; 
          height: 100vh; 
      }
      .sidebar a { 
          color: #fff; 
          text-decoration: none; 
          display: block; 
          padding: 10px; 
          margin: 5px 0; 
      }
      .sidebar a:hover { 
          background: rgba(255,255,255,0.2); 
          border-radius: 5px; 
      }
      .sidebar a.active {
          background: rgba(255,255,255,0.3);
          font-weight: bold;
      }
      .content { 
          margin-left: 260px; 
          padding: 20px; 
      }
      .topbar { 
          display: flex; 
          justify-content: space-between; 
          align-items: center; 
          background: #fff; 
          padding: 10px 20px; 
          box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
      }
      .topbar i { 
          cursor: pointer; 
      }
      .card { 
          border: none; 
          box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
          margin-bottom: 20px; 
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
      /* Container hidden by default */
      .dropdown-container {
          display: none;
          background-color: #6a008a;
          padding-left: 20px;
      }
      /* Show container when active */
      .dropdown-btn.active + .dropdown-container {
          display: block;
      }
      /* Hover effect */
      .dropdown-container a {
          color: white;
          padding: 8px 0;
          display: block;
          text-decoration: none;
      }
      .dropdown-container a:hover {
          background-color: rgba(255,255,255,0.2);
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
      <h2>Sales Panel</h2>
      <a 
        href="/OceanGas/staff/sales_staff_dashboard.php" 
        class="<?php echo ($current_page === 'sales_staff_dashboard.php') ? 'active' : ''; ?>"
      >
        <i class="fas fa-chart-line"></i> Cockpit
      </a>
      <a href="/OceanGas/staff/sales_invoice.php">
        <i class="fas fa-file-invoice"></i> Sales Invoice
      </a>
      <a href="/OceanGas/staff/stock_sales.php">
        <i class="fas fa-box"></i> Stock/Inventory
      </a>
      <a href="/OceanGas/staff/reports.php">
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
  
  <!-- Main Content -->
  <div class="content">
      <!-- Topbar -->
      <div class="topbar mb-4">
          <h1 class="m-0">Welcome, <?php echo htmlspecialchars($salesName); ?></h1>
          <div class="d-flex align-items-center">
              <i class="fas fa-envelope me-3"></i>
              <i class="fas fa-bell me-3"></i>
              <!-- Profile Icon Dropdown -->
              <div class="dropdown">
                <a href="#" 
                   class="dropdown-toggle d-flex align-items-center text-dark text-decoration-none" 
                   id="profileDropdown" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false"
                >
                  <img 
                    src="<?php echo htmlspecialchars($profileImage); ?>" 
                    alt="Profile" 
                    class="rounded-circle" 
                    width="23" 
                    height="23"
                  >
                </a>
                <ul 
                  class="dropdown-menu dropdown-menu-end" 
                  aria-labelledby="profileDropdown" 
                  style="min-width: 250px;"
                >
                  <li class="dropdown-header text-center">
                    <img 
                      src="<?php echo htmlspecialchars($profileImage); ?>" 
                      alt="Profile" 
                      class="rounded-circle mb-2" 
                      width="60" 
                      height="60"
                    >
                    <p class="m-0 fw-bold"><?php echo htmlspecialchars($displayName); ?></p>
                    <small class="text-muted"><?php echo htmlspecialchars($email); ?></small><br>
                    <?php if (!empty($role)): ?>
                      <small class="text-muted"><?php echo htmlspecialchars($role); ?></small>
                    <?php endif; ?>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a 
                      class="dropdown-item" 
                      href="#" 
                      data-bs-toggle="modal" 
                      data-bs-target="#editProfileModal"
                    >
                      Profile
                    </a>
                  </li>
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
      
      <!-- KPI Cards Section -->
      <div class="row">
          <!-- New Orders (Clickable Card) -->
          <div class="col-md-3">
              <a 
                href="/OceanGas/staff/new_orders.php" 
                style="text-decoration: none; color: inherit;"
              >
                <div class="card p-3 text-center shadow-sm">
                  <h5>New Orders</h5>
                  <p class="display-6">
                    <?php echo number_format($new_orders); ?>
                  </p>
                </div>
              </a>
          </div>
          <!-- Total Products in Stock -->
          <div class="col-md-3">
              <div class="card p-3 text-center shadow-sm">
                  <h5>Total Products in Stock</h5>
                  <p class="display-6">
                    <?php echo number_format($total_stock); ?>
                  </p>
              </div>
          </div>
          <!-- Pending Sales (Clickable Card) -->
          <div class="col-md-3">
              <a 
                href="/OceanGas/staff/pending_sales.php" 
                style="text-decoration: none; color: inherit;"
              >
                <div class="card p-3 text-center shadow-sm">
                  <h5>Pending Sales</h5>
                  <p class="display-6">
                    <?php echo number_format($pending_sales); ?>
                  </p>
                </div>
              </a>
          </div>
      </div>
      
      <!-- KPI Charts Section -->
      <!-- First row only contains the month dropdown -->
      <div class="row mt-4">
          <div class="col-md-4 mb-3">
            <form method="GET" id="salesMonthForm">
              <label for="salesMonthSelect" class="form-label">
                Select Month:
              </label>
              <select 
                id="salesMonthSelect" 
                name="month" 
                class="form-select"
                onchange="document.getElementById('salesMonthForm').submit();"
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

      <!-- Second row: two charts side-by-side -->
      <div class="row">
          <!-- Total Sells (Line Chart) -->
          <div class="col-md-6 mb-3">
              <div class="card p-3">
                  <h5>
                    Total Sells (<?php echo 
                      $allowedMonths[$selectedMonth] . ", " . $selectedYear; 
                    ?>)
                  </h5>
                  <canvas id="totalSellsLineChart"></canvas>
              </div>
          </div>
          <!-- Total Orders (Column Chart) -->
          <div class="col-md-6 mb-3">
              <div class="card p-3">
                  <h5>
                    Total Orders (<?php echo 
                      $allowedMonths[$selectedMonth] . ", " . $selectedYear; 
                    ?>)
                  </h5>
                  <canvas id="totalOrdersColumnChart"></canvas>
              </div>
          </div>
      </div>
  </div> <!-- End .content -->
  
  <!-- Edit Profile Modal -->
  <div 
    class="modal fade" 
    id="editProfileModal" 
    tabindex="-1" 
    aria-labelledby="editProfileModalLabel" 
    aria-hidden="true"
  >
      <div class="modal-dialog">
          <div class="modal-content">
              <form id="editProfileForm">
                  <div class="modal-header">
                      <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                      <button 
                        type="button" 
                        class="btn-close" 
                        data-bs-dismiss="modal" 
                        aria-label="Close"
                      ></button>
                  </div>
                  <div class="modal-body">
                      <div class="mb-3">
                          <label for="editName" class="form-label">Name</label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="editName" 
                            name="name" 
                            value="<?php echo htmlspecialchars($displayName); ?>" 
                            required
                          >
                      </div>
                      <div class="mb-3">
                          <label for="editEmail" class="form-label">Email</label>
                          <input 
                            type="email" 
                            class="form-control" 
                            id="editEmail" 
                            name="email" 
                            value="<?php echo htmlspecialchars($email); ?>" 
                            required
                          >
                      </div>
                      <div class="mb-3">
                          <label for="editRole" class="form-label">Role</label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="editRole" 
                            name="role" 
                            value="<?php echo htmlspecialchars($role); ?>" 
                            readonly
                          >
                      </div>
                      <div class="mb-3">
                          <label for="editPassword" class="form-label">New Password</label>
                          <input 
                            type="password" 
                            class="form-control" 
                            id="editPassword" 
                            name="password"
                          >
                          <small class="text-muted">Leave blank to keep current password.</small>
                      </div>
                  </div>
                  <div class="modal-footer">
                      <span id="updateStatus" class="me-auto text-success small"></span>
                      <button type="submit" class="btn btn-primary">Save changes</button>
                  </div>
              </form>
          </div>
      </div>
  </div>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
  
  <script>
      document.addEventListener('DOMContentLoaded', function() {
          // ===== Total Sells Line Chart =====
          const salesDays   = <?php echo json_encode($salesDays); ?>;   // ["2025-06-01", …]
          const salesTotals = <?php echo json_encode($salesTotals); ?>; // [23500, …]
          const totalSellsCtx = document.getElementById('totalSellsLineChart').getContext('2d');
          new Chart(totalSellsCtx, {
              type: 'line',
              data: {
                  labels: salesDays,
                  datasets: [{
                      label: 'Total Sells (KES)',
                      data: salesTotals,
                      borderColor: '#4e73df',
                      backgroundColor: 'rgba(78, 115, 223, 0.2)',
                      fill: true,
                      tension: 0.1
                  }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: { display: false }
                },
                scales: {
                  x: {
                    title: { display: true, text: 'Date' }
                  },
                  y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Sales Amount (KES)' }
                  }
                }
              }
          });

          // ===== Total Orders Column Chart =====
          const orderDays   = <?php echo json_encode($orderDays); ?>;   // ["2025-06-01", …]
          const orderCounts = <?php echo json_encode($orderCounts); ?>; // [5, …]
          const totalOrdersCtx = document.getElementById('totalOrdersColumnChart').getContext('2d');
          new Chart(totalOrdersCtx, {
              type: 'bar',
              data: {
                  labels: orderDays,
                  datasets: [{
                      label: 'Order Count',
                      data: orderCounts,
                      backgroundColor: '#1cc88a'
                  }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: { display: false }
                },
                scales: {
                  x: {
                    title: { display: true, text: 'Date' }
                  },
                  y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Orders' }
                  }
                }
              }
          });

          // Initialize DataTables (if needed elsewhere)
          $('#reviewsTable').DataTable({
              paging: false,
              searching: false,
              info: false,
              language: {
                  emptyTable: "No reviews found."
              }
          });
      });

      // Fetch new orders count every 10s
      function fetchNewOrdersCount() {
          fetch('/OceanGas/staff/get_new_orders.php')
              .then(response => response.json())
              .then(data => {
                  document.querySelector(
                    '.row .col-md-3 .card p.display-6'
                  ).innerText = data.new_orders;
              })
              .catch(err => console.error("Error fetching new orders:", err));
      }
      setInterval(fetchNewOrdersCount, 10000);
      fetchNewOrdersCount();

      // Profile update form
      document.getElementById('editProfileForm').addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(this);
          fetch('update_profile.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.text())
          .then(result => {
              document.getElementById('updateStatus').textContent = result;
              setTimeout(() => location.reload(), 1000);
          })
          .catch(error => {
              document.getElementById('updateStatus').textContent = "Update failed!";
          });
      });

      // Sidebar deliveries dropdown
      document.addEventListener('DOMContentLoaded', () => {
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
