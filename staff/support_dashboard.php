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
$stmt->close();

// Query for Inquiries
$result = $conn->query("SELECT COUNT(*) AS total_inquiries FROM inquiries_and_reviews WHERE (rating IS NULL) ");
if (!$result) {
    die("Total inquiries query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_inquiries = $row['total_inquiries'];

$result = $conn->query("SELECT COUNT(*) AS total_reviews FROM inquiries_and_reviews WHERE (rating IS NOT NULL) ");
if (!$result) {
    die("Total reviews query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_reviews = $row['total_reviews'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Dashboard</title>
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

    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Support Panel</h2>
        <a href="/OceanGas/staff/support_dashboard.php" class="<?php echo ($current_page === 'support_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="/OceanGas/staff/support_inquiries.php" class="<?php echo ($current_page ==='support_inquiries.php') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Inquiries</a>
        <a href="/OceanGas/staff/support_reviews.php" class="<?php echo ($current_page === 'support_reviews') ? 'actie' :''; ?>"><i class="fas fa-star"></i> Product reviews</a>

    </div>
    <div class="content">
        
        <!-- Topbar with Profile Dropdown -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <h1>Welcome <?php echo htmlspecialchars($adminName); ?></h1>
            
            <div class="d-flex align-items-center">
                <!-- (Optional) Envelope & Bell icons -->
                <i class="fas fa-envelope mx-2"></i>
                <i class="fas fa-bell mx-2"></i>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    
                    <button class="btn btn-secondary dropdown-toggle" type="button"
        id="profileDropdown"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        style="display: flex; align-items: center;  background-color: white; border: none; color: black;">
    <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png"
         alt="Profile Picture"
         width="23px"
         height="23px"
         style="border-radius: 50%;">
</button>

                    <!-- The actual dropdown menu -->
                    <ul class="dropdown-menu dropdown-menu-end"  >
                        <!-- Header with image, name, email (like in your screenshot) -->
                        <li class="dropdown-header text-center">
                            <!-- Example avatar. Use a real image or your own placeholder -->
                            <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png" alt="Profile Picture">
                            <div>
                                <strong><?php echo htmlspecialchars($adminName); ?></strong><br>
                                <small><?php echo htmlspecialchars($adminEmail); ?></small>
                            </div>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <!-- Profile link -->
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class=""></i> Profile
                            </a>
                        </li>

                        <!-- Dashboard link -->
                        <li>
                            <a class="dropdown-item" href="/OceanGas/staff/admin_dashboard.php">
                                <i class=""></i> Dashboard
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <!-- Sign Out link -->
                        <li>
                            <a class="dropdown-item text-danger" href="/OceanGas/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
    </div>
       
        <div class="row my-4">
            <div class="col-md-3">
            <a href="support_inquiries.php" style="text-decoration: none;">
                <div class="card p-3" style="text-align: center;">
                    <h5>Total Inquiries</h5>
                    <p class="display-6"><?php echo number_format($total_inquiries); ?></p>
                </div>
                </a>
            </div>
            
            <div class="col-md-3">
            <a href="support_reviews.php" style="text-decoration: none;">
                <div class="card p-3" style="text-align: center;">
                    <h5>Total Reviews</h5>
                    <p class="display-6"><?php echo number_format($total_reviews); ?></p>
                </div>
            </a>
            </div>
        
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
       
    </script>
</body>
</html>
