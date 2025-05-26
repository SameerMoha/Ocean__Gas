<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// 1) Auth check
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// 2) Supplier ID from GET
if (!isset($_GET['id'])) {
    die("Supplier not specified.");
}
$supplier_id = intval($_GET['id']);

// 3) Fetch basic supplier info (no trailing comma!)
$stmt = $conn->prepare("
    SELECT name, address, phone, email, details
      FROM suppliers
     WHERE id = ?
");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$stmt->bind_result($name, $address, $phone, $email, $details);
if (!$stmt->fetch()) {
    die("Supplier not found.");
}
$stmt->close();

// 4) Fetch all products + prices for this supplier
$stmt = $conn->prepare("
    SELECT 
      p.product_id, 
      p.product_name, 
      pr.buying_price 
    FROM price pr
    JOIN products p 
      ON pr.product_id = p.product_id
   WHERE pr.supplier_id = ?
");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

// Build an array of [product_id, name, price]
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id'    => (int)$row['product_id'],
        'name'  => $row['product_name'],
        'price' => (float)$row['buying_price'],
    ];
}
$stmt->close();
$conn->close();

// If no products/prices, let user know
if (empty($items)) {
    die("This supplier has not set any prices yet.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supplier Info – <?php echo htmlspecialchars($name); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body { background: #f7f7f7; }
      .card { margin-top: 30px; }
      .card-header { background-color: #2c3e50; color: #fff; }
      .supplier-info p { font-size: 1.1rem; }
      .purchase-form .form-label { font-weight: 600; }
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
            <p><strong>Phone:</strong>   <?php echo htmlspecialchars($phone); ?></p>
            <p><strong>Email:</strong>   <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Details:</strong> <?php echo htmlspecialchars($details); ?></p>
          </div>
          <!-- Dynamic Pricing Information -->
          <div class="col-md-6">
            <h5 class="mb-3">Pricing</h5>
            <?php foreach ($items as $item): ?>
              <p>
                <strong><?php echo htmlspecialchars($item['name']); ?>:</strong>
                KES <?php echo number_format($item['price'], 2); ?>
              </p>
            <?php endforeach; ?>
          </div>
        </div>
        <hr>
        <!-- Purchase Form -->
        <h5 class="mt-4">Purchase Stock</h5>
        <form class="purchase-form" action="purchase.php" method="POST">
          <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
          
          <div class="mb-3">
            <label for="product_id" class="form-label">Select Product</label>
            <select 
              name="product_id" 
              id="product_id" 
              class="form-select" 
              required>
              
              <?php foreach ($items as $item): ?>
                <option value="<?php echo $item['id']; ?>">
                  <?php echo htmlspecialchars($item['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity to Purchase</label>
            <input 
              type="number" 
              name="quantity" 
              id="quantity" 
              class="form-control" 
              min="1" 
              required>
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
