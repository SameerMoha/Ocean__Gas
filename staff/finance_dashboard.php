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

// Fetch admin details
$adminUsername = $_SESSION['staff_username'];
$adminSql = "SELECT username, email FROM users WHERE username = ? AND role = 'support' LIMIT 1";
$stmt = $conn->prepare($adminSql);
$stmt->bind_param("s", $adminUsername);
$stmt->execute();
$result = $stmt->get_result();
$adminName = "";
$adminEmail = "";
if ($row = $result->fetch_assoc()) {
    $adminName = $row['username'];
    $adminEmail = $row['email'];
}

// Get financial summary
$summary_sql = "
    SELECT 
        SUM(CASE WHEN transaction_type = 'return' AND status = 'approved' THEN amount ELSE 0 END) as total_returns,
        COUNT(CASE WHEN transaction_type = 'return' AND status = 'approved' THEN 1 END) as return_count,
        MAX(CASE WHEN transaction_type = 'return' AND status = 'approved' THEN transaction_date END) as last_return_date,
        SUM(CASE WHEN transaction_type = 'sale' THEN amount ELSE 0 END) as total_sales,
        COUNT(CASE WHEN transaction_type = 'sale' THEN 1 END) as sale_count,
        MAX(CASE WHEN transaction_type = 'sale' THEN transaction_date END) as last_sale_date
    FROM financial_transactions
    WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$summary_result = $conn->query($summary_sql);
$summary = $summary_result->fetch_assoc();

// Get recent transactions
$transactions_sql = "
    SELECT 
        ft.*,
        o.order_number,
        CASE 
            WHEN ft.transaction_type = 'return' THEN 'Return'
            WHEN ft.transaction_type = 'sale' THEN 'Sale'
            ELSE ft.transaction_type
        END as type_label
    FROM financial_transactions ft
    LEFT JOIN orders o ON ft.order_id = o.order_id
    ORDER BY ft.transaction_date DESC
    LIMIT 10
";
$transactions_result = $conn->query($transactions_sql);
$transactions = [];
while ($row = $transactions_result->fetch_assoc()) {
    $transactions[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            left: 0;
            top: 0;
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
            margin-left: 250px;
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
            margin-bottom: 20px;
        }
        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .summary-card {
            background: linear-gradient(45deg, #6a008a, #8e44ad);
            color: white;
        }
        .transaction-row {
            transition: background-color 0.2s;
        }
        .transaction-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Support Panel</h2>
        <a href="/OceanGas/staff/support_dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="/OceanGas/staff/support_inquiries.php">
            <i class="fas fa-question-circle"></i> Inquiries
        </a>
        <a href="/OceanGas/staff/support_reviews.php">
            <i class="fas fa-star"></i> Product reviews
        </a>
        <a href="/OceanGas/staff/return_requests.php">
            <i class="fas fa-undo"></i> Return Requests
        </a>
        <a href="/OceanGas/staff/finance_dashboard.php" class="active">
            <i class="fas fa-money-bill-wave"></i> Finance
        </a>
    </div>

    <div class="content">
        <div class="topbar">
            <h1>Finance Dashboard</h1>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button"
                            id="profileDropdown"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            style="display: flex; align-items: center; background-color: white; border: none; color: black;">
                        <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png"
                             alt="Profile Picture"
                             width="23px"
                             height="23px"
                             style="border-radius: 50%;">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header text-center">
                            <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png" 
                                 alt="Profile Picture"
                                 style="width: 40px; height: 40px; border-radius: 50%; margin-bottom: 5px;">
                            <div>
                                <strong><?php echo htmlspecialchars($adminName); ?></strong><br>
                                <small><?php echo htmlspecialchars($adminEmail); ?></small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="/OceanGas/staff/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/OceanGas/logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="card summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Returns (30 Days)</h5>
                        <h3 class="card-text">Ksh <?php echo number_format($summary['total_returns'], 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Number of Returns</h5>
                        <h3 class="card-text"><?php echo number_format($summary['return_count']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Order #</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="transaction-row">
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['order_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $transaction['transaction_type'] === 'return' ? 'warning' : 'success'; 
                                        ?>">
                                            <?php echo htmlspecialchars($transaction['type_label']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">Ksh <?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $transaction['status'] === 'approved' ? 'success' : 
                                                ($transaction['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 