<?php
session_start();
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if admin is logged in (using staff_username)
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$username = $_SESSION['staff_username'];
$sql = "SELECT username FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
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

// Query purchase history joining suppliers and users for detailed report
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

// Query financial summary using separate queries
$sql_allocated = "SELECT IFNULL(SUM(allocated_amount),0) AS total_allocated FROM procurement_funds";
$allocated_result = $conn->query($sql_allocated);
$allocated_data = $allocated_result->fetch_assoc();
$total_allocated = $allocated_data['total_allocated'];

$sql_used = "SELECT IFNULL(SUM(amount),0) AS total_used FROM funds_deductions";
$used_result = $conn->query($sql_used);
$used_data = $used_result->fetch_assoc();
$total_used = $used_data['total_used'];

$balance = $total_allocated - $total_used;

// Query for Allocation History (for modal)
$allocation_history = [];
$allocations_sql = "SELECT allocated_amount, note, allocated_date FROM procurement_funds ORDER BY allocated_date DESC";
$alloc_result = $conn->query($allocations_sql);
if ($alloc_result) {
    while ($alloc = $alloc_result->fetch_assoc()){
        $allocation_history[] = $alloc;
    }
}

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

// For pie chart, we use Sales Revenue and compute Total Purchases Cost
$result = $conn->query("SELECT IFNULL(SUM(total), 0) AS total_revenue FROM sales");
if (!$result) {
    die("Revenue query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_revenue = $row['total_revenue'];
$total_sales_amount = $total_revenue; // Sales Revenue

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

$conn->close();

$usage_percentage = ($total_allocated > 0) ? round(($total_used / $total_allocated) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Procurement Dashboard - Admin</title>
  <link rel="stylesheet" href="styles.css">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body {
          font-family: 'Arial', sans-serif;
          background-color: #eef2f7;
          margin: 0;
          padding: 0;
      }
      header {
          background-color: #2c3e50;
          color: white;
          padding: 20px;
          text-align: center;
          width: 100%;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          position: relative;
      }
      header form.logout {
          position: absolute;
          top: 20px;
          right: 20px;
      }
      .dashboard {
          width: 90%;
          max-width: 1200px;
          margin: 20px auto;
          display: flex;
          flex-direction: column;
          gap: 20px;
      }
      .cards-container {
          display: flex;
          flex-wrap: wrap;
          gap: 15px;
          justify-content: space-between;
      }
      .card {
          background: white;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
          text-align: center;
          flex: 1;
          min-width: 250px;
      }
      .value {
          font-size: 2em;
          color: #34495e;
      }
      .charts-container {
          display: flex;
          flex-wrap: wrap;
          gap: 20px;
          justify-content: space-between;
      }
      .chart {
          background: white;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
          flex: 1;
          min-width: 280px;
          text-align: center;
      }
      /* Fixed heights for charts */
      #barChart, #lineChart, #pieChart {
          height: 300px !important;
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
      .financial-section {
          background: #fff;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      }
      .financial-section h3 {
          margin-top: 0;
      }
      .financial-summary {
          display: flex;
          gap: 20px;
          margin-bottom: 20px;
      }
      .financial-summary div {
          flex: 1;
          text-align: center;
      }
      .allocate-form input[type="number"],
      .allocate-form input[type="text"] {
          padding: 8px;
          width: 100%;
          margin-bottom: 10px;
      }
      .allocate-form button {
          padding: 10px 20px;
          background-color: #3498db;
          color: #fff;
          border: none;
          border-radius: 5px;
          cursor: pointer;
      }
      .allocate-form button:hover {
          background-color: #2980b9;
      }
  </style>
</head>
<body>
    <header>
        <h1>Welcome to the Procurement Dashboard, <?php echo htmlspecialchars($p_Name); ?></h1>
        <p>Track and manage procurement activities efficiently.</p>
        <form action="/OceanGas/logout.php" method="post" class="logout">
            <button type="submit" class="btn btn-danger">Logout</button> 
            <li class="nav-item">
            <a class="nav-link" href="/OceanGas/staff/admin_dashboard.php">Back to Dashboard</a>
          </li>
        </form>
    </header>
    <main class="dashboard">
        <!-- Current Stock Section -->
        <section class="section">
            <h2>Current Stock</h2>
            <div class="cards-container">
                <?php foreach($stocks as $stock): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($stock['product']); ?></h3>
                        <p class="value"><?php echo htmlspecialchars($stock['quantity']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Procurement Analytics Section -->
        <section class="section">
            <h2>Procurement Analytics</h2>
            <div class="charts-container">
                <div class="chart">
                    <h3>Budget vs Actual </h3>
                    <canvas id="barChart"></canvas>
                </div>
                <div class="chart">
                    <h3>Procurement Trend </h3>
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </section>
        
        <!-- Purchase History & Reports Section -->
        <section class="section">
            <h2>Purchase History & Reports</h2>
            <table class="history-table">
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
                    <?php if (count($purchase_history) > 0): ?>
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
        </section>
        
        <!-- Financial Summary Section with Allocate Additional Funds Form -->
        <section class="section financial-section">
            <h2>Financial Summary (in Kenyan Shillings)</h2>
            <div class="financial-summary row text-center">
                <div class="col-md-4">
                    <h6>Total Allocated</h6>
                    <p class="display-6">KES <?php echo number_format($total_allocated, 2); ?></p>
                </div>
                <div class="col-md-4">
                    <h6>Total Used</h6>
                    <p class="display-6">KES <?php echo number_format($total_used, 2); ?></p>
                </div>
                <div class="col-md-4">
                    <h6>Balance</h6>
                    <p class="display-6">KES <?php echo number_format($balance, 2); ?></p>
                </div>
            </div>
            <?php
            $usage_percentage = ($total_allocated > 0) ? round(($total_used / $total_allocated) * 100) : 0;
            ?>
            <div class="mt-3">
                <h6>Usage: <?php echo $usage_percentage; ?>%</h6>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $usage_percentage; ?>%;" aria-valuenow="<?php echo $usage_percentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $usage_percentage; ?>%</div>
                </div>
            </div>
            <div class="mt-3">
                <h6>Allocate Additional Funds</h6>
                <form class="allocate-form" action="allocate_funds.php" method="POST">
                    <input type="number" name="amount" step="0.01" min="0" placeholder="Enter amount in KES" required>
                    <input type="text" name="note" placeholder="Optional note">
                    <button type="submit" class="btn btn-success">Allocate Funds</button>
                </form>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allocationHistoryModal">View Allocation History</button>
            </div>
        </section>
        
        <!-- Modal for Allocation History -->
        <div class="modal fade" id="allocationHistoryModal" tabindex="-1" aria-labelledby="allocationHistoryLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="allocationHistoryLabel">Allocation History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php if(count($allocation_history) > 0): ?>
                  <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                      <tr>
                        <th>Date</th>
                        <th>Amount (KES)</th>
                        <th>Note</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach($allocation_history as $alloc): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($alloc['allocated_date']); ?></td>
                          <td>KES <?php echo number_format($alloc['allocated_amount'], 2); ?></td>
                          <td><?php echo htmlspecialchars($alloc['note']); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php else: ?>
                  <p>No allocation history available.</p>
                <?php endif; ?>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pie Chart Section -->
        <section class="section">
            <h2>Total Sales vs Purchases</h2>
            <div class="card p-3">
                <canvas id="pieChart"></canvas>
            </div>
        </section>
        
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Bar Chart: Revenue vs Expense (using dummy monthly data for illustration)
            const ctxBar = document.getElementById('barChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr'],
                    datasets: [
                        {
                            label: 'Budget',
                            data: [25000, 30000, 28000, 32000],
                            backgroundColor: '#3498db'
                        },
                        {
                            label: 'Actual',
                            data: [24000, 29000, 27000, 31000],
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
            
            // Line Chart: Procurement Trend (using dummy weekly data for illustration)
            const ctxLine = document.getElementById('lineChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [
                        {
                            label: '6kg Cylinders',
                            data: [20, 25, 22, 30],
                            borderColor: '#2ecc71',
                            fill: false,
                            tension: 0.1
                        },
                        {
                            label: '12kg Cylinders',
                            data: [15, 18, 20, 17],
                            borderColor: '#9b59b6',
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
