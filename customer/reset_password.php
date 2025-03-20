<?php
session_start();
require_once __DIR__ . '/includes/db.php';


// Retrieve the token from the URL
$token = $_GET['token'] ?? '';

if ($token) {
    // Validate the token against the database
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user && strtotime($user['reset_expires']) > time()) {
        // Token is valid; display the form for setting a new password
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Reset Password</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f2f2f2;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .reset-container {
                    background: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    width: 350px;
                    text-align: center;
                }
                h2 {
                    margin-bottom: 20px;
                }
                label {
                    display: block;
                    margin: 10px 0 5px;
                    text-align: left;
                }
                input[type="password"] {
                    width: 100%;
                    padding: 10px;
                    margin-bottom: 10px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    box-sizing: border-box;
                }
                button {
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                }
                button:hover {
                    background-color: #45a049;
                }
            </style>
        </head>
        <body>
            <div class="reset-container">
                <h2>Reset Your Password</h2>
                <form action="process_reset.php" method="post">
                    <!-- Pass the token as a hidden field -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    
                    <button type="submit">Submit</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        echo "Invalid or expired token.";
    }
} else {
    echo "No token provided.";
}
?>
