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

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_condition = $status_filter !== 'all' ? "WHERE r.return_status = ?" : "";

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM return_requests r
    $status_condition
";
$count_stmt = $conn->prepare($count_sql);
if ($status_filter !== 'all') {
    $count_stmt->bind_param("s", $status_filter);
}
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch return requests with pagination
// … after your prepare/bind/execute for $return_requests_stmt …

// 1) Prepare the query
$return_requests_sql = "
  SELECT 
    r.return_id,
    r.order_id,
    r.return_reason,
    r.return_quantity,
    r.return_status,
    r.request_date,
    o.order_number,
    c.F_name,
    c.L_name,
    c.Phone_number,
    oi.product_name,
    oi.unit_price
  FROM return_requests r
  JOIN orders o       ON r.order_id = o.order_id
  JOIN customers c    ON r.cust_id  = c.cust_id
  JOIN order_items oi ON oi.order_id = o.order_id
  $status_condition
  GROUP BY r.return_id
  ORDER BY r.request_date DESC
  LIMIT ? OFFSET ?
";
$return_requests_stmt = $conn->prepare($return_requests_sql);
if (! $return_requests_stmt) {
    die("Prepare failed: " . $conn->error);
}

// 2) Bind parameters (status + pagination)
if ($status_filter !== 'all') {
    $return_requests_stmt->bind_param("sii", $status_filter, $items_per_page, $offset);
} else {
    $return_requests_stmt->bind_param("ii", $items_per_page, $offset);
}

// 3) Execute
if (! $return_requests_stmt->execute()) {
    die("Execute failed: " . $return_requests_stmt->error);
}

$return_requests_stmt->store_result();
// 4) Bind result columns to PHP variables
$return_requests_stmt->bind_result(
    $r_return_id,
    $r_order_id,
    $r_return_reason,
    $r_return_quantity,
    $r_return_status,
    $r_request_date,
    $o_order_number,
    $c_F_name,
    $c_L_name,
    $c_Phone_number,
    $oi_product_name,
    $oi_unit_price
);

