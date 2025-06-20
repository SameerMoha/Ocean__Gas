<?php
session_start();
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}

// DB connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}

// Safe query helper
function safeQuery($conn, $sql) {
    $res = $conn->query($sql);
    if ($res === false) {
        error_log("SQL Error [{$sql}]: " . $conn->error);
        return null;
    }
    return $res;
}

// 1) Core KPIs using unified funds table
$row = safeQuery($conn, "SELECT IFNULL(SUM(funds_in),0) AS v FROM funds")
       ->fetch_assoc() ?? ['v' => 0];
$total_allocated = (float)$row['v'];

$row = safeQuery($conn, "SELECT IFNULL(SUM(funds_out),0) AS v FROM funds")
       ->fetch_assoc() ?? ['v' => 0];
$total_used = (float)$row['v'];

$balance = $total_allocated - $total_used;

// 2) Average Cost per Purchase (unchanged)
$row = safeQuery($conn, "
    SELECT AVG(pr.buying_price * ph.quantity) AS avg_cost
      FROM purchase_history ph
      JOIN products p      ON ph.product = p.product_name
      JOIN price pr        ON pr.product_id = p.product_id
                           AND pr.supplier_id = ph.supplier_id
")->fetch_assoc() ?? ['avg_cost' => 0];
$avg_cost = (float)$row['avg_cost'];

// 3) Low-Stock Count (unchanged)
$row = safeQuery($conn, "
    SELECT COUNT(*) AS cnt
      FROM products
     WHERE (product_name LIKE '%6kg%'  AND quantity < 149)
        OR (product_name LIKE '%12kg%' AND quantity < 100)
")->fetch_assoc() ?? ['cnt' => 0];
$low_count = (int)$row['cnt'];

// 4) Inventory Turnover (unchanged)
$row = safeQuery($conn, "
    SELECT IFNULL(SUM(ph.quantity),0) AS sold
      FROM purchase_history ph
     WHERE ph.purchase_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
")->fetch_assoc() ?? ['sold' => 0];
$sold12 = (int)$row['sold'];

$row = safeQuery($conn, "SELECT IFNULL(AVG(quantity),0) AS avg_inv FROM products")
       ->fetch_assoc() ?? ['avg_inv' => 0];
$avg_inv = (float)$row['avg_inv'];

$turnover = $avg_inv > 0 ? round($sold12 / $avg_inv, 2) : 0;

// 5) Top 5 Suppliers by Spend (unchanged)
$top_labels = $top_data = [];
$res = safeQuery($conn, "
    SELECT s.name,
           SUM(pr.buying_price * ph.quantity) AS total_spent
      FROM purchase_history ph
      JOIN suppliers s    ON ph.supplier_id = s.id
      JOIN products p     ON ph.product = p.product_name
      JOIN price pr       ON pr.product_id = p.product_id
                         AND pr.supplier_id = s.id
  GROUP BY s.name
  ORDER BY total_spent DESC
  LIMIT 5
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $top_labels[] = $r['name'];
        $top_data[]   = (float)$r['total_spent'];
    }
}

// 6) Top Products by Volume & Spend (unchanged)
$prod_labels = $prod_vol = $prod_spend = [];
$res = safeQuery($conn, "
    SELECT ph.product,
           SUM(ph.quantity)                  AS volume,
           SUM(pr.buying_price * ph.quantity) AS spend
      FROM purchase_history ph
      JOIN products p  ON ph.product = p.product_name
      JOIN price pr    ON pr.product_id = p.product_id
                      AND pr.supplier_id = ph.supplier_id
  GROUP BY ph.product
  ORDER BY volume DESC
  LIMIT 5
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $prod_labels[] = $r['product'];
        $prod_vol[]    = (int)$r['volume'];
        $prod_spend[]  = (float)$r['spend'];
    }
}

// 7) Monthly Spend Trend (last 12 months) using funds_out
$mon_labels = $mon_data = [];
$res = safeQuery($conn, "
    SELECT DATE_FORMAT(transaction_date,'%Y-%m') AS m,
           SUM(funds_out)               AS tot
      FROM funds
     WHERE source_type = 'deduction'
       AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
  GROUP BY m
  ORDER BY m
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $mon_labels[] = $r['m'];
        $mon_data[]   = (float)$r['tot'];
    }
}

