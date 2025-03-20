<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        /* Global styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        /* Container styling */
        .forgot-password-container {
            width: 350px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        /* Heading */
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        /* Form styling */
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 14px;
            color: #333;
            margin-top: 10px;
        }
        input[type="email"] {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            margin-top: 20px;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        /* Back link styling */
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .back-link a {
            color: #008CBA;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h2>Forgot Password</h2>
        <form action="process_forgot.php" method="post">
            <label for="email">Enter your registered email address:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Reset Password</button>
        </form>
        <div class="back-link">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>
