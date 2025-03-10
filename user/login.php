<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT user_id, username, password FROM users WHERE email = '$email'";
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
            $_SESSION['username'] = $username;
            header("Location: user_dashboard.php");
            exit();
        } else {
            $message = "Invalid credentials.";
            $alert_class = "alert-error";
        }
    } else {
        $message = "User not found.";
        $alert_class = "alert-error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - OVT</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-group {
            position: relative;
        }
        .fa-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dark);
            font-size: 16px;
            pointer-events: none;
        }
        .form-input {
            padding-left: 35px;
        }
        .form-wrapper {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
    </style>
</head>
<body>
    <main class="login-container">
        <section class="form-wrapper">
            <h1 class="form-title">USER LOGIN</h1>
            <?php if (isset($message)): ?>
                <div class="alert <?php echo $alert_class; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <i class="fa-solid fa-envelope fa-icon"></i>
                    <label for="email" class="form-label">Enter Email</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           class="form-input" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           required 
                           aria-required="true"
                           placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <i class="fa-solid fa-lock fa-icon"></i>
                    <label for="password" class="form-label">Enter Password</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-input" 
                           required 
                           aria-required="true"
                           placeholder="Enter your password">
                </div>

                <button type="submit" class="submit-btn" aria-label="Login">Login</button>
            </form>
            <p class="auth-link">
                You don't have an account? <a href="register.php" class="link">Register</a>
            </p>
        </section>
    </main>
</body>
</html>
<?php mysqli_close($conn); ?>