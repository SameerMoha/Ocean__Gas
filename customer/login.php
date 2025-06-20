<?php
session_start();

// Include the database connection file using an absolute path
require_once $_SERVER['DOCUMENT_ROOT'] . '/OceanGas/includes/db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim form data
    $Email = trim($_POST['Email']);
    $pass  = trim($_POST['pass']);
    
    if (empty($Email) || empty($pass)) {
        $error = "Please enter both Email and password.";
    } else {
        // Prepare a statement to select user by email
        $stmt = $conn->prepare("SELECT cust_id, Email, pass FROM customers WHERE Email = ?");
        $stmt->bind_param("s", $Email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            // Bind the results to variables
            $stmt->bind_result($cust_id, $dbEmail, $hashedPassword);
            $stmt->fetch();
            
            // Verify the provided password against the hash stored in the database
            if (password_verify($pass, $hashedPassword)) {
                $_SESSION['user_id']    = $cust_id;
                $_SESSION['user_email'] = $dbEmail;
                header("Location: /OceanGas/shop.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Login</title>
    <!-- Bootstrap CSS for a clean look -->
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
                    <h3 class="card-title text-center mb-4">Customer Login</h3>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="login.php">
                        <div class="form-group">
                            <label for="Email">Email</label>
                            <input type="email" name="Email" id="Email" class="form-control" placeholder="Enter email" required>
                        </div>
                        <div class="form-group">
                            <label for="pass">Password</label>
                            <input type="password" name="pass" id="pass" class="form-control" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-custom btn-block mt-4">Login</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="register.php">Register Account</a> | 
                        <a href="forgot_password.php">Forgot Password?</a><br>
                        <a href="../index.html">Back home</a>
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
