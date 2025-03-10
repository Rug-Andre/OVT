<?php
session_start();
include 'db.php';

if (!isset($_SESSION['subadmin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$subadmin_id = $_SESSION['subadmin_id'];

// Delete all notifications for this subadmin
$delete_query = "DELETE FROM notifications WHERE user_id = '$subadmin_id'";
if (mysqli_query($conn, $delete_query)) {
    echo json_encode(['status' => 'success', 'message' => 'Notifications deleted']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error deleting notifications: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>