<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role'])) {
    header("Location: staff_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $delivery_date = $_POST["delivery_date"];
    $assigned_to = $_POST["assigned_to"];
    $notes = $_POST["notes"];
    $source = $_POST["source"] ?? 'order'; // 'order' or 'invoice'

    // Determine which key to use
    $order_id = isset($_POST["order_id"]) ? $_POST["order_id"] : null;
    $order_number = isset($_POST["order_number"]) ? $_POST["order_number"] : null;

    // Check if delivery already exists
    if ($source === 'invoice') {
        $check = $conn->prepare("SELECT * FROM deliveries WHERE order_number = ?");
        $check->bind_param("s", $order_number);
    } else {
        $check = $conn->prepare("SELECT * FROM deliveries WHERE order_id = ?");
        $check->bind_param("i", $order_id);
    }
    
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('A delivery for this order already exists.'); window.location.href='add_delivery.php?filter=$source';</script>";
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into deliveries
        $stmt = $conn->prepare("INSERT INTO deliveries (order_id, order_number, assigned_to, delivery_date, notes) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $order_id, $order_number, $assigned_to, $delivery_date, $notes);
        $stmt->execute();

        // Optional: update order status
        // if ($source === 'order') {
        //     $updateStmt = $conn->prepare("UPDATE orders SET order_status = 'out_for_delivery' WHERE order_id = ?");
        //     $updateStmt->bind_param("i", $order_id);
        //     $updateStmt->execute();
        // }

        $conn->commit();

        echo "<script>alert('Delivery added successfully!');</script>";
        if ($_SESSION['staff_role'] === 'sales') {
            echo "<script>window.location.href='view_delivery_sales.php';</script>";
        } else {
            echo "<script>window.location.href='view_deliveries.php';</script>";
        }
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error adding delivery. Please try again.');</script>";
    }
}

 $filter = isset($_GET['filter']) && $_GET['filter'] === 'invoice'
                ? 'invoice'
                : 'all';
                $invoiceOrders = [];
$invoiceQuery = "SELECT DISTINCT order_number, customer_name, total_amount, sale_date 
                 FROM sales_record 
                 WHERE order_number LIKE 'ORD%' 
                   AND order_number NOT IN (SELECT DISTINCT order_number FROM deliveries)";
