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
    return $currentPage === $page ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-blue-700 hover:bg-blue-50';
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

// Fetch account details including profile image
$acctSql = "SELECT F_name, L_name, Email, Phone_number, profile_image FROM customers WHERE cust_id = ?";
$acctStmt = $conn->prepare($acctSql);
if (!$acctStmt) die("Account query failed: " . $conn->error);
$acctStmt->bind_param('i', $custId);
if (!$acctStmt->execute()) die("Account execution failed: " . $acctStmt->error);
$acct = $acctStmt->get_result()->fetch_assoc();
$acctStmt->close();

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

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product       = $conn->real_escape_string($_POST['product']);
    $rating        = (int)$_POST['rating'];
    $reviewComment = $conn->real_escape_string($_POST['review_comment']);
    $firstName     = $conn->real_escape_string($acct['F_name']);
    $lastName      = $conn->real_escape_string($acct['L_name']);
    $email         = $conn->real_escape_string($acct['Email']);
    
    $insertSql = "
      INSERT INTO inquiries_and_reviews
        (name, email, cust_id, product, rating, review_comment, review_date)
      VALUES
        (?, ?, ?, ?, ?, ?, NOW())
    ";
    $stmt = $conn->prepare($insertSql);
    $fullName = $firstName . ' ' . $lastName;
    $stmt->bind_param(
      'ssisis',
      $fullName,
      $email,
      $custId,
      $product,
      $rating,
      $reviewComment,
      // use an int for review_date? MySQL will accept NOW()
      );
    if ($stmt->execute()) {
        $successMsg = "Thanks! Your review has been submitted.";
    } else {
        $errorMsg = "Something went wrong: " . $stmt->error;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Reviews</title>
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
       .navbar .profile-text{
      font-size: 1.2rem;
      color: white;
      margin-right: 10px;
    }
        .table-container {
      overflow-x: auto;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      border-radius: 20px;
      padding: 2px;
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
    .star-rating {
  direction: rtl;               /* reverse for easier CSS selectors */
  font-size: 2rem;             /* adjust star size here */
  display: inline-flex;
  position: relative;
}

.star-rating input {
  display: none;               /* hide the radio buttons */
}

.star-rating label {
  color: #ccc;                 /* base (empty) star color */
  cursor: pointer;
  transition: transform .2s ease, color .2s ease;
}

.star-rating label:hover,
.star-rating label:hover ~ label {
  color: #ffdd57;              /* hover color */
  transform: scale(1.2);       /* slight pop on hover */
}

.star-rating input:checked ~ label,
.star-rating input:checked ~ label ~ label {
  color: #ffc107;              /* selected color */
}

.star-rating label.selected {
  animation: bounce 0.4s ease;
}

/* Bounce animation on selection */
@keyframes bounce {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.4); }
  100% { transform: scale(1); }
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

  </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">
  <!-- Navbar -->
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
        <h2 class="text-2xl font-semibold">Rate a product</h2>
      </header>

<main class="col-md-9 content">
      <div class="row card-section">
   

      <?php if (!empty($successMsg)): ?>
    <div class="alert alert-success"><?= $successMsg ?></div>
  <?php elseif (!empty($errorMsg)): ?>
    <div class="alert alert-danger"><?= $errorMsg ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <!-- Product selector: you can populate this from your product table or past orders -->
    <div class="mb-3">
      <label for="product" class="form-label">Product</label>
      <select name="product" id="product" class="form-select" required>
        <option value="">-- Select a product --</option>
        <?php
          $prodRes = $conn->query("SELECT product_id, product_name FROM products");
          while ($row = $prodRes->fetch_assoc()) {
            echo "<option value=\"{$row['product_name']}\">"
               . htmlspecialchars($row['product_name']) .
                 "</option>";
          }
        ?>
      </select>
    </div>

    <!-- Star rating input -->
    <div class="mb-3">
  <label class="form-label">Rating</label>
  <div class="star-rating">
    <?php for ($i = 5; $i >= 1; $i--): ?>
      <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
      <label for="star<?= $i ?>" title="<?= $i ?> stars">
        <i class="fas fa-star"></i>
      </label>
    <?php endfor; ?>
  </div>
</div>


    <!-- Review comment -->
    <div class="mb-3">
      <label for="review_comment" class="form-label">Comment</label>
      <textarea name="review_comment" id="review_comment" class="form-control" rows="4" required></textarea>
    </div>

    <button type="submit" name="submit_review" class="btn btn-primary btn-sm active">
      Submit Review
    </button>
  </form>
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
    
    document.querySelectorAll('.star-rating input').forEach(radio => {
    radio.addEventListener('change', e => {
      // Remove any existing 'selected' class
      document.querySelectorAll('.star-rating label').forEach(lbl => {
        lbl.classList.remove('selected');
      });
      // Find the label for the checked input and add bounce
      const selectedLabel = document.querySelector(`label[for="${e.target.id}"]`);
      selectedLabel.classList.add('selected');
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