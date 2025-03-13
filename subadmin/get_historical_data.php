<?php
include 'db.php';

header('Content-Type: application/json');

if (isset($_POST['subadmin_id'])) {
    $subadmin_id = $_POST['subadmin_id'];
    $days = 7;
    $historical_data = [
        'approved' => [],
        'rejected' => [],
        'pending' => [],
        'notifications' => []
    ];
    $labels = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M d', strtotime("-$i days"));

        // Approved requests
        $approved_query = "SELECT COUNT(*) AS count FROM requests WHERE subadmin_id = ? AND status = 1 AND DATE(created_at) = ?";
        $approved_stmt = $conn->prepare($approved_query);
        $approved_stmt->bind_param("is", $subadmin_id, $date);
        $approved_stmt->execute();
        $approved_result = $approved_stmt->get_result();
        $historical_data['approved'][] = $approved_result->fetch_assoc()['count'] ?: 0;

        // Rejected requests
        $rejected_query = "SELECT COUNT(*) AS count FROM requests WHERE subadmin_id = ? AND status = 2 AND DATE(created_at) = ?";
        $rejected_stmt = $conn->prepare($rejected_query);
        $rejected_stmt->bind_param("is", $subadmin_id, $date);
        $rejected_stmt->execute();
        $rejected_result = $rejected_stmt->get_result();
        $historical_data['rejected'][] = $rejected_result->fetch_assoc()['count'] ?: 0;

        // Pending requests
        $pending_query = "SELECT COUNT(*) AS count FROM requests WHERE subadmin_id = ? AND status = 0 AND DATE(created_at) = ?";
        $pending_stmt = $conn->prepare($pending_query);
        $pending_stmt->bind_param("is", $subadmin_id, $date);
        $pending_stmt->execute();
        $pending_result = $pending_stmt->get_result();
        $historical_data['pending'][] = $pending_result->fetch_assoc()['count'] ?: 0;

        // Notifications
        $notification_query = "SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND DATE(created_at) = ?";
        $notification_stmt = $conn->prepare($notification_query);
        $notification_stmt->bind_param("is", $subadmin_id, $date);
        $notification_stmt->execute();
        $notification_result = $notification_stmt->get_result();
        $historical_data['notifications'][] = $notification_result->fetch_assoc()['count'] ?: 0;
    }

    $datasets = [
        [
            'label' => 'Approved Requests',
            'data' => $historical_data['approved'],
            'borderColor' => '#2ecc71',
            'backgroundColor' => 'rgba(46, 204, 113, 0.2)',
            'fill' => true,
            'tension' => 0.4
        ],
        [
            'label' => 'Rejected Requests',
            'data' => $historical_data['rejected'],
            'borderColor' => '#e74c3c',
            'backgroundColor' => 'rgba(231, 76, 60, 0.2)',
            'fill' => true,
            'tension' => 0.4
        ],
        [
            'label' => 'Pending Requests',
            'data' => $historical_data['pending'],
            'borderColor' => '#f39c12',
            'backgroundColor' => 'rgba(243, 156, 18, 0.2)',
            'fill' => true,
            'tension' => 0.4
        ],
        [
            'label' => 'Notifications',
            'data' => $historical_data['notifications'],
            'borderColor' => '#007bff',
            'backgroundColor' => 'rgba(0, 123, 255, 0.2)',
            'fill' => true,
            'tension' => 0.4
        ]
    ];

    echo json_encode(['labels' => $labels, 'datasets' => $datasets]);
}

mysqli_close($conn);
?>