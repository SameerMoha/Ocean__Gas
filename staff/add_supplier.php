<?php
session_start();
// Ensure the procurement staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$error = '';
// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection details
    $host = 'localhost';
    $db   = 'oceangas';
    $user = 'root';
    $pass = '';
    
    // Create connection
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get and sanitize form inputs
    $name     = $conn->real_escape_string($_POST['name']);
    $address  = $conn->real_escape_string($_POST['address']);
    $phone    = $conn->real_escape_string($_POST['phone']);
    $email    = $conn->real_escape_string($_POST['email']);
    $details  = $conn->real_escape_string($_POST['details']);
    $cost_6kg = $conn->real_escape_string($_POST['cost_6kg']);
    $cost_12kg= $conn->real_escape_string($_POST['cost_12kg']);

    // Prepare SQL query to insert the new supplier
    $sql = "INSERT INTO suppliers (name, address, phone, email, details, cost_6kg, cost_12kg, created_at)
            VALUES ('$name', '$address', '$phone', '$email', '$details', '$cost_6kg', '$cost_12kg', NOW())";
    
    if ($conn->query($sql) === TRUE) {
        // Redirect back to suppliers page after successful insertion
        header("Location: suppliers.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Supplier - Procurement Panel</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .container {
      max-width: 600px;
      margin-top: 50px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2 class="mb-4">Add New Supplier</h2>
    <?php if($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="add_supplier.php">
      <div class="mb-3">
        <label for="name" class="form-label">Supplier Name</label>
        <input type="text" class="form-control" id="name" name="name" required>
      </div>
      <div class="mb-3">
        <label for="address" class="form-label">Address</label>
        <input type="text" class="form-control" id="address" name="address" required>
      </div>
      <div class="mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
      </div>
      <div class="mb-3">
        <label for="details" class="form-label">Details</label>
        <input type="text" class="form-control" id="details" name="details">
      </div>
      <div class="mb-3">
        <label for="cost_6kg" class="form-label">Cost (6kg)</label>
        <input type="number" step="0.01" class="form-control" id="cost_6kg" name="cost_6kg" required>
      </div>
      <div class="mb-3">
        <label for="cost_12kg" class="form-label">Cost (12kg)</label>
        <input type="number" step="0.01" class="form-control" id="cost_12kg" name="cost_12kg" required>
      </div>
      <button type="submit" class="btn btn-primary">Add Supplier</button>
      <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
