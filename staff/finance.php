<?php 
$current_page = basename($_SERVER['PHP_SELF']);
session_start();

$threshold_balance = 2000000;           // KES 2M safety line
$admin_email       = 'admin@oceangas.com';

// --- Database Connection ---
$host='localhost'; $db='oceangas'; $user='root'; $pass='';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Authentication
if(!isset($_SESSION['staff_username'])){
    header("Location:/OceanGas/staff/staff_login.php");
    exit();
}

// Filters
$sale_day       = $_GET['sale_day']   ?? date('Y-m-d');
$safe_day       = $conn->real_escape_string($sale_day);
$filter_product = $_GET['product']    ?? '';
$safe_product   = $conn->real_escape_string($filter_product);

// 0) Products list
$products = [];
$res_prod = $conn->query("
  SELECT DISTINCT product_name 
    FROM sales_record 
   ORDER BY product_name
");
while($row=$res_prod->fetch_assoc()){
    $products[] = $row['product_name'];
}

// 1) Daily Revenue
$where_product = $safe_product ? "AND product_name='{$safe_product}'" : "";
$daily_sales   = []; $daily_total=0.00;

$detail_sql = "
  SELECT sale_date,order_number,customer_name,product_name,
         quantity,payment_method,total_amount
  FROM sales_record
  WHERE DATE(sale_date)='$safe_day' $where_product
  ORDER BY sale_date ASC
";
if(!$detail_res=$conn->query($detail_sql)) die("Sales query error: ".$conn->error);
while($r=$detail_res->fetch_assoc()){
    $daily_sales[]=$r;
}
$sum_sql = "
  SELECT IFNULL(SUM(total_amount),0) AS daily_total
  FROM sales_record
  WHERE DATE(sale_date)='$safe_day' $where_product
";
$res_sum=$conn->query($sum_sql);
$daily_total=$res_sum->fetch_assoc()['daily_total'];


// 2) Daily Procurement
$procurement_history_day = [];
$sql = "
  SELECT 
    ph.purchase_date,
    ph.product,
    ph.quantity,
    s.name           AS supplier,
    u.username       AS purchased_by,
    (pr.buying_price * ph.quantity) AS total_cost
  FROM purchase_history ph
  JOIN suppliers s 
    ON ph.supplier_id = s.id
  JOIN users u     
    ON ph.purchased_by = u.id
  JOIN products p  
    ON ph.product    = p.product_name
  JOIN price pr    
    ON pr.product_id  = p.product_id
   AND pr.supplier_id = ph.supplier_id
  WHERE DATE(ph.purchase_date) = '{$safe_day}'
  ORDER BY ph.purchase_date ASC
";
$proc_res = $conn->query($sql);
if(!$proc_res) die("Procurement query error: ".$conn->error);
while($p = $proc_res->fetch_assoc()){
    $procurement_history_day[] = $p;
}

$sql_sum = "
  SELECT IFNULL(SUM(pr.buying_price * ph.quantity),0) AS daily_procurement_total
  FROM purchase_history ph
  JOIN products p  
    ON ph.product    = p.product_name
  JOIN price pr    
    ON pr.product_id  = p.product_id
   AND pr.supplier_id = ph.supplier_id
  WHERE DATE(ph.purchase_date) = '{$safe_day}'
";
$res_proc = $conn->query($sql_sum);
if(!$res_proc) die("Procurement sum error: ".$conn->error);
$daily_procurement_total = $res_proc->fetch_assoc()['daily_procurement_total'];

// 3) Funds summary (unified funds table)
$sql = "
  SELECT
    IFNULL(SUM(funds_in),  0) AS total_allocated,
    IFNULL(SUM(funds_out), 0) AS total_used
  FROM funds
";
$result = $conn->query($sql);
if (! $result) {
    die("Funds summary query failed: " . $conn->error);
}
$row = $result->fetch_assoc();

$total_allocated = (float)$row['total_allocated'];
$total_used      = (float)$row['total_used'];
$balance         = $total_allocated - $total_used;
$usage_percent   = $total_allocated
    ? round($total_used / $total_allocated * 100)
    : 0;


// 4) Low-balance alert
if($balance<$threshold_balance){
    @mail($admin_email,"OceanGas: Low Cash Balance",
        "Current cash balance is KES ".number_format($balance,2).".");
}

// 5) Top 5 Products
$top_products=[]; 
$tp_res=$conn->query("
  SELECT product_name,IFNULL(SUM(total_amount),0) AS total_sales
  FROM sales_record
  WHERE DATE(sale_date)='$safe_day'
  GROUP BY product_name
  ORDER BY total_sales DESC
  LIMIT 5
");
while($tp=$tp_res->fetch_assoc()) $top_products[]=$tp;


// 6) 7-day trend:  
$weekly_revenues = [];
$weekly_procure  = [];
for($i=6; $i>=0; $i--){
    $day = date('Y-m-d', strtotime("-{$i} days"));

    $rev = $conn->query("
      SELECT IFNULL(SUM(total_amount),0) AS rev 
        FROM sales_record 
       WHERE DATE(sale_date) = '{$day}'
    ")->fetch_assoc()['rev'];

    $pur = $conn->query("
      SELECT IFNULL(SUM(pr.buying_price * ph.quantity),0) AS pur
      FROM purchase_history ph
      JOIN products p  
        ON ph.product    = p.product_name
      JOIN price pr    
        ON pr.product_id  = p.product_id
       AND pr.supplier_id = ph.supplier_id
      WHERE DATE(ph.purchase_date) = '{$day}'
    ")->fetch_assoc()['pur'];

    $weekly_revenues[] = ['date'=>$day, 'value'=>$rev];
    $weekly_procure[]  = ['date'=>$day, 'value'=>$pur];
}

// 7) Allocation history
$allocation_history = [];

$sql = "
  SELECT
    transaction_date    AS allocated_date,
    funds_in            AS allocated_amount,
    purchased_by,
    note
  FROM funds
  WHERE source_type = 'allocation'
  ORDER BY transaction_date DESC
";
$alloc_res = $conn->query($sql);
if (! $alloc_res) {
    die("Allocation history query failed: " . $conn->error);
}

while ($a = $alloc_res->fetch_assoc()) {
    $allocation_history[] = $a;
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Finance Control Room</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


  <style>
    body  {font-family:'Arial',sans-serif;background:#f4f7fa;margin:0;display:flex;}
    .sidebar{width:250px;background:#6a008a;color:#fff;padding:20px;min-height:100vh;position:fixed;}
    .sidebar a{color:#fff;text-decoration:none;padding:10px;display:block;margin:5px 0;}
    .sidebar a.active{background:rgba(255,255,255,0.3);font-weight:bold;border-radius:6px;}
    .main-content{margin-left:260px;padding:20px;width:calc(100% - 260px);}
    .card-reservoir{border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
    .table-analog th{background:#343a40;color:#fff;}
    .pag-btn{margin:0 5px;}
    .export-buttons{margin-top:1rem;text-align:right;}
    .sidebar a:hover {
      background: rgba(255, 255, 255, 0.2);
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

.dropdown-container {
    display: none;
    background-color:#6a008a;
    padding-left: 20px;
}

.dropdown-btn.active, .dropdown-btn2.active + .dropdown-container {
    display: block;
    
}

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
<nav class="sidebar">
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/OceanGas/staff/stock_admin.php"><i class="fas fa-box"></i> Stock/Inventory</a>
    <a href="/OceanGas/staff/users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="/OceanGas/staff/finance.php" class="<?=($current_page==='finance.php')?'active':''?>"><i class="fas fa-dollar-sign"></i> Finance</a>
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
  </nav>
  <div class="content" style=" padding: 20px; width: 100%;">
  <iframe 
  id="mainFrame"
  name="main-frame"
  src="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1"
  style="margin-left:230px; display:none; width:85%; height:fit-content; border:none; "
></iframe>
<div id="mainContent">
  <div class="main-content">
        <header><h1>Finance Control Room</h1></header>

 <div class="card card-reservoir mb-4 p-4 bg-white text-center">
      <h3>Cash Balance</h3>
      <h2 class="display-4">KES <?=number_format($balance,2)?></h2>
      <?php if($balance<$threshold_balance):?>
      <div class="alert alert-warning mt-3">
        ⚠️ Low balance! Minimum is KES <?=number_format($threshold_balance)?>.
      </div>
      <?php endif;?>
    </div>
    <!-- Filters -->
    <div class="card mb-4 p-3">
      <form method="GET" class="row g-3">
        <div class="col-md-3">
          <label>Date</label>
          <input type="date" name="sale_day" class="form-control" value="<?=htmlspecialchars($sale_day)?>">
        </div>
        <div class="col-md-3">
          <label>Product</label>
          <select name="product" class="form-select">
            <option value="">-- All Products --</option>
            <?php foreach($products as $prod):?>
            <option value="<?=htmlspecialchars($prod)?>"<?= $prod===$safe_product?' selected':''?>>
              <?=htmlspecialchars($prod)?>
            </option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="col-md-2 align-self-end">
          <button class="btn btn-primary w-100">Apply Filters</button>
        </div>
      </form>
    </div>
    <!-- Revenue -->
    <div class="card mb-4 p-4 bg-white">
      <h4><i class="fas fa-chart-simple"></i> Revenue <small>(<?=htmlspecialchars($sale_day)?>)</small></h4>
      <p class="text-muted">Total: <strong>KES <?=number_format($daily_total,2)?></strong></p>
      <?php if($daily_sales):?>
      <table id="revenueTable" class="table table-bordered table-analog">
        <thead><tr>
          <th>Time</th><th>Order#</th><th>Customer</th>
          <th>Product</th><th>Qty</th><th>Payment</th><th>Amount</th>
        </tr></thead>
        <tbody>
          <?php foreach($daily_sales as $s):?>
          <tr>
            <td><?=date('H:i',strtotime($s['sale_date']))?></td>
            <td><?=htmlspecialchars($s['order_number'])?></td>
            <td><?=htmlspecialchars($s['customer_name'])?></td>
            <td><?=htmlspecialchars($s['product_name'])?></td>
            <td><?=intval($s['quantity'])?></td>
            <td><?=htmlspecialchars($s['payment_method'])?></td>
            <td><?=number_format($s['total_amount'],2)?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <div class="text-end">
        <button id="revPrev" class="btn btn-sm btn-outline-primary pag-btn">Previous</button>
        <button id="revNext" class="btn btn-sm btn-outline-primary pag-btn">Next</button>
      </div>
      <?php else:?>
      <p class="text-muted">No revenue recorded.</p>
      <?php endif;?>
    </div>

    <!-- Procurement Spend table -->
    <div class="card mb-4 p-4 bg-white">
      <h4><i class="fas fa-water"></i> Procurement Spend 
        <small>(<?=htmlspecialchars($sale_day)?>)</small>
      </h4>
      <p class="text-muted">
        Total: <strong>KES <?=number_format($daily_procurement_total,2)?></strong>
      </p>
      <?php if($procurement_history_day): ?>
      <table id="procTable" class="table table-bordered">
        <thead>
          <tr>
            <th>Time</th><th>Product</th><th>Qty</th>
            <th>Supplier</th><th>By</th><th>Cost</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($procurement_history_day as $p): ?>
            <tr>
              <td><?=date('H:i',strtotime($p['purchase_date']))?></td>
              <td><?=htmlspecialchars($p['product'])?></td>
              <td><?=intval($p['quantity'])?></td>
              <td><?=htmlspecialchars($p['supplier'])?></td>
              <td><?=htmlspecialchars($p['purchased_by'])?></td>
              <td><?=number_format($p['total_cost'],2)?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p class="text-muted">No procurement recorded.</p>
      <?php endif; ?>
    </div>

    <!-- Export Buttons for reports -->
    <div class="export-buttons">
      <a href="export_report_pdf.php?sale_day=<?=urlencode($sale_day)?>
         &product=<?=urlencode($filter_product)?>" 
         class="btn btn-danger me-2" target="_blank">
        <i class="fas fa-file-pdf"></i> Export PDF
      </a>
      <a href="export_report_excel.php?sale_day=<?=urlencode($sale_day)?>
         &product=<?=urlencode($filter_product)?>" 
         class="btn btn-success">
        <i class="fas fa-file-excel"></i> Export Excel
      </a>
    </div>
    
 <!-- 7-Day Cash Flow Trend -->
    <div class="card mb-4 p-4 bg-white">
      <h4><i class="fas fa-chart-line"></i> 7-Day Cash Flow Trend</h4>
      <canvas id="trendChart" height="100"></canvas>
      <script>
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx,{type:'line',data:{
          labels:<?=json_encode(array_column($weekly_revenues,'date'))?>,
          datasets:[
            {label:'Revenue',data:<?=json_encode(array_column($weekly_revenues,'value'))?>,tension:0.3,fill:false},
            {label:'Procurement',data:<?=json_encode(array_column($weekly_procure,'value'))?>,tension:0.3,fill:false}
          ]
        },options:{scales:{y:{beginAtZero:true}}}});
      </script>
    </div>

    <!-- Procurement Funds Allocation -->
    <div class="card mb-4 p-4 bg-white">
      <h4><i class="fas fa-wallet"></i> Procurement Funds Allocation</h4>
      <div class="row text-center mb-3">
        <div class="col-md-4">
          <h6>Total Allocated</h6>
          <p class="display-6">KES <?=number_format($total_allocated,2)?></p>
        </div>
        <div class="col-md-4">
          <h6>Total Used</h6>
          <p class="display-6">KES <?=number_format($total_used,2)?></p>
        </div>
        <div class="col-md-4">
          <h6>Remaining Balance</h6>
          <p class="display-6">KES <?=number_format($balance,2)?></p>
        </div>
      </div>
      <div class="mb-3">
        <h6>Usage: <?=$usage_percent?>%</h6>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width:<?=$usage_percent?>%" 
               aria-valuenow="<?=$usage_percent?>" aria-valuemin="0" aria-valuemax="100">
            <?=$usage_percent?>%
          </div>
        </div>
      </div>
      <form action="allocate_funds.php" method="POST" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label>Amount (KES)</label>
          <input type="number" name="amount" step="0.01" min="0" class="form-control" placeholder="e.g. 500000" required>
        </div>
        <div class="col-md-6">
          <label>Note</label>
          <input type="text" name="note" class="form-control" placeholder="e.g. May restock">
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100">Allocate</button>
        </div>
      </form>
      <button class="btn btn-link mt-3" data-bs-toggle="modal" data-bs-target="#allocationHistoryModal">
        View Allocation History
      </button>
  </div>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  </div>
  </div>
  <script>
    function paginateTable(tableId,prevId,nextId,pageSize=10){
      const rows=Array.from(document.querySelectorAll(`#${tableId} tbody tr`));
      let page=0, totalPages=Math.ceil(rows.length/pageSize);
      function render(){
        rows.forEach((r,i)=>r.style.display=(i>=page*pageSize&&i<(page+1)*pageSize)?'':'none');
        document.getElementById(prevId).disabled=page===0;
        document.getElementById(nextId).disabled=page+1>=totalPages;
      }
      document.getElementById(prevId).addEventListener('click',()=>{page=Math.max(0,page-1);render();});
      document.getElementById(nextId).addEventListener('click',()=>{page=Math.min(totalPages-1,page+1);render();});
      render();
    }
    document.addEventListener('DOMContentLoaded',()=>{
      paginateTable('revenueTable','revPrev','revNext');
      paginateTable('procTable','procPrev','procNext');
    });

       // Simple toggle script for dropdown
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
