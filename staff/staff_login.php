<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php'; 
// db.php should establish the connection and create a $conn variable

$error = "";

// Process the login form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim username and password inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Prepare statement to fetch user details
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
    
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $dbUsername, $hashedPassword, $role);
                $stmt->fetch();
    
                // Verify the provided password against the hashed password in the DB
                if (password_verify($password, $hashedPassword)) {
                    // Set session variables for the user
                    $_SESSION['staff_id'] = $id;
                    $_SESSION['staff_username'] = $dbUsername;
                    $_SESSION['staff_role'] = $role;

                    // Redirect based on role
                    if ($role === 'admin') {
                        header("Location: /OceanGas/staff/admin_dashboard.php");
                        exit();
                    } elseif ($role === 'sales') {
                        header("Location: /OceanGas/staff/sales_staff_dashboard.php");
                        exit();
                    } elseif ($role === 'procurement') {
                        header("Location: /OceanGas/staff/procurement_staff_dashboard.php");
                        exit();
                    } else {
                        $error = "Access denied. Unknown role.";
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Login</title>
    <!-- Bootstrap CSS for styling -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background: #f7f7f7; }
        .login-container { margin-top: 100px; }
        .card { border: none; border-radius: 10px; box-shadow: 0px 0px 15px rgba(0,0,0,0.1); }
        .card-title { font-weight: 600; font-size: 24px; }
        .btn-custom { background-color: #007bff; color: #fff; font-weight: 600; }
        .btn-custom:hover { background-color: #0069d9; }
    </style>
</head>
<body>
<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Staff Login</h3>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-custom btn-block mt-4">Login</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="/OceanGas/customer/login.php">Back to General Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Optional Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
