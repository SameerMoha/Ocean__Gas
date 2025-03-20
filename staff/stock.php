<?php
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
    .container {
      margin-top: 30px;
    }
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
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
      <a class="navbar-brand" href="#">Inventory</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon" style="color: #fff;"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="/OceanGas/staff/admin_dashboard.php">Back to Dashboard</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">
    <h1 class="mb-4 text-center">Inventory Stock</h1>
    <div class="row">
      <?php while($row = $result->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card">
            <?php 
              // Use provided image URLs based on product type
              if (strtolower($row['product']) === '6kg'): ?>
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRyrg_2y3_ZJc_PaB5J0OMEKRTHWVEttzy_XQ&s" alt="6kg Gas Cylinder" class="card-img-top">
                        <h2 class="text-xl font-bold mb-2"></h2>
            <?php else: ?>
                <img src="https://www.rihalenergy.com/wp-content/uploads/2019/09/gas-bottle-image-layer-B.png" alt="12kg Gas Cylinder" class="card-img-top">
                <h2 class="text-xl font-bold mb-2"></h2>
            <?php endif; ?>
            <div class="card-body">
              <h5 class="card-title"><?php echo htmlspecialchars($row['product']); ?> Gas Cylinder</h5>
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

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
