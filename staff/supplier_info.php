<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Supplier not specified.");
}

$supplier_id = intval($_GET['id']);

// Retrieve supplier information including cost details from the suppliers table
$stmt = $conn->prepare("SELECT name, address, phone, email, details, cost_6kg, cost_12kg FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$stmt->bind_result($name, $address, $phone, $email, $details, $cost_6kg, $cost_12kg);
if (!$stmt->fetch()) {
    die("Supplier not found.");
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supplier Info - <?php echo htmlspecialchars($name); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body {
          background: #f7f7f7;
      }
      .card {
          margin-top: 30px;
      }
      .card-header {
          background-color: #2c3e50;
          color: #fff;
      }
      .supplier-info p {
          font-size: 1.1rem;
      }
      .purchase-form .form-label {
          font-weight: 600;
      }
  </style>
</head>
<body>
  <div class="container">
    <div class="card shadow">
      <div class="card-header text-center">
        <h2><?php echo htmlspecialchars($name); ?></h2>
      </div>
      <div class="card-body">
        <div class="row">
          <!-- Supplier Details -->
          <div class="col-md-6">
            <h5 class="mb-3">Supplier Details</h5>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Details:</strong> <?php echo htmlspecialchars($details); ?></p>
          </div>
          <!-- Pricing Information -->
          <div class="col-md-6">
            <h5 class="mb-3">Pricing</h5>
            <p><strong>Cost for 6kg Gas Cylinder:</strong> KES <?php echo number_format($cost_6kg, 2); ?></p>
            <p><strong>Cost for 12kg Gas Cylinder:</strong> KES <?php echo number_format($cost_12kg, 2); ?></p>
          </div>
        </div>
        <hr>
        <!-- Purchase Form -->
        <h5 class="mt-4">Purchase Stock</h5>
        <form class="purchase-form" action="purchase.php" method="POST">
          <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
          <div class="mb-3">
            <label for="product" class="form-label">Select Product</label>
            <select name="product" id="product" class="form-select" required>
              <option value="6kg">6kg Gas Cylinder</option>
              <option value="12kg">12kg Gas Cylinder</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity to Purchase</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
          </div>
          <button type="submit" class="btn btn-primary">Purchase</button>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
