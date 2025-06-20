<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin') {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
$salesName = $_SESSION['staff_username'];

require_once '../includes/db.php'; // This file connects to the DB

// Helper function to format currency
function formatCurrency($value) {
    return 'Ksh ' . number_format($value, 2);
}

// ---------- KPI Queries (Speedometer & Fuel Gauge) ----------
$dateToday = date('Y-m-d');
$qToday = "SELECT IFNULL(SUM(total_amount),0) AS total_sales_today FROM sales_record WHERE DATE(sale_date) = CURDATE()";
$resultToday = $conn->query($qToday);
$rowToday = $resultToday ? $resultToday->fetch_assoc() : ['total_sales_today' => 0];
$totalSalesToday = $rowToday['total_sales_today'];

$qOverall = "SELECT IFNULL(SUM(total_amount),0) AS total_sales_overall, COUNT(*) AS num_sales FROM sales_record";
$resultOverall = $conn->query($qOverall);
$rowOverall = $resultOverall ? $resultOverall->fetch_assoc() : ['total_sales_overall' => 0, 'num_sales' => 0];
$totalSalesOverall = $rowOverall['total_sales_overall'];
$numSales = $rowOverall['num_sales'];
$averageSale = $numSales > 0 ? $totalSalesOverall / $numSales : 0;

// ---------- Sales Statement (Bank Ledger) ----------
$qLedger = "SELECT sale_id, order_number, sale_date, total_amount, payment_method FROM sales_record ORDER BY sale_date DESC";
$resultLedger = $conn->query($qLedger);
$ledgerData = [];
$cumulativeTotal = 0;
$maxSale = 0;
$minSale = null;
if ($resultLedger) {
    while ($row = $resultLedger->fetch_assoc()){
        $ledgerData[] = $row;
        $cumulativeTotal += $row['total_amount'];
        if ($row['total_amount'] > $maxSale) { $maxSale = $row['total_amount']; }
        if (is_null($minSale) || $row['total_amount'] < $minSale) { $minSale = $row['total_amount']; }
    }
}

// ---------- Sales Trend Data (Monthly) ----------
$trendLabels = [];
$trendData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date("Y-m", strtotime("-$i months"));
    $trendLabels[] = date("M Y", strtotime($month . "-01"));
    $queryTrend = "SELECT IFNULL(SUM(total_amount),0) as monthTotal FROM sales_record WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$month'";
    $resultTrend = $conn->query($queryTrend);
    if ($resultTrend) {
        $rowTrend = $resultTrend->fetch_assoc();
        $trendData[] = (float)$rowTrend['monthTotal'];
    } else {
        $trendData[] = 0;
    }
}

// ---------- Detailed Accounting Reports ----------
// A. Product Performance Report: Joins sales_record with products.
$productPerformanceQuery = "
    SELECT p.product_name, SUM(sr.quantity) AS total_units, SUM(sr.total_amount) AS total_revenue
    FROM sales_record sr
    JOIN products p ON sr.product_name = p.product_name
    GROUP BY sr.product_name
    ORDER BY total_revenue DESC
";
$resultProductPerf = $conn->query($productPerformanceQuery);
if (!$resultProductPerf) { die("Product Performance Query Failed: " . $conn->error); }
$productPerformance = [];
while ($row = $resultProductPerf->fetch_assoc()) {
    $productPerformance[] = $row;
}

// B. Customer  Report: Groups sales by customer.
$customerPerformanceQuery = "
    SELECT
        CONCAT(c.f_name, ' ', c.l_name) AS customer_name,
        COUNT(sr.sale_id) AS num_transactions,
        SUM(sr.total_amount) AS total_revenue
    FROM sales_record AS sr
    JOIN customers AS c ON sr.cust_id = c.cust_id
    WHERE sr.cust_id IS NOT NULL
    GROUP BY c.cust_id, c.f_name, c.l_name
    ORDER BY total_revenue DESC
    LIMIT 5
";
$resultCustomerPerf = $conn->query($customerPerformanceQuery);
if (!$resultCustomerPerf) { die("Customer Query Failed: " . $conn->error); }
$customerPerformance = [];
while ($row = $resultCustomerPerf->fetch_assoc()) {
    $customerPerformance[] = $row;
}

