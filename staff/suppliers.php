<?php
session_start();
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
$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] === 'desc' ? 'DESC' : 'ASC';

// Query the suppliers table sorted by the selected column and order
$sql = "SELECT * FROM suppliers ORDER BY $sort_column $sort_order";
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
    /* === BASIC LAYOUT STYLES === */
    body {
      margin: 0;
      background: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .d-flex {
      display: flex;
    }
    /* === SIDEBAR STYLES === */
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      height: 100vh;
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
      transition: background 0.2s;
    }
    .sidebar a:hover {
      background: rgba(255, 255, 255, 0.2);
    }
    .sidebar a.active {
      background: rgba(255, 255, 255, 0.3);
      font-weight: bold;
    }
    /* === MAIN CONTENT WRAPPER === */
    .content-wrapper {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }
    /* === Suppliers Table Styles === */
    .table-container {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .table td input {
      border: none;
      background: transparent;
      width: 100%;
    }
    .table td input[readonly] {
      color: #000;
    }
    .table td input:not([readonly]) {
      background: #f1f1f1;
    }
    .action-btn {
      min-width: 80px;
      margin-right: 5px;
    }
    .filter-btn {
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <!-- FLEX CONTAINER FOR SIDEBAR AND CONTENT -->
  <div class="d-flex" style="min-height: 100vh;">
    <!-- Sidebar -->
    <div class="sidebar">
      <h2>Procurement Panel</h2>
      <a href="/OceanGas/staff/procurement_staff_dashboard.php" class="<?php echo ($current_page === 'procurement_staff_dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-truck"></i> Dashboard
      </a>
      <a href="/OceanGas/staff/stock_procurement.php" class="<?php echo ($current_page === 'stock_procurement.php') ? 'active' : ''; ?>">
        <i class="fas fa-box"></i> Stock/Inventory
      </a>
      <a href="/OceanGas/staff/purchase_history_reports.php" class="<?php echo ($current_page === 'purchase_history_reports.php') ? 'active' : ''; ?>">
        <i class="fas fa-receipt"></i> Purchase History
      </a>
      <a href="/OceanGas/staff/suppliers.php" class="<?php echo ($current_page === 'suppliers.php') ? 'active' : ''; ?>">
        <i class="fas fa-industry"></i> Suppliers
      </a>
      <a href="/OceanGas/staff/financial_overview.php" class="<?php echo ($current_page === 'financial_overview.php') ? 'active' : ''; ?>">
        <i class="fas fa-credit-card"></i> Financial Overview
      </a>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
      <h2>Suppliers</h2>

      <!-- Add Supplier Button -->
      <a href="add_supplier.php" class="btn btn-primary mb-3">Add Supplier</a>

      <!-- Sort Buttons -->
      <div class="mb-3">
        <a href="?sort_column=cost_6kg&sort_order=<?php echo ($sort_column === 'cost_6kg' && $sort_order === 'ASC') ? 'desc' : 'asc'; ?>" class="btn btn-secondary filter-btn">
          Sort by Cost (6kg) <?php echo ($sort_column === 'cost_6kg' && $sort_order === 'ASC') ? '↓' : '↑'; ?>
        </a>
        <a href="?sort_column=cost_12kg&sort_order=<?php echo ($sort_column === 'cost_12kg' && $sort_order === 'ASC') ? 'desc' : 'asc'; ?>" class="btn btn-secondary filter-btn">
          Sort by Cost (12kg) <?php echo ($sort_column === 'cost_12kg' && $sort_order === 'ASC') ? '↓' : '↑'; ?>
        </a>
      </div>

      <!-- Suppliers Table -->
      <div class="table-container">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Address</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Details</th>
              <th>Created At</th>
              <th>Cost 6kg</th>
              <th>Cost 12kg</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($suppliers)): ?>
              <?php foreach ($suppliers as $supplier): ?>
                <form action="update_supplier.php" method="POST" class="supplier-form">
                  <!-- Add onclick to the row and check for editing state -->
                  <tr
                    onclick="if(event.target.tagName !== 'BUTTON' && event.target.tagName !== 'INPUT' && !this.classList.contains('editing')){ window.location.href='supplier_info.php?id=<?php echo htmlspecialchars($supplier['id']); ?>'; }"
                    style="cursor: pointer;"
                  >
                    <td>
                      <?php echo htmlspecialchars($supplier['id']); ?>
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($supplier['id']); ?>">
                    </td>
                    <td>
                      <input type="text" name="name" value="<?php echo htmlspecialchars($supplier['name']); ?>" readonly>
                    </td>
                    <td>
                      <input type="text" name="address" value="<?php echo htmlspecialchars($supplier['address']); ?>" readonly>
                    </td>
                    <td>
                      <input type="text" name="phone" value="<?php echo htmlspecialchars($supplier['phone']); ?>" readonly>
                    </td>
                    <td>
                      <input type="email" name="email" value="<?php echo htmlspecialchars($supplier['email']); ?>" readonly>
                    </td>
                    <td>
                      <input type="text" name="details" value="<?php echo htmlspecialchars($supplier['details']); ?>" readonly>
                    </td>
                    <td>
                      <?php echo htmlspecialchars($supplier['created_at']); ?>
                    </td>
                    <td>
                      <input type="number" step="0.01" name="cost_6kg" value="<?php echo htmlspecialchars($supplier['cost_6kg']); ?>" readonly>
                    </td>
                    <td>
                      <input type="number" step="0.01" name="cost_12kg" value="<?php echo htmlspecialchars($supplier['cost_12kg']); ?>" readonly>
                    </td>
                    <td>
                      <!-- Edit and Save Buttons -->
                      <button type="button" class="btn btn-sm btn-warning action-btn edit-btn" onclick="enableEditing(this, event)">Edit</button>
                      <button type="submit" class="btn btn-sm btn-success action-btn save-btn" style="display:none;" onclick="event.stopPropagation();">Save</button>
                    </td>
                  </tr>
                </form>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center">No suppliers found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function enableEditing(btn, event) {
      event.stopPropagation(); // Prevent row click redirection
      var row = btn.closest('tr');
      if (!row) return;

      // Add 'editing' class so row click won't redirect
      row.classList.add('editing');

      // Enable inputs for editing
      var inputs = row.querySelectorAll('input[type="text"], input[type="email"], input[type="number"]');
      inputs.forEach(function(input) {
        input.removeAttribute('readonly');
        input.style.background = '#f1f1f1';
      });

      // Toggle button visibility: hide edit, show save
      btn.style.display = 'none';
      var saveBtn = row.querySelector('.save-btn');
      if (saveBtn) {
        saveBtn.style.display = 'inline-block';
      }
    }
  </script>
</body>
</html>
