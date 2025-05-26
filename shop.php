<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: customer/login.php');
  exit;
}
$custId = $_SESSION['user_id'];

// Database credentials
$host     = 'localhost';
$user     = 'root';
$password = '';
$dbname   = 'oceangas';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch account details
$acctSql = "SELECT F_name, L_name FROM customers WHERE cust_id = ?";
$acctStmt = $conn->prepare($acctSql);
if (!$acctStmt) die("Account query failed: " . $conn->error);
$acctStmt->bind_param('i', $custId);
if (!$acctStmt->execute()) die("Account execution failed: " . $acctStmt->error);
$acct = $acctStmt->get_result()->fetch_assoc();

$sql = "
  SELECT
    p.product_id,
    p.product_name,
    p.quantity     AS quantity,
    pr.selling_price       AS unit_price,
    p.image_path
  FROM products p
  LEFT JOIN price pr
    ON p.product_id = pr.product_id
  ORDER BY p.product_id
";
$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . $conn->error);
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shop - OceanGas Enterprise</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


  <style>
    /* Navbar Styles */
    .navbar {
      background: #0066cc;
      padding: 1rem 2rem;
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
    .cart-icon span {
      position: absolute;
      top: -0.25rem;
      right: -0.25rem;
      background-color: #f56565;
      color: white;
      border-radius: 9999px;
      font-size: 0.7rem;
      width: 1.25rem;
      height: 1.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    /* Product Card Styles */
    .product-card {
      background-color: #e0f0ff;
      transition: transform 0.3s ease;
    }
    .product-card:hover {
      transform: scale(1.02);
    }
    /* Modal Styles */
    .modal {
      display: none; /* Hidden by default */
      position: fixed;
      z-index: 200;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
    }
    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      max-width: 500px;
    }
    /* Toast Notification Styles */
    #notification {
      position: fixed;
      top: 5%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #48bb78;
      color: white;
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 300;
    }
    /* Quantity Controls */
    .quantity-controls button {
      font-size: 1rem;
    }
    .profile{
      position: relative;
    }
    .navbar .profile-text{
      font-size: 1.2rem;
      color: white;
      margin-right: 10px;
    }
      h2, h3 {
      color:rgb(0, 102, 17);
    }

    h1 {
      font-size: 5.2em;
      margin-bottom: 10px;
      border-bottom: 2px solid #003366;
      padding-bottom: 5px;
      text-align: center;
    }

    h2 {
      font-size: 1.5em;
      margin-top: 30px;
      padding-left: 10px;
    }

    ul, ol {
      padding-left: 20px;
    }

    ul li, ol li {
      margin-bottom: 10px;
    }

    .highlight {
      background-color: #e9f5ff;
      padding: 10px;
      margin: 15px 0;
    }

    .note {
      font-style: italic;
      color: #666;
    }
    footer {
      text-align: center;
      padding: 1rem 0;
      background: #0066cc;
      color: #fff;
      width: 100%;
    }
    .policy {
      max-width: 900px;
      margin: 0 auto;
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      margin-top: 10px;
    }
  </style>
