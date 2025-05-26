<?php
session_start();

// Check if the cart data is available in POST. If not, set an error and redirect.
if (!isset($_POST['cart']) || empty($_POST['cart'])) {
    $_SESSION['order_error'] = "Your cart is empty or expired. Please add items to your cart.";
    header("Location: shop.php");
    exit();
}

// Ensure the customer is logged in (adjust the session key as needed)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['order_error'] = "Please log in to place an order.";
    header("Location: login.php");
    exit();
}
$cust_id = $_SESSION['user_id'];

require_once 'includes/db.php'; // This file should create and return $conn

// Generate a random order number (in production, this should come from your order processing system)
$orderNo = mt_rand(10000000, 99999999);
date_default_timezone_set('Africa/Nairobi');
$orderDate = date("Y-m-d H:i:s");

// Retrieve cart details from POST (sent as a JSON string)
$cartData = json_decode($_POST['cart'], true);
if (!isset($cartData['items']) || count($cartData['items']) === 0) {
    $_SESSION['order_error'] = "Your cart is empty. Please add items to your cart.";
    header("Location: shop.php");
    exit();
}

// Retrieve billing details from POST (provided in the checkout form)
$billing_name  = isset($_POST['billing_name'])  ? $_POST['billing_name']  : "";
$billing_email = isset($_POST['billing_email']) ? $_POST['billing_email'] : "";
$billing_phone = isset($_POST['billing_phone']) ? $_POST['billing_phone'] : "";

// Retrieve delivery details from POST (provided in the checkout form)
$delivery_address = isset($_POST['delivery_address']) ? $_POST['delivery_address'] : "";
$apartment        = isset($_POST['apartmentNumber'])  ? $_POST['apartmentNumber']  : "";

// Encode billing and delivery info as JSON strings
$billing_info = json_encode([
  "name"  => $billing_name,
  "email" => $billing_email,
  "phone" => $billing_phone
]);
$delivery_info = json_encode([
  "address"   => $delivery_address,
  "apartment" => $apartment
]);

// Compute an aggregated invoice summary from the cart items.
$aggregated = array();
foreach ($cartData['items'] as $item) {
    $product = $item['product_name'];
    $price = floatval($item['price']);
    if (isset($aggregated[$product])) {
        $aggregated[$product]['quantity'] += 1;
    } else {
        $aggregated[$product] = array(
            'quantity'  => 1,
            'unitPrice' => $price
        );
    }
}
$invoice_summary = '';
foreach ($aggregated as $product => $details) {
    if ($invoice_summary !== '') {
        $invoice_summary .= ', ';
    }
    $invoice_summary .= $details['quantity'] . ' X ' . $product;
}

// Total amount from cart data
$totalAmount = $cartData['total'];

// Insert order into orders table.
// Fetch the product ID based on the product name
$productId = null;
$productQuery = "SELECT product_id FROM products WHERE product_name = ?";
$productStmt = $conn->prepare($productQuery);
if ($productStmt) {
    $productStmt->bind_param("s", $product);
    $productStmt->execute();
    $productStmt->bind_result($productId);
    if (!$productStmt->fetch()) {
        // Handle when product is not found
        die("Product not found: " . $product);
    }
    $productStmt->close();
} else {
    die("Product query preparation failed: " . $conn->error);
}

// Insert the order with the product ID
$orderInsertQuery = "INSERT INTO orders (order_number, cust_id, order_date, billing_info, delivery_info, invoice_summary, total_amount, order_status, is_new) VALUES (?, ?, ?, ?, ?, ?, ?, 'new', 1)";
$stmt = $conn->prepare($orderInsertQuery);
if (!$stmt) {
    die("Order prepare failed: " . $conn->error);
}
$stmt->bind_param("iissssd", $orderNo, $cust_id, $orderDate, $billing_info, $delivery_info, $invoice_summary, $totalAmount);
$stmt->execute();
if ($stmt->error) {
    die("Insert execution failed: " . $stmt->error);
}
$stmt->close();

// Get the last inserted order id for inserting order items
$orderId = $conn->insert_id;

// Insert individual order items into order_items table
foreach ($cartData['items'] as $item) {
    $product = $item['product_name'];
    $price   = floatval($item['price']);
    $quantity = 1; // Adjust if your cart can have quantities >1 per entry
    $productId = null;
    $productQuery = "SELECT product_id FROM products WHERE product_name = ?";
    $productStmt = $conn->prepare($productQuery);
    if ($productStmt) {
        $productStmt->bind_param("s", $product);
        $productStmt->execute();
        $productStmt->bind_result($productId);
        $productStmt->fetch();
        $productStmt->close();
    }
    $itemInsertQuery = "INSERT INTO order_items (order_id, product_name, quantity, unit_price, product_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($itemInsertQuery);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("isidi", $orderId, $product, $quantity, $price, $productId);
    $stmt->execute();
    if ($stmt->error) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->close();
}

