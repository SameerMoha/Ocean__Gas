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
  ORDER BY product_id
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
  <title>Stock Sales</title>
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
      flex: 1;
      padding: 20px;
      overflow-y: auto;
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
  <!-- Flex container for sidebar and main content -->
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
    <h2>Sales Panel</h2>
    <a href="sales_staff_dashboard.php"
       class="<?= $current_page === 'sales_staff_dashboard.php' ? 'active' : '' ?>">
       <i class="fas fa-chart-line"></i> Cockpit
    </a>
    <a href="sales_invoice.php"
       class="<?= $current_page === 'sales_invoice.php' ? 'active' : '' ?>">
       <i class="fas fa-file-invoice"></i> Sales Invoice
    </a>
    <a href="stock_sales.php"
       class="<?= $current_page === 'stock_sales.php' ? 'active' : '' ?>">
       <i class="fas fa-box"></i> Stock/Inventory
    </a>
    <a href="reports.php"
       class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">
       <i class="fas fa-clipboard-list"></i> Reports
    </a>
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

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
      <div class="container">
        <h1 class="mb-4">Inventory Stock</h1>
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

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
  </script>
</body>
</html>
