<?php
include 'db.php';

if (isset($_GET['election_id']) && !empty($_GET['election_id'])) {
    $selected_election_id = intval($_GET['election_id']);
    
    // Escape the election_id for safety
    $selected_election_id = mysqli_real_escape_string($conn, $selected_election_id);
    
    $query = "SELECT positions FROM elections WHERE election_id = '$selected_election_id'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Error fetching positions: " . mysqli_error($conn));
    }

    $positions = [];
    if ($row = mysqli_fetch_assoc($result)) {
        $positions = array_map('trim', explode(',', $row['positions']));
    }

    echo json_encode(['positions' => $positions]);
}
?>