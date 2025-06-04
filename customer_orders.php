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

// Get selected tab
$tab = $_GET['status'] ?? 'All';

// Build status filter
$statusMap = [
    'All' => [],
    'Confirmed' => ['confirmed'],
    'Delivered' => ['delivered'],
    'Cancelled' => ['cancelled'],
];
$statuses = $statusMap[$tab] ?? [];

// Pagination settings
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

if ($tab === 'Delivered') {
    // Delivered orders (from deliveries table)
    $orderSql = "
      SELECT 
        o.order_number,
        o.invoice_summary,
        o.total_amount,
        o.order_date,
        d.delivery_status AS order_status
      FROM orders o
      JOIN deliveries d ON o.order_id = d.order_id
      WHERE o.cust_id = ? 
        AND d.delivery_status = 'Delivered'
      ORDER BY d.delivery_date DESC
      LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('iii', $custId, $limit, $offset);

} elseif ($tab === 'Cancelled') {
    // Cancelled orders (from deliveries table)
    $orderSql = "
      SELECT 
        o.order_number,
        o.invoice_summary,
        o.total_amount,
        o.order_date,
        d.delivery_status AS order_status
      FROM orders o
      JOIN deliveries d ON o.order_id = d.order_id
      WHERE o.cust_id = ? 
        AND d.delivery_status = 'Cancelled'
      ORDER BY d.delivery_date DESC
      LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('iii', $custId, $limit, $offset);

} elseif ($tab === 'Confirmed') {
    // Only orders explicitly marked "Confirmed" in orders table
    $orderSql = "
      SELECT 
        order_number,
        invoice_summary,
        total_amount,
        order_date,
        order_status
      FROM orders
      WHERE cust_id = ?
        AND order_status = 'Confirmed'
      ORDER BY order_date DESC
      LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('iii', $custId, $limit, $offset);

} elseif (empty($statuses)) {
    // “All” (no filtering by status array)
    $orderSql = "
      SELECT 
        order_number,
        invoice_summary,
        total_amount,
        order_date,
        order_status
      FROM orders
      WHERE cust_id = ?
      ORDER BY order_date DESC
      LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('iii', $custId, $limit, $offset);

} else {
    // Any other custom set of statuses (but never Cancelled)
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $types        = str_repeat('s', count($statuses));

    $orderSql = "
      SELECT 
        order_number,
        invoice_summary,
        total_amount,
        order_date,
        order_status
      FROM orders
      WHERE cust_id = ?
        AND LOWER(order_status) IN ($placeholders)
        AND LOWER(order_status) != 'cancelled'
      ORDER BY order_date DESC
      LIMIT ? OFFSET ?";
    $orderStmt = $conn->prepare($orderSql);

    // i = int for custId, then one 's' per status, then two ints for limit/offset
    $typesAll = 'i' . $types . 'ii';
    $params   = array_merge([$custId], $statuses, [$limit, $offset]);
    $orderStmt->bind_param($typesAll, ...$params);
}

$orderStmt->execute();
$orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);


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

  <style>
     .navbar {
      background: #0066cc;
      padding: 1rem 3rem;
      position: sticky;
      top: 0;
      z-index: 10;
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

      <!-- Orders Table -->
      <?php if (count($orders) > 0): ?>
        <div class="mt-6 overflow-x-auto bg-white shadow-sm rounded-lg">
          <table id="ordersTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Summary</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($orders as $o): ?>
              <tr class="hover:bg-blue-50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($o['order_number']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M j, Y', strtotime($o['order_date'])); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($o['invoice_summary']); ?></td>
                <td class="px-6 py-4 text-left whitespace-nowrap">Ksh <?php echo number_format($o['total_amount'], 2); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars(ucfirst($o['order_status'])); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
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
          <h3 class="text-xl font-semibold mb-2">No orders in “<?= htmlspecialchars($tab) ?>”</h3>
          <a href="shop.php" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">Start sourcing</a>
        </div>
      <?php endif; ?>
    </main>
  </div>
  
    <div id="cartModal" class="modal" style="display:none; position:fixed; z-index:200; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; border-radius:8px; max-width:500px;">
      <h2 class="text-2xl font-bold mb-4">Your Cart</h2>
      <div id="cartModalContent"></div>
      <p class="mt-4 font-bold">Total: Ksh <span id="cartModalTotal">0</span></p>
      <div class="mt-6 flex justify-end space-x-4">
        <button id="closeCartBtn" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">Close</button>
        <button id="modalCheckoutBtn" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">Checkout</button>
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
  const content = document.getElementById('cartModalContent');
  if (!cart.items.length) {
    content.innerHTML = '<p>Your cart is empty.</p>';
  } else {
    const summary = {};
    cart.items.forEach(i => {
      summary[i.product] = summary[i.product] || { price: i.price, qty: 0 };
      summary[i.product].qty++;
    });
    // build the HTML list correctly
    let html = '<ul class="list-disc pl-5">';
    for (let product in summary) {
      // use backticks for template literals
      html += `<li>${summary[product].qty} x ${product} – Ksh ${summary[product].price.toFixed(2)}</li>`;
    }
    html += '</ul>';
    content.innerHTML = html;
  }
  document.getElementById('cartModalTotal').textContent = cart.total.toFixed(2);
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
  </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