</head>
<body class="bg-gray-100">
  <!-- Notification Container -->
  <div id="notification"></div>
  
  <!-- Navbar -->
  <nav class="navbar">
    <div class="container mx-auto flex items-center">
      <img src="assets/images/Oceangas.png" alt="Logo"/>
      <span class="logo-text">Shop - OceanGas Enterprise</span>
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

  <!-- Shop Header -->
  <header class="bg-white shadow">
    <div class="container mx-auto px-4 py-4">
      <h1 class="text-center text-3xl font-bold">Products</h1>
    </div>
  </header>

  <!-- Products Section -->
  <main class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php while($row = $result->fetch_assoc()): ?>
        <?php
          // Determine image path or fallback
          $img = (!empty($row['image_path']) && file_exists($row['image_path']))
                 ? $row['image_path']
                 : 'assets/images/default.jpg';
          $price = (int)$row['unit_price'];
          $productName = htmlspecialchars($row['product_name']);
          $stock = (int)$row['quantity'];
        ?>
        <div class="product-card p-6 rounded-lg shadow-lg">
          <img src="<?php echo htmlspecialchars($img); ?>"
               alt="<?php echo $productName; ?>"
               class="w-full h-48 object-contain mb-4">
          <h2 class="text-center text-xl font-bold mb-2"><?php echo $productName; ?></h2>
          <p class="text-center text-blue-600 text-lg font-semibold mb-4">
            Price: Ksh <?php echo $price; ?>
          </p>
          <?php if ($stock > 0): ?>
            <div class="button-container flex justify-center">
              <button 
  data-product-id="<?php echo $row['product_id']; ?>"
  data-product_name="<?php echo $productName; ?>" 
  data-price="<?php echo $price; ?>" 
  data-stock="<?php echo $stock; ?>"
  class="add-to-cart bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
  Add to Cart
</button>

            </div>
          <?php else: ?>
            <div class="button-container flex justify-center">
              <button class="bg-red-500 text-white py-2 px-4 rounded cursor-not-allowed" disabled>
                Out of Stock
              </button>
            </div>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    </div>

    <div class="mt-8 text-center">
      <button id="checkout-btn" class="bg-green-500 hover:bg-green-600 text-white py-2 px-6 rounded">
        Checkout
      </button>
    </div>
     <div class="policy">
    <h1 class="text-center text-3xl font-bold">OceanGas Return Policy for Gas</h1>

    <p>At OceanGas, we are committed to providing our customers with high-quality products that are genuine and safe. In the rare event that you need to return a product, please review our return policy below:</p>

    <h2>Timeframe for Returns</h2>
    <ul>
      <li>Gas returns must be initiated within <strong>2 days</strong> from the date of delivery.</li>
      <li>Water returns must be initiated within <strong>24 hours</strong> from the date of delivery.</li>
    </ul>

    <h2>Eligibility Criteria</h2>
    <ul>
      <li>Returns are accepted for water bottles with leakages or faults.</li>
      <li>Returns are accepted for gas cylinders that are defective, leaking, or faulty.</li>
      <li>Gas must not be used and cannot vary by more than <strong>0.1 KG</strong> from original weight.</li>
    </ul>

    <h2>Return Procedure</h2>
    <ol>
      <li>Contact our customer service team immediately upon discovering the issue.</li>
      <li>Provide your order number, details of the issue, and any supporting photographs.</li>
      <li>Our team will assess and guide you through the return process.</li>
    </ol>

    <h2>Replacement</h2>
    <ul>
      <li>Upon approval, you will be eligible for a replacement.</li>
      <li>Replacements will be processed within a reasonable timeframe.</li>
    </ul>

    <h2>General Return Guidelines</h2>
    <ul>
      <li>Ensure the product is in its original packaging.</li>
      <li>Returns not meeting the criteria may be refused.</li>
    </ul>

    <h2>About OceanGas Delivery Service</h2>
    <p>We deliver genuine LPG cooking gas from major brands along with accessories like regulators, igniters, hoses, grills, and burners. We supply new and refill cylinders in 6KG, 12KG, and Composite sizes across Nairobi.</p>

    <p>Our official delivery partners include Rubis, Vivo Energy/Shell Afrigas, and Galana.</p>

    <h2>Delivery Locations</h2>
    <p><em>We deliver in most parts of Nairobi including:</em> Adams Arcade, Akiba, Bellevue, Buruburu, CBD, Donholm, Embakasi, Garden Estate, Hurlingham, Imara Daima, Karen, Kasarani, Kilimani, Langata, Lavington, Parklands, South B, South C, Thika Road, Westlands, Zimmerman — and many more.</p>

    <h2>Why You Should Avoid Counterfeit LPG</h2>
    <ul>
      <li><strong>Safety:</strong> Counterfeit gas risks explosions and serious injuries.</li>
      <li><strong>Quantity:</strong> You may receive less gas than paid for.</li>
      <li><strong>Support Kenyan Businesses:</strong> Buying genuine LPG promotes legal trade.</li>
    </ul>

    <h2>What to Do in Case of a Gas Leak</h2>
    <div class="highlight">
      <ol>
        <li>Smell gas? It’s likely a leak.</li>
        <li>Extinguish flames and do not ignite anything.</li>
        <li>Turn off and remove the regulator.</li>
        <li>Do not use electronics — they may spark.</li>
        <li>Open all windows and doors.</li>
        <li>If safe, move the cylinder outside.</li>
        <li>Evacuate and notify your neighbors.</li>
      </ol>
    </div>

  </main>
