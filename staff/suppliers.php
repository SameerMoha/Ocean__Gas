<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the procurement staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Determine sort order from query parameter (default ascending)
$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'cost_6kg';
$sort_order = (isset($_GET['sort_order']) && $_GET['sort_order'] === 'desc') ? 'DESC' : 'ASC';

// Query the suppliers table sorted by the selected column and order
$sql = "SELECT 
    s.id AS supplier_id,
    s.name AS supplier_name,
    s.email,
    s.phone,
    s.details,
    s.address,
    MAX(CASE WHEN p.product_name LIKE '%6kg%' THEN pr.buying_price END) AS cost_6kg,
    MAX(CASE WHEN p.product_name LIKE '%12kg%' THEN pr.buying_price END) AS cost_12kg,
    MAX(CASE WHEN p.product_name LIKE '%6kg%' THEN pr.selling_price END) AS sell_6kg,
    MAX(CASE WHEN p.product_name LIKE '%12kg%' THEN pr.selling_price END) AS sell_12kg
FROM 
    suppliers s
LEFT JOIN 
    price pr ON s.id = pr.supplier_id
LEFT JOIN 
    products p ON p.product_id = pr.product_id
GROUP BY 
    s.id, s.name, s.email, s.phone, s.details, s.address

ORDER BY $sort_column $sort_order";
$result = $conn->query($sql);
$suppliers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Suppliers - Procurement Panel</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    body { margin: 0; background: #f8f9fa; font-family: Arial, sans-serif; }
    .d-flex { display: flex; }
    .sidebar { width: 250px; background: #6a008a; color: white; padding: 20px; height: 100vh; }
    .sidebar h2 { margin-top: 0; }
    .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 5px; transition: background 0.2s; }
    .sidebar a:hover { background: rgba(255, 255, 255, 0.2); }
    .sidebar a.active { background: rgba(255, 255, 255, 0.3); font-weight: bold; }
    .content-wrapper { flex: 1; padding: 20px; overflow-y: auto; }
    .table-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .table td input { border: none; background: transparent; width: 100%; }
    .table td input[readonly] { color: #000; }
    .table td input:not([readonly]) { background: #f1f1f1; }
    .action-btn { min-width: 80px; margin-right: 5px; }
    .filter-btn { margin-bottom: 15px; }
  </style>
</head>
<body>
  <div class="d-flex" style="min-height: 100vh;">
    <script>
  // If we’re inside an iframe, window.self !== window.top
  if (window.self !== window.top) {
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Remove the sidebar element entirely
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) sidebar.remove();
      
      const topbar = document.querySelector('.topbar');
      if (topbar) topbar.remove();
      // 2. Reset your main content to fill the viewport
      const content = document.querySelector('.content');
      if (content) {
        content.style.marginLeft = '0';
        content.style.width      = '100%';
        content.style.padding    = '20px';
      }

    });
  }
