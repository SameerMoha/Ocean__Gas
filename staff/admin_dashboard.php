<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();

if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$adminUsername = $_SESSION['staff_username'];
$adminSql = "SELECT username, email FROM users WHERE username = ? AND role = 'admin' LIMIT 1";

$stmt = $conn->prepare($adminSql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $adminUsername);
$stmt->execute();
$result = $stmt->get_result();

$adminName = "";
$adminEmail = "";
if ($row = $result->fetch_assoc()) {
    $adminName = $row['username'];
    $adminEmail = $row['email'];
} else {
    die("No admin found for username: " . htmlspecialchars($adminUsername));
}
$stmt->close();

// Query for Total Users
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
if (!$result) {
    die("Total Users query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_users = $row['total_users'];

// Query for Revenue vs Expense 
$result = $conn->query(
    "SELECT IFNULL(SUM(total_amount), 0) AS total_revenue 
       FROM sales_record"
);
if (! $result) {
    die("Revenue query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_revenue = $row['total_revenue'];

$result = $conn->query(
    "SELECT IFNULL(SUM(funds_out), 0) AS total_expense 
       FROM funds
      WHERE source_type = 'deduction'"
);
if (! $result) {
    die("Expense query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_expense = $row['total_expense'];


$total_sales_amount = $total_revenue;

$sql = "
  SELECT 
    IFNULL(SUM(pr.buying_price * ph.quantity), 0) AS total_purchases_amount
  FROM purchase_history ph
  JOIN products p 
    ON ph.product    = p.product_name
  JOIN price pr 
    ON pr.product_id  = p.product_id
   AND pr.supplier_id = ph.supplier_id
";
$result = $conn->query($sql);
if (! $result) {
    die("Total Purchases Amount query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_purchases_amount = $row['total_purchases_amount'];


// --- 3 Most Recent Purchases ---
$purchases_sql = "
  SELECT 
    ph.purchase_date,
    ph.product,
    ph.quantity,
    s.name           AS supplier,
    (pr.buying_price * ph.quantity) AS total_cost
  FROM purchase_history ph
  JOIN suppliers s 
    ON ph.supplier_id = s.id
  JOIN products p 
    ON ph.product    = p.product_name
  JOIN price pr 
    ON pr.product_id  = p.product_id
   AND pr.supplier_id = ph.supplier_id
  ORDER BY ph.purchase_date DESC
  LIMIT 3
";
$result = $conn->query($purchases_sql);
if (!$result) {
    die("Recent Purchases query failed: " . $conn->error);
}
$recent_purchases = [];
while ($row = $result->fetch_assoc()) {
    $recent_purchases[] = $row;
}


// Query for 2 most recent sales
$sales_sql = "SELECT sale_date, product_name, quantity, total_amount
              FROM sales_record
              ORDER BY sale_date DESC LIMIT 3";
$result = $conn->query($sales_sql);
if (!$result) {
    die("Recent Sales query failed: " . $conn->error);
}
$recent_sales = [];
while($row = $result->fetch_assoc()){
    $recent_sales[] = $row;
}

$sale_day = $_GET['sale_day'] ?? date('Y-m-d');
$safe_day = $conn->real_escape_string($sale_day);


$monthlyData = [];
$query = "
  SELECT 
    DATE_FORMAT(sale_date, '%b') AS month,
    SUM(total_amount) AS revenue
  FROM sales_record
  WHERE YEAR(sale_date) = YEAR(CURDATE())
  GROUP BY MONTH(sale_date)
";

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $monthlyData[$row['month']]['revenue'] = (float)$row['revenue'];
}

// Example expense query:
$query_exp = "
 SELECT 
  ph.purchase_date,
  ph.product,
  ph.quantity,
  (pr.buying_price * ph.quantity) AS total_cost,
  s.name AS supplier,
  u.username AS purchased_by
FROM purchase_history ph
JOIN suppliers s ON ph.supplier_id = s.id
JOIN users u ON ph.purchased_by = u.id
JOIN products pd ON pd.product_name = ph.product
JOIN price pr ON pr.product_id = pd.product_id AND pr.supplier_id = ph.supplier_id
WHERE DATE(ph.purchase_date) = '$safe_day'
ORDER BY ph.purchase_date";

$result_exp = $conn->query($query_exp);
while ($row = $result_exp->fetch_assoc()) {
    $monthlyData[$row['month']]['expense'] = (float)$row['expense'];
}

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
foreach ($months as $m) {
    $monthlyData[$m]['revenue'] = $monthlyData[$m]['revenue'] ?? 0;
    $monthlyData[$m]['expense'] = $monthlyData[$m]['expense'] ?? 0;
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        .sidebar a.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
            border-radius:6px;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
            flex-grow: 1;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .topbar-icons {
            display: flex;
            gap: 15px;
        }
        .topbar-icons i {
            font-size: 20px;
            cursor: pointer;
        }
        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .chart-card {
            margin-bottom: 20px;
        }
        #pieChart {
            width: 100% !important;
            height: 300px !important;
        }

        .dropdown-menu li .dropdown-item i {
            margin-right: 8px;
        }
        .dropdown-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 5px;
        }
        

.profile-btn {
  background-color: #6f42c1 !important; 
  color: black !important;
  border: none;
  border-radius: 0.375rem; /* optional rounded corners */
  padding: 8px 12px;
}
.sidebar .dropdown-menu {
    background-color: #6a008a;
    border: none;
}
.sidebar .dropdown-menu .dropdown-item {
    color: black;
}
.sidebar .dropdown-menu .dropdown-item:hover {
    background-color: rgba(255,255,255,0.2);
}/* Style for dropdown button */
.dropdown-btn, .dropdown-btn2 {
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
    background-color:#6a008a;
    padding-left: 20px;
}

/* Show container when active */
.dropdown-btn.active, .dropdown-btn2.active + .dropdown-container {
    display: block;
    
}

/* Optional: hover effect */
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
<div class="sidebar"> 
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/OceanGas/staff/stock_admin.php"><i class="fas fa-box"></i> Stock/Inventory</a>
    <a href="/OceanGas/staff/users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="/OceanGas/staff/finance.php"><i class="fas fa-dollar-sign"></i> Finance</a>
<div class="dropdown">
    <button class="dropdown-btn">
  <i class="fas fa-truck"></i>
  <span>Deliveries</span>
  <i class="fas fa-caret-down ms-auto"></i>
</button>
<div class="dropdown-container">
  <a href="add_delivery.php">Add Delivery</a>
  <a href="view_deliveries.php">View Deliveries</a>
</div>

            </div>
    <div class="dropdown">
    <button class="dropdown-btn" id="btnProcurement">
        <i class="fas fa-truck"></i> Procurement <i class="fas fa-caret-down"></i>
    </button>
        <div class="dropdown-container">

<a href="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Dashboard
</a>

<a href="/OceanGas/staff/purchase_history_reports.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Purchase History
</a>

<a href="/OceanGas/staff/suppliers.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Suppliers
</a>

  <a href="/OceanGas/staff/financial_overview.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame">Financial Overview </a>

        </div>
    </div>
    <div class="dropdown">
        <button class="dropdown-btn">
            <i class="fas fa-shopping-cart"></i> Sales <i class="fas fa-caret-down"></i>
        </button>
        <div class="dropdown-container">
<a href="/OceanGas/staff/sales_staff_dashboard.php?embedded=1"
  onclick="
    document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame"> Dashboard
</a>            

<a href="/OceanGas/staff/sales_invoice.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame"> Sales Invoice 
</a>

 <a href="/OceanGas/staff/reports.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame">Reports 
</a>

        </div>
    </div>
    
    
        </div>
  
</div>

<div class="content" style="margin-left: 250px; padding: 20px; width: calc(100% - 250px);">
<iframe 
  id="mainFrame"
  name="main-frame"
  src="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1"
  style="display:none; width:100%; height:100%; border:none;"
></iframe>
<div id="mainContent">

        <!-- Topbar with Profile Dropdown -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <h1>Welcome Admin, <?php echo htmlspecialchars($adminName); ?></h1>
            
            <div class="d-flex align-items-center">
                <i class="fas fa-envelope mx-2"></i>
                <i class="fas fa-bell mx-2"></i>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    
                    <button class="btn btn-secondary dropdown-toggle" type="button"
        id="profileDropdown"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        style="display: flex; align-items: center;  background-color: white; border: none; color: black;">
    <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png"
         alt="Profile Picture"
         width="23px"
         height="23px"
         style="border-radius: 50%;">
</button>

                    <ul class="dropdown-menu dropdown-menu-end"  >
                        <li class="dropdown-header text-center">
                            <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png" alt="Profile Picture">
                            <div>
                                <strong><?php echo htmlspecialchars($adminName); ?></strong><br>
                                <small><?php echo htmlspecialchars($adminEmail); ?></small>
                            </div>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <!-- Profile link -->
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class=""></i> Profile
                            </a>
                        </li>

                        <!-- Dashboard link -->
                        <li>
                            <a class="dropdown-item" href="/OceanGas/staff/admin_dashboard.php">
                                <i class=""></i> Dashboard
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <!-- Sign Out link -->
                        <li>
                            <a class="dropdown-item text-danger" href="/OceanGas/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Top Cards Section -->
        <div class="row my-4">
            <div class="col-md-4">
                <div class="card p-3">
                    <h5>Total Users</h5>
                    <p class="display-6"><?php echo number_format($total_users); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Analytics Section -->
        <div class="row my-4">
            <div class="col-md-6">
                <div class="card chart-card p-3">
                    <h5>Revenue vs Expense</h5>
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card chart-card p-3">
                    <h5>Revenue vs Expense Trend</h5>
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Pie Chart Section -->
        <div class="row my-4">
            <div class="col-md-6">
                <div class="card p-3">
                    <h5>Total Sales vs Purchases</h5>
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Recent Transactions Section -->
        <div class="row my-4">
            <div class="col-md-6">
                <div class="card p-3">
                    <h5>Recent Purchases</h5>
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Supplier</th>
                                <th>Total Cost (KES)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($recent_purchases) > 0): ?>
                                <?php foreach($recent_purchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($purchase['purchase_date']); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['product']); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['supplier']); ?></td>
                                        <td>KES <?php echo number_format($purchase['total_cost'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No recent purchases.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3">
                    <h5>Recent Sales</h5>
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Total (KES)</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($recent_sales) > 0): ?>
                                <?php foreach($recent_sales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                        <td>KES <?php echo number_format($sale['total_amount'], 2); ?></td>
                     
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No recent sales.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Bar Chart: Revenue vs Expense
            const ctxBar = document.getElementById('barChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Revenue', 'Expense'],
                    datasets: [{
                        label: 'Amount (KES)',
                        data: [<?php echo $total_revenue; ?>, <?php echo $total_expense; ?>],
                        backgroundColor: ['#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
            
const ctxLine = document.getElementById('lineChart').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [
            {
                label: 'Revenue',
                data: <?php echo json_encode(array_column($monthlyData, 'revenue')); ?>,
                borderColor: '#28a745',
                fill: false,
                tension: 0.1
            },
            {
                label: 'Expense',
                data: <?php echo json_encode(array_column($monthlyData, 'expense')); ?>,
                borderColor: '#dc3545',
                fill: false,
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

            
            // Pie Chart: Total Sales Revenue vs Total Purchases Cost
            const ctxPie = document.getElementById('pieChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: ['Sales Revenue', 'Purchases Cost'],
                    datasets: [{
                        data: [<?php echo $total_sales_amount; ?>, <?php echo $total_purchases_amount; ?>],
                        backgroundColor: ['#007bff', '#ffc107']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
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

  function loadFrame(url) {
    const frame = document.getElementById('mainFrame');
    if (frame.src !== url) {
      frame.src = url;
    }
    frame.style.display = 'block';
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[data-target="main-frame"]').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        loadFrame(link.href);
      });
    });
  });
  function loadFrame(url) {
    const frame = document.getElementById('mainFrame');
    if (frame.src !== url) {
      frame.src = url;
    }
    frame.style.display = 'block';
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[data-target="main-frame"]').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        loadFrame(link.href);
      });
    });
  });
    </script>
</body>
</html>
