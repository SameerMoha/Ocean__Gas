<?php
session_start();
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$adminName = $_SESSION['staff_username'];

// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query for Pending Sales
$sales_query = "SELECT sale_date, product, quantity, total, assigned_to AS sold_by 
                FROM sales 
                WHERE status = 'pending'
                ORDER BY sale_date DESC";
$sales_result = $conn->query($sales_query);
if (!$sales_result) {
    die("Pending Sales query failed: " . $conn->error);
}
$pending_sales = [];
while($row = $sales_result->fetch_assoc()){
    $pending_sales[] = $row;
}

// Query for Pending Procurement Requests
$procurements_query = "SELECT purchase_date, product, quantity, 
                          (CASE WHEN product = '6kg' THEN s.cost_6kg ELSE s.cost_12kg END * quantity) AS total_cost, 
                          s.name AS supplier 
                        FROM purchase_history ph 
                        JOIN suppliers s ON ph.supplier_id = s.id 
                        WHERE ph.status = 'pending'
                        ORDER BY purchase_date DESC";
$procurements_result = $conn->query($procurements_query);
if (!$procurements_result) {
    die("Pending Procurements query failed: " . $conn->error);
}
$pending_procurements = [];
while($row = $procurements_result->fetch_assoc()){
    $pending_procurements[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pending Requests - Admin Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body { background: #f8f9fa; font-family: Arial, sans-serif; padding: 20px; }
      .container { max-width: 1000px; margin: auto; }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="mb-4">Pending Requests Details</h1>
    
    <h2>Pending Sales</h2>
    <?php if(count($pending_sales) > 0): ?>
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Total (KES)</th>
            <th>Sold By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($pending_sales as $sale): ?>
            <tr>
              <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
              <td><?php echo htmlspecialchars($sale['product']); ?></td>
              <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
              <td>KES <?php echo number_format($sale['total'], 2); ?></td>
              <td><?php echo htmlspecialchars($sale['sold_by']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No pending sales found.</p>
    <?php endif; ?>

    <h2 class="mt-5">Pending Procurement Requests</h2>
    <?php if(count($pending_procurements) > 0): ?>
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Supplier</th>
            <th>Total Cost (KES)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($pending_procurements as $proc): ?>
            <tr>
              <td><?php echo htmlspecialchars($proc['purchase_date']); ?></td>
              <td><?php echo htmlspecialchars($proc['product']); ?></td>
              <td><?php echo htmlspecialchars($proc['quantity']); ?></td>
              <td><?php echo htmlspecialchars($proc['supplier']); ?></td>
              <td>KES <?php echo number_format($proc['total_cost'], 2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No pending procurement requests found.</p>
    <?php endif; ?>

    <a href="/OceanGas/staff/admin_dashboard.php" class="btn btn-primary mt-4">Back to Dashboard</a>
  </div>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
