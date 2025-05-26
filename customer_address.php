<?php
session_start();
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: customer/login.php');
    exit;
}
$custId = $_SESSION['user_id'];

// Determine current page for active sidebar highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'oceangas';
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$acctSql = "SELECT F_name, L_name FROM customers WHERE cust_id = ?";
$acctStmt = $conn->prepare($acctSql);
if (!$acctStmt) die("Account query failed: " . $conn->error);
$acctStmt->bind_param('i', $custId);
if (!$acctStmt->execute()) die("Account execution failed: " . $acctStmt->error);
$acct = $acctStmt->get_result()->fetch_assoc();

// Handle form submission: update existing customer's address fields
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location  = trim($_POST['delivery_location']);
    $apartment = trim($_POST['apartment_number']);
    $phone     = trim($_POST['phone_number']);

    $updSql = "UPDATE customers SET delivery_location = ?, apartment_number = ?, phone_number = ? WHERE cust_id = ?";
    $updStmt = $conn->prepare($updSql);
    if (!$updStmt) {
        die("Update prepare failed: " . $conn->error);
    }
    if (!$updStmt->bind_param('sssi', $location, $apartment, $phone, $custId)) {
        die("Update bind_param failed: " . $updStmt->error);
    }
    if (!$updStmt->execute()) {
        die("Update execution failed: " . $updStmt->error);
    }
    header('Location: customer_address.php');
    exit;
}

// Fetch current address fields from customers table
$addr = null;
$addrSql = "SELECT delivery_location, apartment_number, phone_number FROM customers WHERE cust_id = ?";
$addrStmt = $conn->prepare($addrSql);
if (!$addrStmt) {
    die("Select prepare failed: " . $conn->error);
}
if (!$addrStmt->bind_param('i', $custId)) {
    die("Select bind_param failed: " . $addrStmt->error);
}
if (!$addrStmt->execute()) {
    die("Select execution failed: " . $addrStmt->error);
}
$res = $addrStmt->get_result();
if ($res && $res->num_rows) {
    $addr = $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Address Book</title>
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
    .sidebar { background: #fff; border-right: 1px solid #dee2e6; padding: 1rem; height: 100vh; }
    .sidebar .nav-link { color: #333; margin-bottom: .5rem; }
    .sidebar .nav-link.active { background: #e9ecef; font-weight: bold; }
    .content { padding: 2rem; }
    .form-section { max-width: 600px; margin: auto; }
    .navbar .profile-text{
      font-size: 1.2rem;
      color: white;
      margin-right: 10px;
    }
  </style>
</head>
<body>
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
    <li><a class="dropdown-item" href='customer_inquiries.php'> Help</a></li>
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

  <div class="container-fluid">
    <div class="row">
      <nav class="col-md-3 sidebar">
        <h5><i class="fas fa-user-circle me-2"></i>My Account</h5>
        <div class="nav flex-column mt-3">
          <a class="nav-link <?php echo isActive('customer_acc.php'); ?>" href="customer_acc.php">Account Overview</a>
          <a class="nav-link <?php echo isActive('customer_orders.php'); ?>" href="customer_orders.php">Orders</a>
          <hr>
          <a class="nav-link <?php echo isActive('customer_address.php'); ?>" href="customer_address.php">Address Book</a>
          <a class="nav-link <?php echo isActive('customer_inquiries.php'); ?>" href="customer_inquiries.php">Inquiries</a>
          <a class="nav-link <?php echo isActive('customer_reviews.php'); ?>" href="customer_reviews.php">Reviews</a>
          <a class="nav-link text-danger" href="/OceanGas/customer/logout.php">Sign Out</a>
          <a href="shop.php" class="btn btn-primary btn-sm active" role="button" aria-pressed="true">Back to shop</a>
        </div>
      </nav>

      <main class="col-md-9 content">
        <h4>Address Book</h4>
        <div class="form-section">
          <?php if ($addr): ?>
            <div class="card mb-4">
              <div class="card-header text-1xl font-bold">Current Address</div>
              <div class="card-body">
                <p><?php echo htmlspecialchars($addr['delivery_location']); ?></p>
                <p><?php echo htmlspecialchars($addr['apartment_number']); ?></p>
                <p>Phone: <?php echo htmlspecialchars($addr['phone_number']); ?></p>
              </div>
            </div>
          <?php endif; ?>

          <div class="card">
            <div class="card-header"><?php echo $addr ? 'Edit Address' : 'Add Address'; ?></div>
            <div class="card-body">
              <form method="post" action="customer_address.php">
                <div class="mb-3">
                  <label for="delivery_location" class="form-label ">Delivery Location</label>
                  <input type="text" class="form-control" id="delivery_location" name="delivery_location" value="<?php echo $addr ? htmlspecialchars($addr['delivery_location']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                  <label for="apartment_number" class="form-label">Apartment / Suite</label>
                  <input type="text" class="form-control" id="apartment_number" name="apartment_number" value="<?php echo $addr ? htmlspecialchars($addr['apartment_number']) : ''; ?>">
                </div>
                <div class="mb-3">
                  <label for="phone_number" class="form-label">Phone Number</label>
                  <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo $addr ? htmlspecialchars($addr['phone_number']) : ''; ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $addr ? 'Update Address' : 'Save Address'; ?></button>
              </form>
            </div>
          </div>
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
