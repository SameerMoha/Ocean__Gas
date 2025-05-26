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
$adminSql = "SELECT username, email FROM users WHERE username = ? AND role = 'admin' LIMIT 1";

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
    // Handle case where admin user is not found or role is incorrect
    // Log out the user or redirect to an error page
    session_unset();
    session_destroy();
    header("Location: /OceanGas/staff/staff_login.php?error=invalid_user");
    exit();
}
$stmt->close();

// Query for Total Users
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
if (!$result) {
    die("Total Users query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_users = $row['total_users'];

// Query for Pending Requests (both sales and procurement)
$result = $conn->query("SELECT COUNT(*) AS pending_sales FROM orders WHERE order_status = 'new'");
if (!$result) {
    die("Pending Sales query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$pending_sales = $row['pending_sales'];

$result = $conn->query("SELECT COUNT(*) AS pending_procurements FROM purchase_history WHERE status = 'pending'");
if (!$result) {
    die("Pending Procurements query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$pending_procurements = $row['pending_procurements'];

$pending_requests = $pending_sales + $pending_procurements;

// Query for Total Revenue (from sales_record)
$result = $conn->query("SELECT IFNULL(SUM(total_amount), 0) AS total_revenue FROM sales_record");
if (!$result) {
    die("Revenue query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_revenue = $row['total_revenue'];

// Query for Total Expense (from funds_deductions - assuming this is for general expenses)
$result = $conn->query("SELECT IFNULL(SUM(amount), 0) AS total_expense FROM funds_deductions");
if (!$result) {
    die("Expense query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_expense = $row['total_expense'];

// --- Total Sales Amount (for pie chart) ---
// This seems to be the same as total_revenue based on your variable assignment
$total_sales_amount = $total_revenue;

// --- Total Purchases Amount (for pie chart) ---
// CORRECTED SQL: Join purchase_history -> products -> price to get unit cost
$sql = "
    SELECT
        IFNULL(SUM(pr.buying_price * ph.quantity), 0) AS total_purchases_amount
    FROM purchase_history ph
    JOIN products p
        ON ph.product = p.product_name
    JOIN price pr
        ON pr.product_id = p.product_id AND pr.supplier_id = ph.supplier_id
";
$result = $conn->query($sql);
if (!$result) {
    die("Total Purchases Amount query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$total_purchases_amount = $row['total_purchases_amount'];


// --- 3 Most Recent Purchases ---
// CORRECTED SQL: Using the same logic for recent purchases
$purchases_sql = "
    SELECT
        ph.purchase_date,
        ph.product,
        ph.quantity,
        s.name AS supplier,
        (pr.buying_price * ph.quantity) AS total_cost
    FROM purchase_history ph
    JOIN suppliers s
        ON ph.supplier_id = s.id
    JOIN users u
        ON ph.purchased_by = u.id
    JOIN products p
        ON ph.product = p.product_name
    JOIN price pr
        ON pr.product_id = p.product_id AND pr.supplier_id = ph.supplier_id
    ORDER BY ph.purchase_date DESC
    LIMIT 3
";
$result = $conn->query($purchases_sql);
if (!$result) {
    die("Recent Purchases query failed: " . $conn->error);
}
$recent_purchases = [];
while ($row = $result->fetch_assoc()) {
    $recent_purchases[] = $row;
}


// Query for 2 most recent sales
$sales_sql = "SELECT sale_date, product_name, quantity, total_amount
                     FROM sales_record
                     ORDER BY sale_date DESC LIMIT 3"; // Changed LIMIT to 3 to match purchases
$result = $conn->query($sales_sql);
if (!$result) {
    die("Recent Sales query failed: " . $conn->error);
}
$recent_sales = [];
while($row = $result->fetch_assoc()){
    $recent_sales[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS Variables */
        :root {
            --primary-purple: #6a008a;
            --light-purple: #E3DAFF;
            --dark-purple: #2A2656;
            --hover-effect: rgba(255,255,255,0.1);
        }

        /* Body Styling */
        body {
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif; /* Added Poppins font, ensure it's linked or available */
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
            display: flex; /* Use flexbox for main layout */
        }

        /* Content Area - Adjusted margin for sidebar */
        .content {
            margin-left: 280px; /* Default margin for desktop with sidebar */
            transition: margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 20px;
            flex-grow: 1; /* Allow content to grow and fill space */
            width: calc(100% - 280px); /* Calculate width based on sidebar */
        }

        /* Responsive adjustment for content margin when sidebar is hidden on small screens */
        /* This media query should match the one in your sidebar.php that hides the sidebar */
        @media (max-width: 768px) {
            .content {
                margin-left: 0; /* Content takes full width when sidebar is hidden */
                width: 100%; /* Content takes full width */
            }
        }

        /* Navbar (Top Bar) - Keep styling specific to the top bar */
        .navbar {
            padding: 15px 20px;
            border-radius: 8px; /* Added for softer look */
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Lighter shadow */
            margin-bottom: 20px; /* Add space below the navbar */
        }

        /* Profile Dropdown Specifics */
        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .profile-btn img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: none;
            z-index: 1001;
            min-width: 200px;
            list-style: none; /* Remove bullet points */
            padding: 0; /* Remove default padding */
            margin: 10px 0 0 0; /* Add margin below button */
        }

        .profile-menu.show {
            display: block;
        }

        .profile-menu .dropdown-item {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--dark-purple);
            transition: background-color 0.2s ease;
        }

        .profile-menu .dropdown-item:hover {
            background-color: #f0f0f0;
        }

        .profile-menu .dropdown-divider {
            border-top: 1px solid #eee;
            margin: 8px 0;
        }

        /* Main Content Cards */
        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05); /* Lighter shadow for general cards */
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            border-radius: 8px; /* Rounded corners */
        }
        .card:hover {
            transform: translateY(-5px); /* Lift card slightly */
            box-shadow: 0 8px 16px rgba(0,0,0,0.1); /* Enhance shadow on hover */
        }

        /* Charts Container */
        .chart-container {
            position: relative;
            height: 300px;
            padding: 15px;
        }

        /* Table Specific Styles */
        .table-responsive {
            border-radius: 8px; /* Apply border-radius to the responsive wrapper */
            overflow: hidden; /* Ensure radius applies correctly and content is clipped */
            margin-bottom: 20px; /* Add some space below the table */
            /* This will automatically add scrollbars if needed on smaller screens */
            overflow-x: auto;
        }

        .table-bordered {
            border-radius: 8px; /* Apply border-radius to the table itself if needed, but overflow hidden on wrapper is key */
        }
        .table-bordered th, .table-bordered td {
            border-color: #dee2e6; /* Keep bootstrap default border color */
        }
        /* Bootstrap's .table-striped already handles the striped rows */
        .table-bordered tbody tr:hover {
            background-color: #e9ecef; 
            cursor: pointer; 
        }
        .sidebar .dropdown-btn .ms-auto {
            margin-left: auto !important;
        }

        /* This rule might still be needed if sidebar.php uses .rotate-180 */
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Adjustments for very small screens */
        @media (max-width: 576px) {
            .navbar .container-fluid {
                flex-direction: column; /* Stack items vertically */
                align-items: center;
            }
            .navbar .d-flex.align-items-center {
                margin-bottom: 10px; /* Space between title and profile */
            }
            .navbar h5 {
                font-size: 1.25rem; /* Smaller title */
                text-align: center;
                width: 100%;
            }
            .profile-dropdown {
                width: 100%;
                text-align: center;
            }
            .profile-menu {
                left: 50%;
                transform: translateX(-50%); /* Center dropdown */
            }
            .content {
                padding: 10px; /* Reduced padding for more space */
            }
        }
    </style>
    </head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <nav class="navbar bg-white shadow-sm mb-4">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <h5 class="me-3">Welcome Admin, <?= htmlspecialchars($adminName ?? 'Guest') ?></h5>
                </div>
                <div class="profile-dropdown">
                    <button class="profile-btn" onclick="toggleProfileMenu()">
                        <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png" alt="Profile">
                    </button>
                    <div class="profile-menu">
                        <div class="p-3 text-center">
                            <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png"
                                 alt="Profile"
                                 class="rounded-circle mb-2"
                                 style="width: 80px; height: 80px;">
                            <h6><?= htmlspecialchars($adminName ?? 'Guest') ?></h6>
                            <small><?= htmlspecialchars($adminEmail ?? 'no-email@example.com') ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="/OceanGas/staff/admin_dashboard.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="/OceanGas/staff/admin_dashboard.php" class="dropdown-item">
                            <i class="fas fa-user me-2"></i>Profile </a>
                        <div class="dropdown-divider"></div>
                        <a href="/OceanGas/logout.php" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>SignOut
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div id="mainContent">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                <div class="col">
                    <div class="card h-100 p-3">
                        <h5>Total Users</h5>
                        <p class="display-6"><?= number_format($total_users ?? 0) ?></p>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100 p-3">
                        <h5>Pending Requests</h5>
                        <p class="display-6"><i class="fas fa-exclamation-triangle text-warning me-2"></i> <?= number_format($pending_requests ?? 0) ?></p>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100 p-3">
                        <h5>Total Revenue</h5>
                        <p class="display-6"><i class="fas fa-dollar-sign text-success me-2"></i> KES <?= number_format($total_revenue ?? 0, 2) ?></p>
                    </div>
                </div>
                 <div class="col">
                    <div class="card h-100 p-3">
                        <h5>Total Sales</h5>
                         <p class="display-6"><i class="fas fa-chart-line text-info me-2"></i> KES <?= number_format($total_sales_amount ?? 0, 2) ?></p>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100 p-3">
                        <h5>Total Purchases Cost</h5>
                        <p class="display-6"><i class="fas fa-chart-bar text-warning me-2"></i> KES <?= number_format($total_purchases_amount ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="card p-3">
                        <h5>Revenue vs Expense</h5>
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card p-3">
                        <h5>Revenue vs Expense Trend</h5>
                        <div class="chart-container">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card p-3">
                        <h5>Total Sales vs Purchases</h5>
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card p-3">
                        <h5>Recent Purchases</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Supplier</th>
                                        <th>Total Cost (KES)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($recent_purchases) && count($recent_purchases) > 0): ?>
                                        <?php foreach($recent_purchases as $purchase): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($purchase['purchase_date'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($purchase['product'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($purchase['quantity'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($purchase['supplier'] ?? '') ?></td>
                                                <td>KES <?= number_format($purchase['total_cost'] ?? 0, 2) ?></td></tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">No recent purchases.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card p-3">
                        <h5>Recent Sales</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Total (KES)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($recent_sales) && count($recent_sales) > 0): ?>
                                        <?php foreach($recent_sales as $sale): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($sale['sale_date'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($sale['product_name'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($sale['quantity'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($sale['total_amount'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center">No recent sales.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <iframe id="mainFrame" name="main-frame" style="display:none; width:100%; height:800px; border:0;"></iframe>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        window.toggleFrame = function(url) {
            const mainContent = document.getElementById('mainContent');
            const mainFrame = document.getElementById('mainFrame');
            if (mainContent) mainContent.style.display = 'none';
            if (mainFrame) {
                 mainFrame.style.display = 'block';
                 mainFrame.src = url; // Load URL into iframe
            }
            if (window.innerWidth <= 768 && typeof window.toggleSidebar === 'function') {
                window.toggleSidebar(); // Call toggleSidebar to close it
            }
        }


        document.addEventListener("DOMContentLoaded", function() {
  
             document.querySelectorAll('.sidebar .dropdown-btn').forEach(btn => {
                 btn.addEventListener('click', function(e) {
                     e.stopPropagation(); // Prevent document click from closing it immediately
                     const dropdown = this.nextElementSibling;
                     // Toggle display
                     dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';

                     // Toggle arrow icon class if you have one
                     const arrowIcon = this.querySelector('.fa-chevron-down');
                     if (arrowIcon) {
                         arrowIcon.classList.toggle('rotate-180'); // Assuming you have this class in your CSS
                     }
                 });
             });

             document.addEventListener('click', (e) => {
                 if (!e.target.closest('.dropdown')) {
                     document.querySelectorAll('.dropdown-container').forEach(dropdown => {
                         dropdown.style.display = 'none';
                     });
                     document.querySelectorAll('.sidebar .fa-chevron-down').forEach(icon => {
                         icon.classList.remove('rotate-180');
                     });
                 }
             });

            document.querySelectorAll('.sidebar a[href]').forEach(link => {
                 link.addEventListener('click', function(e) {

                    if (this.href.includes('?embedded=1')) {
                        e.preventDefault(); 
                        window.toggleFrame(this.href);

                        // Update active class for sidebar links
                        document.querySelectorAll('.sidebar-link').forEach(navLink => navLink.classList.remove('active'));
                        this.classList.add('active');

                        // Open parent dropdowns if necessary (assuming dropdown structure)
                        let parentDropdown = this.closest('.dropdown');
                        while(parentDropdown) {
                            const dropdownContainer = parentDropdown.querySelector('.dropdown-container');
                            const dropdownBtn = parentDropdown.querySelector('.dropdown-btn .fa-chevron-down');
                            if(dropdownContainer) dropdownContainer.style.display = 'block';
                            if(dropdownBtn) dropdownBtn.classList.add('rotate-180');
                            parentDropdown = parentDropdown.parentElement.closest('.dropdown');
                        }
                    }

                 });
            });

            const ctxBar = document.getElementById('barChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Revenue', 'Expense'],
                    datasets: [{
                        label: 'Amount (KES)',
                        data: [<?= $total_revenue ?? 0 ?>, <?= $total_expense ?? 0 ?>],
                        backgroundColor: ['#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allow chart to fit container
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            const ctxLine = document.getElementById('lineChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], // Replace with actual date labels if available
                    datasets: [{
                        label: 'Revenue',
                         // Replace with actual trend data if available
                        data: [<?= ($total_revenue ?? 0) * 0.2 ?>, <?= ($total_revenue ?? 0) * 0.3 ?>, <?= ($total_revenue ?? 0) * 0.25 ?>, <?= ($total_revenue ?? 0) * 0.15 ?>, <?= ($total_revenue ?? 0) * 0.05 ?>, <?= ($total_revenue ?? 0) * 0.05 ?>],
                        borderColor: '#28a745',
                        fill: false,
                        tension: 0.1
                    }, {
                        label: 'Expense',
                         // Replace with actual trend data if available
                        data: [<?= ($total_expense ?? 0) * 0.3 ?>, <?= ($total_expense ?? 0) * 0.25 ?>, <?= ($total_expense ?? 0) * 0.2 ?>, <?= ($total_expense ?? 0) * 0.15 ?>, <?= ($total_expense ?? 0) * 0.05 ?>, <?= ($total_expense ?? 0) * 0.05 ?>],
                        borderColor: '#dc3545',
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allow chart to fit container
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            const ctxPie = document.getElementById('pieChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: ['Sales Revenue', 'Purchases Cost'],
                    datasets: [{
                        data: [<?= $total_sales_amount ?? 0 ?>, <?= $total_purchases_amount ?? 0 ?>],
                        backgroundColor: ['#7A5AF8', '#E3DAFF'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, 
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += 'KES ' + Number(context.raw).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                    }
                                    return label;
                                }
                            }
                        },
                         legend: {
                             position: 'bottom',
                             labels: {
                                 padding: 20,
                                 font: {
                                     size: 14
                                 }
                             }
                         }
                    }
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            const embedded = urlParams.get('embedded');
            const mainContent = document.getElementById('mainContent');
            const mainFrame = document.getElementById('mainFrame');

            if (embedded === '1') { 
                 if (mainContent) mainContent.style.display = 'none';
                 if (mainFrame) {
                     mainFrame.style.display = 'block';
                     const cleanUrl = window.location.href.replace('?embedded=1', '').replace('&embedded=1', '');
                     mainFrame.src = cleanUrl;
                 }
            } else {
                 if (mainContent) mainContent.style.display = 'block';
                 if (mainFrame) mainFrame.style.display = 'none';
            }
        });

        function toggleProfileMenu() {
            const menu = document.querySelector('.profile-menu');
            menu.classList.toggle('show');
        }

        // Close profile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.profile-dropdown')) {
                const profileMenu = document.querySelector('.profile-menu');
                 if (profileMenu) { // Check if element exists
                     profileMenu.classList.remove('show');
                 }
            }
        });

        if (history.scrollRestoration) {
            history.scrollRestoration = 'manual';
        } else {
            window.onbeforeunload = function () {
                window.scrollTo(0, 0);
            }
        }
    </script>
</body>
</html>
