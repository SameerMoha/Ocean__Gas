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
$query = "
  SELECT * FROM products
  ORDER By product_id 
";
$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}

// Base URL & filesystem prefix for image checks
$baseUrl  = '/OceanGas/';
$fsPrefix = $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/';  // e.g. C:\xampp\htdocs\OceanGas\
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock / Inventory</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* General Body Styles */
    body {
      background-color: #f8f9fa;
      font-family: 'Arial', sans-serif;
      margin: 0;
    }
    /* Flex container to hold sidebar and main content */
    .d-flex {
      min-height: 100vh;
    }
    /* Sidebar styles */
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      height: 100vh; /* Full height */
      position: sticky;
      top: 0; /* Stick to the top */
      overflow-y: auto; /* Allows scrolling if content is too long */
    }
    .sidebar h2 {
      margin-top: 0;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 10px;
      margin: 5px 0;
      border-radius: 5px;
    }
    .sidebar a:hover {
      background: rgba(255, 255, 255, 0.2);
    }
    /* Highlight the active page */
    .sidebar a.active {
      background: rgba(255,255,255,0.3);
      font-weight: bold;
    }
    /* Main content wrapper */
    .content-wrapper {
      margin-left: 200px;
            padding: 20px;
            flex-grow: 1;
    }
    /* Inventory card styles */
    .card {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      border: none;
      border-radius: 15px;
      transition: transform 0.2s;
    }
    .card:hover {
      transform: scale(1.02);
    }
    .card-img-top {
      height: 250px;
      object-fit: contain;
    }
    .card-title {
      color: #6a008a;
      font-weight: bold;
      font-size: 1.5rem;
    }
    .available-stock {
      font-size: 1.2rem;
      color: #333;
      font-weight: bold;
      margin-top: 10px;
    }
    .stock-number {
      font-size: 2rem;
      color: #e74c3c;
      font-weight: bold;
    }
    .product-details {
      font-size: 1rem;
      color: #555;
    }
    .back-btn {
      margin: 20px 0;
      background-color: #6a008a;
      color: white;
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
.content {
            margin-left: 260px;
            padding: 20px;
            flex-grow: 1;
        }
  </style>
</head>
<body>
  <!-- Flex container for sidebar and main content -->
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
      <h2>Admin Panel</h2>
      <a href="/OceanGas/staff/admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
      <a href="/OceanGas/staff/stock_admin.php" class="<?php echo ($current_page === 'stock_admin.php') ? 'active' : ''; ?>">
        <i class="fas fa-box"></i> Stock/Inventory
      </a>
      <a href="/OceanGas/staff/users.php"><i class="fas fa-users"></i> Manage Users</a>
      <a href="/OceanGas/staff/finance.php"><i class="fas fa-dollar"></i> Finance</a>
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

    <div class="content" style="margin-left: 1px; padding: 10px; width: calc(100% - 250px);">
<iframe 
  id="mainFrame"
  name="main-frame"
  src="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1"
  style="display:none; width:100%; height:100%; border:none;"
></iframe>
<div id="mainContent">

    <!-- Main Content Wrapper -->
      <div class="container">
        <h1 class="mt-0 mb-4">Inventory Stock</h1>
        <div class="row">
          <?php while($row = $result->fetch_assoc()): ?>
            <?php 
              // Build filesystem path to check existence
              $fsPath = $fsPrefix . ltrim($row['image_path'], '/');
              if (!empty($row['image_path']) && file_exists($fsPath)) {
                  $image_url = $baseUrl . ltrim($row['image_path'], '/');
              } else {
                  $image_url = $baseUrl . 'assets/images/default.jpg';
              }
            ?>
            <div class="col-md-6 col-lg-4">
              <div class="card">
                <img src="<?php echo htmlspecialchars($image_url); ?>"
                     alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                     class="card-img-top">
                <div class="card-body">
                  <!-- Display the full product text -->
                  <h5 class="card-title"><?php echo htmlspecialchars($row['product_name']); ?></h5>
                  <p class="available-stock">Available Stock:</p>
                  <p class="stock-number"><?php echo htmlspecialchars($row['quantity']); ?></p>
                  <p class="product-details">
                    High quality LPG cylinder. Check back regularly for updated stock levels.
                  </p>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
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
    // only set src once, so subsequent shows don’t reload
    if (frame.src !== url) {
      frame.src = url;
    }
    // toggle visibility (you can also just always show: frame.style.display = 'block')
    frame.style.display = 'block';
  }

  // Optional: delegate to _all_ links with data‐target="main-frame":
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
    // only set src once, so subsequent shows don’t reload
    if (frame.src !== url) {
      frame.src = url;
    }
    // toggle visibility (you can also just always show: frame.style.display = 'block')
    frame.style.display = 'block';
  }

  // Optional: delegate to _all_ links with data‐target="main-frame":
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
