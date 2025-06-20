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

// Query for Pending Return Requests
$result = $conn->query("SELECT COUNT(*) AS pending_returns FROM return_requests WHERE return_status = 'pending'");
if (!$result) {
    die("Pending returns query failed: " . $conn->error);
}
$row = $result->fetch_assoc();
$pending_returns = $row['pending_returns'];

// Fetch recent return requests
$return_requests_sql = "
    SELECT 
        r.return_id,
        r.return_reason,
        r.return_quantity,
        r.return_status,
        r.request_date,
        o.order_number,
        o.invoice_summary,
        c.F_name,
        c.L_name,
        c.Phone_number
    FROM return_requests r
    JOIN orders o ON r.order_id = o.order_id
    JOIN customers c ON r.cust_id = c.cust_id
    WHERE r.return_status = 'pending'
    ORDER BY r.request_date DESC
    LIMIT 5
";
$return_requests_result = $conn->query($return_requests_sql);
$recent_returns = [];
if ($return_requests_result) {
    while ($row = $return_requests_result->fetch_assoc()) {
        $recent_returns[] = $row;
    }
}

// Get current date and first day of current month
$current_date = date('Y-m-d');
$first_day_month = date('Y-m-01');

// Query for monthly inquiries trend (last 6 months)
$monthly_inquiries_sql = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM inquiries_and_reviews 
    WHERE rating IS NULL 
    AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
";
$monthly_inquiries_result = $conn->query($monthly_inquiries_sql);
$monthly_inquiries_data = [];
$monthly_inquiries_labels = [];
if ($monthly_inquiries_result) {
    while ($row = $monthly_inquiries_result->fetch_assoc()) {
        $monthly_inquiries_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $monthly_inquiries_data[] = $row['count'];
    }
}

// Query for inquiry status distribution
$inquiry_status_sql = "
    SELECT 
        status,
        COUNT(*) as count
    FROM inquiries_and_reviews 
    WHERE rating IS NULL
    GROUP BY status
";
$inquiry_status_result = $conn->query($inquiry_status_sql);
$inquiry_status_data = [];
$inquiry_status_labels = [];
if ($inquiry_status_result) {
    while ($row = $inquiry_status_result->fetch_assoc()) {
        $inquiry_status_labels[] = ucfirst($row['status']);
        $inquiry_status_data[] = $row['count'];
    }
}