$invoiceResult = mysqli_query($conn, $invoiceQuery);
while ($row = mysqli_fetch_assoc($invoiceResult)) {
    $invoiceOrders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Delivery</title>
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
        .sidebar a.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        .dropdown-btn, .dropdown-btn {
            padding: 10px;
            width: 100%;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 16px;
            color: white;
        }
        .dropdown-container {
            display: none;
            background-color:#6a008a;
            padding-left: 20px;
        }
        .dropdown-btn.active + .dropdown-container {
            display: block;
        }
        .content {
            margin-left: 270px;
            padding: 30px;
            flex-grow: 1;
        }
        .filter-btn {
            margin-right: 10px;
        }
        .filter-btn.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
<nav class="sidebar"> 
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/OceanGas/staff/stock_admin.php"><i class="fas fa-box"></i> Stock/Inventory</a>
    <a href="/OceanGas/staff/users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="/OceanGas/staff/finance.php"><i class="fas fa-dollar-sign"></i> Finance</a>
<div class="dropdown">
    <button class="dropdown-btn">
  <i class="fas fa-truck"></i>
  <span>Deliveries</span>
  <i class="fas fa-caret-down ms-auto"></i>
</button>
<div class="dropdown-container">
  <a href="/OceanGas/staff/add_delivery.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame"
    class="active">Add Delivery
 </a>
  <a href="view_deliveries.php">View Deliveries</a>
</div>

            </div>
    <div class="dropdown">
    <button class="dropdown-btn" id="btnProcurement">
        <i class="fas fa-truck"></i> Procurement <i class="fas fa-caret-down"></i>
    </button>
        <div class="dropdown-container">

<a href="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Dashboard
</a>

<a href="/OceanGas/staff/purchase_history_reports.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Purchase History
</a>

<a href="/OceanGas/staff/suppliers.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Suppliers
</a>

  <a href="/OceanGas/staff/financial_overview.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame">Financial Overview </a>

        </div>
    </div>
    <div class="dropdown">
        <button class="dropdown-btn">
            <i class="fas fa-shopping-cart"></i> Sales <i class="fas fa-caret-down"></i>
        </button>
        <div class="dropdown-container">
<a href="/OceanGas/staff/sales_staff_dashboard.php?embedded=1"
  onclick="
    document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame"> Dashboard
</a>            

<a href="/OceanGas/staff/sales_invoice.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame"> Sales Invoice 
</a>

 <a href="/OceanGas/staff/reports.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame">Reports 
</a>

        </div>
    </div>
        </div>
    </nav>
    <div class="content" style=" padding: 20px; width: calc(100% - 250px);">
<iframe 
  id="mainFrame"
  name="main-frame"
  src="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1"
  style="display:none; width:100%; height:1500px; border:none;"
></iframe>
<div id="mainContent">
   
    <h2 class="mb-4">Add Delivery</h2>
 <!-- Toggle buttons -->
        <div class="mb-3">
            <a href="add_delivery.php?filter=all"
               class="btn filter-btn <?= $filter === 'all' ? 'btn-primary active' : 'btn-outline-primary' ?>">
               All Deliveries
            </a>
            <a href="add_delivery.php?filter=invoice"
               class="btn filter-btn <?= $filter === 'invoice' ? 'btn-primary active' : 'btn-outline-primary' ?>">
               Invoice Deliveries
            </a>
        </div>
<?php
// Get all customers who have confirmed orders
$customers = [];
$customerQuery = "SELECT DISTINCT c.cust_id, c.F_name, c.L_name, c.Email 
                  FROM customers c 
                  INNER JOIN orders o ON c.cust_id = o.cust_id 
                  WHERE o.order_status = 'confirmed' 
                  AND o.order_id NOT IN (SELECT order_id FROM deliveries)
                  ORDER BY c.F_name, c.L_name";
$customerResult = mysqli_query($conn, $customerQuery);
while ($row = mysqli_fetch_assoc($customerResult)) {
    $customers[] = $row;
}

// Get all confirmed orders (for initial load)
$confirmedOrders = [];
$query = "SELECT o.order_id, o.order_number, o.invoice_summary, o.total_amount, o.order_date, 
          c.F_name, c.L_name, c.cust_id
          FROM orders o 
          LEFT JOIN customers c ON o.cust_id = c.cust_id
          WHERE o.order_status = 'confirmed' 
          AND o.order_id NOT IN (SELECT order_id FROM deliveries)
          ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $confirmedOrders[] = $row;
}
?>

