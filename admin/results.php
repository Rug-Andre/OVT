<?php
include 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if election_id is provided in the URL
if (!isset($_GET['election_id']) || !is_numeric($_GET['election_id'])) {
    die("Error: No election ID provided. Please select an election from the dashboard.");
}

$election_id = intval($_GET['election_id']);

// Fetch results for the chosen election with all candidate info and total voters
$sql = "SELECT elections.election_name AS title, 
               candidates.candidate_id, 
               candidates.election_id, 
               candidates.candidate_name AS name, 
               candidates.position, 
               candidates.candidate_info, 
               candidates.candidate_image, 
               COUNT(votes.vote_id) AS vote_count,
               (SELECT COUNT(DISTINCT v.user_id) 
                FROM votes v 
                WHERE v.election_id = elections.election_id) AS total_voters
        FROM votes
        JOIN candidates ON votes.candidate_id = candidates.candidate_id
        JOIN elections ON votes.election_id = elections.election_id
        WHERE elections.election_id = ?
              AND elections.status = 1 
              AND NOW() BETWEEN elections.start_date AND elections.end_date
        GROUP BY elections.election_id, candidates.candidate_id
        ORDER BY elections.election_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $election_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("SQL Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            width: 100%;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-header {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }

        .card-body {
            padding: 20px 0;
        }

        .election-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .election-header h3 {
            color: #3498db;
            font-size: 24px;
            margin: 0;
        }

        .election-header p {
            color: #7f8c8d;
            font-size: 16px;
            margin: 5px 0 0;
        }

        .candidate-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .candidate-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 20px;
            background: #ecf0f1;
        }

        .candidate-details {
            flex: 1;
        }

        .candidate-details h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
        }

        .candidate-details p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 14px;
        }

        .vote-count {
            background: #2ecc71;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            margin-left: 20px;
        }

        .no-results {
            text-align: center;
            color: #7f8c8d;
            font-size: 16px;
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s ease;
            text-align: center;
        }

        .btn:hover {
            background: #2980b9;
        }

        @media (max-width: 768px) {
            .candidate-card {
                flex-direction: column;
                text-align: center;
            }

            .candidate-image {
                margin: 0 0 15px 0;
            }

            .vote-count {
                margin: 10px 0 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Election Results</h2>
            </div>
            <div class="card-body">
                <?php 
                if ($result->num_rows > 0) {
                    $current_election = '';
                    while ($row = $result->fetch_assoc()) {
                        // Display election header with total voters
                        if ($current_election !== $row['title']) {
                            $current_election = $row['title'];
                            echo '<div class="election-header">';
                            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                            echo '<p>Total Unique Voters: ' . $row['total_voters'] . '</p>';
                            echo '</div>';
                        }
                ?>
                        <div class="candidate-card">
                            <?php if (!empty($row['candidate_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['candidate_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                     class="candidate-image">
                            <?php else: ?>
                                <div class="candidate-image" style="background: #ecf0f1; text-align: center; line-height: 100px; color: #7f8c8d;">No Image</div>
                            <?php endif; ?>
                            <div class="candidate-details">
                                <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                                <p><strong>Position:</strong> <?php echo htmlspecialchars($row['position']); ?></p>
                                <p><strong>Info:</strong> <?php echo htmlspecialchars($row['candidate_info']); ?></p>
                                <p><strong>Candidate ID:</strong> <?php echo htmlspecialchars($row['candidate_id']); ?> | 
                                   <strong>Election ID:</strong> <?php echo htmlspecialchars($row['election_id']); ?></p>
                            </div>
                            <span class="vote-count"><?php echo $row['vote_count']; ?> Votes</span>
                        </div>
                <?php 
                    }
                } else {
                    echo '<p class="no-results">No results available for this election.</p>';
                }
                ?>
                <a href="user_dashboard.php" class="btn">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
$stmt->close();
mysqli_close($conn); 
?>