// 5) Fetch rows and compute return_amount
$return_requests = [];
while ($return_requests_stmt->fetch()) {
    $totalAmount = 0;
    $lines = preg_split('/[\r\n]+/', trim($r_return_reason));

    foreach ($lines as $line) {
        if (preg_match('/-\s*(.+?):\s*Returning\s*(\d+)\s*units?/i', $line, $m)) {
            list(, $productName, $qty) = $m;

            $pstmt = $conn->prepare("
                SELECT unit_price
                  FROM order_items
                 WHERE order_id    = ?
                   AND product_name = ?
                 LIMIT 1
            ");
            if (! $pstmt) {
                die("Lookup prepare failed: " . $conn->error);
            }
            $pstmt->bind_param("is", $r_order_id, $productName);
            if (! $pstmt->execute()) {
                die("Lookup execute failed: " . $pstmt->error);
            }
            $pstmt->bind_result($unitPriceFetched);
            $pstmt->fetch();
            $pstmt->close();

            $unitPrice = ($unitPriceFetched !== null)
                       ? (float)$unitPriceFetched
                       : (float)$oi_unit_price;

            $totalAmount += $unitPrice * (int)$qty;
        }
    }

    // fallback if no lines matched
    if ($totalAmount === 0) {
        $totalAmount = (float)$oi_unit_price * (int)$r_return_quantity;
    }

    $return_requests[] = [
        'return_id'       => $r_return_id,
        'order_id'        => $r_order_id,
        'return_reason'   => $r_return_reason,
        'return_quantity' => $r_return_quantity,
        'return_status'   => $r_return_status,
        'request_date'    => $r_request_date,
        'order_number'    => $o_order_number,
        'F_name'          => $c_F_name,
        'L_name'          => $c_L_name,
        'Phone_number'    => $c_Phone_number,
        'product_name'    => $oi_product_name,
        'unit_price'      => $oi_unit_price,
        'return_amount'   => number_format($totalAmount, 2),
    ];
}

// 6) Clean up
$return_requests_stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Requests</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            margin-bottom: 20px;
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
        .profile-btn {
            background-color: #6f42c1 !important;
            color: black !important;
            border: none;
            border-radius: 0.375rem;
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
        .status-filter {
            margin-bottom: 20px;
        }
        .status-filter .btn {
            margin-right: 10px;
        }
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        .pagination .page-link {
            color: #6a008a;
        }
        .pagination .page-item.active .page-link {
            background-color: #6a008a;
            border-color: #6a008a;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Support Panel</h2>
        <a href="/OceanGas/staff/support_dashboard.php" class="<?php echo ($current_page === 'support_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="/OceanGas/staff/support_inquiries.php" class="<?php echo ($current_page === 'support_inquiries.php') ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i> Inquiries
        </a>
        <a href="/OceanGas/staff/support_reviews.php" class="<?php echo ($current_page === 'support_reviews.php') ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Product reviews
        </a>
        <a href="/OceanGas/staff/return_requests.php" class="<?php echo ($current_page === 'return_requests.php') ? 'active' : ''; ?>">
            <i class="fas fa-undo"></i> Return Requests
        </a>
        <a href="/OceanGas/staff/finance_dashboard.php">Finance</a>
    </div>

    <div class="content">
        <!-- Topbar with Profile Dropdown -->
        <div class="topbar">
            <h1>Return Requests</h1>
            
            <div class="d-flex align-items-center">
                <i class="fas fa-envelope mx-2"></i>
                <i class="fas fa-bell mx-2"></i>

                <!-- Profile Dropdown -->
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

                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="/OceanGas/staff/admin_dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item text-danger" href="/OceanGas/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Status Filter -->
        <div class="status-filter">
            <a href="?status=all<?= $page > 1 ? '&page=' . $page : '' ?>" 
               class="btn <?= $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                All Requests
            </a>
            <a href="?status=pending<?= $page > 1 ? '&page=' . $page : '' ?>" 
               class="btn <?= $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Pending
            </a>
            <a href="?status=approved<?= $page > 1 ? '&page=' . $page : '' ?>" 
               class="btn <?= $status_filter === 'approved' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Approved
            </a>
            <a href="?status=declined<?= $page > 1 ? '&page=' . $page : '' ?>" 
               class="btn <?= $status_filter === 'declined' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Declined
            </a>
        </div>

        <!-- Return Requests Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Reason</th>
                                <th>Quantity</th>
                                <th>Amount (Ksh)</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($return_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['order_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($request['F_name'] . ' ' . $request['L_name']); ?><br>
                                        <small><?php echo htmlspecialchars($request['Phone_number']); ?></small>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($request['return_reason'])); ?></td>
                                    <td><?php echo htmlspecialchars($request['return_quantity']); ?></td>
<td class="text-end">
  <?php echo htmlspecialchars($request['return_amount']); ?>
</td>                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'declined' => 'danger'
                                        ][$request['return_status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst($request['return_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($request['return_status'] === 'pending'): ?>
                                            <button class="btn btn-success btn-sm" onclick="processReturn(<?php echo $request['return_id']; ?>, 'approve')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="processReturn(<?php echo $request['return_id']; ?>, 'decline')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?status=<?= $status_filter ?>&page=<?= $page - 1 ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?status=<?= $status_filter ?>&page=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?status=<?= $status_filter ?>&page=<?= $page + 1 ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processReturn(returnId, action) {
  Swal.fire({
    title: 'Add Notes',
    input: 'textarea',
    inputPlaceholder: 'Enter any notes about this decision…',
    showCancelButton: true,
    confirmButtonText: action === 'approve' ? 'Approve' : 'Decline',
    confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
    showLoaderOnConfirm: true,
    preConfirm: (notes) => {
      return fetch('process_return.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ return_id: returnId, action, notes })
      })
      .then(async response => {
        const text = await response.text();
        console.log('Response status:', response.status, response.statusText);
        console.log('Response body:', text);
        if (!response.ok) {
          // throw the entire body as the error message
          throw new Error(`Server returned ${response.status}: ${text}`);
        }
        try {
          return JSON.parse(text);
        } catch (err) {
          throw new Error('Invalid JSON: ' + text);
        }
      })
      .then(data => {
        if (!data.success) {
          throw new Error(data.message || 'Unknown error');
        }
        return data;
      })
      .catch(err => {
        Swal.showValidationMessage(`Request failed: ${err.message}`);
      });
    },
    allowOutsideClick: () => !Swal.isLoading()
  }).then(result => {
    if (result.isConfirmed) {
      Swal.fire('Success!', result.value.message, 'success')
        .then(() => location.reload());
    }
  });
}

    </script>
</body>
</html> 