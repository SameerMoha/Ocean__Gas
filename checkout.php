<?php
session_start();
require_once 'includes/db.php'; // This file should create and return $conn

// Ensure the customer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: customer/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Retrieve customer billing info from the Customers table
$query = "SELECT F_name, L_name, Email, Phone_number FROM customers WHERE cust_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($F_name, $L_name, $billing_email, $billing_phone);
$stmt->fetch();
$stmt->close();
$conn->close();

// Combine first and last name for billing name
$billing_name = $F_name . ' ' . $L_name;



?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout - OceanGas Enterprise</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .container { margin-top: 50px; }
    .section-title { margin-bottom: 20px; font-weight: bold; }
    /* Additional styling for a modern look */
    .card { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .card-header { background: #007bff; color: #fff; border-top-left-radius: 10px; border-top-right-radius: 10px; }
    .alert-success { background-color: #d4edda; color: #155724; font-weight: bold; }
  </style>
</head>
<body class="bg-light">
  <!-- Header with Blue Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow mb-4">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="shop.php">
        <img src="images/logo.png" alt="OceanGas Logo" height="50" class="me-2">
        <span class="fw-bold">OceanGas Enterprise</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="shop.php">Back to Shop</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Checkout Form Container -->
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <div class="card mb-4">
          <div class="card-header text-center">
            <h2>Billing &amp; Delivery Details</h2>
          </div>
          <div class="card-body">
            <form action="place_order.php" method="POST">
              <!-- Billing Details: Pre-populated with registered info; user can edit if needed -->
              <h4 class="mb-3">Billing Details</h4>
              <div class="mb-3">
                <label for="billing_name" class="form-label">Name</label>
                <input type="text" id="billing_name" name="billing_name" class="form-control" 
                       value="<?php echo htmlspecialchars($billing_name); ?>" required>
              </div>
              <div class="mb-3">
                <label for="billing_email" class="form-label">Email</label>
                <input type="email" id="billing_email" name="billing_email" class="form-control" 
                       value="<?php echo htmlspecialchars($billing_email); ?>" required>
              </div>
              <div class="mb-3">
                <label for="billing_phone" class="form-label">Phone Number</label>
                <input type="tel" id="billing_phone" name="billing_phone" class="form-control" 
                       value="<?php echo htmlspecialchars($billing_phone); ?>" required>
              </div>
              
              <!-- Delivery Details: Only delivery address with a dropdown and optional apartment number -->
              <h4 class="mb-3">Delivery Details</h4>
              <div class="mb-3">
                <label for="delivery_address" class="form-label">Delivery Address</label>
                <select id="delivery_address" name="delivery_address" class="form-select" required>
                  <option value="">Select your county</option>
                  <option value="Nairobi">Nairobi</option>
                  <option value="Mombasa">Mombasa</option>
                  <option value="Kisumu">Kisumu</option>
                  <option value="Nakuru">Nakuru</option>
                  <option value="Eldoret">Eldoret</option>
                  <option value="Thika">Thika</option>
                  <option value="Limuru">Limuru</option>
                  <option value="Machakos">Machakos</option>
                  <option value="Naivasha">Naivasha</option>
                  <option value="Nyeri">Nyeri</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="apartmentNumber" class="form-label">Apartment/House Number (Optional)</label>
                <input type="text" id="apartmentNumber" name="apartmentNumber" class="form-control" placeholder="Enter apartment/house number">
              </div>
              
              <!-- Cart Summary -->
              <h4 class="mb-3">Cart Summary</h4>
              <div id="cart-summary" class="border rounded p-3 mb-3"></div>
              <!-- Hidden input to send cart details -->
              <input type="hidden" name="cart" id="cartData">
              
              <p class="mb-3">Delivery fee of 200/= covers up to 5km. Extra charge incurred beyond 5km. Payment required before delivery.</p>
              <div class="alert alert-success text-center" role="alert">M-PESA ONLY</div>
              
              <!-- Place Order Button -->
              <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Script to load cart summary from localStorage and set hidden cart data -->
  <script>
  // 1. Retrieve cart (expects each item to have .id, .product, .price, .quantity)
  let cart = JSON.parse(localStorage.getItem('cart')) || { items: [] };
  let cartSummary   = document.getElementById('cart-summary');
  let cartDataInput  = document.getElementById('cartData');

  // 2. Always POST the full cart JSON (including IDs)
  cartDataInput.value = JSON.stringify(cart);

  // 3. If there's something in the cart, build a grouped summary by ID
  if (cart.items.length > 0) {
    let itemMap     = {};
    let totalAmount = 0;

    cart.items.forEach(item => {
      const id       = item.id;
      const name     = item.product_name;
      const price    = parseFloat(item.price);
      const qty      = item.quantity || 1;

      if (!itemMap[id]) {
        // new entry keyed by product_id
        itemMap[id] = {
          product_id:   id,
          product_name: name,
          unitPrice:    price,
          quantity:     0,
          lineTotal:    0
        };
      }

      // accumulate quantity and line total
      itemMap[id].quantity += qty;
      itemMap[id].lineTotal  = itemMap[id].quantity * price;
    });

    // 4. Render as a Bootstrap list-group (or table if you prefer)
    let summaryHTML = '<ul class="list-group mb-3">';
    Object.values(itemMap).forEach(entry => {
      summaryHTML += `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          ${entry.quantity} × ${entry.product_name}
          <span>Ksh ${entry.unitPrice.toFixed(2)} each</span>
        </li>
      `;
      totalAmount += entry.lineTotal;
    });
    summaryHTML += `</ul>
      <h5 class="text-end">Total: Ksh ${totalAmount.toFixed(2)}</h5>`;

    cartSummary.innerHTML = summaryHTML;

  } else {
    cartSummary.innerHTML = '<p>Your cart is empty.</p>';
  }
</script>

  
  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>