$ProductGrossProfitQuery ="
SELECT
  p.product_name,
  SUM(sr.quantity)           AS total_units,
  SUM(sr.total_amount)       AS total_revenue,
  SUM(COALESCE(pb.buying_price,0) * sr.quantity) AS total_cost,
  SUM(sr.total_amount)
    - SUM(COALESCE(pb.buying_price,0) * sr.quantity) AS gross_profit,
  CASE 
      WHEN SUM(sr.total_amount) = 0 THEN 0
      ELSE (SUM(sr.total_amount) - SUM(COALESCE(pb.buying_price,0) * sr.quantity)) / SUM(sr.total_amount) * 100 
  END AS gross_profit_margin
FROM sales_record AS sr
JOIN products      AS p
  ON sr.product_name = p.product_name
LEFT JOIN (
    -- pick one buying price per product (e.g. lowest)
    SELECT product_id, MIN(buying_price) AS buying_price
    FROM price
    GROUP BY product_id
) AS pb
  ON p.product_id = pb.product_id
GROUP BY
  p.product_name
ORDER BY
  gross_profit DESC
  ";
$resultgrossproduct = $conn->query($ProductGrossProfitQuery);
if (!$resultgrossproduct) {die("Gross Product Query Failed:". $conn->error);}
$Grossproduct =[];
while ($row = $resultgrossproduct->fetch_assoc()){
    $Grossproduct[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports | OceanGas Enterprise</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
      body { background: #f8f9fa; font-family: Arial, sans-serif; }
      .sidebar { width: 250px; background: #6a008a; color: #fff; padding: 20px; position: fixed; height: 100vh; }
      .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px; margin: 5px 0; }
      .sidebar a:hover { background: rgba(255,255,255,0.2); border-radius: 5px; }
      .sidebar a.active { background: rgba(255,255,255,0.3); font-weight: bold; }
      .content { margin-left: 260px; padding: 20px; }
      .kpi-card { min-width: 200px; }
      .export-buttons { margin-bottom: 20px; }
      .report-section { margin-bottom: 40px; }
      .summary-metrics { margin-top: 20px; }
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
<script>
  // If we're inside an iframe, window.self !== window.top
  if (window.self !== window.top) {
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Remove the sidebar element entirely
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) sidebar.remove();

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
      <h2>Sales Panel</h2>
      <a href="/OceanGas/staff/sales_staff_dashboard.php"><i class="fas fa-chart-line"></i> Cockpit</a>
      <a href="/OceanGas/staff/sales_invoice.php"><i class="fas fa-file-invoice"></i> Sales Invoice</a>
      <a href="/OceanGas/staff/stock_sales.php"><i class="fas fa-box"></i> Stock/Inventory</a>
      <a href="/OceanGas/staff/reports.php" class="<?php echo ($current_page === 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Reports</a>
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
      <div class="d-flex justify-content-between align-items-center mb-4">
          <h1>Reports</h1>
          <p>Welcome, <?= htmlspecialchars($salesName); ?></p>
      </div>
      
      <!-- KPI Section -->
      <div class="row mb-4">
          <div class="col-md-4">
              <div class="card text-white bg-primary kpi-card">
                  <div class="card-body">
                      <h5 class="card-title">Total Sales Today</h5>
                      <p class="card-text display-6">Ksh <?= number_format($totalSalesToday, 2); ?></p>
                  </div>
              </div>
          </div>
          <div class="col-md-4">
              <div class="card text-white bg-success kpi-card">
                  <div class="card-body">
                      <h5 class="card-title">Total Confirmed Sales</h5>
                      <p class="card-text display-6">Ksh <?= number_format($totalSalesOverall, 2); ?></p>
                  </div>
              </div>
          </div>
          <div class="col-md-4">
              <div class="card text-white bg-info kpi-card">
                  <div class="card-body">
                      <h5 class="card-title">Average Sale Value</h5>
                      <p class="card-text display-6">Ksh <?= number_format($averageSale, 2); ?></p>
                  </div>
              </div>
          </div>
      </div>
      
      <!-- Sales Trend (Monthly) KPI -->
      <div class="card p-5 mb-4 report-section">
          <h5>Sales Trend (Monthly)</h5>
          <canvas id="salesTrendChart" width="400" height="210"></canvas>
          <div class="export-buttons mb-3" style="position: absolute; bottom: -10px; right: 45px;">
              <a href="export_sales_trend_excel.php" class="btn btn-outline-success btn-sm" style="position:top-right">Export Excel</a>
              <a href="export_sales_trend_pdf.php" class="btn btn-outline-danger btn-sm">Export PDF</a>
          </div>
      </div>
      
      <!-- Sales Statement (Bank Ledger) -->
      <div class="card p-3 mb-4 report-section">
          <h5>Sales Statement (Bank Ledger)</h5>
          <div class="table-responsive">
              <table id="ledgerTable" class="table table-bordered">
                  <thead class="table-dark">
                      <tr>
                          <th>Sale ID</th>
                          <th>Order Number</th>
                          <th>Sale Date</th>
                          <th>Total Amount (Ksh)</th>
                          <th>Payment Method</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php if (!empty($ledgerData)): ?>
                          <?php foreach ($ledgerData as $row): ?>
                              <tr>
                                  <td><?= htmlspecialchars($row['sale_id']); ?></td>
                                  <td><?= htmlspecialchars($row['order_number']); ?></td>
                                  <td><?= htmlspecialchars($row['sale_date']); ?></td>
                                  <td><?= formatCurrency($row['total_amount']); ?></td>
                                  <td><?= htmlspecialchars($row['payment_method']); ?></td>
                              </tr>
                          <?php endforeach; ?>
                      <?php else: ?>
                          <tr><td colspan="5">No sales statement data available.</td></tr>
                      <?php endif; ?>
                  </tbody>
                  <tfoot>
                      <tr>
                          <th colspan="3" class="text-end">Cumulative Total:</th>
                          <th colspan="2">Ksh <?= number_format($cumulativeTotal, 2); ?></th>
                      </tr>
                  </tfoot>
              </table>
          </div>
          <div class="summary-metrics">
              <p><strong>Number of Transactions:</strong> <?= $numSales; ?></p>
              <p><strong>Average Sale Value:</strong> Ksh <?= number_format($averageSale, 2); ?></p>
              <p><strong>Maximum Sale Value:</strong> Ksh <?= number_format($maxSale, 2); ?></p>
              <p><strong>Minimum Sale Value:</strong> <?= is_null($minSale) ? "N/A" : 'Ksh ' . number_format($minSale, 2); ?></p>
          </div>
          <div class="export-buttons mt-2">
              <a href="export_ledger_excel.php" class="btn btn-outline-success btn-sm">Export Excel</a>
              <a href="export_ledger_pdf.php" class="btn btn-outline-danger btn-sm">Export PDF</a>
          </div>
      </div>
      
      <!-- Detailed Accounting Reports -->
      <div class="report-section">
          <h3>Detailed Accounting Reports</h3>
          
          <!-- A. Product Performance Report -->
          <div class="card mb-5" style="padding-bottom: 30px;">
              <div class="card-header bg-dark text-white">Product Performance Report</div>
              <div class="card-body">
                  <div class="table-responsive">
                      <table id="productPerformanceTable" class="table table-sm">
                          <thead>
                              <tr>
                                  <th>Product</th>
                                  <th>Total Units Sold</th>
                                  <th>Total Revenue (Ksh)</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if (!empty($productPerformance)): ?>
                                  <?php foreach ($productPerformance as $row): ?>
                                      <tr>
                                          <td><?= htmlspecialchars($row['product_name']); ?></td>
                                          <td><?= number_format($row['total_units']); ?></td>
                                          <td><?= formatCurrency($row['total_revenue']); ?></td>
                                      </tr>
                                  <?php endforeach; ?>
                              <?php else: ?>
                                  <tr><td colspan="3">No product performance data available.</td></tr>
                              <?php endif; ?>
                          </tbody>
                      </table>
                  </div>
                  <div class="export-buttons mt-2"style="position: absolute; bottom: -10px; right: 15px;">
                      <a href="export_product_performance_excel.php" class="btn btn-outline-success btn-sm">Export Excel</a>
                      <a href="export_product_performance_pdf.php" class="btn btn-outline-danger btn-sm">Export PDF</a>
                  </div>
              </div>
          </div>
          
          <!-- B. Customer Report -->
          <div class="card mb-3">
              <div class="card-header bg-dark text-white">Customer Report</div>
              <div class="card-body">
                  <div class="table-responsive">
                      <table id="customerPerformanceTable" class="table table-sm">
                          <thead>
                              <tr>
                                  <th>Customer Name</th>
                                  <th>Transactions</th>
                                  <th>Total Revenue (Ksh)</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if (!empty($customerPerformance)): ?>
                                  <?php foreach ($customerPerformance as $row): ?>
                                      <tr>
                                          <td><?= htmlspecialchars($row['customer_name']); ?></td>
                                          <td><?= number_format($row['num_transactions']); ?></td>
                                          <td><?= formatCurrency($row['total_revenue']); ?></td>
                                      </tr>
                                  <?php endforeach; ?>
                              <?php else: ?>
                                  <tr><td colspan="3">No customer data available.</td></tr>
                              <?php endif; ?>
                          </tbody>
                      </table>
                  </div>
                  <div class="export-buttons mt-2">
                      <a href="export_customer_performance_excel.php" class="btn btn-outline-success btn-sm">Export Excel</a>
                      <a href="export_customer_performance_pdf.php" class="btn btn-outline-danger btn-sm">Export PDF</a>
                  </div>
              </div>
          </div>
                    <!-- C. Gross Profit per product Report -->
                    <div class="card mb-3">
              <div class="card-header bg-dark text-white">Gross Profit per product Report</div>
              <div class="card-body">
                  <div class="table-responsive">
                      <table id="grossProfitTable" class="table table-sm">
                          <thead>
                              <tr>
                                  <th>Product name</th>
                                  <th>Total Units</th>
                                  <th>Total Revenue (Ksh)</th>
                                  <th>Total Cost (Ksh)</th>
                                  <th>Gross Profit</th>
                                  <th>Gross Profit Margin</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if (!empty($Grossproduct)): ?>
                                  <?php foreach ($Grossproduct as $row): ?>
                                      <tr>
                                          <td><?= htmlspecialchars($row['product_name']); ?></td>
                                          <td><?= number_format($row['total_units']); ?></td>
                                          <td><?= formatCurrency($row['total_revenue']); ?></td>
                                          <td><?= formatCurrency($row['total_cost']);?></td>
                                          <td><?= formatCurrency($row['gross_profit']);?></td>
                                          <td>
                                              <?php
                                                  $margin = $row['gross_profit_margin'];
                                                  $marginFormatted = number_format($margin, 2) . '%';
                                                  if ($margin < 0) {
                                                      echo '<span style="color: #dc3545; font-weight: bold;"><i class="fas fa-arrow-down"></i> ' . $marginFormatted . '</span>';
                                                  } elseif ($margin > 0) {
                                                      echo '<span style="color: #28a745; font-weight: bold;"><i class="fas fa-arrow-up"></i> ' . $marginFormatted . '</span>';
                                                  } else {
                                                      echo '<span style="color: #6c757d; font-weight: bold;"><i class="fas fa-minus"></i> ' . $marginFormatted . '</span>';
                                                  }
                                              ?>
                                          </td>
                                      </tr>
                                  <?php endforeach; ?>
                              <?php else: ?>
                                  <tr><td colspan="6">No gross profit data available.</td></tr>
                              <?php endif; ?>
                          </tbody>
                      </table>
                  </div>
                  <div class="export-buttons mt-2">
                      <a href="export_gross_profit_excel.php" class="btn btn-outline-success btn-sm">Export Excel</a>
                      <a href="export_gross_profit_pdf.php" class="btn btn-outline-danger btn-sm">Export PDF</a>
                  </div>
              </div>
          </div>

      </div>
      
  </div>
  
  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>
  <script>
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
      // Initialize DataTables for Ledger, Product Performance, and Customer  tables
      $(document).ready(function(){
          $('#ledgerTable').DataTable({ lengthChange: true, paging: true, searching: true, info: true });
          $('#productPerformanceTable').DataTable({ lengthChange: true, paging: true, searching: true, info: true });
          $('#customerPerformanceTable').DataTable({ lengthChange: true, paging: true, searching: true, info: true });
          $('#grossProfitTable').DataTable({ lengthChange: true, paging: true, searching: true, info: true });
      });
      
      // Sales Trend Bar Chart (Monthly)
      const trendLabels = <?php echo json_encode($trendLabels); ?>;
      const trendData = <?php echo json_encode($trendData); ?>;
      const ctx = document.getElementById('salesTrendChart').getContext('2d');
      const salesTrendChart = new Chart(ctx, {
          type: 'bar',
          data: {
              labels: trendLabels,
              datasets: [{
                  label: 'Sales (Ksh)',
                  data: trendData,
                  backgroundColor: 'rgba(54, 162, 235, 0.6)',
                  borderColor: 'rgba(54, 162, 235, 1)',
                  borderWidth: 1
              }]
          },
          options: {
              responsive: true,
              scales: { y: { beginAtZero: true } }
          }
      });
      
      // Filter Table by Payment Method for Orders Summary
      document.getElementById('filterMethod').addEventListener('change', function(){
          const filter = this.value;
          const rows = document.querySelectorAll('table.table-striped tbody tr');
          rows.forEach(function(row) {
              // Assuming payment method is in the 5th column
              if (filter === "" || row.cells[4].textContent.trim() === filter) {
                  row.style.display = "";
              } else {
                  row.style.display = "none";
              }
          });
      });
      
  </script>
</body>
</html> 