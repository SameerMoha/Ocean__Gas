<?php
session_start();
// Allow access to sales dashboard for users with role 'sales' or 'admin'
if (!isset($_SESSION['staff_username']) || !in_array($_SESSION['staff_role'], ['sales', 'admin'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// Fetch sales data from the sales table
$salesQuery = "SELECT * FROM sales";
$salesResult = $conn->query($salesQuery);
if (!$salesResult) {
    die("Query error: " . $conn->error);
}

$salesData = [];
while ($row = $salesResult->fetch_assoc()) {
    $salesData[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Arial', sans-serif;
    }
    /* Navbar styling */
    .navbar-custom {
      background-color: #6a008a;
    }
    .navbar-custom .navbar-brand,
    .navbar-custom .nav-link {
      color: #fff;
    }
    .container { margin-top: 30px; }
    .summary-card {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border: none;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 20px;
      text-align: center;
      background: #fff;
    }
    .summary-card h5 { color: #6a008a; font-weight: bold; }
    .summary-card p { font-size: 2rem; color: #e74c3c; font-weight: bold; }
    .back-btn { margin-top: 20px; }
    .charts-container, .table-container { margin-top: 30px; }
    table { background: #fff; border-collapse: collapse; width: 100%; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #6a008a; color: #fff; }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
      <a class="navbar-brand" href="#">Sales Dashboard</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon" style="color: #fff;"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="/OceanGas/staff/admin_dashboard.php">Back to Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/OceanGas/logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  
  <!-- Main Content -->
  <div class="container">
    <!-- Summary Cards -->
    <div class="row">
      <div class="col-md-4">
        <div class="summary-card">
          <h5>Total Sales</h5>
          <p>15,000</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="summary-card">
          <h5>Pending Sales</h5>
          <p>8</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="summary-card">
          <h5>Deliveries Made</h5>
          <p>25</p>
        </div>
      </div>
    </div>
    
    <!-- Charts Section -->
    <div class="charts-container row">
      <div class="col-md-6">
        <div class="card p-3">
          <h5 class="text-center">Sales Trends</h5>
          <canvas id="salesTrendChart"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3">
          <h5 class="text-center">Product Distribution</h5>
          <canvas id="salesDistributionChart"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Sales Report Table -->
    <div class="table-container mt-4">
      <h3>Sales Report</h3>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Customer Name</th>
            <th>Contact</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($salesData as $sale): ?>
          <tr>
            <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($sale['contact']); ?></td>
            <td><?php echo htmlspecialchars($sale['product']); ?></td>
            <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
            <td><?php echo htmlspecialchars($sale['status']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Chart.js Scripts -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sales Trends: Combined Line & Bar Chart
      const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
      new Chart(salesTrendCtx, {
        type: 'bar',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
          datasets: [
            {
              type: 'line',
              label: '6kg Cylinders',
              data: [50, 60, 55, 70, 65, 80],
              borderColor: '#3498db',
              fill: false,
              tension: 0.4
            },
            {
              type: 'bar',
              label: '12kg Cylinders',
              data: [30, 40, 35, 50, 45, 60],
              backgroundColor: '#e74c3c'
            }
          ]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'top' } }
        }
      });
      
      // Sales Distribution Pie Chart
      const salesDistributionCtx = document.getElementById('salesDistributionChart').getContext('2d');
      new Chart(salesDistributionCtx, {
        type: 'pie',
        data: {
          labels: ['6kg Cylinders', '12kg Cylinders'],
          datasets: [{
            data: [200, 150],
            backgroundColor: ['#f39c12', '#27ae60']
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } }
        }
      });
    });
  </script>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
