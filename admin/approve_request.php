<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $admin_id = $_SESSION['user_id']; // Current admin's ID
    
    // Fetch request details
    $sql = "SELECT * FROM requests WHERE request_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Insert into elections table
        $insert_sql = "INSERT INTO elections (election_name, start_date, end_date, created_by, positions, requires_code) 
                      VALUES (?, ?, ?, ?, ?, 0)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssii", 
            $row['election_name'], 
            $row['start_date'], 
            $row['end_date'], 
            $admin_id,
            $row['positions']
        );
        
        if ($insert_stmt->execute()) {
            // Update request status to approved
            $update_sql = "UPDATE requests SET status = 1 WHERE request_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $request_id);
            $update_stmt->execute();
            
            // Insert notification for subadmin
            $subadmin_id = $row['subadmin_id'];
            $title = "Election Request Approved";
            $message = "Your election request (ID: $request_id) has been approved.";
            $status = "unread";
            
            $notif_sql = "INSERT INTO notifications (user_id, title, message, status) 
                         VALUES (?, ?, ?, ?)";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("isss", $subadmin_id, $title, $message, $status);
            
            if ($notif_stmt->execute()) {
                $final_message = "Request approved, election added, and notification sent successfully!";
            } else {
                $final_message = "Request approved and election added, but failed to send notification: " . $conn->error;
            }
            
            $notif_stmt->close();
            $update_stmt->close();
            $insert_stmt->close();
        } else {
            $final_message = "Error adding election: " . $conn->error;
            $insert_stmt->close();
        }
    } else {
        $final_message = "Request not found!";
    }
    
    $stmt->close();
    header("Location: view_requested_elections.php?message=" . urlencode($final_message));
    exit();
}
?>