// 8) Alerts
$alerts = [];
if ($total_used > 0.9 * $total_allocated) {
    $alerts[] = "⚠️ You've used over 90% of your procurement budget.";
}
if ($low_count > 0) {
    $alerts[] = "⚠️ {$low_count} low-stock item(s) detected.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Financial Overview</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .sidebar { width:250px; position:fixed; top:0; bottom:0; background:#6a008a; color:#fff; padding:20px; }
    .sidebar a { color:#fff; display:block; padding:10px; border-radius:4px; text-decoration:none; }
    .sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.2); }
    .content { margin-left:270px; padding:20px; }
    .stat-card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); text-align:center; }
    .alert-banner { background:#fff3cd; border:1px solid #ffeeba; color:#856404; padding:10px; border-radius:4px; margin-bottom:20px; }
    canvas { max-height:300px; }
  </style>
</head>
<body>
  <script>
  if (window.self !== window.top) {
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar = document.querySelector('.sidebar'); if (sidebar) sidebar.remove();
      const content = document.querySelector('.content'); if (content) { content.style.marginLeft = '0'; content.style.width='100%'; content.style.padding='20px'; }
    });
  }
  </script>
  <div class="sidebar">
    <h2>Procurement Panel</h2>
    <?php $cur = basename($_SERVER['PHP_SELF']); ?>
    <a href="procurement_staff_dashboard.php" class="<?= $cur==='procurement_staff_dashboard.php'?'active':'' ?>"><i class="fas fa-truck"></i> Dashboard</a>
    <a href="stock_procurement.php"               class="<?= $cur==='stock_procurement.php'?'active':'' ?>"><i class="fas fa-box"></i> Stock/Inventory</a>
    <a href="purchase_history_reports.php"         class="<?= $cur==='purchase_history_reports.php'?'active':'' ?>"><i class="fas fa-receipt"></i> Purchase History</a>
    <a href="suppliers.php"                        class="<?= $cur==='suppliers.php'?'active':'' ?>"><i class="fas fa-industry"></i> Suppliers</a>
    <a href="financial_overview.php"               class="<?= $cur==='financial_overview.php'?'active':'' ?>"><i class="fas fa-credit-card"></i> Financial Overview</a>
  </div>

  <div class="content">
    <h2 class="mb-4">Financial Overview (KES)</h2>
    <?php foreach ($alerts as $a): ?><div class="alert-banner"><?= $a ?></div><?php endforeach; ?>

    <div class="row g-3 mb-4">
      <?php foreach ([
        ['Allocated', $total_allocated],
        ['Used',      $total_used],
        ['Balance',   $balance],
        ['Low-Stock Items',   $low_count],
      ] as $st): ?>
      <div class="col-md-3">
        <div class="stat-card">
          <h6><?= $st[0] ?></h6>
          <p class="fs-5 fw-bold"><?php
            if (strpos($st[0],'Items')!==false) echo number_format($st[1]);
            elseif (strpos($st[0],'Turnover')!==false) echo number_format($st[1],2);
            else echo 'KES '.number_format($st[1],2);
          ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="row gy-4 mb-4">
      <div class="col-md-6">
        <div class="stat-card">
          <h5>Monthly Spend Trend</h5>
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card">
          <h5>Top 5 Suppliers by Spend</h5>
          <canvas id="topSuppliersChart"></canvas>
        </div>
      </div>
    </div>

    <div class="row gy-4">
      <div class="col-md-6">
        <div class="stat-card">
          <h5>Top Products by Volume</h5>
          <canvas id="prodVolChart"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card">
          <h5>Top Products by Spend</h5>
          <canvas id="prodSpendChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    new Chart(document.getElementById('monthlyChart'), {
      type: 'line', data: { labels: <?= json_encode($mon_labels) ?>, datasets: [{ label:'Spend (KES)', data:<?= json_encode($mon_data) ?>, fill:false }] }, options:{responsive:true}
    });
    new Chart(document.getElementById('topSuppliersChart'), {
      type: 'bar', data: { labels: <?= json_encode($top_labels) ?>, datasets: [{ label:'Total Spent', data:<?= json_encode($top_data) ?> }] }, options:{indexAxis:'y',responsive:true}
    });
    new Chart(document.getElementById('prodVolChart'), {
      type: 'bar', data: { labels: <?= json_encode($prod_labels) ?>, datasets: [{ label:'Volume', data:<?= json_encode($prod_vol) ?> }] }, options:{responsive:true}
    });
    new Chart(document.getElementById('prodSpendChart'), {
      type: 'bar', data: { labels: <?= json_encode($prod_labels) ?>, datasets: [{ label:'Spend (KES)', data:<?= json_encode($prod_spend) ?> }] }, options:{responsive:true}
    });
  </script>
</body>
</html>
