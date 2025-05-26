
<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = $_POST["order_id"];
    $delivery_date = $_POST["delivery_date"];
    $assigned_to = $_POST["assigned_to"];
    $notes = $_POST["notes"];

    // Prevent duplicate deliveries
    $check = $conn->prepare("SELECT * FROM deliveries WHERE order_id = ?");
    $check->bind_param("i", $order_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('A delivery for this order already exists.'); window.location.href='add_delivery.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO deliveries (order_id, assigned_to, delivery_date, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $order_id, $assigned_to, $delivery_date, $notes);
    $stmt->execute();

session_start();

// make sure they’re logged in…
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role'])) {
    header("Location: staff_login.php");
    exit;
}

// now choose where to send them
if ($_SESSION['staff_role'] === 'sales') {
    header("Location: view_delivery_sales.php");
    exit;
}

// for admins (or any other roles) fall back to the generic page
header("Location: view_deliveries.php");    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            background: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .sidebar {
            width: 250px;
            background: #6a008a;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        .sidebar a.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        .dropdown-btn, .dropdown-btn {
            padding: 10px;
            width: 100%;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 16px;
            color: white;
        }
        .dropdown-container {
            display: none;
            background-color:#6a008a;
            padding-left: 20px;
        }
        .dropdown-btn.active + .dropdown-container {
            display: block;
        }
        .content {
            margin-left: 270px;
            padding: 30px;
            flex-grow: 1;
        }
    </style>
</head>
<body>
<nav class="sidebar"> 
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/OceanGas/staff/stock_admin.php"><i class="fas fa-box"></i> Stock/Inventory</a>
    <a href="/OceanGas/staff/users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="/OceanGas/staff/finance.php"><i class="fas fa-dollar-sign"></i> Finance</a>
<div class="dropdown">
    <button class="dropdown-btn">
  <i class="fas fa-truck"></i>
  <span>Deliveries</span>
  <i class="fas fa-caret-down ms-auto"></i>
</button>
<div class="dropdown-container">
  <a href="/OceanGas/staff/add_delivery.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame"
    class="active">Add Delivery
 </a>
  <a href="view_deliveries.php">View Deliveries</a>
</div>

            </div>
    <div class="dropdown">
    <button class="dropdown-btn" id="btnProcurement">
        <i class="fas fa-truck"></i> Procurement <i class="fas fa-caret-down"></i>
    </button>
        <div class="dropdown-container">

<a href="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Dashboard
</a>

<a href="/OceanGas/staff/purchase_history_reports.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Purchase History
</a>

<a href="/OceanGas/staff/suppliers.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
    target="main-frame">Suppliers
</a>

  <a href="/OceanGas/staff/financial_overview.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame">Financial Overview </a>

        </div>
    </div>
    <div class="dropdown">
        <button class="dropdown-btn">
            <i class="fas fa-shopping-cart"></i> Sales <i class="fas fa-caret-down"></i>
        </button>
        <div class="dropdown-container">
<a href="/OceanGas/staff/sales_staff_dashboard.php?embedded=1"
  onclick="
    document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame"> Dashboard
</a>            

<a href="/OceanGas/staff/sales_invoice.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame"> Sales Invoice 
</a>

 <a href="/OceanGas/staff/reports.php?embedded=1" onclick=" document.getElementById('mainContent').style.display = 'none';
    var f = document.getElementById('mainFrame');
    f.src = this.href;
    f.style.display = 'block';
    return false;"
  target="main-frame">Reports 
</a>

        </div>
    </div>
        </div>
    </nav>
    <div class="content" style=" padding: 20px; width: calc(100% - 250px);">
<iframe 
  id="mainFrame"
  name="main-frame"
  src="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1"
  style="display:none; width:100%; height:1500px; border:none;"
></iframe>
<div id="mainContent">
    <h2 class="mb-4">Add Delivery</h2>

<?php
$confirmedOrders = [];
$query = "SELECT order_id FROM orders WHERE order_status = 'confirmed' AND order_id NOT IN (SELECT order_id FROM deliveries)";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $confirmedOrders[] = $row['order_id'];
}
?>

<form method="POST">
        <div class="mb-3">
            

<label for="order_id" class="form-label">Order ID</label>
<select name="order_id" class="form-control" required>
    <option value="">-- Select Confirmed Order --</option>
    <?php foreach ($confirmedOrders as $orderId): ?>
        <option value="<?= $orderId ?>"><?= $orderId ?></option>
    <?php endforeach; ?>
</select>


        </div>
        <div class="mb-3">
            <label for="assigned_to" class="form-label">Assign To:</label>
<select class="form-control" name="assigned_to" required>
    <option value="">-- Select Driver --</option>
    <option value="Kevin">Kevin</option>
    <option value="Joe">Joe</option>
    <option value="Sarah">Sarah</option>
    <option value="John">John</option>
    <option value="Yusra">Yusra</option>
</select>
        </div>
        <div class="mb-3">
            <label for="delivery_date" class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" id="delivery_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" id="notes" class="form-control"></textarea>
        </div>
       
        <button type="submit" class="btn btn-primary">Add Delivery</button>
    </form>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var dropdowns = document.getElementsByClassName("dropdown-btn");
  
  for (var i = 0; i < dropdowns.length; i++) {
    dropdowns[i].addEventListener("click", function() {
      this.classList.toggle("active");
      var dropdownContent = this.nextElementSibling;
      if (dropdownContent.style.display === "block") {
        dropdownContent.style.display = "none";
      } else {
        dropdownContent.style.display = "block";
      }
    });
  }

  // Auto-open Deliveries dropdown (assumes it's the first one)
  if (dropdowns.length > 0) {
    dropdowns[0].click();
  }
});

  function loadFrame(url) {
    const frame = document.getElementById('mainFrame');
    // only set src once, so subsequent shows don’t reload
    if (frame.src !== url) {
      frame.src = url;
    }
    // toggle visibility (you can also just always show: frame.style.display = 'block')
    frame.style.display = 'block';
  }

  // Optional: delegate to _all_ links with data‐target="main-frame":
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[data-target="main-frame"]').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        loadFrame(link.href);
      });
    });
  });

</script>
</body>
</html>
