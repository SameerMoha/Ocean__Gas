<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);

$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check that a staff user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Fetch users from the database, include is_active
$sql = "SELECT id, username, email, role, is_active FROM users";
$result = $conn->query($sql);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_role'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['role'];
        $updateSql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $newRole, $userId);
        $stmt->execute();
        $stmt->close();
        header("Location: /OceanGas/staff/users.php");
        exit();
    }

    if (isset($_POST['add_user'])) {
        $newUsername = $_POST['new_username'];
        $newEmail = $_POST['new_email'];
        $newRole = $_POST['new_role'];
        $password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $insertSql = "INSERT INTO users (username, email, role, password, is_active) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("ssss", $newUsername, $newEmail, $newRole, $password);
        $stmt->execute();
        $stmt->close();
        header("Location: /OceanGas/staff/users.php");
        exit();
    }

    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        $deleteSql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        header("Location: /OceanGas/staff/users.php");
        exit();
    }

    if (isset($_POST['toggle_active'])) {
        $userId = $_POST['user_id'];
        $currentStatus = (int)$_POST['current_status'];
        $newStatus = $currentStatus ? 0 : 1;
        $toggleSql = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($toggleSql);
        $stmt->bind_param("ii", $newStatus, $userId);
        $stmt->execute();
        $stmt->close();
        header("Location: /OceanGas/staff/users.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
     body { font-family: 'Arial',sans-serif; background: #f8f9fa; margin:0; display:flex; }
    .sidebar { width:260px; background:#6a008a; color:#fff; min-height:100vh; padding:20px; position:sticky; top:0; left:0;  }
    .sidebar h2 { font-size:32px; margin-top:0; }
    .sidebar a { color:#fff; text-decoration:none; padding:10px; display:block; margin:5px 0; }
    .sidebar a:hover { background:rgba(255,255,255,0.2); border-radius:5px; }
    .sidebar a.active { background:rgba(255,255,255,0.3); font-weight:bold; }
    .main-content { margin-left:240px; width:calc(100% - 240px); padding:20px; }
    .sidebar .dropdown-menu .dropdown-item {
    color: black;
}
.sidebar .dropdown-menu .dropdown-item:hover {
    background-color: rgba(255,255,255,0.2);
}/* Style for dropdown button */
.dropdown-btn, .dropdown-btn2 {
    padding: 10px;
    width: 100%;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    font-size: 16px;
    color: white;
}

/* Container hidden by default */
.dropdown-container {
    display: none;
    background-color:#6a008a;
    padding-left: 20px;
}

/* Show container when active */
.dropdown-btn.active, .dropdown-btn2.active + .dropdown-container {
    display: block;
    
}

/* Optional: hover effect */
.dropdown-container a {
    color: white;
    padding: 8px 0;
    display: block;
    text-decoration: none;
}

.dropdown-container a:hover {
    background-color:rgba(255,255,255,0.2);
}
.content {
            padding: 20px;
            flex-grow: 1;
            min-height: calc(100%-250px);
        }
    </style>
</head>
<body>

<nav class="sidebar">
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php" class="<?=($current_page==='admin_dashboard.php')?'active':''?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/OceanGas/staff/stock_admin.php" class="<?=($current_page==='stock_admin.php')?'active':''?>"><i class="fas fa-box"></i> Stock/Inventory</a>
    <a href="/OceanGas/staff/users.php" class="<?=($current_page==='users.php')?'active':''?>"><i class="fas fa-users" ></i> Manage Users</a>
    <a href="/OceanGas/staff/finance.php" class="<?=($current_page==='finance.php')?'active':''?>"><i class="fas fa-dollar"></i> Finance</a>
        <div class="dropdown">
    <button class="dropdown-btn">
  <i class="fas fa-truck"></i>
  <span>Deliveries</span>
  <i class="fas fa-caret-down ms-auto"></i>
</button>
<div class="dropdown-container">
  <a href="add_delivery.php">Add Delivery</a>
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
  </nav>
  <div class="content" style="margin-left: 1px; padding: 10px; width: calc(100% - 250px);">
<iframe 
  id="mainFrame"
  name="main-frame"
  src="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1"
  style="display:none; width:100%; height:762px; border:none;"
></iframe>
<div class="container mt-5 me-5">

<div id="mainContent">
    
    <h2>Manage Users</h2>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Status</th>
            <th>Role</th>
            <th>Update Role</th>
            <th>Activation</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <?php if ($row['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" class="d-flex">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <select name="role" class="form-select me-2">
                            <option value="admin" <?php if ($row['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                            <option value="user" <?php if ($row['role'] == 'user') echo 'selected'; ?>>User</option>
                            <option value="procurement" <?php if ($row['role'] == 'procurement') echo 'selected'; ?>>Procurement</option>
                            <option value="sales" <?php if ($row['role'] == 'sales') echo 'selected'; ?>>Sales</option>
                            <option value="support" <?php if ($row['role'] == 'support') echo 'selected'; ?>>Support</option>
                        </select>
                        <button type="submit" name="update_role" class="btn btn-primary">Update</button>
                    </form>
                </td>
                <td>
                    <form method="post">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                    </form>
                </td>
                <td>
                    <form method="post">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="current_status" value="<?php echo $row['is_active']; ?>">
                        <?php if ($row['is_active']): ?>
                            <button type="submit" name="toggle_active" class="btn btn-warning">Deactivate</button>
                        <?php else: ?>
                            <button type="submit" name="toggle_active" class="btn btn-success">Activate</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h3>Add New User</h3>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="new_username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="new_email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Role</label>
            <select name="new_role" class="form-select">
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="procurement">Procurement</option>
                <option value="sales">Sales</option>
                <option value="support">Support</option>
            </select>
        </div>
        <button type="submit" name="add_user" class="btn btn-success">Add User</button>
    </form>
</div>
</div>
<script>
           // Simple toggle script for dropdown
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dropdown-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.classList.toggle('active');
        const container = btn.nextElementSibling;
        container.style.display = container.style.display === 'block'
          ? 'none'
          : 'block';
      });
    });
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
