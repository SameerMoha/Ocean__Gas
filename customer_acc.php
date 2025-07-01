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

// Fetch account details including profile image
$acctSql = "SELECT F_name, L_name, Email, Phone_number, profile_image FROM customers WHERE cust_id = ?";
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
$limit = 3;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

if ($tab === 'Delivered') {
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

    $typesAll = 'i' . $types . 'ii';
    $params   = array_merge([$custId], $statuses, [$limit, $offset]);
    $orderStmt->bind_param($typesAll, ...$params);
}

$orderStmt->execute();
$orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch address data
$addrSql = "SELECT delivery_location, apartment_number, phone_number FROM customers WHERE cust_id = ?";
$addrStmt = $conn->prepare($addrSql);
$addrStmt->bind_param('i', $custId);
$addrStmt->execute();
$addr = $addrStmt->get_result()->fetch_assoc();

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
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    .navbar {
      background: #0066cc;
      padding: 1rem 3rem;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
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
    .cart-icon {
      position: relative;
    }
    .cart-icon i {
      color: white;
    }
    .cart-icon span {
      position: absolute;top: -0.25rem;right: -0.25rem;background-color: #f56565;color: white;border-radius: 9999px;font-size: 0.7rem;width: 1.25rem;height: 1.25rem;
      display: flex; align-items: center; justify-content: center;
    } 
    .navbar .profile-text{
      font-size: 1.2rem;
      color: white;
      margin-right: 10px;
    }
    
    /* Profile Image Styling */
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
    
    .account-profile-image {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #e5e7eb;
      display: block;
      flex-shrink: 0;
    }
    
    .dropdown-toggle {
      align-items: center !important;
      display: flex !important;
    }
    
    .nav-links .dropdown {
      display: flex;
      align-items: center;
    }
    
    .nav-links img.profile-image {
      border-radius: 50% !important;
      width: 40px !important;
      height: 40px !important;
      object-fit: cover !important;
    }

    /* Modal Styling */
    .modal {
      z-index: 1050 !important;
    }
    
    .modal-content {
      animation: modalFadeIn 0.3s ease-out;
    }
    
    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
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
  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style=" color:black;">
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
        <h2 class="text-2xl font-semibold">Account Overview</h2>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Account Details Card -->
        <div class="bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition-shadow duration-150">
          <h3 class="text-lg font-medium mb-4">Account Details</h3>
          <div class="flex items-center mb-4">
            <?php if (hasProfileImage($acct['profile_image'])): ?>
              <img src="<?php echo getProfileImageSrc($acct['profile_image']); ?>" 
                   alt="Profile" class="account-profile-image mr-4">
            <?php else: ?>
              <div class="account-profile-image mr-4 bg-gray-200 flex items-center justify-center rounded-full">
                <i class="fas fa-user fa-2x text-gray-500"></i>
              </div>
            <?php endif; ?>
            <div>
              <p class="font-semibold"><?php echo htmlspecialchars($acct['F_name'] . ' ' . $acct['L_name']); ?></p>
              <p class="text-gray-500"><?php echo htmlspecialchars($acct['Email']); ?></p>
              <p class="text-gray-500"><?php echo htmlspecialchars($acct['Phone_number']); ?></p>
            </div>
          </div>
        </div>

        <!-- Address Book Card -->
        <div class="bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition-shadow duration-150">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Address Book</h3>
            <button type="button" onclick="openAddressModal()" class="text-gray-600 hover:text-gray-800 transition-colors duration-150 cursor-pointer p-2 rounded hover:bg-gray-100" title="Edit Address">
              <i class="fas fa-pen"></i>
            </button>
          </div>
          <?php if ($addr && !empty($addr['delivery_location'])): ?>
            <p class="font-semibold"><?php echo htmlspecialchars($acct['F_name'] . ' ' . $acct['L_name']); ?></p>
            <p class="text-gray-700"><?php echo htmlspecialchars($addr['delivery_location']); ?></p>
            <?php if (!empty($addr['apartment_number'])): ?>
              <p class="text-gray-700"><?php echo htmlspecialchars($addr['apartment_number']); ?></p>
            <?php endif; ?>
            <p class="text-gray-700"><?php echo htmlspecialchars($addr['phone_number']); ?></p>
          <?php else: ?>
            <p class="text-gray-500">No default address set.</p>
            <button type="button" onclick="openAddressModal()" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150 cursor-pointer">Add Address</button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Orders Overview -->
      <div class="mt-12 flex justify-between items-center">
        <h2 class="text-2xl font-semibold">Orders Overview</h2>
        <a href="customer_orders.php" class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors duration-150">View all orders</a>
      </div>

      <!-- Tabs -->
      <div class="mt-4 flex space-x-3">
        <?php foreach (array_keys($statusMap) as $t): ?>
          <a href="?status=<?= urlencode($t) ?>" class="px-4 py-2 rounded-md font-medium <?= $t === $tab ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?> transition-all duration-150"><?= htmlspecialchars($t) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Orders Table -->
      <?php if (count($orders) > 0): ?>
        <div class="mt-6 overflow-x-auto bg-white shadow-sm rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Summary</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($orders as $o): ?>
              <tr class="hover:bg-blue-50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($o['order_number']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M j, Y', strtotime($o['order_date'])); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($o['invoice_summary']); ?></td>
                <td class="px-6 py-4 text-right whitespace-nowrap">Ksh <?php echo number_format($o['total_amount'], 2); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars(ucfirst($o['order_status'])); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="mt-12 text-center p-12 bg-white rounded-lg shadow-sm">
          <i class="fas fa-box-open fa-3x text-gray-300 mb-4"></i>
          <h3 class="text-xl font-semibold mb-2">No orders in "<?= htmlspecialchars($tab) ?>"</h3>
          <a href="shop.php" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">Start sourcing</a>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <!-- Address Book Popup Modal -->
  <div id="addressModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:5% auto; padding:30px; border-radius:12px; max-width:600px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Address Book</h2>
        <span onclick="closeAddressModal()" class="text-gray-400 hover:text-gray-600 text-3xl cursor-pointer">&times;</span>
      </div>
      
      <!-- Current Address Display -->
      <?php if ($addr && !empty($addr['delivery_location'])): ?>
      <div class="bg-blue-50 p-4 rounded-lg mb-6">
        <h3 class="font-semibold text-blue-800 mb-2">Current Address</h3>
        <p class="text-blue-700"><?php echo htmlspecialchars($addr['delivery_location']); ?></p>
        <?php if (!empty($addr['apartment_number'])): ?>
          <p class="text-blue-700"><?php echo htmlspecialchars($addr['apartment_number']); ?></p>
        <?php endif; ?>
        <p class="text-blue-700">Phone: <?php echo htmlspecialchars($addr['phone_number']); ?></p>
      </div>
      <?php endif; ?>
      
      <form id="addressForm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
            <input type="text" value="<?php echo htmlspecialchars($acct['F_name']); ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
            <input type="text" value="<?php echo htmlspecialchars($acct['L_name']); ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
          </div>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Location *</label>
          <input type="text" id="deliveryLocation" value="<?php echo htmlspecialchars($addr['delivery_location'] ?? ''); ?>" 
                 placeholder="Enter your delivery address" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Apartment/House Number</label>
          <input type="text" id="apartmentNumber" value="<?php echo htmlspecialchars($addr['apartment_number'] ?? ''); ?>" 
                 placeholder="Apt, suite, house number, etc."
                 class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
          <input type="tel" id="phoneNumber" value="<?php echo htmlspecialchars($addr['phone_number'] ?? $acct['Phone_number']); ?>" 
                 placeholder="Enter your phone number" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="flex justify-end space-x-3">
          <button type="button" onclick="closeAddressModal()" 
                  class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors duration-150">
            Cancel
          </button>
          <button type="submit" id="saveAddressBtn" 
                  class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-150">
            <i class="fas fa-save mr-2"></i><?php echo ($addr && !empty($addr['delivery_location'])) ? 'Update Address' : 'Save Address'; ?>
          </button>
        </div>
      </form>
    </div>
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

  <div id="emptyCartModal" class="modal" style="display:none; position:fixed; z-index:200; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; border-radius:8px; max-width:500px;">
      <h2 class="text-2xl font-bold mb-4">Cart is Empty</h2>
      <p class="mb-4">Your cart is empty. Please add an item before checking out.</p>
      <button id="closeEmptyCartBtn" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">Okay</button>
    </div>
  </div>

  <script>
    // Cart functionality
    let cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };

    function saveCart() { localStorage.setItem('cart', JSON.stringify(cart)); }
    function updateCartDisplay() {
      document.getElementById('cart-count').textContent = cart.items.length;
    }
    
    function showNotification(msg) {
      let n = document.getElementById('notification');
      
      // If notification element doesn't exist, create it
      if (!n) {
        document.body.insertAdjacentHTML('afterbegin',
          '<div id="notification" style="position:fixed;top:5%;left:50%;transform:translateX(-50%);background:#48bb78;color:#fff;padding:.75rem 1rem;border-radius:.5rem;opacity:0;transition:.3s;z-index:300;"></div>'
        );
        n = document.getElementById('notification');
      }
      
      // Now safely set the content and show the notification
      n.textContent = msg;
      n.style.opacity = 1;
      setTimeout(() => n.style.opacity = 0, 2000);
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
        let html = '<ul class="list-disc pl-5">';
        for (let product in summary) {
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

    // Cart icon events
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

    // Initialize cart display
    updateCartDisplay();

    // Address Modal Functions
    function openAddressModal() {
      document.getElementById('addressModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeAddressModal() {
      document.getElementById('addressModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
      const addressModal = document.getElementById('addressModal');
      if (e.target === addressModal) {
        closeAddressModal();
      }
    });

    // Address form submission
    document.getElementById('addressForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData();
      formData.append('delivery_location', document.getElementById('deliveryLocation').value);
      formData.append('apartment_number', document.getElementById('apartmentNumber').value);
      formData.append('phone_number', document.getElementById('phoneNumber').value);
      
      const saveBtn = document.getElementById('saveAddressBtn');
      const originalText = saveBtn.innerHTML;
      saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
      saveBtn.disabled = true;
      
      fetch('update_address.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification('Address updated successfully!');
          closeAddressModal();
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          alert('Error updating address: ' + (data.message || 'Unknown error'));
          saveBtn.innerHTML = originalText;
          saveBtn.disabled = false;
        }
      })
      .catch(error => {
        alert('Error updating address: ' + error.message);
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
      });
    });

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