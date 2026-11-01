
<?php
session_start();
require_once "../config/database.php";

// Allow logged-in students only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch only proposals submitted by this student
$stmt = $conn->prepare("
    SELECT title, description, status, created_at
    FROM proposals
    WHERE proposed_by = ?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Proposals | GROUPWARE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="my-proposals-container">

    <div class="my-proposals-header">
        <div>
            <h1>My Proposals</h1>
            <p>Track the changes you have suggested to your team.</p>
        </div>

        <a href="proposals.php" class="new-proposal-btn">
            + New Proposal
        </a>
    </div>

    <div class="proposal-summary">
        <h3><?php echo count($proposals); ?></h3>
        <p>Total Submitted Proposals</p>
    </div>

    <?php if (count($proposals) > 0): ?>

        <div class="proposal-list">

            <?php foreach ($proposals as $proposal): ?>

                <div class="proposal-item">

                    <div class="proposal-item-top">
                        <h2>
                            <?php echo htmlspecialchars($proposal['title']); ?>
                        </h2>

                        <span class="status-badge status-<?php
                            echo htmlspecialchars($proposal['status']);
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($proposal['status'])); ?>
                        </span>
                    </div>

                    <p class="proposal-description">
                        <?php echo nl2br(htmlspecialchars($proposal['description'])); ?>
                    </p>

                    <div class="proposal-date">
                        Submitted:
                        <?php
                        echo date(
                            "M d, Y - h:i A",
                            strtotime($proposal['created_at'])
                        );
                        ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-proposals">
            <h2>No proposals yet</h2>
            <p>You haven't submitted any change proposals.</p>

            <a href="proposals.php" class="new-proposal-btn">
                Submit Your First Proposal
            </a>
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back-dashboard">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