<footer>
     <p>&copy; 2025 Ocean Gas Company. All Rights Reserved.</p>
  </footer>
  <!-- Cart Modal -->
  <div id="cartModal" class="modal">
    <div class="modal-content">
      <h2 class="text-2xl font-bold mb-4">Your Cart</h2>
      <div id="cartModalContent"></div>
      <p class="mt-4 font-bold">Total: Ksh <span id="cartModalTotal">0</span></p>
      <div class="mt-6 flex justify-end space-x-4">
        <button id="closeCartBtn" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
          Close
        </button>
        <button id="modalCheckoutBtn" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">
          Checkout
        </button>
      </div>
    </div>
  </div>

  <!-- Empty Cart Modal -->
  <div id="emptyCartModal" class="modal">
    <div class="modal-content">
      <h2 class="text-2xl font-bold mb-4">Cart is Empty</h2>
      <p class="mb-4">Your cart is empty. Please add an item before checking out.</p>
      <button id="closeEmptyCartBtn" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
        Okay
      </button>
    </div>
  </div>

  <!-- JavaScript Section -->
  <script>
  // 1. Load cart from localStorage (or start fresh)
  let cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };

  // Utility: save cart back to localStorage
  function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
  }

  // Update the little badge count
  function updateCartDisplay() {
    document.getElementById('cart-count').textContent = cart.items.length;
  }

  function showNotification(message) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.style.opacity = 1;
    setTimeout(() => notification.style.opacity = 0, 2000);
  }

  // Render cart inside the modal
  function renderCartModal() {
    const modalContent = document.getElementById('cartModalContent');
    if (cart.items.length === 0) {
      modalContent.innerHTML = '<p>Your cart is empty.</p>';
    } else {
      const summary = {};
      cart.items.forEach(item => {
        summary[item.product_name] = summary[item.product_name] || { price: item.price, quantity: 0 };
        summary[item.product_name].quantity++;
      });
      let html = '<ul class="list-disc pl-5">';
      for (let prod in summary) {
        html += `<li>${summary[prod].quantity} x ${prod} - Ksh ${summary[prod].price}</li>`;
      }
      html += '</ul>';
      modalContent.innerHTML = html;
    }
    document.getElementById('cartModalTotal').textContent = cart.total;
  }

  // Show “empty cart” warning
  function showEmptyCartModal() {
    document.getElementById('emptyCartModal').style.display = 'block';
  }

  // Create the +/– controls
  function createQuantityControls(product_name, price, qty, maxStock) {
    const container = document.createElement('div');
    container.className = 'quantity-controls';
    container.dataset.product_name = product_name;
    container.dataset.price = price;
    container.dataset.maxStock = maxStock;

    const minus = document.createElement('button');
    minus.textContent = '-';
    minus.className = 'minus-btn bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded';

    const qtyDisp = document.createElement('span');
    qtyDisp.textContent = qty;
    qtyDisp.className = 'quantity font-bold mx-2';

    const plus = document.createElement('button');
    plus.textContent = '+';
    plus.className = 'plus-btn bg-green-500 hover:bg-green-600 text-white py-1 px-2 rounded';

    // Decrease
    minus.addEventListener('click', () => {
      let current = parseInt(qtyDisp.textContent);
      if (current > 1) {
        qtyDisp.textContent = --current;
        const idx = cart.items.findIndex(i => i.product_name === product_name);
        cart.items.splice(idx, 1);
        cart.total -= price;
        saveCart(); updateCartDisplay(); showNotification(`${product_name} quantity decreased to ${current}`);
      } else {
        container.parentNode.innerHTML =
          `<button data-product_name="${product_name}" data-price="${price}" data-stock="${maxStock}"
                   class="add-to-cart bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
             Add to Cart
           </button>`;
        attachAddHandler();
        cart.items = cart.items.filter(i => i.product_name !== product_name);
        cart.total -= price;
        saveCart(); updateCartDisplay(); showNotification(`${product_name} removed from cart`);
      }
    });

    // Increase
    plus.addEventListener('click', () => {
      let current = parseInt(qtyDisp.textContent);
      if (current < maxStock) {
        qtyDisp.textContent = ++current;
        cart.items.push({ product_name, price });
        cart.total += price;
        saveCart(); updateCartDisplay(); showNotification(`${product_name} quantity increased to ${current}`);
      } else {
        showNotification(`Max stock reached for ${product_name}`);
      }
    });

    container.append(minus, qtyDisp, plus);
    return container;
  }

  // Handler for new “Add to Cart”
  function addToCartHandler() {
    const productId = this.dataset.productId;
    const product_name = this.dataset.product_name;
    const price = parseInt(this.dataset.price);
    const stock = parseInt(this.dataset.stock);

    cart.items.push({ productId, product_name, price });
    cart.total += price;
    saveCart(); updateCartDisplay(); showNotification(`${product_name} added. Total: Ksh ${cart.total}`);

    const qc = createQuantityControls(product_name, price, 1, stock);
    this.parentNode.replaceChild(qc, this);
  }

  // Attach Add-to-Cart click listeners
  function attachAddHandler() {
    document.querySelectorAll('.add-to-cart').forEach(btn =>
      btn.addEventListener('click', addToCartHandler)
    );
  }

  // On page load, re-render any existing cart items
  function restoreCartUI() {
    const summary = {};
    cart.items.forEach(item => {
      summary[item.product_name] = summary[item.product_name] || 0;
      summary[item.product_name]++;
    });

    for (let prod in summary) {
      const btn = document.querySelector(`button[data-product_name="${prod}"]`);
      if (btn) {
        const price = parseInt(btn.dataset.price);
        const maxStock = parseInt(btn.dataset.stock);
        const qty = summary[prod];
        const qc = createQuantityControls(prod, price, qty, maxStock);
        btn.parentNode.replaceChild(qc, btn);
      }
    }
  }

  // Modal & Checkout wiring
  document.getElementById('cartIcon').addEventListener('click', e => {
    e.preventDefault();
    renderCartModal();
    document.getElementById('cartModal').style.display = 'block';
  });
  document.getElementById('closeCartBtn').addEventListener('click', () => {
    document.getElementById('cartModal').style.display = 'none';
  });
  document.getElementById('closeEmptyCartBtn').addEventListener('click', () => {
    document.getElementById('emptyCartModal').style.display = 'none';
  });
  document.getElementById('modalCheckoutBtn').addEventListener('click', () => {
    if (cart.items.length === 0) return showEmptyCartModal();
    window.location.href = 'checkout.php';
  });
  document.getElementById('checkout-btn').addEventListener('click', () => {
    if (cart.items.length === 0) return showEmptyCartModal();
    window.location.href = 'checkout.php';
  });

  // kick things off
  attachAddHandler();
  updateCartDisplay();
  restoreCartUI();
</script>


</body>
</html>
