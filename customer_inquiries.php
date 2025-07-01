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
SELECT 
  message,
  submitted_at,
  status
FROM inquiries_and_reviews
WHERE cust_id = ?
  AND message IS NOT NULL
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

$limit = 3;                                    // inquiries per page
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// 2) Get total count
$countSql  = "SELECT COUNT(*) AS cnt
              FROM inquiries_and_reviews
              WHERE cust_id = ?";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param('i', $custId);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['cnt'];
$countStmt->close();

$totalPages = (int) ceil($total / $limit);

// 3) Fetch only current page's inquiries
$inqSql = "
  SELECT message, submitted_at, status
  FROM inquiries_and_reviews
  WHERE cust_id = ?
  AND message IS NOT NULL
  ORDER BY submitted_at DESC
  LIMIT ? OFFSET ?
";
$inqStmt = $conn->prepare($inqSql);
$inqStmt->bind_param('iii', $custId, $limit, $offset);
$inqStmt->execute();
$inquiries = $inqStmt->get_result();
$inqStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Inquiries</title>
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
    .table-container {
      overflow-x: auto;
      background: #fff;
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

   <!-- Replace your existing Main Content with: -->
<main class="flex-1 p-8 bg-gray-50">
  <header class="mb-8 flex justify-between items-center">
    <h2 class="text-2xl font-bold text-gray-800">Inquiries</h2>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Make an Inquiry -->
    <section class="bg-white rounded-2xl shadow-sm p-6">
      <h3 class="text-xl font-semibold mb-4">Submit Your Inquiry</h3>
      <form action="" method="POST" class="space-y-4">
        <textarea
          name="message"
          rows="5"
          required
          class="w-full p-4 border border-gray-300 rounded-xl resize-none 
                 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
          placeholder="Enter your message here…"></textarea>
        <button
          type="submit"
          class="inline-block bg-blue-600 text-white font-medium 
                 px-6 py-2 rounded-xl shadow hover:bg-blue-700 
                 focus:ring-2 focus:ring-blue-400 transition">
          Submit Inquiry
        </button>
      </form>
    </section>

    <!-- My Inquiries Table -->
    <section class="bg-white rounded-2xl shadow-sm p-6 overflow-x-auto">
      <h3 class="text-xl font-semibold mb-4">My Inquiries</h3>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100 rounded-t-xl">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Message</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Submitted At</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php if ($inquiries->num_rows): ?>
            <?php while ($row = $inquiries->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($row['submitted_at']); ?></td>
                <td class="px-4 py-3 text-sm">
                  <span class="inline-block px-3 py-1 rounded-full 
                    <?php echo $row['status']=='Open' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-gray-200 text-gray-600'; ?>">
                    <?php echo htmlspecialchars($row['status']); ?>
                  </span>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                You have not submitted any inquiries yet.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      <?php if ($totalPages > 1): ?>
  <nav class="mt-4 flex justify-center space-x-2">
    <!-- Previous link -->
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page-1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
    <?php else: ?>
      <span class="px-3 py-1 text-gray-400 bg-gray-100 rounded">Previous</span>
    <?php endif; ?>

    <!-- Page number links -->
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <?php if ($p === $page): ?>
        <span class="px-3 py-1 bg-blue-600 text-white rounded"><?= $p ?></span>
      <?php else: ?>
        <a href="?page=<?= $p ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <!-- Next link -->
    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page+1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
    <?php else: ?>
      <span class="px-3 py-1 text-gray-400 bg-gray-100 rounded">Next</span>
    <?php endif; ?>
  </nav>
<?php endif; ?>

    </section>
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