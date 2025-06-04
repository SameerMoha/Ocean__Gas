<?php
// Define the current page file name
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// Check that a staff user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Query the stock table for inventory details + the image_path
$query = "SELECT * FROM products ORDER BY product_id";
$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}

// Base URL & filesystem prefix for image checks
$baseUrl  = '/OceanGas/';
$fsPrefix = $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock / Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
  <style>
    body { background-color: #f8f9fa; font-family: 'Arial', sans-serif; }
    .sidebar { width: 250px; background: #6a008a; color: white; padding: 20px; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; }
    .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .sidebar a:hover { background: rgba(255, 255, 255, 0.2); }
    .sidebar a.active { background: rgba(255,255,255,0.3); font-weight: bold; }
    .content {  padding: 20px; }
    .table th, .table td { vertical-align: middle; }
    .table img { height: 60px; width: auto; object-fit: contain; display: block; margin: auto; }
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
<div class="d-flex">
  <div class="sidebar"> 
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/OceanGas/staff/stock_admin.php"class="<?php echo ($current_page === 'stock_admin.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Stock/Inventory</a>
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
  style="display:none; width:100%; height:1600px; border:none;"
></iframe>
  

  <div id="mainContent" class="content ">
    <div class="container-fluid">
      <h1 class="mb-4">Inventory Stock</h1>
      <div class="mb-3">
        <input type="text" id="liveSearch" class="form-control" placeholder="Search product name...">
      </div>
      <div class="table-responsive">
        <table id="stockTable" class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr>
              <th scope="col">Product Image</th>
              <th scope="col">Product Name</th>
              <th scope="col">Available Stock</th>
              <th scope="col">Description</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
              <?php
                $fsPath = $fsPrefix . ltrim($row['image_path'], '/');
                $image_url = (!empty($row['image_path']) && file_exists($fsPath))
                  ? $baseUrl . ltrim($row['image_path'], '/')
                  : $baseUrl . 'assets/images/default.jpg';
              ?>
              <tr>
                <td><img src="<?php echo htmlspecialchars($image_url); ?>" alt="Product Image"></td>
                <td class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                <td>High quality LPG cylinder. Check back regularly for updated stock levels.</td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script>
  $(document).ready(function() {
    const table = $('#stockTable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        'copy', 'csv', 'excel',
        {
          extend: 'pdfHtml5',
          orientation: 'landscape',
          pageSize: 'A4',
          customize: function(doc) {
            const imgCells = document.querySelectorAll('#stockTable tbody tr td:first-child img');
            const body = [];
            $('#stockTable thead tr').each(function() {
              const row = [];
              $(this).find('th').each(function() {
                row.push({ text: $(this).text(), style: 'tableHeader' });
              });
              body.push(row);
            });
            $('#stockTable tbody tr').each(function(index) {
              const row = [];
              const img = imgCells[index];
              const canvas = document.createElement('canvas');
              canvas.width = img.naturalWidth;
              canvas.height = img.naturalHeight;
              const ctx = canvas.getContext('2d');
              ctx.drawImage(img, 0, 0);
              const dataURL = canvas.toDataURL();
              row.push({ image: dataURL, width: 60 });
              $(this).find('td:not(:first-child)').each(function() {
                row.push($(this).text());
              });
              body.push(row);
            });
            doc.content[1].table.body = body;
          }
        },
        'print'
      ],
      responsive: true,
      paging: true,
      ordering: true
    });

    $('#liveSearch').on('keyup', function () {
      table.columns(1).search(this.value).draw();
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
  
</script>
</body>
</html>
