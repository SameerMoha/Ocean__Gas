<?php   
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || ($_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin')) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
$salesName = $_SESSION['staff_username'];

// Database Connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$success = '';
$customer = '';
$invoiceCustomer = '';
$order_number = '';
$invoiceDetails = [];
$grand_total = 0;

// Fetch products with current stock levels from products table - FIXED TABLE NAME
$queryProducts = "
    SELECT
      p.product_id,
      p.product_name,
      p.quantity          AS available_quantity,
      pr.selling_price    AS price
    FROM products p
    JOIN price pr
      ON p.product_id = pr.product_id
";
$resultProducts = $conn->query($queryProducts);
$productsList = [];
if ($resultProducts) {
    while ($row = $resultProducts->fetch_assoc()) {
        $productsList[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = trim($_POST['customer']);
    $invoiceCustomer = $customer;
    $products   = $_POST['product'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_prices= $_POST['unit_price'] ?? [];

    if ($customer === '') {
        $errors[] = 'Customer name is required.';
    }

    $lineItems = [];
    foreach ($products as $i => $pid) {
        if (empty($pid)) continue;
        $qty   = intval($quantities[$i]);
        $price = floatval($unit_prices[$i]);
        if ($qty <= 0) $errors[] = "Line #".($i+1).": Quantity must be > 0.";
        if ($price <= 0) $errors[] = "Line #".($i+1).": Unit price must be > 0.";
        // find product in list
        $found = array_filter($productsList, fn($p) => $p['product_id'] == $pid);
        $pData = $found ? array_shift($found) : null;
        if (!$pData) {
            $errors[] = "Line #".($i+1).": Invalid product.";
        } elseif ($qty > $pData['available_quantity']) {
            $errors[] = "Line #".($i+1).": Insufficient stock for {$pData['product_name']}. Available: {$pData['available_quantity']}";
        }
        $lineItems[] = ['product_id'=>$pid,'quantity'=>$qty,'unit_price'=>$price, 'product_name' => $pData['product_name']];
    }
    if (empty($lineItems)) $errors[] = 'At least one product line is required.';

    if (empty($errors)) {
        $order_number = 'ORD'.time();
        $grand_total = 0;
        $conn->begin_transaction();
        try {
            foreach ($lineItems as $item) {
                $line_total = $item['quantity'] * $item['unit_price'];
                $grand_total += $line_total;
                
                // FIXED: Insert into sales_record with proper line_total calculation
                $stmt = $conn->prepare("INSERT INTO sales_record
                    (order_number, customer_name, quantity, sale_date, payment_method, product_name, total_amount, product_id, line_total)
                    VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissdid",
                    $order_number,
                    $customer,
                    $item['quantity'],
                    $_POST['payment_method'],
                    $item['product_name'],
                    $line_total,  // This represents the line total
                    $item['product_id'],
                    $line_total   // Store in line_total column as well
                );
                $stmt->execute();
                $stmt->close();
                
                // update products stock
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
                $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
                $stmt->execute();
                $stmt->close();
                
                $invoiceDetails[] = [
                    'product_name'=>$item['product_name'],
                    'quantity'=>$item['quantity'],
                    'unit_price'=>$item['unit_price'],
                    'line_total'=>$line_total
                ];
            }
            $conn->commit();
            $success = "Invoice <strong>".htmlspecialchars($order_number)."</strong> submitted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Transaction error: " . $e->getMessage();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OceanGas Sales Invoice</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- jsPDF Library for PDF Download -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Optional: Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  
  <style>
    body { 
      background: #eef2f7; 
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }
    /* Sidebar Styling */
    .sidebar {
      min-height: 100vh;
      background: #6a008a;
      padding: 20px;
      width: 250px;
    }
    
    .sidebar .nav-link {
      color: #fff;
      margin: 0.5px 0;
    }
    .sidebar .nav-link:hover {
      background-color: rgba(255,255,255,0.2);
      border-radius: 5px; 
    }
    .sidebar .nav-link.active {
      background: rgba(255,255,255,0.3);
      font-weight: bold;
    }
    /* Main content area */
    .main-content {
      padding: 2rem;
    }
    /* Invoice container and watermark */
    .invoice-container { 
      position: relative; 
      padding: 2rem; 
      background: #fff; 
      border-radius: 0.5rem; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
    }
    .watermark::after {
      content: '<?php echo addslashes($salesName); ?>';
      position: absolute;
      bottom: 1rem;
      right: 1rem;
      font-size: 5rem;
      color: rgba(0,0,0,0.05);
      transform: rotate(-30deg);
      pointer-events: none;
    }
    .header-title {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      color: #4a4a4a;
    }
    .greeting {
      font-style: italic;
      color: #6c757d;
    }
    .table thead {
      background: #007bff;
      color: #fff;
    }
    .btn-add {
      background: #28a745;
      color: #fff;
    }
    .btn-remove {
      background: #dc3545;
      color: #fff;
    }
    .action-buttons {
      margin-top: 2rem;
    }
    #invoiceSummary {
      margin-top: 2rem;
    }
    .grand-total {
      font-size: 1.2em;
      font-weight: bold;
      color: #28a745;
    }
          .dropdown-btn {
    padding: 10px;
    width: 100%;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    font-size: 16px;
    color: white;
}
      /* Container hidden by default */
.dropdown-container {
    display: none;
    background-color:#6a008a;
    padding-left: 20px;
}

/* Show container when active */
.dropdown-btn.active, .dropdown-btn2.active + .dropdown-container {
    display: block;
    
}

/* Optional: hover effect */
.dropdown-container a {
    color: white;
    padding: 8px 0;
    display: block;
    text-decoration: none;
}

.dropdown-container a:hover {
    background-color:rgba(255,255,255,0.2);
}
  </style>
</head>
<body>
<script>
  // If we're inside an iframe, window.self !== window.top
  if (window.self !== window.top) {
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Remove the sidebar element entirely
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) sidebar.remove();

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

<div class="container-fluid">
  <div class="row">
  
    <!-- Sidebar -->
    <nav class="col-12 col-md-2 sidebar d-flex flex-column p-3">
      <h2 class="text-white mt-1 mx-1">Sales Panel</h2>
      <ul class="nav flex-column">
        <li class="nav-item mb-2">
          <a href="sales_staff_dashboard.php" class="nav-link text-white">
          <i class="fas fa-chart-line"></i> Cockpit
          </a>
        </li>
        <li class="nav-item mb-2">
          <a href="/OceanGas/staff/sales_invoice.php" class="nav-link <?php echo ($current_page === 'sales_invoice.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice"></i> Sales invoice</a>
        </li>
        <li class="nav-item mb-2">
          <a href="stock_sales.php" class="nav-link text-white">
          <i class="fas fa-box"></i> Stock Inventory
          </a>
        </li>
        <li class="nav-item mb-2">
          <a href="reports.php" class="nav-link text-white">
          <i class="fas fa-clipboard-list"></i> Reports
          </a>
        </li>
             <div class="dropdown">
    <button class="dropdown-btn">
     <i class="fas fa-truck"></i>
     <span>Deliveries</span>
     <i class="fas fa-caret-down ms-auto"></i>
    </button>
<div class="dropdown-container">
  <a href="add_delivery_sales.php">Add Delivery</a>
  <a href="view_deliveries_sales.php">View Deliveries</a>
</div>
</div>
      
      </ul>
    </nav>
    <!-- End Sidebar -->
    
    <!-- Main Content -->
    <div class="col main-content">
      <div class="invoice-container watermark">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="header-title"><i class="fas fa-file-invoice"></i> New Invoice</h1>
          <span class="greeting">"Crafting deals, one invoice at a time."</span>
        </div>
        <p class="text-end text-muted">Sales Officer: <strong><?php echo htmlspecialchars($salesName); ?></strong></p>
        
        <?php if ($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
          
          <!-- Invoice Summary -->
          <div id="invoiceSummary">
            <h4>Invoice Details</h4>
            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order_number); ?></p>
            <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($invoiceCustomer); ?></p>
            <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($_POST['payment_method'] ?? 'N/A'); ?></p>
            
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Product Name</th>
                  <th>Quantity</th>
                  <th>Unit Price (Ksh)</th>
                  <th>Line Total (Ksh)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($invoiceDetails as $line): ?>
                <tr>
                  <td><?php echo htmlspecialchars($line['product_name']); ?></td>
                  <td><?php echo $line['quantity']; ?></td>
                  <td><?php echo number_format($line['unit_price'], 2); ?></td>
                  <td><?php echo number_format($line['line_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="3" class="text-end">Grand Total (Ksh):</th>
                  <th class="grand-total"><?php echo number_format($grand_total, 2); ?></th>
                </tr>
              </tfoot>
            </table>
          </div>
          
          <!-- Action Buttons -->
          <div class="action-buttons text-center">
            <button class="btn btn-secondary" onclick="window.print();">
              <i class="fas fa-print"></i> Print Invoice
            </button>
            <button class="btn btn-info" id="downloadPDF">
              <i class="fas fa-download"></i> Download as PDF
            </button>
            <a class="btn btn-success" href="<?php echo $_SERVER['PHP_SELF']; ?>">
              <i class="fas fa-plus"></i> New Invoice
            </a>
            <a class="btn btn-primary" href="/OceanGas/staff/sales_staff_dashboard.php">
              <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
          </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <?php foreach($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
          </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" id="invoiceForm">
          <div class="mb-4">
            <label class="form-label">Customer Name</label>
            <input name="customer" class="form-control form-control-lg" value="<?php echo htmlspecialchars($customer); ?>" placeholder="Enter customer name" required>
          </div>
          
          <table class="table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Product</th>  
                <th>In Stock</th>
                <th>Unit Price (Ksh)</th>
                <th>Quantity</th>
                <th>Line Total (Ksh)</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="itemsBody">
              <tr class="item-row">
                <td>
                  <select name="product[]" class="form-select product-select" required>
                    <option value="">-- select product --</option>
                    <?php foreach($productsList as $p): ?>
                    <option
                      value="<?= $p['product_id'] ?>"
                      data-price="<?= number_format($p['price'],2) ?>"
                      data-available="<?= $p['available_quantity'] ?>"
                    >
                      <?= htmlspecialchars($p['product_name']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td class="available-cell">0</td>
                <td><input name="unit_price[]" class="form-control unit-price" readonly></td>
                <td><input name="quantity[]" class="form-control qty" type="number" min="1"></td>
                <td><input name="line_total[]" class="form-control line-total" readonly></td>
                <td>
                  <button type="button" class="btn btn-remove remove-row"><i class="fas fa-times"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div class="row">
            <div class="col-md-6">
              <button type="button" class="btn btn-add" id="addRow">
                <i class="fas fa-plus"></i> Add Product
              </button>
            </div>
            <div class="col-md-6 text-end">
              <label class="me-2">Payment Method:</label>
              <select name="payment_method" class="form-select d-inline-block w-auto">
                <option value="M-PESA">M-PESA</option>
                <option value="CASH">Cash</option>
              </select>
            </div>
          </div>
          
          <div class="text-center mt-4">
            <button class="btn btn-primary btn-lg">
              <i class="fas fa-paper-plane"></i> Submit Invoice
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <!-- End Main Content -->
    
  </div>
</div>

<!-- Additional Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
  (function(){
    const products = <?php echo json_encode($productsList); ?>;
    const body = document.getElementById('itemsBody');
    
    if (document.getElementById('addRow')) {
      document.getElementById('addRow').onclick = addRow;
    }
    
    if (body) {
      body.addEventListener('change', updateRow);
      body.addEventListener('input', updateRow);
      body.addEventListener('click', e => {
          if(e.target.closest('.remove-row')) {
              const rows = body.querySelectorAll('.item-row');
              if (rows.length > 1) {
                  e.target.closest('tr').remove();
              } else {
                  alert('At least one product line is required.');
              }
          }
      });
    }
    
    function addRow(){
      const row = body.querySelector('.item-row').cloneNode(true);
      row.querySelectorAll('input').forEach(i => i.value = '');
      row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
      row.querySelector('.available-cell').textContent = '0';
      body.append(row);
    }
    
    function updateRow(e){
      const row = e.target.closest('tr');
      if (!row) return;
      
      const sel = row.querySelector('.product-select');
      const price = row.querySelector('.unit-price');
      const avail = row.querySelector('.available-cell');
      const qty = row.querySelector('.qty');
      const total = row.querySelector('.line-total');
      
      if (!sel || !price || !avail || !qty || !total) return;
      
      const prod = products.find(p => p.product_id == sel.value) || {price:0, available_quantity:0};
      price.value = parseFloat(prod.price).toFixed(2);
      avail.textContent = prod.available_quantity;
      
      const quantity = parseFloat(qty.value) || 0;
      const unitPrice = parseFloat(prod.price) || 0;
      total.value = (quantity * unitPrice).toFixed(2);
      
      // Validate stock
      if (quantity > prod.available_quantity && prod.available_quantity > 0) {
        qty.setCustomValidity(`Only ${prod.available_quantity} units available in stock`);
        row.style.backgroundColor = '#ffebee';
      } else {
        qty.setCustomValidity('');
        row.style.backgroundColor = '';
      }
    }
    
    // IMPROVED PDF Generation
    if (document.getElementById('downloadPDF')) {
      document.getElementById('downloadPDF').addEventListener('click', function(){
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        let yPosition = 20;
        
        // Header
        doc.setFontSize(20);
        doc.text("OCEANGAS INVOICE", 20, yPosition);
        yPosition += 10;
        
        // Invoice details
        doc.setFontSize(12);
        doc.text("Order Number: <?php echo htmlspecialchars($order_number); ?>", 20, yPosition);
        yPosition += 7;
        doc.text("Customer: <?php echo htmlspecialchars($invoiceCustomer); ?>", 20, yPosition);
        yPosition += 7;
        doc.text("Date: <?php echo date('Y-m-d H:i:s'); ?>", 20, yPosition);
        yPosition += 7;
        doc.text("Payment: <?php echo htmlspecialchars($_POST['payment_method'] ?? 'N/A'); ?>", 20, yPosition);
        yPosition += 15;
        
        // Table header
        doc.setFontSize(10);
        doc.text("Product", 20, yPosition);
        doc.text("Qty", 120, yPosition);
        doc.text("Price", 140, yPosition);
        doc.text("Total", 170, yPosition);
        yPosition += 5;
        
        // Draw line
        doc.line(20, yPosition, 190, yPosition);
        yPosition += 7;
        
        // Invoice items
        const invoiceDetails = <?php echo json_encode($invoiceDetails ?? []); ?>;
        invoiceDetails.forEach(item => {
          doc.text(item.product_name.substring(0, 25), 20, yPosition);
          doc.text(item.quantity.toString(), 120, yPosition);
          doc.text("Ksh " + parseFloat(item.unit_price).toFixed(2), 140, yPosition);
          doc.text("Ksh " + parseFloat(item.line_total).toFixed(2), 170, yPosition);
          yPosition += 7;
        });
        
        // Total line
        yPosition += 5;
        doc.line(20, yPosition, 190, yPosition);
        yPosition += 7;
        doc.setFontSize(12);
        doc.text("GRAND TOTAL: Ksh <?php echo number_format($grand_total, 2); ?>", 140, yPosition);
        
        // Footer
        yPosition += 20;
        doc.setFontSize(8);
        doc.text("Sales Officer: <?php echo htmlspecialchars($salesName); ?>", 20, yPosition);
        doc.text("Thank you for your business!", 20, yPosition + 5);
        
        doc.save("invoice_<?php echo $order_number; ?>.pdf");
      });
    }
    
  })();
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dropdown-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.classList.toggle('active');
        const container = btn.nextElementSibling;
        container.style.display = container.style.display === 'block'
          ? 'none'
          : 'block';
      });
    });
  });
</script>
</body>
</html>