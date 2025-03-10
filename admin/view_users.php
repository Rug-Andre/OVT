<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Set timezone for date display
date_default_timezone_set('Africa/Nairobi'); // Adjust to your timezone

// Pagination settings
$users_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $users_per_page;

// Sorting settings
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'user_id';
$sort_order = isset($_GET['sort_order']) && in_array($_GET['sort_order'], ['ASC', 'DESC']) ? $_GET['sort_order'] : 'ASC';
$allowed_sort_columns = ['user_id', 'username', 'email', 'role', 'created_at'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'user_id';
}

// Search settings
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, "%$search%");
    $search_condition = " WHERE username LIKE '$search' OR email LIKE '$search'";
}

// Get total number of users
$total_query = "SELECT COUNT(*) as total FROM users" . $search_condition;
$total_result = mysqli_query($conn, $total_query);

if (!$total_result) {
    die("Error fetching total users: " . mysqli_error($conn));
}

$total_users = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_users / $users_per_page);

// Fetch users for the current page
$query = "SELECT user_id, username, email, role, created_at 
          FROM users" . $search_condition . " 
          ORDER BY $sort_by $sort_order 
          LIMIT $users_per_page OFFSET $offset";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching users: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - Online Voting Platform</title>
    <link rel="stylesheet" href="css/view_users.css">
</head>
<body>
    <div class="main-container">
        <h1>View Users</h1>

        <!-- Search Bar -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search ? substr($search, 1, -1) : ''); ?>">
            <button type="submit" class="btn btn-search">Search</button>
            <?php if (!empty($search)): ?>
                <a href="view_users.php" class="btn btn-clear">Clear Search</a>
            <?php endif; ?>
        </form>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>
                            <a href="?sort_by=user_id&sort_order=<?php echo $sort_by == 'user_id' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>">ID</a>
                            <?php if ($sort_by == 'user_id'): ?>
                                <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                            <?php endif; ?>
                        </th>
                        <th>
                            <a href="?sort_by=username&sort_order=<?php echo $sort_by == 'username' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>">Username</a>
                            <?php if ($sort_by == 'username'): ?>
                                <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                            <?php endif; ?>
                        </th>
                        <th>
                            <a href="?sort_by=email&sort_order=<?php echo $sort_by == 'email' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>">Email</a>
                            <?php if ($sort_by == 'email'): ?>
                                <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                            <?php endif; ?>
                        </th>
                        <th>
                            <a href="?sort_by=role&sort_order=<?php echo $sort_by == 'role' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>">Role</a>
                            <?php if ($sort_by == 'role'): ?>
                                <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                            <?php endif; ?>
                        </th>
                        <th>
                            <a href="?sort_by=created_at&sort_order=<?php echo $sort_by == 'created_at' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>">Created At</a>
                            <?php if ($sort_by == 'created_at'): ?>
                                <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                            <?php endif; ?>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($user['created_at']))); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-update">Update</a>
                                    <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-users">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Enhanced Pagination -->
        <div class="pagination">
            <?php if ($total_pages > 1): ?>
                <?php if ($page > 1): ?>
                    <a href="view_users.php?page=1&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>" class="btn btn-page">First</a>
                    <a href="view_users.php?page=<?php echo $page - 1; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>" class="btn btn-prev">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="view_users.php?page=<?php echo $i; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>" class="btn btn-page <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="view_users.php?page=<?php echo $page + 1; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>" class="btn btn-next">Next</a>
                    <a href="view_users.php?page=<?php echo $total_pages; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search ? substr($search, 1, -1) : ''); ?>" class="btn btn-page">Last</a>
                <?php endif; ?>
            <?php endif; ?>
            <p>Page <?php echo $page; ?> of <?php echo $total_pages; ?> (Total Users: <?php echo $total_users; ?>)</p>
        </div>

        <a href="admin_dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>