<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DB connection
    $conn = new mysqli('localhost', 'root', '', 'oceangas');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Sanitize inputs
    $name       = $conn->real_escape_string($_POST['name']);
    $address    = $conn->real_escape_string($_POST['address']);
    $phone      = $conn->real_escape_string($_POST['phone']);
    $email      = $conn->real_escape_string($_POST['email']);
    $details    = $conn->real_escape_string($_POST['details']);
    $cost_6kg   = $conn->real_escape_string($_POST['cost_6kg']);
    $cost_12kg  = $conn->real_escape_string($_POST['cost_12kg']);
    $sell_6kg  = $conn->real_escape_string($_POST['sell_6kg']);
    $sell_12kg = $conn->real_escape_string($_POST['sell_12kg']);

    $conn->begin_transaction();
    try {
        // 1) suppliers
        $sql1 = "INSERT INTO suppliers
                    (name, address, phone, email, details, created_at)
                 VALUES
                    ('$name', '$address', '$phone', '$email', '$details', NOW())";
        $conn->query($sql1);
        $supplier_id = $conn->insert_id;

        // --- 6kg product + stock ---
        $product_name_6 = "$name 6kg";
        $desc_6         = "6kg cylinder supplied by $name";

        // Insert 6kg product
$sql2 = "INSERT INTO products (product_name, description, created_at, quantity)
         VALUES ('$product_name_6', '$desc_6', NOW(), 0)";
$conn->query($sql2);
$product_id_6kg = $conn->insert_id; // get the ID

// Insert 6kg price
$sql3 = "INSERT INTO price
            (product_id, supplier_id, selling_price, buying_price)
         VALUES
            ('$product_id_6kg', '$supplier_id', '$sell_6kg', '$cost_6kg')";

if (!$conn->query($sql3)) {
    throw new Exception("Failed to insert 6kg price: " . $conn->error);
}


        // --- 12kg product + stock ---
        $product_name_12 = "$name 12kg";
        $desc_12         = "12kg cylinder supplied by $name";

      // Insert 12kg product
$sql4 = "INSERT INTO products (product_name, description, created_at, quantity)
         VALUES ('$product_name_12', '$desc_12', NOW(), 0)";
$conn->query($sql4);
$product_id_12kg = $conn->insert_id;

// Insert 12kg price
$sql5 = "INSERT INTO price
            (product_id, supplier_id, selling_price, buying_price)
         VALUES
            ('$product_id_12kg', '$supplier_id', '$sell_12kg', '$cost_12kg')";

if (!$conn->query($sql5)) {
    throw new Exception("Failed to insert 12kg price: " . $conn->error);
}


        $conn->commit();
        echo "<div class='alert alert-success'>Supplier, products & stock entries added successfully!</div>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Supplier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f7fa; }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 { margin-bottom: 25px; }
    </style>
</head>
<body>
  <div class="container">
    <h2>Add New Supplier</h2>
    <form method="POST" action="" enctype="multipart/form-data">
      <!-- Supplier details -->
      <div class="mb-3">
        <label class="form-label">Supplier Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Details</label>
        <textarea name="details" class="form-control" rows="2"></textarea>
      </div>

      <!-- Cost & Price -->
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Cost Price (6kg)</label>
          <input type="number" step="0.01" name="cost_6kg" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Selling Price (6kg)</label>
          <input type="number" step="0.01" name="sell_6kg" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Cost Price (12kg)</label>
          <input type="number" step="0.01" name="cost_12kg" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Selling Price (12kg)</label>
          <input type="number" step="0.01" name="sell_12kg" class="form-control" required>
        </div>
        <div class="mb-3">
  <label class="form-label">Product Image</label>
  <input type="file" name="product_image" class="form-control" accept="image/*" required>
</div>

      </div>

      <button type="submit" class="btn btn-primary w-100">Add Supplier</button>
    </form>
  </div>
</body>
</html>
