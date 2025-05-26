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

// Fetch account details
$acctSql = "SELECT F_name, L_name, Email, Phone_number FROM customers WHERE cust_id = ?";
$acctStmt = $conn->prepare($acctSql);
if (!$acctStmt) die("Account query failed: " . $conn->error);
$acctStmt->bind_param('i', $custId);
if (!$acctStmt->execute()) die("Account execution failed: " . $acctStmt->error);
$acct = $acctStmt->get_result()->fetch_assoc();
$acctStmt->close();



// Handle inquiry submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';

    if (!empty($message)) {
      date_default_timezone_set('Africa/Nairobi');
        $submitted_at = date('Y-m-d H:i:s');

        $sql = "INSERT INTO inquiries (cust_id, message, submitted_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("iss", $custId, $message, $submitted_at);

            if ($stmt->execute()) {
                echo "Inquiry submitted successfully!";
            } else {
                echo "Error submitting inquiry: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Database error: " . $conn->error;
        }
    } else {
        echo "Message cannot be empty.";
    }
}

$inqSql = "
SELECT message, submitted_at, status
FROM inquiries_and_reviews
WHERE cust_id = ?
ORDER BY submitted_at DESC
";
$inqStmt = $conn->prepare($inqSql);
if (!$inqStmt) {
  die("Inquiry query failed: " . $conn->error);
}
$inqStmt->bind_param('i', $custId);
$inqStmt->execute();
$inquiries = $inqStmt->get_result();
$inqStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inquiries</title>
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
    .table-container {
      overflow-x: auto;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      border-radius: 4px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid #eee;
      text-align: left;
      vertical-align: top;
    }
    th {
      background: #f0f0f0;
      font-weight: bold;
    }

    /* Delete button */
    .btn-delete {
      background: #dc3545;
      color: #fff;
      border: none;
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background .3s;
    }
    .btn-delete:hover {
      background: #c82333;
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
      <div class="row card-section">
          <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
              <div class="card-header">Make an inquiry</div>
              <div class="card-body">
                <p class="mb-1"></p>
                
      <h2>Submit Your Inquiry</h2>
  <form action="" method="POST">
    <textarea   class="w-full p-3 border border-gray-300 rounded-md resize-none focus:outline-none focus:ring-2 focus:ring-blue-600 transition"
    name="message" placeholder="Enter your message here..." required rows="5" cols="40"></textarea><br><br>
    <button class="btn btn-primary btn-sm active" type="submit">Submit Inquiry</button>
  </form>
  </div>
  </div>
  </div>
  
      <div class="row card-section">
          <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
              <div class="card-header">My inquiries</div>
              <div class="card-body">
                <p class="mb-2"></p>
                <div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Message</th>
        <th>Submitted At</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($inquiries->num_rows): ?>
        <?php while ($row = $inquiries->fetch_assoc()): ?>
          <tr>
            <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
            <td><?php echo htmlspecialchars($row['submitted_at']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>

          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="3">You have not submitted any inquiries yet.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
  </div>
  </div>
  </main>
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
