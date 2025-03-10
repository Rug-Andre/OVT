<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    
    // Fetch subadmin_id before updating status
    $fetch_sql = "SELECT subadmin_id FROM requests WHERE request_id = ?";
    $fetch_stmt = $conn->prepare($fetch_sql);
    $fetch_stmt->bind_param("i", $request_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $subadmin_id = $row['subadmin_id'];
        
        // Update request status to rejected (2)
        $sql = "UPDATE requests SET status = 2 WHERE request_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        
        if ($stmt->execute()) {
            // Insert notification for subadmin
            $title = "Election Request Rejected";
            $message = "Your election request (ID: $request_id) has been rejected by an admin.";
            $status = "unread";
            
            $notif_sql = "INSERT INTO notifications (user_id, title, message, status) 
                         VALUES (?, ?, ?, ?)";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("isss", $subadmin_id, $title, $message, $status);
            
            if ($notif_stmt->execute()) {
                $final_message = "Request rejected and notification sent successfully!";
            } else {
                $final_message = "Request rejected, but failed to send notification: " . $conn->error;
            }
            
            $notif_stmt->close();
        } else {
            $final_message = "Error rejecting request: " . $conn->error;
        }
        
        $stmt->close();
    } else {
        $final_message = "Request not found!";
    }
    
    $fetch_stmt->close();
    header("Location: view_requested_elections.php?message=" . urlencode($final_message));
    exit();
} else {
    header("Location: view_requested_elections.php?message=" . urlencode("Invalid request ID"));
    exit();
}

mysqli_close($conn);
?>