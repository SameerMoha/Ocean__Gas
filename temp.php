<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: customer/login.php');
    exit;
}
$custId = $_SESSION['user_id'];
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-blue-700 hover:bg-blue-50';
}

// Database connection (unchanged)
$host = 'localhost'; $user = 'root'; $password = ''; $dbname = 'oceangas';
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
// Fetch account details
$acctSql = "SELECT F_name, L_name, Email, Phone_number FROM customers WHERE cust_id = ?";
$acctStmt = $conn->prepare($acctSql);
$acctStmt->bind_param('i', $custId);
$acctStmt->execute();
$acct = $acctStmt->get_result()->fetch_assoc();

// Define status map with the new Return Requests tab
$statusMap = [
    'Delivered' => ['Delivered'],
    'Return Requests' => ['pending', 'approved', 'declined'],
    'Kept' => ['Kept']
];

// Get the current tab from URL parameter
$tab = isset($_GET['status']) ? $_GET['status'] : 'Delivered';

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query for orders
if ($tab === 'Return Requests') {
    // Query for return requests with simplified reason format
    $orderSql = "
        SELECT 
            o.order_number,
            o.order_date,
            oi.product_name,
            oi.unit_price,
            oi.quantity as original_quantity,
            r.return_quantity,
            r.return_status,
            CASE 
                WHEN r.return_reason LIKE '%Damaged%' THEN 'Damaged Product'
                WHEN r.return_reason LIKE '%Wrong Item%' THEN 'Wrong Item'
                WHEN r.return_reason LIKE '%Quality%' THEN 'Quality Issues'
                WHEN r.return_reason LIKE '%Size%' THEN 'Size/Quantity Issues'
                ELSE 'Other'
            END as return_reason,
            r.request_date,
            (oi.unit_price * r.return_quantity) as return_subtotal
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN return_requests r ON o.order_id = r.order_id
        WHERE o.cust_id = ?
        AND r.return_status IN ('pending', 'approved', 'declined')
        ORDER BY r.request_date DESC
        LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('iii', $custId, $limit, $offset);
    $orderStmt->execute();
    $orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Calculate total return amount
    $totalAmount = 0;
    foreach ($orders as $order) {
        $totalAmount += $order['return_subtotal'];
    }
} elseif ($tab === 'Kept') {
    // Kept items (items that haven't been returned)
    $orderSql = "
        SELECT 
            o.order_number,
            o.order_date,
            oi.product_name,
            oi.unit_price,
            oi.quantity as original_quantity,
            COALESCE(
                (
                    SELECT SUM(r.return_quantity)
                    FROM return_requests r
                    WHERE r.order_id = oi.order_id
                    AND r.return_status = 'approved'
                    AND r.return_reason LIKE CONCAT('%', oi.product_name, '%')
                ),
                0
            ) as returned_quantity,
            (oi.quantity - COALESCE(
                (
                    SELECT SUM(r.return_quantity)
                    FROM return_requests r
                    WHERE r.order_id = oi.order_id
                    AND r.return_status = 'approved'
                    AND r.return_reason LIKE CONCAT('%', oi.product_name, '%')
                ),
                0
            )) as kept_quantity,
            (oi.unit_price * (oi.quantity - COALESCE(
                (
                    SELECT SUM(r.return_quantity)
                    FROM return_requests r
                    WHERE r.order_id = oi.order_id
                    AND r.return_status = 'approved'
                    AND r.return_reason LIKE CONCAT('%', oi.product_name, '%')
                ),
                0
            ))) as kept_subtotal
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN deliveries d ON o.order_id = d.order_id
        WHERE o.cust_id = ?
        AND d.delivery_status = 'Delivered'
        HAVING kept_quantity > 0
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('iii', $custId, $limit, $offset);
    $orderStmt->execute();
    $orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Calculate total kept amount
    $totalAmount = 0;
    foreach ($orders as $order) {
        $totalAmount += $order['kept_subtotal'];
    }
} else {
    // Delivered orders (completely untouched)
    $orderSql = "
        SELECT 
            o.order_number,
            o.order_date,
            oi.product_name,
            oi.unit_price,
            oi.quantity,
            (oi.unit_price * oi.quantity) as subtotal
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN deliveries d ON o.order_id = d.order_id
        WHERE o.cust_id = ? 
        AND d.delivery_status = 'Delivered'
        AND NOT EXISTS (
            SELECT 1 
            FROM return_requests r 
            WHERE r.order_id = o.order_id
            AND r.return_reason LIKE CONCAT('%', oi.product_name, '%')
        )
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('iii', $custId, $limit, $offset);
    $orderStmt->execute();
    $orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Calculate total amount
    $totalAmount = 0;
    foreach ($orders as $order) {
        $totalAmount += $order['subtotal'];
    }
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Orders</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
     .navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  z-index: 1000;
  background: #0066cc;
  padding: 1rem 3rem;
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}
    .navbar .logo-text {
      font-size: 1.2rem;
      font-weight: bold;
      color: white;
    }
    .navbar img {
      height: 50px;
      margin-right: 20px;
      margin-left: 0px;
    }
    .nav-links {
      list-style: none;
      display: flex;
      gap: 20px;
      margin-left: auto;
      align-items: center;
      padding: 0;
    }
    .nav-links li a {
      text-decoration: none;
      color: black;
      font-size: 18px;
      padding: 8px 16px;
    
      transition: 0.3s;
      cursor: pointer;
    }
    
    /* Cart Icon Styling */
    .cart-icon {
      position: relative;
    }
    .cart-icon i {
      color: white;
    }
    .cart-icon span {position: absolute;top: -0.25rem;right: -0.25rem;background-color: #f56565;color: white;border-radius: 9999px;font-size: 0.7rem;width: 1.25rem;height: 1.25rem;
      display: flex; align-items: center; justify-content: center; } 
       .navbar .profile-text{
      font-size: 1.2rem;
      color: white;
      margin-right: 10px;
    }
  .dt-buttons {
  margin-bottom: 1rem;
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Base export button styling */
.dt-button {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 0.5rem 1rem !important;
  font-size: 0.875rem !important;
  font-weight: 500 !important;
  line-height: 1.25rem !important;
  border-radius: 0.375rem !important;
  border: none !important;
  cursor: pointer !important;
  transition: all 0.15s ease-in-out !important;
  text-decoration: none !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}

/* Excel export button */
.dt-button.buttons-excel {
  background-color: #059669 !important; /* Green-600 */
  color: white !important;
}

.dt-button.buttons-excel:hover {
  background-color: #047857 !important; /* Green-700 */
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

/* PDF export button */
.dt-button.buttons-pdf {
  background-color: #dc2626 !important; /* Red-600 */
  color: white !important;
}

.dt-button.buttons-pdf:hover {
  background-color: #b91c1c !important; /* Red-700 */
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

/* Add icons to buttons */
.dt-button.buttons-excel:before {
  content: "📊 ";
  margin-right: 0.25rem;
}

.dt-button.buttons-pdf:before {
  content: "📄 ";
  margin-right: 0.25rem;
}

/* Button focus states for accessibility */
.dt-button:focus {
  outline: 2px solid transparent !important;
  outline-offset: 2px !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5) !important;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .dt-buttons {
    justify-content: center;
  }
  
  .dt-button {
    min-width: 120px;
    justify-content: center !important;
  }
}
/* DataTables search box styling to match your design */
.dataTables_filter input {
  padding: 0.5rem 0.75rem !important;
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  margin-left: 0.5rem !important;
}

.dataTables_filter input:focus {
  outline: none !important;
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

/* DataTables length select styling */
.dataTables_length select {
  padding: 0.5rem !important;
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  margin: 0 0.5rem !important;
}

/* DataTables info and pagination styling */
.dataTables_info {
  color: #6b7280 !important;
  font-size: 0.875rem !important;
}

.dataTables_paginate .paginate_button {
  padding: 0.5rem 0.75rem !important;
  margin: 0 0.125rem !important;
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  color: #374151 !important;
  text-decoration: none !important;
}

.dataTables_paginate .paginate_button:hover {
  background-color: #f3f4f6 !important;
  border-color: #9ca3af !important;
}

.dataTables_paginate .paginate_button.current {
  background-color: #3b82f6 !important;
  border-color: #3b82f6 !important;
  color: white !important;
}

body {
    
    min-height: 100vh;
    background: #f8f9fa;
    font-family: Arial, sans-serif;
    padding-top: 70px;
}
.sidebar {
    width: 250px;
    background: #6a008a;
    color: white;
    padding: 20px;
    position: fixed;
    height: 100vh;
    left: 0;
    top: 0;
    z-index: 1000;
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
.content {
    margin-left: 250px;
    padding: 20px;
    flex-grow: 1;
    width: calc(100% - 250px);
}
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 10px 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.card {
    border: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.order-status {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9em;
    display: inline-block;
}
.status-confirmed { background-color: #17a2b8; color: white; }
.status-delivered { background-color: #28a745; color: white; }
.status-cancelled { background-color: #dc3545; color: white; }
.status-return { background-color: #ffc107; color: black; }
.return-request-form {
    display: none;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 20px;
}
.return-request-form.show {
    display: block;
}
.quantity-control {
    display: flex;
    align-items: center;
    gap: 10px;
}
.quantity-control input {
    width: 60px;
    text-align: center;
}
.keep-quantity {
    color: #6c757d;
    font-size: 0.9em;
    margin-left: 10px;
}

/* Add these styles to your existing styles */
.modal-content {
  animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.cart-item-row {
  transition: background-color 0.2s ease;
}

.cart-item-row:hover {
  background-color: #f8fafc;
}

.quantity-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.quantity-btn {
  padding: 0.25rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.25rem;
  background-color: #f8fafc;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quantity-btn:hover {
  background-color: #e2e8f0;
}

.quantity-input {
  width: 3rem;
  text-align: center;
  border: 1px solid #e2e8f0;
  border-radius: 0.25rem;
  padding: 0.25rem;
}

.product-image {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 0.25rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-badge i {
  margin-right: 0.5rem;
}

.status-confirmed { background-color: #dbeafe; color: #1e40af; }
.status-delivered { background-color: #dcfce7; color: #166534; }
.status-cancelled { background-color: #fee2e2; color: #991b1b; }
.status-pending { background-color: #fef3c7; color: #92400e; }

/* Tooltip styles */
[data-tooltip] {
  position: relative;
}

[data-tooltip]:before {
  content: attr(data-tooltip);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.5rem;
  background-color: #1f2937;
  color: white;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s ease;
}

[data-tooltip]:hover:before {
  opacity: 1;
  visibility: visible;
}

/* Modal Styles */
.return-modal-container {
    z-index: 9999;
}

.return-modal-popup {
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.return-modal-header {
    border-bottom: 1px solid #E5E7EB;
    padding-bottom: 1rem;
}

.return-modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1F2937;
}

.return-modal-content {
    padding: 1.5rem 0;
}

.return-modal-actions {
    border-top: 1px solid #E5E7EB;
    padding-top: 1rem;
}

.return-modal-confirm-button {
    background-color: #3B82F6 !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    border-radius: 0.375rem !important;
    font-weight: 500 !important;
    transition: all 0.2s !important;
}

.return-modal-confirm-button:hover {
    background-color: #2563EB !important;
}

.return-modal-cancel-button {
    background-color: #6B7280 !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    border-radius: 0.375rem !important;
    font-weight: 500 !important;
    transition: all 0.2s !important;
}

.return-modal-cancel-button:hover {
    background-color: #4B5563 !important;
}



input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
  </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">
  <!-- Navbar -->
   <!-- Navbar -->
  <nav class="navbar">
    <div class="container mx-auto flex items-center">
      <img src="assets/images/Oceangas.png" alt="Logo"/>
      <ul class="nav-links">
      <div class="dropdown"> 
  <a href="#" class="dropdown-toggle d-flex align-items-center" 
     id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
     style="text-decoration: none; color: white;">
     <p class="profile-text">Hi, <?php echo htmlspecialchars($acct['F_name'] . ' ' . $acct['L_name']); ?></p>
    <i class="fas fa-user fa-lg"></i> <!-- Add an icon or text here -->
  </a>
  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style=" color:black;">
    <li><a class="dropdown-item" href="customer_acc.php"> My Account</a></li>
    <li><a class="dropdown-item" href="customer_orders.php"> Orders</a></li>
    <li><a class="dropdown-item" href='customer_inuiries.php'> Help</a></li>
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item text-danger" href="/OceanGas/customer/logout.php">
        <i class="fas fa-sign-out-alt"></i> Sign Out
      </a>
    </li>
  </ul>
</div>
        <li class="cart-icon">
          <a href="#" id="cartIcon"> 
            <i class="fas fa-shopping-cart fa-lg"></i>
            <span id="cart-count">0</span>
        
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <div class="flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md h-screen sticky top-0 overflow-y-auto">
      <div class="p-6">
        <h5 class="text-xl font-semibold flex items-center mb-4"><i class="fas fa-user-circle mr-2"></i>My Account</h5>
        <nav class="space-y-2">
          <a href="customer_acc.php" class="block px-4 py-2 rounded-md font-medium <?= isActive('customer_acc.php') ?> transition-all duration-150"><i class="fas fa-user-circle mr-2"></i>Account Overview</a>
          <a href="customer_orders.php" class="block px-4 py-2 rounded-md font-medium <?= isActive('customer_orders.php') ?> transition-all duration-150"><i class="fas fa-shopping-cart mr-2"></i>Orders</a>
          <hr class="my-2 border-black-200">
          <a href="customer_address.php" class="block px-4 py-2 rounded-md font-medium <?= isActive('customer_address.php') ?> transition-all duration-150"><i class="fas fa-address-book mr-2"></i>Address Book</a>
          <a href="customer_inquiries.php" class="block px-4 py-2 rounded-md font-medium <?= isActive('customer_inquiries.php') ?> transition-all duration-150"><i class="fas fa-question-circle mr-2"></i>Inquiries</a>
          <a href="customer_reviews.php" class="block px-4 py-2 rounded-md font-medium <?= isActive('customer_reviews.php') ?> transition-all duration-150"><i class="fas fa-star mr-2"></i>Reviews</a>
          <a href="customer/logout.php" class="block px-4 py-2 mt-4 text-red-600 hover:bg-red-50 rounded-md transition-colors duration-150">Sign Out</a>
          <a href="shop.php" class="inline-block mt-4 w-full text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">Back to shop</a>
        </nav>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
      <header class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-semibold">Orders</h2>
      </header>

      <!-- Tabs -->
      <div class="mt-4 flex space-x-3">
        <?php foreach (array_keys($statusMap) as $t): ?>
          <a href="?status=<?= urlencode($t) ?>" class="px-4 py-2 rounded-md font-medium <?= $t === $tab ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?> transition-all duration-150"><?= htmlspecialchars($t) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Orders Display -->
      <?php if (count($orders) > 0): ?>
        <?php if ($tab === 'Delivered'): ?>
            <div class="mt-6 bg-white shadow-sm rounded-lg p-6">
                <div class="space-y-6">
                    <?php foreach ($orders as $o): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-150">
                            <div class="flex-1">
                                <div class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($o['quantity']); ?> × <?php echo htmlspecialchars($o['product_name']); ?>
                                </div>
                                <div class="mt-1 text-sm text-gray-500">
                                    Order #<?php echo htmlspecialchars($o['order_number']); ?> • 
                                    <?php echo date('M j, Y', strtotime($o['order_date'])); ?>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-lg font-semibold text-gray-900">
                                    KES <?php echo number_format($o['subtotal'], 2); ?>
                                </div>
                                <button 
                                    onclick="openReturnModal('<?php echo htmlspecialchars($o['order_number']); ?>', [{
                                        name: '<?php echo htmlspecialchars($o['product_name']); ?>',
                                        quantity: <?php echo (int)$o['quantity']; ?>,
                                        price: <?php echo (float)$o['unit_price']; ?>
                                    }])"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150"
                                >
                                    <i class="fas fa-undo-alt mr-2"></i>
                                    Request Return
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-xl font-semibold text-gray-900">
                                Total Amount: KES <?php echo number_format($totalAmount, 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($tab === 'Return Requests'): ?>
            <div class="mt-6 overflow-x-auto bg-white shadow-sm rounded-lg">
                <table id="ordersTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($o['order_number']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($o['request_date'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($o['product_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($o['return_quantity']); ?> / <?php echo htmlspecialchars($o['original_quantity']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge <?php 
                                    echo $o['return_status'] === 'pending' ? 'status-pending' : 
                                        ($o['return_status'] === 'approved' ? 'status-delivered' : 'status-cancelled'); 
                                ?>">
                                    <i class="fas <?php 
                                        echo $o['return_status'] === 'pending' ? 'fa-clock' : 
                                            ($o['return_status'] === 'approved' ? 'fa-check' : 'fa-times'); 
                                    ?>"></i>
                                    <?php echo htmlspecialchars(ucfirst($o['return_status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($o['return_reason']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">KES <?php echo number_format($o['return_subtotal'], 2); ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-right font-semibold">Total Return Amount:</td>
                            <td class="px-6 py-4 font-semibold">KES <?php echo number_format($totalAmount, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php elseif ($tab === 'Kept'): ?>
            <div class="mt-6 overflow-x-auto bg-white shadow-sm rounded-lg">
                <table id="ordersTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kept Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($o['order_number']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($o['order_date'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($o['product_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($o['kept_quantity']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">KES <?php echo number_format($o['kept_subtotal'], 2); ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right font-semibold">Total Kept Amount:</td>
                            <td class="px-6 py-4 font-semibold">KES <?php echo number_format($totalAmount, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="mt-6 overflow-x-auto bg-white shadow-sm rounded-lg">
                <table id="ordersTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <?php if ($tab === 'Return Requests'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Details</th>
                            <?php endif; ?>
                            <?php if ($tab === 'Delivered'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($o['order_number']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($o['order_date'])); ?></div>
                                <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($o['order_date'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($o['product_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($tab === 'Return Requests'): ?>
                                    <div class="text-sm text-gray-900">
                                        Return: <?php echo htmlspecialchars($o['return_quantity']); ?><br>
                                        <span class="text-gray-500">Original: <?php echo htmlspecialchars($o['original_quantity']); ?></span>
                                    </div>
                                <?php elseif ($tab === 'Kept'): ?>
                                    <div class="text-sm text-gray-900">
                                        Kept: <?php echo htmlspecialchars($o['kept_quantity']); ?><br>
                                        <span class="text-gray-500">Original: <?php echo htmlspecialchars($o['original_quantity']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($o['quantity']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    KES <?php 
                                    if ($tab === 'Return Requests') {
                                        echo number_format($o['return_subtotal'], 2);
                                    } elseif ($tab === 'Kept') {
                                        echo number_format($o['kept_subtotal'], 2);
                                    } else {
                                        echo number_format($o['subtotal'], 2);
                                    }
                                    ?>
                                </div>
                            </td>
                            <?php if ($tab === 'Return Requests'): ?>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge <?php 
                                        echo $o['return_status'] === 'pending' ? 'status-pending' : 
                                            ($o['return_status'] === 'approved' ? 'status-delivered' : 'status-cancelled'); 
                                    ?>">
                                        <i class="fas <?php 
                                            echo $o['return_status'] === 'pending' ? 'fa-clock' : 
                                                ($o['return_status'] === 'approved' ? 'fa-check' : 'fa-times'); 
                                        ?>"></i>
                                        <?php echo htmlspecialchars(ucfirst($o['return_status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 space-y-1">
                                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($o['return_reason']); ?></p>
                                        <p><strong>Requested:</strong> <?php echo date('M j, Y', strtotime($o['request_date'])); ?></p>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <?php if ($tab === 'Delivered'): ?>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button type="button" 
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150"
                                            onclick="openReturnModal('<?php echo htmlspecialchars($o['order_number']); ?>', [{
                                                name: '<?php echo htmlspecialchars($o['product_name']); ?>',
                                                quantity: <?php echo (int)$o['quantity']; ?>,
                                                price: <?php echo (float)$o['unit_price']; ?>
                                            }])"
                                            data-tooltip="Request Return">
                                        <i class="fas fa-undo-alt mr-2"></i>
                                        Request Return
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-right font-semibold">Total Amount:</td>
                            <td class="px-6 py-4 font-semibold">KES <?php echo number_format($totalAmount, 2); ?></td>
                            <?php if ($tab === 'Return Requests'): ?>
                                <td colspan="2"></td>
                            <?php endif; ?>
                            <?php if ($tab === 'Delivered'): ?>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php
        $countSql = "SELECT COUNT(*) FROM orders WHERE cust_id = ?";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param('i', $custId);
        $countStmt->execute();
        $totalItems = $countStmt->get_result()->fetch_row()[0];
        $totalPages = ceil($totalItems / $limit);
        ?>

        <nav class="mt-6">
            <ul class="flex space-x-2">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li>
                        <a 
                            href="?status=<?= urlencode($tab) ?>&page=<?= $p ?>"
                            class="px-3 py-1 rounded-md font-medium
                                    <?= $p === $page 
                                        ? 'bg-blue-600 text-white' 
                                        : 'text-gray-600 hover:bg-gray-100' ?>"
                        >
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
      <?php else: ?>
        <div class="mt-12 text-center p-12 bg-white rounded-lg shadow-sm">
          <i class="fas fa-box-open fa-3x text-gray-300 mb-4"></i>
          <h3 class="text-xl font-semibold mb-2">No orders in "<?= htmlspecialchars($tab) ?>"</h3>
          <a href="shop.php" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">Start sourcing</a>
        </div>
      <?php endif; ?>
    </main>
  </div>
  
    <div id="cartModal" class="modal" style="display:none; position:fixed; z-index:200; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; border-radius:8px; max-width:800px;">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Your Cart</h2>
        <button id="closeCartBtn" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times fa-lg"></i>
        </button>
      </div>
      
      <div id="cartModalContent" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody id="cartTableBody" class="bg-white divide-y divide-gray-200">
            <!-- Cart items will be inserted here -->
          </tbody>
        </table>
      </div>

      <div class="mt-6 flex justify-between items-center">
        <div class="text-lg font-semibold">
          Total: Ksh <span id="cartModalTotal">0.00</span>
        </div>
        <div class="space-x-4">
          <a href="shop.php" class="inline-block px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors duration-150">
            Continue Shopping
          </a>
          <button id="modalCheckoutBtn" class="inline-block px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors duration-150">
            Proceed to Checkout
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- (2) Empty-Cart Modal -->
  <div id="emptyCartModal" class="modal" style="display:none; position:fixed; z-index:200; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; border-radius:8px; max-width:500px;">
      <h2 class="text-2xl font-bold mb-4">Cart is Empty</h2>
      <p class="mb-4">Your cart is empty. Please add an item before checking out.</p>
      <button id="closeEmptyCartBtn" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">Okay</button>
    </div>
  </div>

  <!-- Return Request Modal -->
  <div id="returnModal" class="modal" style="display:none; position:fixed; z-index:200; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);">
    <div class="modal-content bg-white rounded-lg shadow-xl p-6 max-w-md mx-auto mt-20">
      <h2 class="text-2xl font-bold mb-4">Request Return</h2>
      <form id="returnForm" class="space-y-4">
        <input type="hidden" id="returnOrderNumber" name="order_number">
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Order Items</label>
          <p id="returnOrderItems" class="mt-1 text-sm text-gray-500"></p>
        </div>

        <div>
          <label for="returnQuantity" class="block text-sm font-medium text-gray-700">Quantity to Return</label>
          <input type="number" id="returnQuantity" name="quantity" min="1" required
                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
          <label for="returnReason" class="block text-sm font-medium text-gray-700">Reason for Return</label>
          <select id="returnReason" name="reason" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Select a reason</option>
            <option value="Damaged Product">Damaged Product</option>
            <option value="Wrong Item">Wrong Item</option>
            <option value="Quality Issues">Quality Issues</option>
            <option value="Size/Quantity Issues">Size/Quantity Issues</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div id="otherReasonDiv" style="display: none;">
          <label for="otherReason" class="block text-sm font-medium text-gray-700 mb-1">Please specify</label>
          <textarea id="otherReason" name="other_reason" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        </div>

        <div class="flex justify-end space-x-4 mt-6">
          <button type="button" onclick="closeReturnModal()" 
                  class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            Cancel
          </button>
          <button type="submit" 
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Submit Request
          </button>
        </div>
      </form>
    </div>
  </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>



    <script>
     
    // 1. Load / init
    let cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };

    function saveCart() { localStorage.setItem('cart', JSON.stringify(cart)); }
    function updateCartDisplay() {
      document.getElementById('cart-count').textContent = cart.items.length;
    }
    function showNotification(msg) {
      const n = document.getElementById('notification') || document.body.insertAdjacentHTML('afterbegin',
        '<div id="notification" style="position:fixed;top:5%;left:50%;transform:translateX(-50%);background:#48bb78;color:#fff;padding:.75rem 1rem;border-radius:.5rem;opacity:0;transition:.3s;z-index:300;"></div>'
      ) && document.getElementById('notification');
      n.textContent = msg; n.style.opacity = 1;
      setTimeout(()=>n.style.opacity=0,2000);
    }

  function renderCartModal() {
  const tableBody = document.getElementById('cartTableBody');
  if (!cart.items.length) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
          Your cart is empty
        </td>
      </tr>
    `;
  } else {
    const summary = {};
    cart.items.forEach(i => {
      if (!summary[i.product]) {
        summary[i.product] = {
          price: i.price,
          qty: 0
        };
      }
      summary[i.product].qty++;
    });

    let html = '';
    for (let product in summary) {
      const item = summary[product];
      const total = item.price * item.qty;
      
      html += `
        <tr class="cart-item-row">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <span class="text-sm font-medium text-gray-900">${product}</span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="quantity-control">
              <button class="quantity-btn" onclick="updateQuantity('${product}', -1)" data-tooltip="Decrease quantity">
                <i class="fas fa-minus"></i>
              </button>
              <input type="number" class="quantity-input" value="${item.qty}" 
                     onchange="updateQuantity('${product}', this.value - ${item.qty})" min="1">
              <button class="quantity-btn" onclick="updateQuantity('${product}', 1)" data-tooltip="Increase quantity">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            Ksh ${item.price.toFixed(2)}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
            Ksh ${total.toFixed(2)}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <button onclick="removeFromCart('${product}')" class="text-red-600 hover:text-red-900" data-tooltip="Remove item">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    }
    tableBody.innerHTML = html;
  }
  
  document.getElementById('cartModalTotal').textContent = cart.total.toFixed(2);
}

function updateQuantity(product, change) {
  const items = cart.items.filter(i => i.product === product);
  if (items.length === 0) return;

  if (typeof change === 'number') {
    // Handle increment/decrement
    if (change > 0) {
      cart.items.push({...items[0]});
    } else {
      cart.items.splice(cart.items.indexOf(items[0]), 1);
    }
  } else {
    // Handle direct quantity input
    const currentQty = items.length;
    const newQty = parseInt(change);
    const diff = newQty - currentQty;
    
    if (diff > 0) {
      for (let i = 0; i < diff; i++) {
        cart.items.push({...items[0]});
      }
    } else if (diff < 0) {
      for (let i = 0; i < -diff; i++) {
        cart.items.splice(cart.items.indexOf(items[0]), 1);
      }
    }
  }

  // Recalculate total
  cart.total = cart.items.reduce((sum, item) => sum + item.price, 0);
  
  // Update display
  saveCart();
  updateCartDisplay();
  renderCartModal();
}

function removeFromCart(product) {
  cart.items = cart.items.filter(i => i.product !== product);
  cart.total = cart.items.reduce((sum, item) => sum + item.price, 0);
  
  saveCart();
  updateCartDisplay();
  renderCartModal();
  
  showNotification('Item removed from cart');
}

    function showEmptyCartModal() {
      document.getElementById('emptyCartModal').style.display = 'block';
    }

    // attach Cart icon
    document.getElementById('cartIcon').addEventListener('click', e => {
      e.preventDefault();
      renderCartModal();
      document.getElementById('cartModal').style.display = 'block';
    });
    document.getElementById('closeCartBtn').addEventListener('click', () =>
      document.getElementById('cartModal').style.display = 'none'
    );
    document.getElementById('closeEmptyCartBtn').addEventListener('click', () =>
      document.getElementById('emptyCartModal').style.display = 'none'
    );

    // Checkout buttons
    document.getElementById('modalCheckoutBtn').addEventListener('click', () => {
      if (!cart.items.length) return showEmptyCartModal();
      window.location.href = 'checkout.php';
    });

    // initialize badge
    updateCartDisplay();
      $(document).ready(function() {
    $('#ordersTable').DataTable({
      dom: 'Bfrtip',
    buttons: [
  {
    extend: 'excelHtml5',
    text: 'Export to Excel',
    className: 'dt-button px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 mr-2'
  },
  {
    extend: 'pdfHtml5',
    text: 'Export to PDF',
    className: 'dt-button px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700'
  }
]
    });
  });

function openReturnModal(orderNumber, products) {
    let productHtml = `
        <form id="returnForm" class="space-y-4">
            <input type="hidden" id="order_number" value="${orderNumber}">
            <div class="space-y-4">
                ${products.map((product, index) => `
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between items-center mb-3">
                            <div class="font-medium">${product.name}</div>
                            <div class="text-sm text-gray-500">Original Quantity: ${product.quantity}</div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Return Quantity</label>
                                <div class="flex items-center space-x-2">
                                    <button type="button" 
                                            onclick="updateReturnQuantity(${index}, -1, ${product.quantity})"
                                            class="p-2 border rounded-md hover:bg-gray-100">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           id="return_quantity_${index}"
                                           class="w-20 text-center border rounded-md"
                                           min="1" 
                                           max="${product.quantity}"
                                           value="1"
                                           onchange="validateReturnQuantity(${index}, ${product.quantity})">
                                    <button type="button"
                                            onclick="updateReturnQuantity(${index}, 1, ${product.quantity})"
                                            class="p-2 border rounded-md hover:bg-gray-100">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                Keep: <span id="keep_quantity_${index}">${product.quantity - 1}</span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Return</label>
                <select id="return_reason" class="w-full border rounded-md p-2" required>
                    <option value="">Select a reason</option>
                    <option value="Damaged Product">Damaged Product</option>
                    <option value="Wrong Item">Wrong Item</option>
                    <option value="Quality Issues">Quality Issues</option>
                    <option value="Size/Quantity Issues">Size/Quantity Issues</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div id="other_reason_div" class="mt-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Please specify</label>
                <textarea id="other_reason" class="w-full border rounded-md p-2" rows="3"></textarea>
            </div>
        </form>
    `;

    Swal.fire({
        title: 'Request Return',
        html: productHtml,
        showCancelButton: true,
        confirmButtonText: 'Submit Return Request',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        width: '600px',
        customClass: {
            container: 'return-modal-container',
            popup: 'return-modal-popup',
            header: 'return-modal-header',
            title: 'return-modal-title',
            content: 'return-modal-content',
            actions: 'return-modal-actions',
            confirmButton: 'return-modal-confirm-button',
            cancelButton: 'return-modal-cancel-button'
        },
        didOpen: () => {
            document.getElementById('return_reason').addEventListener('change', function() {
                const otherReasonDiv = document.getElementById('other_reason_div');
                otherReasonDiv.classList.toggle('hidden', this.value !== 'Other');
            });
        },
        preConfirm: () => {
            const form = document.getElementById('returnForm');
            const selectedProducts = [];
            let totalQuantity = 0;
            
            products.forEach((product, index) => {
                const quantityInput = document.getElementById(`return_quantity_${index}`);
                const quantity = parseInt(quantityInput.value);
                
                if (quantity > 0) {
                    totalQuantity += quantity;
                    selectedProducts.push({
                        name: product.name,
                        quantity: quantity,
                        price: product.price
                    });
                }
            });

            if (selectedProducts.length === 0) {
                Swal.showValidationMessage('Please select at least one product to return');
                return false;
            }

            const reason = document.getElementById('return_reason').value;
            if (!reason) {
                Swal.showValidationMessage('Please select a reason for return');
                return false;
            }

            if (reason === 'Other' && !document.getElementById('other_reason').value.trim()) {
                Swal.showValidationMessage('Please specify the reason for return');
                return false;
            }

            // Format the return reason
            let finalReason = reason;
            if (reason === 'Other') {
                finalReason = document.getElementById('other_reason').value.trim();
            }

            return {
                order_number: document.getElementById('order_number').value,
                products: selectedProducts,
                total_quantity: totalQuantity,
                reason: finalReason
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = result.value;
            
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we process your return request',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('process_return.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#3B82F6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to submit return request');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#3B82F6'
                });
            });
        }
    });
}

function updateReturnQuantity(index, change, maxQuantity) {
    const input = document.getElementById(`return_quantity_${index}`);
    const currentValue = parseInt(input.value) || 0;
    const newValue = currentValue + change;
    
    if (newValue >= 1 && newValue <= maxQuantity) {
        input.value = newValue;
        updateKeepQuantity(index, maxQuantity, newValue);
    }
}

function validateReturnQuantity(index, maxQuantity) {
    const input = document.getElementById(`return_quantity_${index}`);
    const value = parseInt(input.value);
    
    if (value < 1) {
        input.value = 1;
    } else if (value > maxQuantity) {
        input.value = maxQuantity;
        Swal.fire({
            title: 'Invalid Quantity',
            text: `You cannot return more than ${maxQuantity} items`,
            icon: 'warning',
            confirmButtonColor: '#3B82F6'
        });
    }
    
    updateKeepQuantity(index, maxQuantity, input.value);
}

function updateKeepQuantity(index, maxQuantity, returnQuantity) {
    const keepQuantitySpan = document.getElementById(`keep_quantity_${index}`);
    keepQuantitySpan.textContent = maxQuantity - returnQuantity;
}

function closeReturnModal() {
  document.getElementById('returnModal').style.display = 'none';
  document.getElementById('returnForm').reset();
}
  </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