// Optionally clear the cart (if stored in session, etc.)
// unset($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OceanGas Order Confirmation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Prevent browser caching -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <!-- Blue Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
    <img src="Oceangas.png" alt="Logo" class="img-fluid" style="height: 50px;">

      <a class="navbar-brand" href="#">OceanGas</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="shop.php">Shop</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="text-center mb-4">
    </div>
    <div class="card shadow">
      <div class="card-body">
        <h1 class="card-title text-center mb-4">OceanGas Order Confirmation</h1>
        <p>
          Thank you for ordering with OceanGas. We are processing your order 
          (<span class="fw-bold"><?php echo $orderNo; ?></span>). All orders require payment before delivery. An invoice will be sent to you shortly via email. For assistance, please call 0712345548.
        </p>
        <div class="mb-4">
          <p><span class="fw-bold">Order Number:</span> <?php echo $orderNo; ?></p>
          <p><span class="fw-bold">Order Date:</span> <?php echo $orderDate; ?></p>
          <p>
            <span class="fw-bold">Payment Method:</span>
            <span class="badge bg-success fs-6">MPESA</span>
          </p>
          <p class="mb-0">
            <small>Paybill No. 793793 | Acc. No: OCEANGAS</small>
          </p>
        </div>
        <h4 class="mb-3">Invoice</h4>
        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th>Product</th>
              <th class="text-end">Amount (Ksh)</th>
            </tr>
          </thead>
          <tbody id="invoiceBody">
            <!-- Aggregated invoice rows will be inserted here via JavaScript -->
          </tbody>
          <tfoot>
            <tr>
              <td class="text-end fw-bold">Total:</td>
              <td class="fw-bold text-end" id="invoiceTotal">0</td>
            </tr>
          </tfoot>
        </table>
        <p class="text-muted">
          Delivery fee of 200/= covers up to 5km. Extra charge incurred beyond 5km.
        </p>
        <div class="text-center">
          <button id="backToShopBtn" class="btn btn-primary mt-3">Back to Shop</button>
        </div>
      </div>
    </div>
  </div>
  
 
  
  <!-- Bootstrap Bundle with Popper (only include once) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Invoice related code:
      let cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };
      let aggregated = {};
      cart.items.forEach(item => {
        if (aggregated[item.product_name]) {
          aggregated[item.product_name].quantity += 1;
        } else {
          aggregated[item.product_name] = { quantity: 1, unitPrice: parseFloat(item.price) };
        }
      });
      let invoiceBody = document.getElementById('invoiceBody');
      let computedTotal = 0;
      for (const [product_name, details] of Object.entries(aggregated)) {
        let row = document.createElement('tr');
        let productCell = document.createElement('td');
        productCell.textContent = details.quantity + " X " + product_name;
        let productTotal = details.quantity * details.unitPrice;
        computedTotal += productTotal;
        let priceCell = document.createElement('td');
        priceCell.className = "text-end";
        priceCell.textContent = productTotal.toFixed(2);
        row.appendChild(productCell);
        row.appendChild(priceCell);
        invoiceBody.appendChild(row);
      }
      document.getElementById('invoiceTotal').textContent = computedTotal.toFixed(2);
      localStorage.removeItem('cart');

      document.getElementById('backToShopBtn').addEventListener('click', function(){
        window.location.href = 'shop.php';
      });
      
      // Prevent going back to confirmation page via browser back button.
      history.pushState(null, null, location.href);
      window.addEventListener('popstate', function() {
        window.location.replace("shop.php");
      });
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          window.location.replace("shop.php");
        }
      });
      
      // Trigger the review modal after a 2-second delay.
      setTimeout(function() {
        var reviewModal = new bootstrap.Modal(document.getElementById("reviewModal"));
        reviewModal.show();
      }, 2000);
      
      // Attach AJAX event listener for the review form submission.
      document.getElementById("reviewForm").addEventListener("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        fetch("submit_review.php", {
          method: "POST",
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          alert(data.message);
          // Use getOrCreateInstance to safely get the modal instance and hide it.
          var reviewModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById("reviewModal"));
          reviewModalInstance.hide();
        })
        .catch(error => {
          console.error("Error:", error);
          alert("There was an error submitting your review. Please try again later.");
        });
      });
    });
  </script>
</body>
</html>
