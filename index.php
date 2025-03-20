<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      position: fixed;
      height: 100vh;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 10px;
      margin: 5px 0;
    }
    .sidebar a:hover {
      background: rgba(255,255,255,0.2);
      border-radius: 5px;
    }
    .content { margin-left: 260px; padding: 20px; }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      padding: 10px 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .topbar-icons { display: flex; gap: 15px; }
    .topbar-icons i { font-size: 20px; cursor: pointer; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Sales Panel</h2>
    <a href="sales.php"><i class="fas fa-chart-line"></i> Sales Overview</a>
    <a href="approve_sales.php"><i class="fas fa-check-circle"></i> Approve Sales</a>
    <a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
  
  <div class="content">
    <div class="topbar">
      <h1>Inventory Management, <?php echo htmlspecialchars($username); ?></h1>
      <div class="topbar-icons">
        <i class="fas fa-envelope"></i>
        <i class="fas fa-bell"></i>
        <i class="fas fa-user"></i>
      </div>
    </div>
    
    <div class="card p-3">
      <h5>Current Inventory</h5>
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Product</th>
            <th>Stock</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>6kg Gas Cylinder</td>
            <td>120</td>
            <td>$20</td>
          </tr>
          <tr>
            <td>12kg Gas Cylinder</td>
            <td>80</td>
            <td>$30</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
