<?php  
session_start();
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: customer/login.php');
    exit;
}
$custId = $_SESSION['user_id'];

// Determine current page for sidebar highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? ' active' : '';
}

// Determine current tab and pagination
$tab = isset($_GET['tab']) && $_GET['tab'] === 'pending' ? 'pending' : 'confirmed';
$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'oceangas';
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch customer account info
$acctSql = "SELECT F_name, L_name, Email, Phone_number FROM customers WHERE cust_id = ?";
$acctStmt = $conn->prepare($acctSql);
if (!$acctStmt) die("Account query failed: " . $conn->error);
$acctStmt->bind_param('i', $custId);
$acctStmt->execute();
$acct = $acctStmt->get_result()->fetch_assoc();

// Fetch counts for tabs
$countConfirmedSql = "SELECT COUNT(*) FROM orders WHERE cust_id = ? AND LOWER(order_status) IN ('confirmed', 'delivered')";
$countConfirmedStmt = $conn->prepare($countConfirmedSql);
$countConfirmedStmt->bind_param('i', $custId);
$countConfirmedStmt->execute();
$countConfirmed = $countConfirmedStmt->get_result()->fetch_row()[0];

$countPendingSql = "SELECT COUNT(*) FROM orders WHERE cust_id = ? AND LOWER(order_status) IN ('pending', 'new')";
$countPendingStmt = $conn->prepare($countPendingSql);
$countPendingStmt->bind_param('i', $custId);
$countPendingStmt->execute();
$countPending = $countPendingStmt->get_result()->fetch_row()[0];

