<?php
session_start();
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
</head>
<body>
<div class="container mt-4">
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
</body>
</html>