</script>
    <!-- Sidebar -->
    <div class="sidebar">
      <h2>Procurement Panel</h2>
      <a href="/OceanGas/staff/procurement_staff_dashboard.php" class="<?= ($current_page === 'procurement_staff_dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-truck"></i> Dashboard
      </a>
      <a href="/OceanGas/staff/stock_procurement.php" class="<?= ($current_page === 'stock_procurement.php') ? 'active' : ''; ?>">
        <i class="fas fa-box"></i> Stock/Inventory
      </a>
      <a href="/OceanGas/staff/purchase_history_reports.php" class="<?= ($current_page === 'purchase_history_reports.php') ? 'active' : ''; ?>">
        <i class="fas fa-receipt"></i> Purchase History
      </a>
      <a href="/OceanGas/staff/suppliers.php" class="<?= ($current_page === 'suppliers.php') ? 'active' : ''; ?>">
        <i class="fas fa-industry"></i> Suppliers
      </a>
      <a href="/OceanGas/staff/financial_overview.php" class="<?= ($current_page === 'financial_overview.php') ? 'active' : ''; ?>">
        <i class="fas fa-credit-card"></i> Financial Overview
      </a>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
      <h2>Suppliers</h2>
      <a href="add_supplier.php" class="btn btn-primary mb-3">Add Supplier</a>
      <div class="mb-3">
        <a href="?sort_column=cost_6kg&sort_order=<?= ($sort_column==='cost_6kg'&&$sort_order==='ASC')?'desc':'asc'; ?>" class="btn btn-secondary filter-btn">
          Sort by Cost (6kg) <?= ($sort_column==='cost_6kg'&&$sort_order==='ASC')?'↓':'↑'; ?>
        </a>
        <a href="?sort_column=cost_12kg&sort_order=<?= ($sort_column==='cost_12kg'&&$sort_order==='ASC')?'desc':'asc'; ?>" class="btn btn-secondary filter-btn">
          Sort by Cost (12kg) <?= ($sort_column==='cost_12kg'&&$sort_order==='ASC')?'↓':'↑'; ?>
        </a>
      </div>

      <div class="table-container">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Supplier Name</th>
              <th>Address</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Details</th>
              <th>Cost_6kg</th>
              <th>Cost_12kg</th>
              <th>Sell_6kg</th>
              <th>Sell_12kg</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($suppliers): ?>
              <?php foreach ($suppliers as $supplier): ?>
                <tr class="supplier-row" data-id="<?= $supplier['supplier_id'] ?>">
                  <td><?= htmlspecialchars($supplier['supplier_id']) ?></td>
                  <form action="update_supplier.php" method="POST">
                    <input type="hidden" name="supplier_id" value="<?= htmlspecialchars($supplier['supplier_id']) ?>">
                    <td><input type="text" name="supplier_name"     value="<?= htmlspecialchars($supplier['supplier_name']) ?>" readonly></td>
                    <td><input type="text" name="address"           value="<?= htmlspecialchars($supplier['address']) ?>" readonly></td>
                    <td><input type="text" name="phone"             value="<?= htmlspecialchars($supplier['phone']) ?>" readonly></td>
                    <td><input type="email"name="email"             value="<?= htmlspecialchars($supplier['email']) ?>" readonly></td>
                    <td><input type="text" name="details"           value="<?= htmlspecialchars($supplier['details']) ?>" readonly></td>
                    <td><input type="number"step="0.01" name="cost_6kg" value="<?= htmlspecialchars($supplier['cost_6kg']) ?>" readonly></td>
                    <td><input type="number"step="0.01" name="cost_12kg" value="<?= htmlspecialchars($supplier['cost_12kg']) ?>" readonly></td>
                    <td><input type="number"step="0.01" name="sell_6kg" value="<?= htmlspecialchars($supplier['sell_6kg']) ?>" readonly></td>
                    <td><input type="number"step="0.01" name="sell_12kg" value="<?= htmlspecialchars($supplier['sell_12kg']) ?>" readonly></td>
                    <td>
                      <button type="button" class="btn btn-warning btn-sm edit-btn" onclick="enableEditing(this,event)">Edit</button>
                      <button type="submit" class="btn btn-success btn-sm save-btn" style="display:none;" onclick="event.stopPropagation()">Save</button>
                    </td>
                  </form>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="9" class="text-center">No suppliers found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Row click navigation (when not editing)
    document.querySelectorAll('.supplier-row').forEach(row => {
      row.addEventListener('click', function(e) {
        if (!this.classList.contains('editing') &&
            e.target.tagName !== 'BUTTON' &&
            e.target.tagName !== 'INPUT') {
          window.location.href = `supplier_info.php?id=${encodeURIComponent(this.dataset.id)}`;
        }
      });
    });

    function enableEditing(btn, event) {
      event.stopPropagation();
      const row = btn.closest('tr');
      row.classList.add('editing');
      row.querySelectorAll('input').forEach(input => {
        input.removeAttribute('readonly');
        input.style.background = '#f1f1f1';
      });
      btn.style.display = 'none';
      row.querySelector('.save-btn').style.display = 'inline-block';
    }
  </script>
</body>
</html>
