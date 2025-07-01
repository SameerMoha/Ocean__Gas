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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    body { margin: 0; background: #f8f9fa; font-family: Arial, sans-serif; }
      .card { margin-top: 30px; }
      .card-header { background-color: #2c3e50; color: #fff; }
      .supplier-info p { font-size: 1.1rem; }
      .purchase-form .form-label { font-weight: 600; }
      .sidebar { width: 250px; background: #6a008a; color: white; padding: 20px; height: 100vh; }
    .sidebar h2 { margin-top: 0; }
    .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 5px; transition: background 0.2s; }
    .sidebar a:hover { background: rgba(255, 255, 255, 0.2); }
    .sidebar a.active { background: rgba(255, 255, 255, 0.3); font-weight: bold; }
    .content-wrapper { flex: 1; padding: 20px; overflow-y: auto; }

  </style>
</head>
<body>
  <div class="d-flex" style="min-height: 100vh;">
    <script>
  // If we're inside an iframe, window.self !== window.top
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
  <div class="content-wrapper">
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
                <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                  <?php echo htmlspecialchars($item['name']); ?> (KES <?php echo number_format($item['price'], 2); ?>)
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

          <!-- Live Purchase Summary -->
          <div id="purchase-summary" class="mb-3" style="display:none;">
            <div class="card border-primary shadow-sm animate__animated animate__fadeIn">
              <div class="card-body d-flex flex-column align-items-center">
                <h6 class="card-title mb-2 text-primary"><i class="fas fa-receipt"></i> Purchase Summary</h6>
                <div class="d-flex flex-row align-items-center mb-2">
                  <span class="badge bg-info me-2" id="summary-product"></span>
                  <span class="badge bg-secondary me-2" id="summary-unit"></span>
                  <span class="badge bg-warning text-dark" id="summary-qty"></span>
                </div>
                <h5 class="mb-0">Total: <span class="text-success" id="summary-total"></span></h5>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 mt-2" id="purchase-btn">Purchase</button>
        </form>
        <!-- Persistent alert for large quantity -->
        <div id="large-qty-alert" class="alert alert-warning mt-3 d-none" role="alert">
          <strong>Large Quantity Detected:</strong> For orders above 150 units, please lower the quantity or contact your administrator for approval.
        </div>
      </div>
    </div>
  </div>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Animate.css for subtle animation -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const summaryDiv    = document.getElementById('purchase-summary');
    const summaryProduct = document.getElementById('summary-product');
    const summaryUnit    = document.getElementById('summary-unit');
    const summaryQty     = document.getElementById('summary-qty');
    const summaryTotal   = document.getElementById('summary-total');
    const purchaseForm   = document.querySelector('.purchase-form');
    const purchaseBtn    = document.getElementById('purchase-btn');
    const largeQtyAlert  = document.getElementById('large-qty-alert');

    function updateSummary() {
      const selected = productSelect.options[productSelect.selectedIndex];
      const price = parseFloat(selected.getAttribute('data-price')) || 0;
      const name  = selected.getAttribute('data-name') || '';
      const qty   = parseInt(quantityInput.value) || 0;
      if (qty > 0) {
        summaryDiv.style.display = '';
        summaryProduct.textContent = name;
        summaryUnit.textContent    = 'KES ' + price.toLocaleString(undefined, {minimumFractionDigits:2});
        summaryQty.textContent     = '× ' + qty;
        summaryTotal.textContent   = 'KES ' + (price * qty).toLocaleString(undefined, {minimumFractionDigits:2});
      } else {
        summaryDiv.style.display = 'none';
      }
      // Large quantity persistent alert logic
      if (qty > 150) {
        purchaseBtn.disabled = true;
        largeQtyAlert.classList.remove('d-none');
      } else {
        purchaseBtn.disabled = false;
        largeQtyAlert.classList.add('d-none');
      }
    }
    productSelect.addEventListener('change', updateSummary);
    quantityInput.addEventListener('input', updateSummary);
    updateSummary();

    // SweetAlert2 for purchase confirmation and large quantity check
    purchaseForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const qty = parseInt(quantityInput.value) || 0;
      const selected = productSelect.options[productSelect.selectedIndex];
      const name  = selected.getAttribute('data-name') || '';
      const price = parseFloat(selected.getAttribute('data-price')) || 0;
      const total = price * qty;
      if (qty > 150) {
        Swal.fire({
          icon: 'warning',
          title: 'Large Quantity Detected',
          html: `<b>You are attempting to procure <span style=\"color:#d35400\">${qty}</span> units of <span style=\"color:#2980b9\">${name}</span>.</b><br><br>For large orders, please lower the quantity or contact your administrator for approval.`,
          confirmButtonText: 'OK',
          customClass: {popup: 'animate__animated animate__fadeInDown'}
        });
        purchaseBtn.disabled = true;
        largeQtyAlert.classList.remove('d-none');
        return;
      }
      Swal.fire({
        title: 'Confirm Purchase',
        html: `<div style=\"font-size:1.1em\">Are you sure you want to purchase <b>${qty}</b> units of <b>${name}</b> for a total of <b>KES ${total.toLocaleString(undefined, {minimumFractionDigits:2})}</b>?</div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Purchase',
        cancelButtonText: 'Cancel',
        customClass: {popup: 'animate__animated animate__fadeInDown'}
      }).then((result) => {
        if (result.isConfirmed) {
          purchaseForm.submit();
        }
      });
    });
  });
  </script>
</body>
</html>
