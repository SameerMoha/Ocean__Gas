<?php
session_start();
// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if procurement staff is logged in (using staff_username)
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Procurement Staff Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <style>
      body {
          font-family: Arial, sans-serif;
          background-color: #eef2f7;
      }
      header {
          background-color: #2c3e50;
          color: white;
          padding: 20px;
          text-align: center;
          position: relative;
      }
      header .logout {
          position: absolute;
          top: 20px;
          right: 20px;
      }
      .dashboard {
          padding: 20px;
          max-width: 1200px;
          margin: auto;
      }
      .section {
          margin-bottom: 30px;
      }
      .cards-container, .suppliers-container {
          display: flex;
          flex-wrap: wrap;
          gap: 20px;
      }
      .card, .supplier-card {
          background: white;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
          flex: 1;
          min-width: 250px;
          text-align: center;
      }
      .value {
          font-size: 2em;
          color: #34495e;
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
          margin-top: 20px;
      }
      .financial-section h3 {
          margin-top: 0;
      }
      .financial-summary {
          display: flex;
          gap: 20px;
      }
      .financial-summary div {
          flex: 1;
          text-align: center;
      }
      /* Sticky notification style */
      .sticky-alert {
          position: sticky;
          top: 0;
          z-index: 1050;
          margin-bottom: 20px;
      }
  </style>
</head>
<body>
  <header>
      <h1>Welcome, <?php echo htmlspecialchars($p_Name); ?></h1>
      <p>Procurement Staff Dashboard</p>
      <form action="/OceanGas/logout.php" method="post" class="logout">
          <button type="submit" class="btn btn-danger">Logout</button>
      </form>
  </header>
  
  <div class="container">
    <!-- Sticky Notification -->
    <?php if (!empty($low_stock_notifications)): ?>
      <div class="sticky-alert">
        <?php foreach ($low_stock_notifications as $note): ?>
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($note); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  
  <div class="dashboard">
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
          <div class="row">
              <div class="col-md-6 mb-3">
                  <div class="card shadow">
                      <div class="card-header bg-primary text-white">
                          <h5 class="mb-0">Budget vs Actual</h5>
                      </div>
                      <div class="card-body">
                          <canvas id="barChart" width="400" height="300"></canvas>
                      </div>
                  </div>
              </div>
              <div class="col-md-6 mb-3">
                  <div class="card shadow">
                      <div class="card-header bg-success text-white">
                          <h5 class="mb-0">Procurement Trend</h5>
                      </div>
                      <div class="card-body">
                          <canvas id="lineChart" width="400" height="300"></canvas>
                      </div>
                  </div>
              </div>
          </div>
      </section>
      
      <!-- Suppliers Section -->
      <section class="section">
          <h2>Suppliers</h2>
          <div class="suppliers-container">
              <!-- Hardcoded supplier cards; later you could load these dynamically -->
              <div class="supplier-card">
                  <h3>GasPro Solutions</h3>
                  <p>Reliable supplier of high-quality gas cylinders.</p>
                  <button onclick="location.href='supplier_info.php?id=1'" class="btn btn-primary">View Supplier</button>
              </div>
              <div class="supplier-card">
                  <h3>BlueFlame Distributors</h3>
                  <p>Leading distributor with competitive pricing.</p>
                  <button onclick="location.href='supplier_info.php?id=2'" class="btn btn-primary">View Supplier</button>
              </div>
              <div class="supplier-card">
                  <h3>EcoFuel Suppliers</h3>
                  <p>Eco-friendly and sustainable gas solutions.</p>
                  <button onclick="location.href='supplier_info.php?id=3'" class="btn btn-primary">View Supplier</button>
              </div>
          </div>
      </section>
      
      <!-- Purchase History & Reports Section -->
      <section class="section">
          <h2>Purchase History & Reports</h2>
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
      
      <!-- Financial Overview Section (Read-Only) -->
      <section class="section financial-section">
          <h2>Financial Overview (in Kenyan Shillings)</h2>
          <div class="financial-summary row text-center">
              <div class="col-md-4">
                  <h3>Allocated</h3>
                  <p class="value">KES <?php echo number_format($total_allocated, 2); ?></p>
              </div>
              <div class="col-md-4">
                  <h3>Used</h3>
                  <p class="value">KES <?php echo number_format($total_used, 2); ?></p>
              </div>
              <div class="col-md-4">
                  <h3>Balance</h3>
                  <p class="value">KES <?php echo number_format($balance, 2); ?></p>
              </div>
          </div>
      </section>
  </div>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
      document.addEventListener("DOMContentLoaded", function() {
          // Bar Chart: Budget vs Actual
          const barCtx = document.getElementById('barChart').getContext('2d');
          new Chart(barCtx, {
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
          
          // Line Chart: Procurement Trend
          const lineCtx = document.getElementById('lineChart').getContext('2d');
          new Chart(lineCtx, {
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
      });
  </script>
</body>
</html>
