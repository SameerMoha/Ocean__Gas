<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role'])) {
    header("Location: staff_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = $_POST["order_id"];
    $delivery_date = $_POST["delivery_date"];
    $assigned_to = $_POST["assigned_to"];
    $notes = $_POST["notes"];

    // Prevent duplicate deliveries
    $check = $conn->prepare("SELECT * FROM deliveries WHERE order_id = ?");
    $check->bind_param("i", $order_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "A delivery for this order already exists.";
    } else {
        // Start transaction to ensure both operations succeed
        $conn->begin_transaction();
        
        try {
            // Insert delivery record
            $stmt = $conn->prepare("INSERT INTO deliveries (order_id, assigned_to, delivery_date, notes) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $order_id, $assigned_to, $delivery_date, $notes);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Set success flag instead of showing alert
            $success = true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error adding delivery. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #6a008a;
            --hover-transition: all 0.3s ease;
        }

        body {
            display: flex;
            background: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: var(--hover-transition);
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
            border-radius: 8px;
            transition: var(--hover-transition);
            position: relative;
            overflow: hidden;
            background: none;
        }

        .sidebar a:hover {
            background: #5a0076;
            transform: translateX(5px);
        }

        .sidebar a.active {
            background: #4a005f;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .sidebar a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: white;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .sidebar a:hover::after {
            transform: scaleX(1);
        }

        /* Dropdown Styles */
        .dropdown-btn {
            padding: 12px 15px;
            width: 100%;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 16px;
            color: white;
            border-radius: 8px;
            transition: var(--hover-transition);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dropdown-btn:hover {
            background: #5a0076;
        }

        .dropdown-container {
            display: none;
            background-color: #6a008a;
            padding-left: 20px;
            border-radius: 8px;
            margin: 5px 0;
            transition: var(--hover-transition);
        }

        /* Form Styles */
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #dee2e6;
            transition: var(--hover-transition);
            background: white;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(106, 0, 138, 0.25);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .form-label {
            font-weight: 600;
            color: #444;
            margin-bottom: 8px;
            transition: var(--hover-transition);
        }

        /* Button Styles */
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            transition: var(--hover-transition);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 0, 138, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        /* Card Styles */
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: var(--hover-transition);
            border: none;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Order Details Animation */
        #order-details {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        #order-details.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Content Area */
        .content {
            margin-left: 270px;
            padding: 30px;
            flex-grow: 1;
            background: #f8f9fa;
        }

        /* Custom Select Styles */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236a008a' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 30px;
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<!-- Loading Overlay -->
<div class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<nav class="sidebar"> 
    <h2>Sales Panel</h2>
    <a href="/OceanGas/staff/sales_staff_dashboard.php" class="<?php echo ($current_page === 'sales_staff_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Cockpit</a>
    <a href="/OceanGas/staff/sales_invoice.php"><i class="fas fa-file-invoice"></i> Sales Invoice</a>
    <a href="/OceanGas/staff/stock_sales.php"><i class="fas fa-box"></i> Stock/Inventory</a>
    <a href="/OceanGas/staff/reports.php"><i class="fas fa-clipboard-list"></i> Reports</a>
    <div class="dropdown">
        <button class="dropdown-btn">
            <i class="fas fa-truck"></i>
            <span>Deliveries</span>
            <i class="fas fa-caret-down ms-auto"></i>
        </button>
        <div class="dropdown-container">
            <a href="add_delivery_sales.php" class="active">Add Delivery</a>
            <a href="view_deliveries_sales.php">View Deliveries</a>
        </div>
    </div>
</nav>

<div class="content" style=" padding: 20px; width: calc(100% - 250px);">
    <div id="mainContent">
        <h2 class="mb-4">Add Delivery</h2>

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
            <a href="view_deliveries_sales.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
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
        setTimeout(() => {
            detailsDiv.classList.add('show');
        }, 50);
    } else {
        detailsDiv.classList.remove('show');
        setTimeout(() => {
            detailsDiv.style.display = 'none';
        }, 500);
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

  // Auto-open Deliveries dropdown
  if (dropdowns.length > 0) {
    dropdowns[0].click();
  }
});

// Form submission handling
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show loading overlay
    document.querySelector('.loading-overlay').style.display = 'flex';
    
    // Show SweetAlert2 loading
    Swal.fire({
        title: 'Processing',
        html: 'Adding delivery...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Submit the form
    this.submit();
});

// Show success message
<?php if (isset($success)): ?>
Swal.fire({
    title: 'Success!',
    text: 'Delivery added successfully!',
    icon: 'success',
    confirmButtonColor: '#6a008a',
    showClass: {
        popup: 'animate__animated animate__fadeInDown'
    },
    hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
    }
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = 'view_deliveries_sales.php';
    }
});
<?php endif; ?>

// Show error message
<?php if (isset($error)): ?>
Swal.fire({
    title: 'Error!',
    text: '<?php echo $error; ?>',
    icon: 'error',
    confirmButtonColor: '#6a008a',
    showClass: {
        popup: 'animate__animated animate__fadeInDown'
    },
    hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
    }
});
<?php endif; ?>
</script>
</body>
</html>