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

// Fetch admin details (for the top navbar profile dropdown)
$adminUsername = $_SESSION['staff_username'];
$adminSql = "SELECT username, email FROM users WHERE username = ? AND role = 'admin' LIMIT 1";
$stmt = $conn->prepare($adminSql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $adminUsername);
$stmt->execute();
$result_admin = $stmt->get_result();
$adminName = "";
$adminEmail = "";
if ($row_admin = $result_admin->fetch_assoc()) {
    $adminName = $row_admin['username'];
    $adminEmail = $row_admin['email'];
}
$stmt->close();


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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Define CSS variables if used in sidebar.php and not defined there */
        :root {
            --primary-purple: #6a008a;
            --light-purple: #E3DAFF;
            --dark-purple: #2A2656;
            --hover-effect: rgba(255,255,255,0.1);
        }

        body {
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        /* Content Area - Adjusted margin based on sidebar width */
        .content {
            margin-left: 280px; /* Default margin for desktop assuming 280px sidebar */
            transition: margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 20px;
        }

        /* Responsive adjustment for content margin when sidebar is hidden on small screens */
        /* This media query should match the one in your sidebar.php that hides the sidebar */
        @media (max-width: 768px) {
            .content {
                margin-left: 0; /* Content takes full width when sidebar is hidden */
            }
        }

        /* Navbar (Top Bar) - Keep styling specific to the top bar */
        .navbar {
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Profile Dropdown Specifics - Keep styling specific to the profile dropdown */
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
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
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

        /* Adjustments for very small screens - Keep styling specific to the top bar */
        @media (max-width: 576px) {
            .navbar .container-fluid {
                flex-direction: column;
                align-items: center;
            }
            .navbar .d-flex.align-items-center {
                margin-bottom: 10px;
            }
            .navbar h5 {
                font-size: 1.25rem;
                text-align: center;
                width: 100%;
            }
            .profile-dropdown {
                width: 100%;
                text-align: center;
            }
            .profile-menu {
                left: 50%;
                transform: translateX(-50%);
            }
            .content {
                padding: 10px;
            }
        }

        /* Table Specific Styles - Ensure these are only for the table within the main content */
        .table-responsive {
             /* Add border-radius to the responsive wrapper */
            border-radius: 8px;
            /* Ensure radius applies correctly and content is clipped */
            overflow: hidden;
            /* Add some space below the table */
            margin-bottom: 20px;
             /* This will automatically add scrollbars if needed on smaller screens */
             overflow-x: auto;
        }

        .table-bordered {
            /* Apply border-radius to the table itself if needed, but overflow hidden on wrapper is key */
            border-radius: 8px;
        }
        .table-bordered th, .table-bordered td {
            border-color: #dee2e6; /* Keep bootstrap default border color */
        }
        /* Bootstrap's .table-striped already handles the striped rows */
        .table-bordered tbody tr:hover {
            background-color: #e9ecef; /* Light grey on hover, slightly darker than stripe */
            cursor: pointer; /* Indicate interactivity */
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
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-user me-2"></i>Profile </a>
                        <div class="dropdown-divider"></div>
                        <a href="/OceanGas/logout.php" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>SignOut
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div id="dynamicContent">
            <div class="container mt-4">
                <h2>Manage Users</h2>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Update Role</th>
                                <th>Delete</th>
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
                </div>

                <h3 class="mt-5">Add New User</h3>
                <form method="post" class="mt-3 mb-5">
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

        <iframe id="mainFrame" style="display:none; width:100%; height:800px; border:0;"></iframe>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile menu toggle - This JS is specific to the main content's top navbar
        function toggleProfileMenu() {
            const menu = document.querySelector('.profile-menu');
            menu.classList.toggle('show');
        }

        // Close profile menu when clicking outside - This JS is specific to the main content's top navbar
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.profile-dropdown')) {
                const profileMenu = document.querySelector('.profile-menu');
                if (profileMenu) {
                    profileMenu.classList.remove('show');
                }
            }
        });

        // Prevent automatic scroll restoration
        if (history.scrollRestoration) {
            history.scrollRestoration = 'manual';
        } else {
            window.onbeforeunload = function () {
                window.scrollTo(0, 0);
            }
        }

        // NOTE: Sidebar specific JavaScript (like toggleSidebar, loadInIframe,
        // sidebar dropdown logic, and mobile toggle button functionality)
        // is expected to be defined and loaded within your 'sidebar.php' file.
        // The 'loadInIframe' function in sidebar.php should target the
        // '#dynamicContent' and '#mainFrame' elements in this users.php file.
    </script>
</body>
</html>
