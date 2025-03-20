<?php
session_start();

// Database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'oceangas';

// Create connection using mysqli
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate a random order number (in production, consider a more robust method)
$orderNo = mt_rand(10000000, 99999999);

// Retrieve billing details from POST data and sanitize
$firstName = isset($_POST['firstName']) ? $conn->real_escape_string(trim($_POST['firstName'])) : '';
$lastName = isset($_POST['lastName']) ? $conn->real_escape_string(trim($_POST['lastName'])) : '';
$phoneNumber = isset($_POST['phoneNumber']) ? $conn->real_escape_string(trim($_POST['phoneNumber'])) : '';
$deliveryLocation = isset($_POST['deliveryLocation']) ? $conn->real_escape_string(trim($_POST['deliveryLocation'])) : '';
$apartmentNumber = isset($_POST['apartmentNumber']) ? $conn->real_escape_string(trim($_POST['apartmentNumber'])) : '';

// Retrieve the cart data (JSON string) passed from checkout page
$cartData = isset($_POST['cartData']) ? $_POST['cartData'] : '{}';
$cart = json_decode($cartData, true);

// Calculate total amount from the cart items
$totalAmount = 0;
if (isset($cart['items']) && is_array($cart['items'])) {
    foreach ($cart['items'] as $item) {
        $totalAmount += $item['price'];
    }
}

// Insert the order into the orders table
$session_id = session_id();
$sql = "INSERT INTO orders (order_no, session_id, first_name, last_name, phone_number, delivery_location, apartment_number, cart_summary, total_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param(
    "isssssssi",
    $orderNo,
    $session_id,
    $firstName,
    $lastName,
    $phoneNumber,
    $deliveryLocation,
    $apartmentNumber,
    $cartData,
    $totalAmount
);
$stmt->execute();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OceanGas Order Confirmation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <!-- Blue Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
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
      <img src="Oceangas.png" alt="Logo" class="img-fluid" style="max-height: 100px;">
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
          <p><span class="fw-bold">Order Date:</span> <?php echo date("Y-m-d H:i:s"); ?></p>
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
  
  <!-- JavaScript to aggregate cart items and update invoice -->
  <script>
    // Retrieve cart details from localStorage (stored in checkout page)
    let cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };

    // Aggregate items by product name.
    let aggregated = {};
    cart.items.forEach(item => {
      if (aggregated[item.product]) {
        aggregated[item.product].quantity += 1;
      } else {
        aggregated[item.product] = {
          quantity: 1,
          unitPrice: parseFloat(item.price)
        };
      }
    });

    let invoiceBody = document.getElementById('invoiceBody');
    let computedTotal = 0;
    
    for (const [product, details] of Object.entries(aggregated)) {
      let row = document.createElement('tr');
      
      let productCell = document.createElement('td');
      productCell.textContent = details.quantity + " X " + product;
      
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

    document.getElementById('backToShopBtn').addEventListener('click', function(){
      window.location.href = 'shop.php';
    });
  </script>
  
  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
