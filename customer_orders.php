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

// Database connection
$host = 'localhost'; $user = 'root'; $password = ''; $dbname = 'oceangas';
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch account details INCLUDING profile_image
$acctSql = "SELECT F_name, L_name, Email, Phone_number, profile_image FROM customers WHERE cust_id = ?";
$acctStmt = $conn->prepare($acctSql);
if (!$acctStmt) die("Account query failed: " . $conn->error);
$acctStmt->bind_param('i', $custId);
if (!$acctStmt->execute()) die("Account execution failed: " . $acctStmt->error);
$acct = $acctStmt->get_result()->fetch_assoc();
$acctStmt->close();

// Function to generate profile image (blob or default icon)
function getProfileImageSrc($imageBlob) {
    if ($imageBlob) {
        return 'data:image/jpeg;base64,' . base64_encode($imageBlob);
    }
    return null;
}

function hasProfileImage($imageBlob) {
    return !empty($imageBlob);
}

// Tabs
$statusMap = [
    'Delivered' => 'Delivered',
    'Return Requests' => 'Return Requests',
    'Kept' => 'Kept'
];
$tab = isset($_GET['status']) ? $_GET['status'] : 'Delivered';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

if ($tab === 'Delivered') {
    $orderSql = "
        SELECT 
            o.order_id,
            o.order_number,
            o.order_date,
            oi.order_item_id,
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
            WHERE r.order_item_id = oi.order_item_id
        )
        ORDER BY o.order_date DESC, o.order_id DESC
        LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    if (!$orderStmt) {
        die('Prepare failed: ' . $conn->error . "\nQuery: " . $orderSql);
    }
    $orderStmt->bind_param('iii', $custId, $limit, $offset);
    $orderStmt->execute();
    $rawItems = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $orders = [];
    foreach ($rawItems as $row) {
        $oid = $row['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_number' => $row['order_number'],
                'order_date' => $row['order_date'],
                'products' => [],
                'order_total' => 0,
                'order_id' => $oid
            ];
        }
        $orders[$oid]['products'][] = [
            'order_item_id' => $row['order_item_id'],
            'name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit_price' => $row['unit_price'],
            'subtotal' => $row['subtotal']
        ];
        $orders[$oid]['order_total'] += $row['subtotal'];
    }
    $orders = array_values($orders);
    $totalAmount = 0;
    foreach ($orders as $order) {
        $totalAmount += $order['order_total'];
    }
} elseif ($tab === 'Return Requests') {
    $orderSql = "
        SELECT 
            o.order_number,
            o.order_date,
            oi.product_name,
            r.return_quantity,
            r.return_status,
            r.return_reason,
            r.request_date,
            oi.unit_price,
            oi.quantity as original_quantity,
            (oi.unit_price * r.return_quantity) as return_subtotal
        FROM orders o
        JOIN return_requests r ON o.order_id = r.order_id
        JOIN order_items oi ON r.order_item_id = oi.order_item_id
        WHERE o.cust_id = ?
        AND r.return_status IN ('pending', 'approved', 'declined')
        ORDER BY r.request_date DESC
        LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    if (!$orderStmt) {
        die('Prepare failed: ' . $conn->error . "\nQuery: " . $orderSql);
    }
    $orderStmt->bind_param('iii', $custId, $limit, $offset);
    $orderStmt->execute();
    $orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $totalAmount = 0;
    foreach ($orders as $order) {
        $totalAmount += $order['return_subtotal'];
    }
} elseif ($tab === 'Kept') {
    $orderSql = "
        SELECT 
            o.order_number,
            o.order_date,
            oi.product_name,
            oi.unit_price,
            oi.quantity as original_quantity,
            COALESCE((
                SELECT SUM(r.return_quantity)
                FROM return_requests r
                WHERE r.order_item_id = oi.order_item_id
                  AND r.return_status = 'approved'
            ), 0) as returned_quantity,
            (oi.quantity - COALESCE((
                SELECT SUM(r.return_quantity)
                FROM return_requests r
                WHERE r.order_item_id = oi.order_item_id
                  AND r.return_status = 'approved'
            ), 0)) as kept_quantity,
            (oi.unit_price * (oi.quantity - COALESCE((
                SELECT SUM(r.return_quantity)
                FROM return_requests r
                WHERE r.order_item_id = oi.order_item_id
                  AND r.return_status = 'approved'
            ), 0))) as kept_subtotal
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN deliveries d ON o.order_id = d.order_id
        WHERE o.cust_id = ?
        AND d.delivery_status = 'Delivered'
        HAVING kept_quantity > 0
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    if (!$orderStmt) {
        die('Prepare failed: ' . $conn->error . "\nQuery: " . $orderSql);
    }
    $orderStmt->bind_param('iii', $custId, $limit, $offset);
    $orderStmt->execute();
    $orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $totalAmount = 0;
    foreach ($orders as $order) {
        $totalAmount += $order['kept_subtotal'];
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
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
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

/* Input Styles */
input[type="number"] {
    -moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;

    
    }
    .profile-image {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid white;
      margin-left: 10px;
      display: block;
      flex-shrink: 0;
    }
    .nav-links img.profile-image {
      border-radius: 50% !important;
      width: 40px !important;
      height: 40px !important;
      object-fit: cover !important;
    }
  </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">
  <!-- Navbar -->
  <nav class="navbar">
    <div class="container mx-auto flex items-center">
      <img src="assets/images/Oceangas.png" alt="Logo"/>
      <ul class="nav-links">
        <div class="dropdown"> 
          <a href="#" class="dropdown-toggle d-flex align-items-center" 
             id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
             style="text-decoration: none; color: white; align-items: center;">
             <p class="profile-text">Hi, <?php echo htmlspecialchars($acct['F_name'] . ' ' . $acct['L_name']); ?></p>
             <?php if (hasProfileImage($acct['profile_image'])): ?>
               <img src="<?php echo getProfileImageSrc($acct['profile_image']); ?>" 
                    alt="Profile" class="profile-image">
             <?php else: ?>
               <i class="fas fa-user-circle fa-lg" style="margin-left: 10px; color: white;"></i>
             <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style="color:black;">
            <li><a class="dropdown-item" href="customer_acc.php"><i class="fas fa-user mr-2"></i> My Account</a></li>
            <li><a class="dropdown-item" href="customer_orders.php"><i class="fas fa-shopping-cart mr-2"></i> Orders</a></li>
            <li><a class="dropdown-item" href='customer_inquiries.php'><i class="fas fa-question-circle mr-2"></i> Help</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <label class="dropdown-item" style="cursor: pointer;">
                <i class="fas fa-camera mr-2"></i> Change Profile Picture
                <input type="file" id="profileImageInput" accept="image/*" style="display: none;">
              </label>
            </li>
            <?php if (hasProfileImage($acct['profile_image'])): ?>
            <li>
              <a class="dropdown-item text-danger" href="#" id="deleteProfileImage" style="cursor: pointer;">
                <i class="fas fa-trash mr-2"></i> Delete Profile Picture
              </a>
            </li>
            <?php endif; ?>
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
      <?php if ($tab === 'Delivered'): ?>
        <?php if (count($orders) > 0): ?>
          <div class="mt-6 overflow-x-auto bg-white shadow-sm rounded-lg">
            <table id="ordersTable" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Summary</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Total</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                      <div class="text-sm font-medium text-gray-900">
                        <?php echo implode('<br>', array_map(function($prod) {
                          return htmlspecialchars($prod['quantity'] . ' × ' . $prod['name']);
                        }, $o['products'])); ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">Ksh <?php echo number_format($o['order_total'], 2); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="status-badge status-delivered">Delivered</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <button type="button"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150"
                        onclick='openReturnModalMulti("<?php echo addslashes(trim($o['order_number'])); ?>", <?php echo json_encode(array_map(function($prod) {
                          return ["order_item_id" => $prod['order_item_id'], "name" => $prod['name'], "quantity" => (int)$prod['quantity']];
                        }, $o['products'])); ?>)'
                        data-tooltip="Request Return">
                        <i class="fas fa-undo-alt mr-2"></i>
                        Request Return
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="bg-gray-50">
                <tr>
                  <td colspan="3" class="px-6 py-4 text-right font-semibold">Total Amount:</td>
                  <td class="px-6 py-4 font-semibold">Ksh <?php echo number_format($totalAmount, 2); ?></td>
                  <td colspan="2"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php else: ?>
          <div class="mt-12 text-center p-12 bg-white rounded-lg shadow-sm">
            <i class="fas fa-box-open fa-3x text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">No orders in "<?= htmlspecialchars($tab) ?>"</h3>
            <a href="shop.php" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">Start sourcing</a>
          </div>
        <?php endif; ?>
      <?php elseif ($tab === 'Return Requests'): ?>
        <?php if (count($orders) > 0): ?>
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
        <?php else: ?>
          <div class="mt-12 text-center p-12 bg-white rounded-lg shadow-sm">
            <i class="fas fa-box-open fa-3x text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">No orders in "<?= htmlspecialchars($tab) ?>"</h3>
            <a href="shop.php" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">Start sourcing</a>
          </div>
        <?php endif; ?>
      <?php elseif ($tab === 'Kept'): ?>
        <?php if (count($orders) > 0): ?>
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
          <div class="mt-12 text-center p-12 bg-white rounded-lg shadow-sm">
            <i class="fas fa-box-open fa-3x text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">No orders in "<?= htmlspecialchars($tab) ?>"</h3>
            <a href="shop.php" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">Start sourcing</a>
          </div>
        <?php endif; ?>
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
      <form id="returnFormMulti" class="space-y-4">
        <input type="hidden" id="order_number" name="order_number">
        
        <div class="space-y-4">
            <?php foreach ($orders as $o): ?>
                <div class="bg-gray-50 p-4 rounded-lg flex items-center gap-4">
                    <input type="checkbox" id="select_product_<?php echo $index; ?>" onchange="toggleReturnInput(<?php echo $index; ?>)">
                    <label for="select_product_<?php echo $index; ?>" class="flex-1 font-medium"><?php echo htmlspecialchars(implode('<br>', $o['products'])); ?></label>
                    <input type="number" id="return_quantity_<?php echo $index; ?>" class="w-20 text-center border rounded-md" min="1" max="<?php echo htmlspecialchars($o['quantity']); ?>" value="1" disabled>
                    <span class="text-sm text-gray-500">(max <?php echo htmlspecialchars($o['quantity']); ?>)</span>
                </div>
            <?php endforeach; ?>
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

