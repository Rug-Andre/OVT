<?php
include 'db.php';
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to register user
function registerUser($conn, $username, $email, $password, $identity_card) {
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $password = mysqli_real_escape_string($conn, $password);
    $identity_card = mysqli_real_escape_string($conn, $identity_card);
    $query = "INSERT INTO users (username, email, password, identity_card) VALUES ('$username', '$email', '$password', '$identity_card')";
    return mysqli_query($conn, $query);
}

// Validation functions
function validateUsername($username) {
    if (strlen($username) < 5) return "Username must be at least 5 characters";
    if (!preg_match("/^[a-zA-Z0-9 ]+$/", $username)) return "Username can only contain letters, numbers, and spaces";
    return true;
}

function validatePassword($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters long";
    if (!preg_match("/[A-Z]/", $password)) return "Password must contain at least one uppercase letter";
    if (!preg_match("/[0-9]/", $password)) return "Password must contain at least one number";
    if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) return "Password must contain at least one special character";
    return true;
}

function validateIdentityCard($identity_card) {
    if (empty($identity_card)) return "Identity card number is required";
    if (!preg_match("/^[A-Za-z0-9-]{8,20}$/", $identity_card)) return "Identity card must be 8-20 alphanumeric characters with optional hyphens";
    return true;
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $raw_password = trim($_POST['password'] ?? '');
        $identity_card = trim($_POST['identity_card'] ?? '');
        $terms = isset($_POST['terms']);

        $errors = [];

        $username_validation = validateUsername($username);
        if ($username_validation !== true) $errors[] = $username_validation;

        if (empty($email)) $errors[] = "Email is required";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        elseif (strlen($email) > 100) $errors[] = "Email must not exceed 100 characters";

        if (empty($raw_password)) $errors[] = "Password is required";
        else {
            $password_validation = validatePassword($raw_password);
            if ($password_validation !== true) $errors[] = $password_validation;
        }

        $identity_validation = validateIdentityCard($identity_card);
        if ($identity_validation !== true) $errors[] = $identity_validation;

        if (!$terms) $errors[] = "You must agree to the terms";

        $email_check = mysqli_real_escape_string($conn, $email);
        $result = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email_check'");
        if (mysqli_num_rows($result) > 0) $errors[] = "Email already exists! <a href='login.php'>Login here</a>";

        if (!empty($errors)) {
            $message = implode("<br>", $errors);
            $alert_class = "alert-error";
        } else {
            $password = password_hash($raw_password, PASSWORD_DEFAULT);
            if (registerUser($conn, $username, $email, $password, $identity_card)) {
                $message = "Registration successful! <a href='login.php'>Login here</a>";
                $alert_class = "alert-success";
            } else {
                throw new Exception("Registration failed: " . mysqli_error($conn));
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $alert_class = "alert-error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - OVT</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css">
    <style>
        .country-select {
            width: 100%;
            padding: 0.5rem;
            height: 40px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.9rem;
            background: #f0f4f8;
        }
        .flag-icon {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <main class="register-container">
        <section class="form-wrapper">
            <h1 class="form-title"><i class="fas fa-user-plus"></i> USER REGISTER</h1>
            <?php if (isset($message)): ?>
                <div class="alert <?php echo $alert_class; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="register-form" id="registerForm">
                <div class="form-group">
                    <label for="username" class="form-label"><i class="fas fa-user"></i> Username</label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           class="form-input" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           required 
                           aria-required="true"
                           placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
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
                    <label for="password" class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-input" 
                           required 
                           aria-required="true"
                           placeholder="Create a password">
                </div>

                <div class="form-group">
                    <label for="identity_card" class="form-label"><i class="fas fa-id-card"></i> Identity Card</label>
                    <input type="text" 
                           name="identity_card" 
                           id="identity_card" 
                           class="form-input" 
                           value="<?php echo htmlspecialchars($identity_card ?? ''); ?>"
                           required 
                           aria-required="true"
                           placeholder="Enter your ID number">
                </div>

           
                <div class="form-group terms-group">
                    <input type="checkbox" 
                           name="terms" 
                           id="terms" 
                           class="terms-checkbox" 
                           required 
                           aria-required="true"
                           <?php echo (isset($terms) && $terms) ? 'checked' : ''; ?>>
                    <label for="terms" class="terms-label">
                        <i class="fas fa-check-square"></i> I agree to <a href="terms.php"> terms </a> and objectives of OVT! And I have 18 ages
                    </label>
                </div>

                <button type="submit" class="submit-btn" aria-label="Register"><i class="fas fa-user-plus"></i> Register</button>
            </form>
            <p class="auth-link">
                <i class="fas fa-sign-in-alt"></i> Already have an account? <a href="login.php" class="link">Login</a>
            </p>
        </section>
    </main>
</body>
</html>
<?php mysqli_close($conn); ?>