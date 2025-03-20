<?php
session_start();
// Database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'oceangas';

// Create connection using mysqli
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query the stock table for product information
$query = "SELECT * FROM stock";
$result = $conn->query($query);

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
  
  <!-- Font Awesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  
  <style>
    /* Navbar Styles */
    .navbar {
      background: #0066cc;
      padding: 1rem 3rem;
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    }
    .navbar .logo-text {
      font-size: 1.25rem;
      font-weight: bold;
      color: white;
    }
    .navbar img {
      height: 70px;
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
      color: white;
      font-size: 18px;
      padding: 8px 16px;
      border-radius: 5px;
      transition: 0.3s;
    }
    .nav-links li a:hover {
      background: #005bb5;
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
      font-size: 0.75rem;
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
  </style>
</head>
<body class="bg-gray-100">
  <!-- Navbar -->
  <nav class="navbar">
    <div class="container mx-auto flex items-center">
      <img src="assets/images/Oceangas.png" alt="Logo"/>
      <span class="logo-text">Shop - OceanGas Enterprise</span>
      <ul class="nav-links">
        <li><a href="index.html">Home</a></li>
        <li><a href="about.html">About</a></li>
        <li><a href="contact.html">Contact</a></li>
        <li><a onclick="window.location.href='logout.php'">Logout</a></li>
        <li class="cart-icon">
          <a href="checkout.php" id="cartIcon"> 
            <i class="fas fa-shopping-cart fa-2x"></i>
            <span id="cart-count">0</span>
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- Shop Header -->
  <header class="bg-white shadow">
    <div class="container mx-auto px-4 py-4">
      <h1 class="text-3xl font-bold">Products</h1>
    </div>
  </header>

  <!-- Products Section -->
  <main class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php 
      while($row = $result->fetch_assoc()): 
        // Normalize the product string to lowercase for easier comparison.
        $productName = strtolower(trim($row['product']));
        
        // Check if the product is a 6kg gas cylinder.
        if (strpos($productName, '6kg') !== false) {
            $img = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRyrg_2y3_ZJc_PaB5J0OMEKRTHWVEttzy_XQ&s";
            $price = 1200;
        } else if (strpos($productName, '12kg') !== false) {
            $img = "https://www.rihalenergy.com/wp-content/uploads/2019/09/gas-bottle-image-layer-B.png";
            $price = 2300;
        } else {
            $img = "https://example.com/path/to/generic-default-image.jpg"; 
            $price = 0;
        }
      ?>
      <div class="product-card p-6 rounded-lg shadow-lg">
        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($row['product']); ?>" class="w-full h-48 object-contain mb-4">
        <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($row['product']); ?></h2>
        <p class="text-blue-600 text-lg font-semibold mb-4">Price: Ksh <?php echo $price; ?></p>
        
        <?php if($row['quantity'] > 0): ?>
          <button data-product="<?php echo htmlspecialchars($row['product']); ?>" data-price="<?php echo $price; ?>" class="add-to-cart bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
            Add to Cart
          </button>
        <?php else: ?>
          <p class="text-red-600 font-bold">Out of Stock</p>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
    </div>
    <!-- Checkout Button -->
    <div class="mt-8 text-center">
      <button id="checkout-btn" class="bg-green-500 hover:bg-green-600 text-white py-2 px-6 rounded">
        Checkout
      </button>
    </div>
  </main>

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
    // Initialize an empty cart object.
    let cart = { items: [], total: 0 };

    // Function to update cart count.
    function updateCartDisplay() {
      document.getElementById('cart-count').textContent = cart.items.length;
    }

    // Function to render cart modal content.
    function renderCartModal() {
      let modalContent = document.getElementById('cartModalContent');
      let html = '';

      if (cart.items.length === 0) {
        html = '<p>Your cart is empty.</p>';
      } else {
        let cartSummary = {};

        // Count quantity of each unique item
        cart.items.forEach(item => {
          if (cartSummary[item.product]) {
            cartSummary[item.product].quantity += 1;
          } else {
            cartSummary[item.product] = { price: item.price, quantity: 1 };
          }
        });

        // Generate formatted cart display
        html = '<ul class="list-disc pl-5">';
        for (let product in cartSummary) {
          let { price, quantity } = cartSummary[product];
          html += `<li>${quantity} x ${product} - Ksh ${price} each</li>`;
        }
        html += '</ul>';
      }

      modalContent.innerHTML = html;
      document.getElementById('cartModalTotal').innerHTML = `Ksh ${cart.total}`;
    }

    // Show empty cart modal.
    function showEmptyCartModal() {
      document.getElementById('emptyCartModal').style.display = 'block';
    }

    // Add event listener to Add to Cart buttons.
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
      button.addEventListener('click', function() {
        const product = this.getAttribute('data-product');
        const price = parseInt(this.getAttribute('data-price'));

        // Add item to local cart.
        cart.items.push({ product, price });
        cart.total += price;
        updateCartDisplay();
        alert(product + " added to cart. Total Price: Ksh " + cart.total);

        // Send an AJAX request to store the cart item in the database.
        fetch('store_cart_item.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `product=${encodeURIComponent(product)}&price=${encodeURIComponent(price)}`
        })
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(error => console.error(error));
      });
    });

    // Show cart modal when clicking on the cart icon.
    document.getElementById('cartIcon').addEventListener('click', function(e) {
      e.preventDefault();
      renderCartModal();
      document.getElementById('cartModal').style.display = 'block';
    });

    // Close cart modal.
    document.getElementById('closeCartBtn').addEventListener('click', function() {
      document.getElementById('cartModal').style.display = 'none';
    });

    // Empty cart modal close button.
    document.getElementById('closeEmptyCartBtn').addEventListener('click', function() {
      document.getElementById('emptyCartModal').style.display = 'none';
    });

    // Checkout button event (modal).
    document.getElementById('modalCheckoutBtn').addEventListener('click', function() {
      if (cart.items.length === 0) {
        showEmptyCartModal();
        return;
      }
      localStorage.setItem('cart', JSON.stringify(cart));
      window.location.href = 'checkout.php';
    });
    
    // Checkout button event (main).
    document.getElementById('checkout-btn').addEventListener('click', function() {
      if (cart.items.length === 0) {
        showEmptyCartModal();
        return;
      }
      localStorage.setItem('cart', JSON.stringify(cart));
      window.location.href = 'checkout.php';
    });
  </script>
</body>
</html>