<form method="POST">
        <!-- Customer Selection -->
        <div class="mb-3">
            <label for="customer_id" class="form-label">Select Customer</label>
            <select name="customer_id" id="customer_id" class="form-control" onchange="filterOrdersByCustomer()">
                <option value="">-- All Customers --</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= $customer['cust_id'] ?>">
                        <?= htmlspecialchars($customer['F_name'] . ' ' . $customer['L_name']) ?> 
                        (<?= htmlspecialchars($customer['Email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Order Selection -->
        <div class="mb-3">
            <label for="order_id" class="form-label">Order Number</label>
            <select name="order_id" id="order_id" class="form-control" required onchange="showOrderDetails()">
                <option value="">-- Select Confirmed Order --</option>
                <?php foreach ($confirmedOrders as $order): ?>
                    <option value="<?= $order['order_id'] ?>" 
                            data-customer-id="<?= $order['cust_id'] ?>"
                            data-summary="<?= htmlspecialchars($order['invoice_summary']) ?>"
                            data-amount="<?= $order['total_amount'] ?>"
                            data-date="<?= $order['order_date'] ?>"
                            data-order-number="<?= htmlspecialchars($order['order_number']) ?>">
                        <?= htmlspecialchars($order['order_number']) ?>
                        <?php if ($order['F_name']): ?>
                            - <?= htmlspecialchars($order['F_name'] . ' ' . $order['L_name']) ?>
                        <?php endif; ?>
                        (KES <?= number_format($order['total_amount']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Order Details Display -->
        <div id="order-details" class="mb-3" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h5>Order Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Order Number:</strong> <span id="order-number"></span></p>
                    <p><strong>Order Date:</strong> <span id="order-date"></span></p>
                    <p><strong>Items:</strong> <span id="order-summary"></span></p>
                    <p><strong>Total Amount:</strong> KES <span id="order-amount"></span></p>
                </div>
            </div>
        </div>

        <!-- Driver Assignment -->
        <div class="mb-3">
            <label for="assigned_to" class="form-label">Assign To:</label>
            <select class="form-control" name="assigned_to" required>
                <option value="">-- Select Driver --</option>
                <option value="Kevin">Kevin</option>
                <option value="Joe">Joe</option>
                <option value="Sarah">Sarah</option>
                <option value="John">John</option>
                <option value="Yusra">Yusra</option>
            </select>
        </div>

        <!-- Delivery Date -->
        <div class="mb-3">
            <label for="delivery_date" class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" id="delivery_date" class="form-control" required>
        </div>

        <!-- Notes -->
        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Any special delivery instructions..."></textarea>
        </div>
       
        <button type="submit" class="btn btn-primary">Add Delivery</button>
        <a href="view_deliveries.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</div>
<?php if ($filter === 'invoice'): ?>
    <form method="POST" action="add_delivery.php">
        <h4>Invoice Delivery</h4>

        <div class="mb-3">
            <label for="invoice_order_number" class="form-label">Select Invoice Order</label>
            <select name="order_number" id="invoice_order_number" class="form-control" required onchange="showInvoiceDetails()">
                <option value="">-- Select Invoice Order --</option>
                <?php foreach ($invoiceOrders as $order): ?>
                    <option value="<?= htmlspecialchars($order['order_number']) ?>"
                            data-summary="<?= htmlspecialchars($order['customer_name']) ?>"
                            data-amount="<?= $order['total_amount'] ?>"
                            data-date="<?= $order['sale_date'] ?>">
                        <?= htmlspecialchars($order['order_number']) ?> - <?= htmlspecialchars($order['customer_name']) ?> (KES <?= number_format($order['total_amount'], 2) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Display details -->
        <div id="invoice-details" class="mb-3" style="display: none;">
            <div class="card">
                <div class="card-header">Invoice Details</div>
                <div class="card-body">
                    <p><strong>Customer:</strong> <span id="inv-customer-name"></span></p>
                    <p><strong>Sale Date:</strong> <span id="inv-sale-date"></span></p>
                    <p><strong>Total Amount:</strong> KES <span id="inv-total-amount"></span></p>
                </div>
            </div>
        </div>

        <!-- Assign To -->
        <div class="mb-3">
            <label for="assigned_to" class="form-label">Assign To:</label>
            <select class="form-control" name="assigned_to" required>
                <option value="">-- Select Driver --</option>
                <option value="Kevin">Kevin</option>
                <option value="Joe">Joe</option>
                <option value="Sarah">Sarah</option>
                <option value="John">John</option>
                <option value="Yusra">Yusra</option>
            </select>
        </div>

        <!-- Delivery Date -->
        <div class="mb-3">
            <label for="delivery_date" class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" required min="<?= date('Y-m-d') ?>">
        </div>

        <!-- Notes -->
        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" class="form-control" placeholder="Delivery notes..."></textarea>
        </div>

        <input type="hidden" name="source" value="invoice">

        <button type="submit" class="btn btn-primary">Add Delivery</button>
        <a href="view_deliveries.php" class="btn btn-secondary">Cancel</a>
    </form>
<?php endif; ?>
<script>
// Store all orders data for filtering
const allOrders = <?= json_encode($confirmedOrders) ?>;

function filterOrdersByCustomer() {
    const customerSelect = document.getElementById('customer_id');
    const orderSelect = document.getElementById('order_id');
    const selectedCustomerId = customerSelect.value;
    
    // Clear current options except the first one
    orderSelect.innerHTML = '<option value="">-- Select Confirmed Order --</option>';
    
    // Filter orders based on selected customer
    const filteredOrders = selectedCustomerId === '' 
        ? allOrders 
        : allOrders.filter(order => order.cust_id == selectedCustomerId);
    
    // Add filtered options
    filteredOrders.forEach(order => {
        const option = document.createElement('option');
        option.value = order.order_id;
        option.setAttribute('data-customer-id', order.cust_id || '');
        option.setAttribute('data-summary', order.invoice_summary || '');
        option.setAttribute('data-amount', order.total_amount || '0');
        option.setAttribute('data-date', order.order_date || '');
        option.setAttribute('data-order-number', order.order_number || '');
        
        let optionText = `${order.order_number}`;
        if (order.F_name) {
            optionText += ` - ${order.F_name} ${order.L_name}`;
        }
        optionText += ` (KES ${new Intl.NumberFormat().format(order.total_amount || 0)})`;
        
        option.textContent = optionText;
        orderSelect.appendChild(option);
    });
    
    // Hide order details when customer changes
    document.getElementById('order-details').style.display = 'none';
}

function showOrderDetails() {
    const orderSelect = document.getElementById('order_id');
    const selectedOption = orderSelect.selectedOptions[0];
    const detailsDiv = document.getElementById('order-details');
    
    if (selectedOption && selectedOption.value) {
        const orderNumber = selectedOption.getAttribute('data-order-number') || 'N/A';
        const summary = selectedOption.getAttribute('data-summary') || 'No items specified';
        const amount = selectedOption.getAttribute('data-amount') || '0';
        const date = selectedOption.getAttribute('data-date') || 'Unknown';
        
        document.getElementById('order-number').textContent = orderNumber;
        document.getElementById('order-summary').textContent = summary;
        document.getElementById('order-amount').textContent = new Intl.NumberFormat().format(amount);
        document.getElementById('order-date').textContent = new Date(date).toLocaleDateString();
        
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
    }
}

// Set minimum date to today
document.getElementById('delivery_date').min = new Date().toISOString().split('T')[0];

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

  // Auto-open Deliveries dropdown (assumes it's the first one)
  if (dropdowns.length > 0) {
    dropdowns[0].click();
  }
});

function loadFrame(url) {
    const frame = document.getElementById('mainFrame');
    // only set src once, so subsequent shows don't reload
    if (frame.src !== url) {
      frame.src = url;
    }
    // toggle visibility (you can also just always show: frame.style.display = 'block')
    frame.style.display = 'block';
}

// Optional: delegate to _all_ links with data‐target="main-frame":
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[data-target="main-frame"]').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        loadFrame(link.href);
      });
    });
});
function showInvoiceDetails() {
    const select = document.getElementById('invoice_order_number');
    const selected = select.options[select.selectedIndex];

    if (selected && selected.value) {
        document.getElementById('inv-customer-name').textContent = selected.getAttribute('data-summary');
        document.getElementById('inv-sale-date').textContent = new Date(selected.getAttribute('data-date')).toLocaleDateString();
        document.getElementById('inv-total-amount').textContent = parseFloat(selected.getAttribute('data-amount')).toLocaleString();

        document.getElementById('invoice-details').style.display = 'block';
    } else {
        document.getElementById('invoice-details').style.display = 'none';
    }
}
</script>
</body>
</html>