// Query for return requests trend (last 6 months)
$monthly_returns_sql = "
    SELECT 
        DATE_FORMAT(request_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM return_requests 
    WHERE request_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(request_date, '%Y-%m')
    ORDER BY month ASC
";
$monthly_returns_result = $conn->query($monthly_returns_sql);
$monthly_returns_data = [];
$monthly_returns_labels = [];
if ($monthly_returns_result) {
    while ($row = $monthly_returns_result->fetch_assoc()) {
        $monthly_returns_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $monthly_returns_data[] = $row['count'];
    }
}

// Query for return status distribution
$return_status_sql = "
    SELECT 
        return_status,
        COUNT(*) as count
    FROM return_requests 
    GROUP BY return_status
";
$return_status_result = $conn->query($return_status_sql);
$return_status_data = [];
$return_status_labels = [];
if ($return_status_result) {
    while ($row = $return_status_result->fetch_assoc()) {
        $return_status_labels[] = ucfirst($row['return_status']);
        $return_status_data[] = $row['count'];
    }
}

// Query for review ratings distribution
$review_ratings_sql = "
    SELECT 
        rating,
        COUNT(*) as count
    FROM inquiries_and_reviews 
    WHERE rating IS NOT NULL
    GROUP BY rating
    ORDER BY rating ASC
";
$review_ratings_result = $conn->query($review_ratings_sql);
$review_ratings_data = [];
$review_ratings_labels = [];
if ($review_ratings_result) {
    while ($row = $review_ratings_result->fetch_assoc()) {
        $review_ratings_labels[] = $row['rating'] . ' Stars';
        $review_ratings_data[] = $row['count'];
    }
}

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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        /* OPTIONAL: Slight styling to match your screenshot's profile dropdown */
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
        .return-request-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .return-request-card:hover {
            transform: translateY(-2px);
        }
        .action-buttons {
            display: none;
        }
        .return-request-card:hover .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Support Panel</h2>
        <a href="/OceanGas/staff/support_dashboard.php" class="<?php echo ($current_page === 'support_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="/OceanGas/staff/support_inquiries.php" class="<?php echo ($current_page ==='support_inquiries.php') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Inquiries</a>
        <a href="/OceanGas/staff/support_reviews.php" class="<?php echo ($current_page === 'support_reviews') ? 'actie' :''; ?>"><i class="fas fa-star"></i> Product reviews</a>
        <a href="/OceanGas/staff/return_requests.php" class="<?php echo ($current_page === 'return_requests.php') ? 'active' : ''; ?>"><i class="fas fa-undo"></i> Return Requests</a>
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

            <div class="col-md-3">
                <a href="#" onclick="showReturnRequests()" style="text-decoration: none;">
                    <div class="card p-3" style="text-align: center;">
                        <h5>Pending Returns</h5>
                        <p class="display-6"><?php echo number_format($pending_returns); ?></p>
                    </div>
                </a>
            </div>
        
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function processReturn(returnId, action) {
            Swal.fire({
                title: 'Add Notes',
                input: 'textarea',
                inputPlaceholder: 'Enter any notes about this decision...',
                showCancelButton: true,
                confirmButtonText: action === 'approve' ? 'Approve' : 'Decline',
                confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
                showLoaderOnConfirm: true,
                preConfirm: (notes) => {
                    return fetch('process_return.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            return_id: returnId,
                            action: action,
                            notes: notes
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to process return request');
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error.message}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: result.value.message,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }

        function showReturnRequests() {
            // Fetch all return requests
            fetch('get_return_requests.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to fetch return requests');
                    }

                    // Create HTML for the modal content
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Reason</th>
                                        <th>Quantity</th>
                                        <th>Request Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    data.returns.forEach(request => {
                        const statusClass = {
                            'pending': 'warning',
                            'approved': 'success',
                            'declined': 'danger'
                        }[request.return_status] || 'secondary';

                        html += `
                            <tr>
                                <td>${request.order_number}</td>
                                <td>${request.F_name} ${request.L_name}</td>
                                <td>${request.return_reason}</td>
                                <td>${request.return_quantity}</td>
                                <td>${new Date(request.request_date).toLocaleDateString()}</td>
                                <td><span class="badge bg-${statusClass}">${request.return_status}</span></td>
                                <td>
                        `;

                        if (request.return_status === 'pending') {
                            html += `
                                <button class="btn btn-success btn-sm" onclick="processReturn(${request.return_id}, 'approve')">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="processReturn(${request.return_id}, 'decline')">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                        }

                        html += `
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    Swal.fire({
                        title: 'All Return Requests',
                        html: html,
                        width: '80%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        customClass: {
                            container: 'return-requests-modal'
                        }
                    });
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: error.message,
                        icon: 'error'
                    });
                });
        }
    </script>

    <!-- Add this after the top cards row -->
    <div class="row my-4">
        <!-- Inquiry Status Distribution -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Inquiry Status Distribution</h5>
                    <div style="height: 250px;">
                        <canvas id="inquiryStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Returns Trend -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Monthly Returns Trend</h5>
                    <div style="height: 250px;">
                        <canvas id="returnsTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Status Distribution -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Return Status Distribution</h5>
                    <div style="height: 250px;">
                        <canvas id="returnStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Ratings Distribution -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Review Ratings Distribution</h5>
                    <div style="height: 250px;">
                        <canvas id="reviewRatingsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this before the closing body tag -->
<script>
// Inquiry Status Distribution Chart
new Chart(document.getElementById('inquiryStatusChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($inquiry_status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($inquiry_status_data); ?>,
            backgroundColor: ['#6a008a', '#28a745', '#dc3545', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 15,
                    padding: 10
                }
            }
        }
    }
});

// Monthly Returns Trend Chart
new Chart(document.getElementById('returnsTrendChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthly_returns_labels); ?>,
        datasets: [{
            label: 'Returns',
            data: <?php echo json_encode($monthly_returns_data); ?>,
            backgroundColor: '#6a008a'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Return Status Distribution Chart
new Chart(document.getElementById('returnStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($return_status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($return_status_data); ?>,
            backgroundColor: ['#ffc107', '#28a745', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 15,
                    padding: 10
                }
            }
        }
    }
});

// Review Ratings Distribution Chart
new Chart(document.getElementById('reviewRatingsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($review_ratings_labels); ?>,
        datasets: [{
            label: 'Number of Reviews',
            data: <?php echo json_encode($review_ratings_data); ?>,
            backgroundColor: '#6a008a'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
</body>
</html>
