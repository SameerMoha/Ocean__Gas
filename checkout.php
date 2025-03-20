<?php
session_start();
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
</head>
<body class="bg-light">
  <!-- Header with Blue Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow mb-4">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="shop.php">
        <img src="images/logo.png" alt="OceanGas Logo" height="50" class="me-2">
        <span class="fw-bold">OceanGas Enterprise</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
        <div class="card shadow mb-4">
          <div class="card-body">
            <h2 class="card-title text-center mb-4">Billing &amp; Delivery Details</h2>
            <form action="place_order.php" method="POST" id="checkoutForm">
              <!-- Billing Details -->
              <h4 class="mb-3">Billing Details</h4>
              <div class="mb-3">
                <label for="firstName" class="form-label">First Name</label>
                <input type="text" id="firstName" name="firstName" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="lastName" class="form-label">Last Name</label>
                <input type="text" id="lastName" name="lastName" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="phoneNumber" class="form-label">Phone Number</label>
                <input type="tel" id="phoneNumber" name="phoneNumber" class="form-control" required>
              </div>
              <!-- Delivery Details -->
              <h4 class="mb-3">Delivery Details</h4>
              <div class="mb-3">
                <label for="deliveryLocation" class="form-label">Delivery Location</label>
                <input type="text" id="deliveryLocation" name="deliveryLocation" list="kenyaLocations" class="form-control" required>
                <datalist id="kenyaLocations">
                  <option value="Nairobi">
                  <option value="Mombasa">
                  <option value="Kisumu">
                  <option value="Nakuru">
                  <option value="Eldoret">
                  <option value="Thika">
                  <option value="Limuru">
                  <option value="Machakos">
                  <option value="Naivasha">
                  <option value="Nyeri">
                </datalist>
              </div>
              <div class="mb-3">
                <label for="apartmentNumber" class="form-label">Apartment/House Number (Optional)</label>
                <input type="text" id="apartmentNumber" name="apartmentNumber" class="form-control">
              </div>
              <!-- Hidden input to store cart data -->
              <input type="hidden" name="cartData" id="cartData">
              <!-- Cart Summary -->
              <h4 class="mb-3">Cart Summary</h4>
              <div id="cart-summary" class="border rounded p-3 mb-3"></div>
              <!-- Additional Info -->
              <p class="mb-3">
                Delivery fee of 200/= covers up to 5km. Extra charge incurred beyond 5km. Payment required before delivery.
              </p>
              <div class="alert alert-success text-center fw-bold" role="alert">
                M-PESA ONLY
              </div>
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

  <!-- Script to load cart summary from localStorage and attach cart data to the form -->
  <script>
    // Retrieve the cart details from localStorage.
    let cart = JSON.parse(localStorage.getItem('cart')) || { items: [] };

    // Display a summary of the cart.
    let cartSummary = document.getElementById('cart-summary');

    if (cart.items.length > 0) {
      let itemMap = {};
      let totalAmount = 0;

      // Group items by product name
      cart.items.forEach(item => {
        if (itemMap[item.product]) {
          itemMap[item.product].quantity += 1;
        } else {
          itemMap[item.product] = {
            quantity: 1,
            unitPrice: item.price
          };
        }
      });

      let summaryHTML = '<ul class="list-group mb-3">';
      for (let product in itemMap) {
        let quantity = itemMap[product].quantity;
        let unitPrice = itemMap[product].unitPrice;
        let totalPrice = quantity * unitPrice;
        totalAmount += totalPrice;

        summaryHTML += `<li class="list-group-item d-flex justify-content-between align-items-center">
                          ${quantity} x ${product}
                          <span>Ksh ${unitPrice} each</span>
                        </li>`;
      }
      summaryHTML += `</ul>
                      <h5 class="text-end">Total: Ksh ${totalAmount}</h5>`;
      cartSummary.innerHTML = summaryHTML;
    } else {
      cartSummary.innerHTML = '<p>Your cart is empty.</p>';
    }

    // Attach the cart JSON to the hidden input before form submission.
    const checkoutForm = document.getElementById('checkoutForm');
    checkoutForm.addEventListener('submit', function(event) {
      document.getElementById('cartData').value = JSON.stringify(cart);
    });
  </script>
  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
