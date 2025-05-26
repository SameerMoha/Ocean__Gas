<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();

// Check if the staff user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Database connection details
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch admin details from the "users" table
$adminUsername = $_SESSION['staff_username'];
$adminSql = "SELECT username, email FROM users WHERE username = ? AND role = 'support' LIMIT 1";

$stmt = $conn->prepare($adminSql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $adminUsername);
$stmt->execute();
$result = $stmt->get_result();

$adminName = "";
$adminEmail = "";
if ($row = $result->fetch_assoc()) {
    $adminName = $row['username'];
    $adminEmail = $row['email'];
} else {
    die("No admin found for username: " . htmlspecialchars($adminUsername));
}

$sql = "
  SELECT *
    FROM inquiries_and_reviews
     WHERE rating IS NOT NULL 
ORDER BY review_date DESC
";
$anonResult   = $conn->query($sql);
$reviews    = $anonResult->fetch_all(MYSQLI_ASSOC);


$conn->close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Reviews</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
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
        .content {
            margin-left: 260px;
            padding: 20px;
            flex-grow: 1;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .topbar-icons {
            display: flex;
            gap: 15px;
        }
        .topbar-icons i {
            font-size: 20px;
            cursor: pointer;
        }
        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .chart-card {
            margin-bottom: 20px;
        }
        /* Ensure pie chart is visible */
        #pieChart {
            width: 100% !important;
            height: 300px !important;
        }

        /* OPTIONAL: Slight styling to match your screenshot’s profile dropdown */
        .dropdown-menu li .dropdown-item i {
            margin-right: 8px;
        }
        .dropdown-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 5px;
        }
        
.profile-btn {
  background-color: #6f42c1 !important; /* Bootstrap purple or your preferred color */
  color: black !important;
  border: none;
  border-radius: 0.375rem; /* optional rounded corners */
  padding: 8px 12px;
}
.sidebar .dropdown-menu {
    background-color: #6a008a;
    border: none;
}
.sidebar .dropdown-menu .dropdown-item {
    color: black;
}
.sidebar .dropdown-menu .dropdown-item:hover {
    background-color: rgba(255,255,255,0.2);
}
table-container {
      overflow-x: auto;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Support Panel</h2>
        <a href="/OceanGas/staff/support_dashboard.php" class="<?php echo ($current_page === 'support_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="/OceanGas/staff/support_inquiries.php" class="<?php echo ($current_page === 'support_inquiries.php') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Inquiries</a>
        <a href="/OceanGas/staff/support_reviews.php" class="<?php echo ($current_page === 'support_reviews.php') ? 'active' :''; ?>"><i class="fas fa-star"></i> Product reviews</a>

    </div>
    
      <main class="col-md-9 content">
      <h1>Reviews</h1>

<?php if (empty($reviews)): ?>
  <p>No Reviews found.</p>
<?php else: ?>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Rating</th>
          <th>Review Comment</th><th>Product</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reviews as $rev): ?>
          <tr>
            <td><?= htmlspecialchars($rev['name']) ?></td>
            <td><?= htmlspecialchars($rev['email']) ?></td>
            <td><?= htmlspecialchars($rev['rating']) ?></td>
            <td><?= htmlspecialchars($rev['review_comment']) ?></td>
            <td><?= htmlspecialchars($rev['product']) ?></td>
            <td><?= htmlspecialchars(string: $rev['review_date']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
      </main>
      <?php endif; ?>

    </div>
  </div>

       
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
       
    </script>
</body>
</html>