function openReturnModalMulti(orderNumber, products) {
  console.log('openReturnModalMulti orderNumber:', orderNumber, 'products:', products);
  let productHtml = `
        <form id=\"returnFormMulti\" class=\"space-y-4\">\n      <input type=\"hidden\" id=\"order_number\" value=\"${orderNumber}\">\n      <div class=\"space-y-4\">\n        ${products.map((product, index) => `\n          <div class=\"bg-gray-50 p-4 rounded-lg flex items-center gap-4\">\n            <input type=\"checkbox\" id=\"select_product_${index}\" onchange=\"toggleReturnInput(${index})\">\n            <label for=\"select_product_${index}\" class=\"flex-1 font-medium\">${product.quantity} × ${product.name}</label>\n            <input type=\"number\" id=\"return_quantity_${index}\" class=\"w-20 text-center border rounded-md\" min=\"1\" max=\"${product.quantity}\" value=\"1\" disabled>\n            <span class=\"text-sm text-gray-500\">(max ${product.quantity})</span>\n          </div>\n        `).join('')}\n      </div>\n      <div class=\"mt-4\">\n        <label class=\"block text-sm font-medium text-gray-700 mb-1\">Reason for Return</label>\n        <select id=\"return_reason\" class=\"w-full border rounded-md p-2\" required>\n          <option value=\"\">Select a reason</option>\n          <option value=\"Damaged Product\">Damaged Product</option>\n          <option value=\"Wrong Item\">Wrong Item</option>\n          <option value=\"Quality Issues\">Quality Issues</option>\n          <option value=\"Size/Quantity Issues\">Size/Quantity Issues</option>\n          <option value=\"Other\">Other</option>\n        </select>\n      </div>\n      <div id=\"other_reason_div\" class=\"mt-4 hidden\">\n        <label class=\"block text-sm font-medium text-gray-700 mb-1\">Please specify</label>\n        <textarea id=\"other_reason\" class=\"w-full border rounded-md p-2\" rows=\"3\"></textarea>\n      </div>\n    </form>\n  `;
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
            const selectedProducts = [];
            products.forEach((product, index) => {
                const checked = document.getElementById(`select_product_${index}`).checked;
                if (checked) {
                    const quantityInput = document.getElementById(`return_quantity_${index}`);
                    const quantity = parseInt(quantityInput.value);
                    if (!quantity || quantity < 1 || quantity > product.quantity) {
                        Swal.showValidationMessage(`Invalid quantity for ${product.name}`);
                        return false;
                    }
                    selectedProducts.push({
                        order_item_id: product.order_item_id,
                        name: product.name,
                        quantity: quantity
                    });
                }
            });
            if (selectedProducts.length === 0) {
                Swal.showValidationMessage('Please select at least one product to return');
                return false;
            }
            const reasonEl = Swal.getPopup().querySelector('#return_reason');
            const reason = reasonEl ? reasonEl.value : '';
            if (!reason) {
                Swal.showValidationMessage('Please select a reason for return');
                return false;
            }
            const otherReasonEl = Swal.getPopup().querySelector('#other_reason');
            if (reason === 'Other' && (!otherReasonEl || !otherReasonEl.value.trim())) {
                Swal.showValidationMessage('Please specify the reason for return');
                return false;
            }
            let finalReason = reason;
            if (reason === 'Other' && otherReasonEl) {
                finalReason = otherReasonEl.value.trim();
            }
            const formData = {
                order_number: document.getElementById('order_number').value,
                products: selectedProducts,
                total_quantity: selectedProducts.reduce((sum, p) => sum + p.quantity, 0),
                reason: finalReason
            };
            console.log('Submitting return formData:', formData);
            return formData;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = result.value;
            console.log('Sending fetch to process_return.php with:', formData);
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

function toggleReturnInput(index) {
    const cb = document.getElementById(`select_product_${index}`);
    const input = document.getElementById(`return_quantity_${index}`);
    input.disabled = !cb.checked;
}

    // Profile image upload
    document.getElementById('profileImageInput').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        if (file.size > 5 * 1024 * 1024) {
          alert('Image size should be less than 5MB');
          return;
        }
        
        if (!file.type.match('image.*')) {
          alert('Please select a valid image file');
          return;
        }
        
        const formData = new FormData();
        formData.append('profile_image', file);
        
        const loadingDiv = document.createElement('div');
        loadingDiv.innerHTML = '<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.8);color:white;padding:20px;border-radius:10px;z-index:9999;"><i class="fas fa-spinner fa-spin"></i> Uploading...</div>';
        document.body.appendChild(loadingDiv);
        
        fetch('upload_profile_image.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          document.body.removeChild(loadingDiv);
          if (data.success) {
            window.location.reload();
          } else {
            alert('Error uploading image: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          document.body.removeChild(loadingDiv);
          alert('Error uploading image: ' + error.message);
        });
      }
    });

    // Delete profile image
    const deleteBtn = document.getElementById('deleteProfileImage');
    if (deleteBtn) {
      deleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to delete your profile picture?')) {
          const loadingDiv = document.createElement('div');
          loadingDiv.innerHTML = '<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.8);color:white;padding:20px;border-radius:10px;z-index:9999;"><i class="fas fa-spinner fa-spin"></i> Deleting...</div>';
          document.body.appendChild(loadingDiv);
          
          fetch('delete_profile_image.php', {
            method: 'POST'
          })
          .then(response => response.json())
          .then(data => {
            document.body.removeChild(loadingDiv);
            if (data.success) {
              window.location.reload();
            } else {
              alert('Error deleting image: ' + (data.message || 'Unknown error'));
            }
          })
          .catch(error => {
            document.body.removeChild(loadingDiv);
            alert('Error deleting image: ' + error.message);
          });
        }
      });
    }
  </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
