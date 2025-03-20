<?php
session_start();

// Database connection settings
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

// Create a new database connection using MySQLi
$conn = new mysqli($host, $user, $pass, $db);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = ""; // Success message
$error = ""; // Error message

if (isset($_POST['register'])) {
    // Retrieve and trim form data
    $F_name       = trim($_POST['F_name']);
    $L_name       = trim($_POST['L_name']);
    $Email        = trim($_POST['Email']);
    $Phone_number = trim($_POST['Phone_number']);
    $password     = trim($_POST['pass']);
    $confirm_pass = trim($_POST['confirm_pass']);

    // Server-side validation
    if (empty($F_name) || empty($L_name) || empty($Email) || empty($Phone_number) || empty($password) || empty($confirm_pass)) {
        $error = "All fields are required.";
    } elseif (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        // Check if the email already exists
        $checkStmt = $conn->prepare("SELECT cust_id FROM customers WHERE Email = ?");
        $checkStmt->bind_param("s", $Email);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // Hash the password for security
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Prepare and execute the INSERT query
            $stmt = $conn->prepare("INSERT INTO customers (F_name, L_name, Email, Phone_number, pass) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $F_name, $L_name, $Email, $Phone_number, $hashedPassword);

            if ($stmt->execute()) {
                $success = "Registration successful. Redirecting to login...";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <?php
    if (!empty($success)) {
        echo '<meta http-equiv="refresh" content="3;url=login.php">';
    }
    ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
        }
        .register-container {
            width: 350px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        .register-container label {
            display: block;
            margin-top: 10px;
        }
        .register-container input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .register-container .required {
            color: red;
        }
        .error {
            color: red;
            font-size: 0.9em;
            text-align: center;
        }
        .success {
            color: green;
            font-size: 0.9em;
            text-align: center;
        }
        .register-container button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            margin-top: 15px;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        .login-link a {
            color: #008CBA;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        <?php
            if (!empty($error)) {
                echo "<p class='error'>{$error}</p>";
            }
            if (!empty($success)) {
                echo "<p class='success'>{$success}</p>";
            }
        ?>
        <form id="registerForm" method="POST" action="register.php" novalidate>
            <label for="F_name">
                First Name: <span class="required">*</span>
            </label>
            <input type="text" id="F_name" name="F_name">
            <span id="firstNameError" class="error"></span>

            <label for="L_name">
                Last Name: <span class="required">*</span>
            </label>
            <input type="text" id="L_name" name="L_name">
            <span id="lastNameError" class="error"></span>

            <label for="Phone_number">
                Phone Number: <span class="required">*</span>
            </label>
            <input type="tel" id="Phone_number" name="Phone_number">
            <span id="phoneError" class="error"></span>

            <label for="Email">
                Email: <span class="required">*</span>
            </label>
            <input type="email" id="Email" name="Email">
            <span id="emailError" class="error"></span>

            <label for="pass">
                Password: <span class="required">*</span>
            </label>
            <input type="password" id="pass" name="pass">
            <span id="passwordError" class="error"></span>

            <label for="confirm_pass">
                Confirm Password: <span class="required">*</span>
            </label>
            <input type="password" id="confirm_pass" name="confirm_pass">
            <span id="confirmPasswordError" class="error"></span>

            <button type="submit" name="register">Register</button>
        </form>
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            // Clear previous error messages
            document.getElementById('firstNameError').innerText = '';
            document.getElementById('lastNameError').innerText = '';
            document.getElementById('phoneError').innerText = '';
            document.getElementById('emailError').innerText = '';
            document.getElementById('passwordError').innerText = '';
            document.getElementById('confirmPasswordError').innerText = '';

            let valid = true;
            let F_name = document.getElementById('F_name').value.trim();
            let L_name = document.getElementById('L_name').value.trim();
            let Phone_number = document.getElementById('Phone_number').value.trim();
            let Email = document.getElementById('Email').value.trim();
            let pass = document.getElementById('pass').value.trim();
            let confirmpass = document.getElementById('confirm_pass').value.trim();

            // Validate first name
            if (F_name === '') {
                document.getElementById('firstNameError').innerText = 'First name is required.';
                valid = false;
            }
            // Validate last name
            if (L_name === '') {
                document.getElementById('lastNameError').innerText = 'Last name is required.';
                valid = false;
            }
            // Validate phone number
            if (Phone_number === '') {
                document.getElementById('phoneError').innerText = 'Phone number is required.';
                valid = false;
            }
            // Validate email
            if (Email === '') {
                document.getElementById('emailError').innerText = 'Email is required.';
                valid = false;
            } else {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(Email)) {
                    document.getElementById('emailError').innerText = 'Enter a valid email address.';
                    valid = false;
                }
            }
            // Validate password
            if (pass === '') {
                document.getElementById('passwordError').innerText = 'Password is required.';
                valid = false;
            }
            // Validate confirm password
            if (confirmpass === '') {
                document.getElementById('confirmPasswordError').innerText = 'Please confirm your password.';
                valid = false;
            } else if (pass !== confirmpass) {
                document.getElementById('confirmPasswordError').innerText = 'Passwords do not match.';
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
