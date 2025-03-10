<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_class = '';

// Fetch user data
$user_id = mysqli_real_escape_string($conn, $user_id);
$query = "SELECT username, email, role, created_at, image FROM admin WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching user: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($result);

if ($user === null) {
    $user = ['username' => '', 'email' => '', 'role' => '', 'created_at' => '', 'image' => null];
    $message = "Error: User not found.";
    $message_class = 'error';
}

// Handle profile update (username and email)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $check_query = "SELECT user_id FROM admin WHERE username = '$username' AND user_id != '$user_id'";
    $check_result = mysqli_query($conn, $check_query);

    if (!$check_result) {
        $message = "Error checking username: " . mysqli_error($conn);
        $message_class = 'error';
    } elseif (mysqli_num_rows($check_result) > 0) {
        $message = "Error: Username is already taken.";
        $message_class = 'error';
    } else {
        $update_query = "UPDATE admin SET username = '$username', email = '$email' WHERE user_id = '$user_id'";
        if (mysqli_query($conn, $update_query)) {
            $message = "Profile updated successfully!";
            $message_class = 'success';
            $user['username'] = $username;
            $user['email'] = $email;
            $_SESSION['username'] = $username;
        } else {
            $message = "Error updating profile: " . mysqli_error($conn);
            $message_class = 'error';
        }
    }
}

// Handle image update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['update_image'])) {
    $image = $_FILES['update_image']['tmp_name'];
    $image_size = $_FILES['update_image']['size']; // Get file size in bytes

    // Set maximum allowed size (e.g., 2MB = 2 * 1024 * 1024 bytes)
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!empty($image)) {
        if ($image_size > $max_size) {
            $message = "Too large image. Maximum size allowed is 2MB.";
            $message_class = 'error';
        } else {
            $imgContent = mysqli_real_escape_string($conn, file_get_contents($image));
            $update_query = "UPDATE admin SET image = '$imgContent' WHERE user_id = '$user_id'";
            
            if (mysqli_query($conn, $update_query)) {
                $message = "Image updated successfully!";
                $message_class = 'success';
                $user['image'] = $imgContent;
            } else {
                $message = "Error updating image. Please try again.";
                $message_class = 'error';
            }
        }
    } else {
        $message = "Error: No image selected.";
        $message_class = 'error';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $pass_query = "SELECT password FROM admin WHERE user_id = '$user_id'";
    $pass_result = mysqli_query($conn, $pass_query);
    
    if (!$pass_result) {
        $message = "Error fetching password: " . mysqli_error($conn);
        $message_class = 'error';
    } else {
        $current_hashed_password = mysqli_fetch_assoc($pass_result)['password'];

        if (password_verify($current_password, $current_hashed_password)) {
            if ($new_password === $confirm_password) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $new_hashed_password = mysqli_real_escape_string($conn, $new_hashed_password);
                $update_pass_query = "UPDATE admin SET password = '$new_hashed_password' WHERE user_id = '$user_id'";
                
                if (mysqli_query($conn, $update_pass_query)) {
                    $message = "Password changed successfully!";
                    $message_class = 'success';
                } else {
                    $message = "Error changing password: " . mysqli_error($conn);
                    $message_class = 'error';
                }
            } else {
                $message = "New password and confirmation do not match.";
                $message_class = 'error';
            }
        } else {
            $message = "Current password is incorrect.";
            $message_class = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/settings.css">
</head>
<body>
 
    <div class="main-content">
        <div class="settings-container">
            <h1>User Settings</h1>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_class; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="settings.php">
                <h3>Update Profile</h3>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="role">Role (Read-only)</label>
                    <input type="text" id="role" value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="created_at">Account Created (Read-only)</label>
                    <input type="text" id="created_at" value="<?php echo htmlspecialchars($user['created_at']); ?>" readonly>
                </div>
                <div class="form-group">
                    <button type="submit" name="update_profile">Update Profile</button>
                </div>
            </form>

            <form method="POST" action="settings.php" enctype="multipart/form-data">
                <h3>Update Profile Image</h3>
                <div class="form-group">
                    <label>Current Image</label>
                    <div style="width: 150px; height: 150px; margin: 10px auto; background-size: cover; background-position: center; border: 1px solid #ccc; border-radius: 5px; <?php echo $user['image'] ? 'background-image: url(data:image/jpeg;base64,' . base64_encode($user['image']) . ');' : ''; ?>"></div>
                </div>
                <div class="form-group">
                    <label for="update_image">Upload New Image</label>
                    <input type="file" id="update_image" name="update_image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="update_image">Update Image</button>
                </div>
            </form>

            <form method="POST" action="settings.php">
                <h3>Change Password</h3>
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="change_password">Change Password</button>
                </div>
            </form>
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');
        const topNav = document.querySelector('.top_nav');

        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    if (window.innerWidth > 768) {
                        mainContent.classList.add('active');
                        topNav.style.width = 'calc(100% - 200px)';
                        topNav.style.left = '200px';
                    }
                } else {
                    mainContent.classList.remove('active');
                    topNav.style.width = '100%';
                    topNav.style.left = '0';
                }
            });
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>