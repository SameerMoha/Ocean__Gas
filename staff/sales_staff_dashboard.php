<?php
session_start();
// Restrict access to only sales staff
if (!isset($_SESSION['staff_username']) || $_SESSION['staff_role'] !== 'sales') {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

// Process new sales order form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_sale'])) {
    // Get and sanitize form inputs
    $order_no = trim($_POST['order_no']);
    $customer_name = trim($_POST['customer_name']);
    $contact = trim($_POST['contact']);
    $delivery_location = trim($_POST['delivery_location']);
    $apartment_number = trim($_POST['apartment_number']);
    $qty6 = intval($_POST['qty6']);
    $qty12 = intval($_POST['qty12']);
    
    $assigned_to = $_SESSION['staff_username'];
    $status = 'Pending';
    
    // Assume sample prices for each cylinder type; adjust as needed.
    $price6 = 100;
    $price12 = 150;
    
    // Insert sales record for 6kg cylinder if quantity > 0
    if ($qty6 > 0) {
        $total6 = $qty6 * $price6;
        $product6 = "6kg Gas Cylinder";
        $stmt = $conn->prepare("INSERT INTO sales (order_no, customer_name, contact, product, quantity, status, assigned_to, total, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssisiis", $order_no, $customer_name, $contact, $product6, $qty6, $status, $assigned_to, $total6);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert sales record for 12kg cylinder if quantity > 0
    if ($qty12 > 0) {
        $total12 = $qty12 * $price12;
        $product12 = "12kg Gas Cylinder";
        $stmt = $conn->prepare("INSERT INTO sales (order_no, customer_name, contact, product, quantity, status, assigned_to, total, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssisiis", $order_no, $customer_name, $contact, $product12, $qty12, $status, $assigned_to, $total12);
        $stmt->execute();
        $stmt->close();
    }
    
    // Remove the order from orders table (order review) if it exists.
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_no = ?");
    $stmt->bind_param("s", $order_no);
    $stmt->execute();
    $stmt->close();
    
    $success = "New sales order created successfully.";
}

// Fetch sales orders for the logged-in sales staff
$salesQuery = "SELECT * FROM sales WHERE assigned_to = ?";
$stmt = $conn->prepare($salesQuery);
$stmt->bind_param("s", $_SESSION['staff_username']);
$stmt->execute();
$result = $stmt->get_result();

$salesData = [];
while ($row = $result->fetch_assoc()) {
    $salesData[] = $row;
}
$stmt->close();

// Calculate summary metrics based on fetched sales orders
$totalSales = count($salesData);
$pendingOrders = 0;
$deliveriesMade = 0;
foreach ($salesData as $sale) {
    if ($sale['status'] == 'Pending') {
        $pendingOrders++;
    }
    if ($sale['status'] == 'Approved' || $sale['status'] == 'Delivered') {
        $deliveriesMade++;
    }
}

// Fetch orders from the orders table to populate the "New Orders" section.
$ordersQuery = "SELECT order_no, first_name, last_name, phone_number, delivery_location, apartment_number, cart_summary FROM orders ORDER BY created_at DESC";
$ordersResult = $conn->query($ordersQuery);
$ordersData = [];
while ($order = $ordersResult->fetch_assoc()) {
    $ordersData[] = $order;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Staff Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Arial', sans-serif;
    }
    /* Navbar styling */
    .navbar-custom {
      background-color: #6a008a;
    }
    .navbar-custom .navbar-brand,
    .navbar-custom .nav-link {
      color: #fff !important;
    }
    .container { margin-top: 20px; }
    /* Summary Cards */
    .summary-card {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }
    .summary-card h5 {
      color: #6a008a;
      font-weight: bold;
    }
    .summary-card p {
      font-size: 2rem;
      color: #e74c3c;
      font-weight: bold;
    }
    /* Form Section */
    .form-section {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 20px;
      margin-bottom: 30px;
    }
    /* Chart Cards */
    .chart-card {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 20px;
      margin-bottom: 20px;
    }
    /* Table styling */
    .table-container { margin-top: 30px; }
    table { background: #fff; width: 100%; border-collapse: collapse; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #6a008a; color: #fff; }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
      <a class="navbar-brand" href="#">Sales Dashboard</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="/OceanGas/logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  
  <!-- Main Content -->
  <div class="container">
    <!-- Summary Cards -->
    <div class="row">
      <div class="col-md-4">
        <div class="summary-card">
          <h5>Total Sales Orders</h5>
          <p><?php echo $totalSales; ?></p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="summary-card">
          <h5>Pending Orders</h5>
          <p><?php echo $pendingOrders; ?></p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="summary-card">
          <h5>Deliveries Made</h5>
          <p><?php echo $deliveriesMade; ?></p>
        </div>
      </div>
    </div>
    
    <!-- New Sales Order Form -->
    <div class="form-section">
      <h4>Create New Sales Order</h4>
      <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php elseif (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>
      <form action="" method="POST">
        <div class="row mb-3">
          <div class="col-md-3">
            <label for="order_no" class="form-label">Order No.</label>
            <input type="text" name="order_no" id="order_no" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label for="customer_name" class="form-label">Customer Name</label>
            <input type="text" name="customer_name" id="customer_name" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label for="contact" class="form-label">Phone Number</label>
            <input type="text" name="contact" id="contact" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label for="delivery_location" class="form-label">Delivery Location</label>
            <input type="text" name="delivery_location" id="delivery_location" class="form-control" required>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3">
            <label for="apartment_number" class="form-label">Apartment/House No.</label>
            <input type="text" name="apartment_number" id="apartment_number" class="form-control" required>
          </div>
          <!-- Order Items Section -->
          <div class="col-md-3">
            <label for="qty6" class="form-label">6kg Cylinders</label>
            <input type="number" name="qty6" id="qty6" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label for="qty12" class="form-label">12kg Cylinders</label>
            <input type="number" name="qty12" id="qty12" class="form-control" min="0" value="0">
          </div>
        </div>
        <button type="submit" name="create_sale" class="btn btn-primary">Create Sales Order</button>
      </form>
    </div>
    
    <!-- Orders from Orders Table Section -->
    <div class="table-container">
      <h4>Orders Overview</h4>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Order No.</th>
            <th>Customer Name</th>
            <th>Phone Number</th>
            <th>Delivery Location</th>
            <th>Apartment/House No.</th>
            <th>Order Items</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($ordersData)): ?>
            <?php foreach ($ordersData as $order): ?>
              <tr>
                <td><?php echo htmlspecialchars($order['order_no']); ?></td>
                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                <td><?php echo htmlspecialchars($order['phone_number']); ?></td>
                <td><?php echo htmlspecialchars($order['delivery_location']); ?></td>
                <td><?php echo htmlspecialchars($order['apartment_number']); ?></td>
                <td>
                  <?php 
                    // Decode the cart summary JSON and display the items as a list.
                    $cartItems = json_decode($order['cart_summary'], true);
                    if (!empty($cartItems['items'])) {
                        echo "<ul>";
                        // Group items by product name.
                        $grouped = [];
                        foreach ($cartItems['items'] as $item) {
                            $name = htmlspecialchars($item['product']);
                            if(isset($grouped[$name])){
                                $grouped[$name]++;
                            } else {
                                $grouped[$name] = 1;
                            }
                        }
                        foreach($grouped as $product => $qty){
                            echo "<li>$qty x $product</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "No items";
                    }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center">No orders found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Charts Section -->
    <div class="row charts-container">
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="text-center">Sales Trends</h5>
          <canvas id="salesTrendChart"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="text-center">Product Distribution</h5>
          <canvas id="salesDistributionChart"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Detailed Sales Report Table -->
    <div class="table-container">
      <h4>Sales Report</h4>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Order Number</th>
            <th>Customer Name</th>
            <th>Contact</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Total</th>
            <th>Sale Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($salesData as $sale): ?>
          <tr>
            <td><?php echo htmlspecialchars($sale['order_no']); ?></td>
            <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($sale['contact']); ?></td>
            <td><?php echo htmlspecialchars($sale['product']); ?></td>
            <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
            <td><?php echo htmlspecialchars($sale['status']); ?></td>
            <td><?php echo htmlspecialchars($sale['total']); ?></td>
            <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
            <td>
              <?php if ($sale['status'] == 'Pending'): ?>
                <a href="approve_sale.php?sale_id=<?php echo $sale['id']; ?>" class="btn btn-success btn-sm">Approve</a>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled>Approved</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Chart.js Scripts -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sales Trends Chart: Combined Line & Bar Chart
      const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
      new Chart(salesTrendCtx, {
        type: 'bar',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
          datasets: [
            {
              type: 'line',
              label: '6kg Cylinders',
              data: [50, 60, 55, 70, 65, 80],
              borderColor: '#3498db',
              fill: false,
              tension: 0.4
            },
            {
              type: 'bar',
              label: '12kg Cylinders',
              data: [30, 40, 35, 50, 45, 60],
              backgroundColor: '#e74c3c'
            }
          ]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'top' } }
        }
      });
      
      // Sales Distribution Pie Chart
      const salesDistributionCtx = document.getElementById('salesDistributionChart').getContext('2d');
      new Chart(salesDistributionCtx, {
        type: 'pie',
        data: {
          labels: ['6kg Cylinders', '12kg Cylinders'],
          datasets: [{
            data: [200, 150],
            backgroundColor: ['#f39c12', '#27ae60']
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } }
        }
      });
    });
  </script>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
