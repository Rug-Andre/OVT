<?php
include 'db.php';
session_start();

// Login Logic (unchanged, just wrapped in isset check)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Update the SQL query to match the table structure
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT user_id, username, password FROM admin WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Error executing query: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id = $row['user_id'];
        $username = $row['username'];
        $hashed_password = $row['password'];
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;  // Set username
            // Remove the role assignment if it's not in the database
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "User not found.";
    }
}

// Forgot Password Logic
$forgot_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_email'])) {
    $forgot_email = mysqli_real_escape_string($conn, $_POST['forgot_email']);
    $query = "SELECT user_id FROM admin WHERE email = '$forgot_email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user_id = $row['user_id'];

        // Generate a unique reset token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Store token in the database (assumes a `password_resets` table)
        $insert_query = "INSERT INTO password_resets (user_id, token, expires) VALUES ('$user_id', '$token', '$expires')";
        if (mysqli_query($conn, $insert_query)) {
            // Send reset email (XAMPP workaround: display link for testing)
            $reset_link = "http://localhost/OVT/admin/reset_password.php?token=$token";
            $subject = "Password Reset Request";
            $body = "Click this link to reset your password: $reset_link\nThis link expires in 1 hour.";
            $headers = "From: no-reply@localhost";

            // Since mail() won't work in XAMPP without SMTP, show the link for testing
            if (false) { // Replace with mail($forgot_email, $subject, $body, $headers) when SMTP is configured
                $forgot_message = "A password reset link has been sent to your email.";
            } else {
                $forgot_message = "Email not sent (XAMPP limitation). Use this link for testing: <a href='$reset_link'>$reset_link</a>";
            }
        } else {
            $forgot_message = "Error generating reset link: " . mysqli_error($conn);
        }
    } else {
        $forgot_message = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/admin_login.css">
    <style>
        /* Forgot Password Popup Styles */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .forgot-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            width: 90%;
            max-width: 400px;
        }
        .forgot-popup h3 {
            margin: 0 0 15px;
            text-align: center;
            color: #333;
        }
        .forgot-popup label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .forgot-popup input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .forgot-popup button {
            width: 100%;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .forgot-popup button:hover {
            background: #45a049;
        }
        .forgot-popup .close-btn {
            background: #ccc;
            margin-top: 10px;
        }
        .forgot-popup .close-btn:hover {
            background: #bbb;
        }
        .forgot-message {
            margin: 10px 0;
            text-align: center;
            color: #d9534f; /* Red for errors */
        }
        .forgot-message a {
            color: #007bff;
            text-decoration: none;
        }
        .forgot-message a:hover {
            text-decoration: underline;
        }
        .forgot-password-link {
            text-align: center;
            margin-top: 10px;
        }
        .forgot-password-link a {
            color: #007bff;
            text-decoration: none;
        }
        .forgot-password-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form login" id="form">
            <form method="POST">
                <h2>ADMIN LOGIN</h2>
                <label for="email">Enter Email</label>
                <input type="email" name="email" required id="email">
                <label for="password">Enter Password</label>
                <input type="password" name="password" required id="password">
                <button type="submit" class="submit">Login</button>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            </form>
            <p class="login-link"><a href="admin_register.php">You don't have an account? Register</a></p>
            <p class="forgot-password-link"><a href="#" onclick="showForgotPopup(); return false;">Forgot Password?</a></p>
        </div>
    </div>

    <!-- Forgot Password Popup -->
    <div id="forgot-overlay" class="overlay"></div>
    <div id="forgot-popup" class="forgot-popup">
        <h3>Reset Password</h3>
        <form method="POST">
            <label for="forgot_email">Enter Your Email</label>
            <input type="email" name="forgot_email" id="forgot_email" required>
            <button type="submit">Send Reset Link</button>
            <?php if (!empty($forgot_message)): ?>
                <p class="forgot-message"><?php echo $forgot_message; ?></p>
            <?php endif; ?>
            <button type="button" class="close-btn" onclick="hideForgotPopup()">Close</button>
        </form>
    </div>

    <script>
        // Forgot Password Popup Functions
        const forgotOverlay = document.getElementById('forgot-overlay');
        const forgotPopup = document.getElementById('forgot-popup');

        function showForgotPopup() {
            forgotOverlay.style.display = 'block';
            forgotPopup.style.display = 'block';
            document.body.classList.add('overlay-active');
        }

        function hideForgotPopup() {
            forgotOverlay.style.display = 'none';
            forgotPopup.style.display = 'none';
            document.body.classList.remove('overlay-active');
        }

        forgotOverlay.addEventListener('click', hideForgotPopup);
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>