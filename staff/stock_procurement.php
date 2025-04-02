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

// Query the stock table for inventory details
$query = "SELECT * FROM stock";
$result = $conn->query($query);

if (!$result) {
    die("Query error: " . $conn->error);
}
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
  </style>
</head>
<body>
  <!-- Flex container for sidebar and main content -->
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
      <h2>Procurement Panel</h2>
      <a href="/OceanGas/staff/procurement_staff_dashboard.php" class="<?php echo ($current_page === 'procurement_staff_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Dashboard</a>
      <a href="/OceanGas/staff/stock_procurement.php" class="<?php echo ($current_page === 'stock_procurement.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Stock/Inventory</a>
      <a href="/OceanGas/staff/purchase_history_reports.php" class="<?php echo ($current_page === 'purchase_history_reports.php') ? 'active' : ''; ?>"><i class="fas fa-receipt"></i> Purchase History</a>
      <a href="/OceanGas/staff/suppliers.php" class="<?php echo ($current_page === 'suppliers.php') ? 'active' : ''; ?>"><i class="fas fa-industry"></i> Suppliers</a>
      <a href="/OceanGas/staff/financial_overview.php" class="<?php echo ($current_page === 'financial_overview.php') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Financial Overview</a>
    </div>

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
      <div class="container">
        <h1 class="mb-4">Inventory Stock</h1>
        <div class="row">
          <?php while($row = $result->fetch_assoc()): ?>
            <?php 
              // Determine the image URL based on the product text
              $image_url = '';
              $product = $row['product'];
              if (stripos($product, 'Shell Afrigas') !== false) {
                  if (stripos($product, '6kg') !== false) {
                      $image_url = 'https://gobeba.com/wp-content/uploads/2019/03/IMG_0170-768x512.jpg';
                  } else {
                      $image_url = 'https://gobeba.com/wp-content/uploads/2019/03/IMG_0180-768x512.jpg';
                  }
              } elseif (stripos($product, 'K-Gas') !== false) {
                  if (stripos($product, '6kg') !== false) {
                      $image_url = 'https://gobeba.com/wp-content/uploads/2019/03/IMG_0174-1-300x300.jpg';
                  } else {
                      $image_url = 'https://gobeba.com/wp-content/uploads/2019/03/IMG_0167-1-300x300.jpg';
                  }
              } elseif (stripos($product, 'Total Gas') !== false) {
                  if (stripos($product, '6kg') !== false) {
                      $image_url = 'https://gobeba.com/wp-content/uploads/2019/03/IMG_0177-768x512.jpg';
                  } else {
                      $image_url = 'https://gobeba.com/wp-content/uploads/2019/03/IMG_0173.jpg';
                  }
              } else {
                  // Default image if none of the suppliers match
                  $image_url = 'https://example.com/default_gas_cylinder.jpg';
              }
            ?>
            <div class="col-md-6 col-lg-4">
              <div class="card">
                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product); ?>" class="card-img-top">
                <div class="card-body">
                  <!-- Display the full product text -->
                  <h5 class="card-title"><?php echo htmlspecialchars($product); ?></h5>
                  <p class="available-stock">Available Stock:</p>
                  <p class="stock-number"><?php echo htmlspecialchars($row['quantity']); ?></p>
                  <p class="product-details">
                    High quality LPG cylinder. Check back regularly for updated stock levels.
                  </p>
                  <a href="#" class="btn btn-primary">Update Stock</a>
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
</body>
</html>
