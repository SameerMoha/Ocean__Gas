<?php
session_start();

// Check if the staff user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$adminName = $_SESSION['staff_username'];

// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query for Total Users
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
if (!$result) {
    die("Total Users query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_users = $row['total_users'];

// Query for Active Sessions (Assuming a 'sessions' table with 'is_active' field)
$result = $conn->query("SELECT COUNT(*) AS active_sessions FROM sessions WHERE is_active = 1");
if (!$result) {
    die("Active Sessions query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$active_sessions = $row['active_sessions'];

// Query for Pending Requests (assuming both sales and procurement requests have a status field)
// For pending sales:
$result = $conn->query("SELECT COUNT(*) AS pending_sales FROM sales WHERE status = 'pending'");
if (!$result) {
    die("Pending Sales query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$pending_sales = $row['pending_sales'];

// For pending procurement requests:
$result = $conn->query("SELECT COUNT(*) AS pending_procurements FROM purchase_history WHERE status = 'pending'");
if (!$result) {
    die("Pending Procurements query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$pending_procurements = $row['pending_procurements'];

$pending_requests = $pending_sales + $pending_procurements;

// Query for Revenue vs Expense using the 'total' column from sales
$result = $conn->query("SELECT IFNULL(SUM(total), 0) AS total_revenue FROM sales");
if (!$result) {
    die("Revenue query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_revenue = $row['total_revenue'];

$result = $conn->query("SELECT IFNULL(SUM(amount), 0) AS total_expense FROM funds_deductions");
if (!$result) {
    die("Expense query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_expense = $row['total_expense'];

// For the pie chart, we use Sales Revenue (total) and compute Total Purchases Cost
$total_sales_amount = $total_revenue;

$result = $conn->query("
  SELECT IFNULL(SUM(
         CASE WHEN ph.product = '6kg' THEN s.cost_6kg 
              ELSE s.cost_12kg 
         END * ph.quantity), 0) AS total_purchases_amount 
  FROM purchase_history ph 
  JOIN suppliers s ON ph.supplier_id = s.id
");
if (!$result) {
    die("Total Purchases Amount query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_purchases_amount = $row['total_purchases_amount'];

// Query for 2 most recent purchases
$purchases_sql = "SELECT ph.purchase_date, ph.product, ph.quantity, s.name AS supplier, 
                        (CASE WHEN ph.product = '6kg' THEN s.cost_6kg ELSE s.cost_12kg END * ph.quantity) AS total_cost 
                   FROM purchase_history ph 
                   JOIN suppliers s ON ph.supplier_id = s.id 
                   ORDER BY ph.purchase_date DESC LIMIT 2";
$result = $conn->query($purchases_sql);
if (!$result) {
    die("Recent Purchases query failed: " . $conn->error);
}
$recent_purchases = [];
while($row = $result->fetch_assoc()){
    $recent_purchases[] = $row;
}

// Query for 2 most recent sales
$sales_sql = "SELECT sale_date, product, quantity, total, assigned_to AS sold_by 
              FROM sales 
              ORDER BY sale_date DESC LIMIT 2";
$result = $conn->query($sales_sql);
if (!$result) {
    die("Recent Sales query failed: " . $conn->error);
}
$recent_sales = [];
while($row = $result->fetch_assoc()){
    $recent_sales[] = $row;
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
        /* Ensure pie chart is visible */
        #pieChart {
            width: 100% !important;
            height: 300px !important;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="/OceanGas/staff/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="/OceanGas/staff/stock.php"><i class="fas fa-box"></i> Stock/Inventory</a>
        <a href="/OceanGas/staff/procurement_dashboard.php"><i class="fas fa-truck"></i> Procurement</a>
        <a href="/OceanGas/staff/sales.php"><i class="fas fa-shopping-cart"></i> Sales</a>
        <a href="/OceanGas/staff/users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="/OceanGas/staff/settings.php"><i class="fas fa-cogs"></i> Settings</a>
        <a href="/OceanGas/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="content">
        <div class="topbar">
            <h1>Welcome Admin, <?php echo htmlspecialchars($adminName); ?></h1>
            <div class="topbar-icons">
                <i class="fas fa-envelope"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
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
            <div class="col-md-4">
                <div class="card p-3">
                    <h5>Active Sessions</h5>
                    <p class="display-6"><?php echo number_format($active_sessions); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <a href="/OceanGas/staff/pending_requests.php" style="text-decoration: none; color: inherit;">
                    <div class="card p-3">
                        <h5>Pending Requests</h5>
                        <p class="display-6"><?php echo number_format($pending_requests); ?></p>
                    </div>
                </a>
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
                                <th>Sold By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($recent_sales) > 0): ?>
                                <?php foreach($recent_sales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['product']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                        <td>KES <?php echo number_format($sale['total'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($sale['sold_by']); ?></td>
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
            
            // Line Chart: Revenue vs Expense Trend (Dummy Data for illustration)
            const ctxLine = document.getElementById('lineChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [
                        {
                            label: 'Revenue',
                            data: [<?php echo $total_revenue * 0.2; ?>, <?php echo $total_revenue * 0.3; ?>, <?php echo $total_revenue * 0.25; ?>, <?php echo $total_revenue * 0.15; ?>, <?php echo $total_revenue * 0.05; ?>, <?php echo $total_revenue * 0.05; ?>],
                            borderColor: '#28a745',
                            fill: false,
                            tension: 0.1
                        },
                        {
                            label: 'Expense',
                            data: [<?php echo $total_expense * 0.3; ?>, <?php echo $total_expense * 0.25; ?>, <?php echo $total_expense * 0.2; ?>, <?php echo $total_expense * 0.15; ?>, <?php echo $total_expense * 0.05; ?>, <?php echo $total_expense * 0.05; ?>],
                            borderColor: '#dc3545',
                            fill: false,
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
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
    </script>
</body>
</html>