// Fetch orders for current tab with pagination
if ($tab === 'confirmed') {
    $orderSql = "SELECT invoice_summary, total_amount, order_date, order_number, order_status
                 FROM orders
                 WHERE cust_id = ? AND LOWER(order_status) IN ('confirmed', 'delivered')
                 ORDER BY order_date DESC LIMIT ? OFFSET ?";
    $totalItems = $countConfirmed;
} else {
    $orderSql = "SELECT invoice_summary, total_amount, order_date, order_number, order_status
                 FROM orders
                 WHERE cust_id = ? AND LOWER(order_status) IN ('pending', 'new')
                 ORDER BY order_date DESC LIMIT ? OFFSET ?";
    $totalItems = $countPending;
}
$orderStmt = $conn->prepare($orderSql);
$orderStmt->bind_param('iii', $custId, $limit, $offset);
$orderStmt->execute();
$result = $orderStmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Calculate total pages
$totalPages = $totalItems > 0 ? ceil($totalItems / $limit) : 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
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
    body { background: #f5f5f5; }
    .sidebar { background: #fff; border-right: 1px solid #dee2e6; padding: 1rem; height: relative; }
    .sidebar .nav-link { color: #333; margin-bottom: .5rem; }
    .sidebar .nav-link.active { background: #e9ecef; font-weight: bold; }
    .content { padding: 2rem; }
    .card-section { margin-bottom: 1.5rem; }
    .card-section .card { border: none; border-radius: .25rem; }
    .card-section .card-header { background: #f8f9fa; font-weight: bold; }
    .navbar .profile-text{
      font-size: 1.2rem;
      color: white;
      margin-right: 10px;
    }
        .content { padding: 2rem; }
    .order-card { border: 1px solid #dee2e6; border-radius: .25rem; background: #fff; }
    .order-card img { width: 80px; height: 80px; object-fit: cover; border-radius: .25rem; }
    .badge-status-confirmed { background-color: #ffc107; color: #212529; }
    .badge-status-delivered { background-color: #28a745; }
    .badge-status-pending { background-color: #17a2b8; }
    .badge-status-new { background-color: #0dcaf0; }
  </style>
</head>
<body>
<nav class="navbar d-flex align-items-center" style="background: #0066cc; padding: 1rem 3rem; position: sticky; top: 0; z-index: 10; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);">
    <img src="assets/images/Oceangas.png" alt="Logo" height="50" />
    <ul class="nav ms-auto">
      <li class="nav-item dropdown me-4">
        <a class="nav-link dropdown-toggle d-flex align-items-center text-white text-1xl m-0" href="#" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="me-2">Hi, <?php echo htmlspecialchars($acct['F_name'] . ' ' . $acct['L_name']); ?></span>
          <i class="fas fa-user fa-lg"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="customer_acc.php">My Account</a></li>
          <li><a class="dropdown-item" href="customer_orders.php?tab=confirmed&page=1">Orders</a></li>
          <li><a class="dropdown-item" href="customer_inquiries.php">Help</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="/OceanGas/customer/logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
        </ul>
      </li>
      <li class="nav-item cart-icon position-relative">
        <a class="nav-link" href="#" id="cartIcon"><i class="fas fa-shopping-cart fa-lg text-1xl m-0"></i><span id="cart-count">0</span></a>
      </li>
    </ul>
  </nav>

  <div class="container-fluid">
    <div class="row">
      <nav class="col-md-3 sidebar">
        <h5><i class="fas fa-user-circle me-2"></i>My Account</h5>
        <div class="nav flex-column mt-3">
          <a class="nav-link<?php echo isActive('customer_acc.php'); ?>" href="customer_acc.php">Account Overview</a>
          <a class="nav-link<?php echo isActive('customer_orders.php'); ?>" href="customer_orders.php?tab=confirmed&page=1">Orders</a>
          <hr>
          <a class="nav-link<?php echo isActive('customer_address.php'); ?>" href="customer_address.php">Address Book</a>
          <a class="nav-link <?php echo isActive('customer_inquiries.php'); ?>" href="customer_inquiries.php">Inquiries</a>
          <a class="nav-link <?php echo isActive('customer_reviews.php'); ?>" href="customer_reviews.php">Reviews</a>
          <a class="nav-link text-danger" href="/OceanGas/customer/logout.php">Sign Out</a>
          <a href="shop.php" class="btn btn-primary btn-sm active" role="button" aria-pressed="true">Back to shop</a>
        </div>
      </nav>
      <main class="col-md-9 content">
        <h4 class="fw-bold">Orders</h4>
        <ul class="nav nav-tabs mt-4">
          <li class="nav-item">
            <a class="nav-link<?php echo $tab === 'confirmed' ? ' active' : ''; ?>" href="customer_orders.php?tab=confirmed&page=1">
              Confirmed & Delivered (<?php echo $countConfirmed; ?>)
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?php echo $tab === 'pending' ? ' active' : ''; ?>" href="customer_orders.php?tab=pending&page=1">
              Pending Confirmation (<?php echo $countPending; ?>)
            </a>
          </li>
        </ul>

        <div class="mt-3">
          <?php if (empty($orders)): ?>
            <p>No orders found.</p>
          <?php else: ?>
            <?php foreach ($orders as $o): 
              $status = strtolower($o['order_status']);
              if ($status === 'delivered') {
                $badge = 'badge-status-delivered';
              } elseif ($status === 'new') {
                $badge = 'badge-status-new';
              } elseif ($status === 'pending') {
                $badge = 'badge-status-pending';
              } else {
                $badge = 'badge-status-confirmed';
              }
            ?>
              <div class="d-flex align-items-center mb-3 order-card p-3">
                <div>
                  <small>Order #<?php echo htmlspecialchars($o['order_number']); ?></small>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-1"><?php echo htmlspecialchars($o['invoice_summary']); ?></h6>
                  <span class="badge <?php echo $badge; ?> ms-1"><?php echo ucfirst($o['order_status']); ?></span>
                  <div class="mt-1"><small>On <?php echo date('d-m-Y', strtotime($o['order_date'])); ?></small></div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
              <nav>
                <ul class="pagination">
                  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item<?php echo $p === $page ? ' active' : ''; ?>">
                      <a class="page-link" href="customer_orders.php?tab=<?php echo $tab; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                  <?php endfor; ?>
                </ul>
              </nav>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

 <!-- (1) Cart Modal -->
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

  <!-- (3) Cart JavaScript -->
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
        let html = '<ul class="list-disc pl-5">';
        for (let p in summary) {
          html += `<li>${summary[p].qty} x ${p} - Ksh ${summary[p].price}</li>`;
        }
        content.innerHTML = html + '</ul>';
      }
      document.getElementById('cartModalTotal').textContent = cart.total;
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
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
