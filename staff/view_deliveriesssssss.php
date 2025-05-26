<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Deliveries</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body {
      display: flex;
      background: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      height: 100vh;
      position: fixed;
    }
    .sidebar a {
      color: white;
      display: block;
      padding: 10px;
      text-decoration: none;
      margin: 5px 0;
    }
    .sidebar a:hover, .sidebar a.active {
      background: rgba(255,255,255,0.2);
      border-radius: 5px;
    }
    .dropdown-btn {
      background: none;
      border: none;
      color: white;
      padding: 10px;
      text-align: left;
      width: 100%;
      cursor: pointer;
      font-size: 16px;
    }
    .dropdown-container {
      display: none;
      background-color: #6a008a;
      padding-left: 20px;
    }
    .dropdown-btn.active + .dropdown-container {
      display: block;
    }
    .content {
      margin-left: 270px;
      padding: 30px;
      width: 100%;
    }
  </style>
</head>
<body>
  
<div class="sidebar"> 
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="/OceanGas/staff/stock_admin.php">
      <i class="fas fa-box"></i> Stock/Inventory
    </a>
    <a href="/OceanGas/staff/users.php">
      <i class="fas fa-users"></i> Manage Users
    </a>
    <a href="/OceanGas/staff/finance.php">
      <i class="fas fa-dollar-sign"></i> Finance
    </a>

    <button class="dropdown-btn">
      <i class="fas fa-truck"></i> Deliveries
      <i class="fas fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
      <a href="view_deliveries.php">View Deliveries</a>
      <a href="add_delivery.php">Add Delivery</a>
    </div>

    <button class="dropdown-btn">
      <i class="fas fa-truck"></i> Procurement
      <i class="fas fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
      <a href="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1">Dashboard</a>
      <a href="/OceanGas/staff/purchase_history_reports.php?embedded=1">Purchase History</a>
      <a href="/OceanGas/staff/suppliers.php?embedded=1">Suppliers</a>
      <a href="/OceanGas/staff/financial_overview.php?embedded=1">Financial Overview</a>
    </div>

    <button class="dropdown-btn">
      <i class="fas fa-shopping-cart"></i> Sales
      <i class="fas fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
      <a href="/OceanGas/staff/sales_staff_dashboard.php?embedded=1">Dashboard</a>
      <a href="/OceanGas/staff/sales_invoice.php?embedded=1">Sales Invoice</a>
      <a href="/OceanGas/staff/reports.php?embedded=1">Reports</a>
    </div>
</div>

  <div class="content">
    <?php
    $host = 'localhost';
    $db   = 'oceangas';
    $user = 'root';
    $pass = '';
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $result = $conn->query("SELECT * FROM deliveries ORDER BY delivery_date DESC");
    ?>
    <h2 class="mb-4">All Deliveries</h2>
    <table class="table table-bordered">
      <thead class="table-dark">
        <tr>
          <th>Order ID</th>
          <th>Assigned To</th>
          <th>Delivery Date</th>
          <th>Status</th>
          <th>Notes</th>
        </tr>
      </thead>
      
<tbody>
<?php while ($row = mysqli_fetch_assoc($result)): ?>
  <tr>
    <td><?= htmlspecialchars($row['order_id']) ?></td>
    <td><?= htmlspecialchars($row['assigned_to']) ?></td>
    <td><?= htmlspecialchars($row['delivery_date']) ?></td>
    <td><?= htmlspecialchars($row['delivery_status']) ?></td>
    <td><?= htmlspecialchars($row['notes']) ?></td>
  </tr>
<?php endwhile; ?>
</tbody>

    </table>
  </div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var dropdowns = document.getElementsByClassName("dropdown-btn");
    for (var i = 0; i < dropdowns.length; i++) {
      dropdowns[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var dropdownContent = this.nextElementSibling;
        if (dropdownContent.style.display === "block") {
          dropdownContent.style.display = "none";
        } else {
          dropdownContent.style.display = "block";
        }
      });
    }
    
    // Automatically open the Deliveries dropdown
    document.querySelectorAll('.dropdown-btn')[0].click();
  });
</script>
</body>
</html>