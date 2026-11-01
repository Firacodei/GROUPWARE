
<?php
session_start();
require_once "../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: ../index.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$message = "";

// Find the student's team
$teamQuery = $conn->prepare("
    SELECT team_id
    FROM team_members
    WHERE user_id = ?
    LIMIT 1
");
$teamQuery->execute([$userId]);
$team = $teamQuery->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    die("You are not assigned to a team.");
}

$teamId = (int) $team["team_id"];

// Process a vote
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $proposalId = filter_input(
        INPUT_POST,
        "proposal_id",
        FILTER_VALIDATE_INT
    );

    $vote = $_POST["vote"] ?? "";

    if (!$proposalId || !in_array($vote, ["approve", "reject"], true)) {
        $message = "Invalid vote request.";
    } else {
        try {
            // Check that proposal belongs to the student's team
            // and that the student did not create it.
            $check = $conn->prepare("
                SELECT id
                FROM proposals
                WHERE id = ?
                  AND team_id = ?
                  AND proposed_by <> ?
                  AND status = 'pending'
            ");
            $check->execute([$proposalId, $teamId, $userId]);

            if (!$check->fetch()) {
                $message = "You cannot vote on this proposal.";
            } else {
                $insert = $conn->prepare("
                    INSERT INTO proposal_votes
                    (proposal_id, user_id, vote)
                    VALUES (?, ?, ?)
                ");

                $insert->execute([$proposalId, $userId, $vote]);

                $message = "Your vote was recorded successfully!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $message = "You have already voted on this proposal.";
            } else {
                error_log($e->getMessage());
                $message = "Something went wrong. Please try again.";
            }
        }
    }
}

// Fetch pending proposals and vote counts
$stmt = $conn->prepare("
    SELECT
        p.id,
        p.title,
        p.description,
        p.created_at,
        u.full_name AS proposer,
        (SELECT COUNT(*)
         FROM proposal_votes pv
         WHERE pv.proposal_id = p.id
           AND pv.vote = 'approve') AS approvals,
        (SELECT COUNT(*)
         FROM proposal_votes pv
         WHERE pv.proposal_id = p.id
           AND pv.vote = 'reject') AS rejections,
        (SELECT COUNT(*)
         FROM proposal_votes pv
         WHERE pv.proposal_id = p.id
           AND pv.user_id = ?) AS has_voted
    FROM proposals p
    JOIN users u ON p.proposed_by = u.id
    WHERE p.team_id = ?
      AND p.status = 'pending'
      AND p.proposed_by <> ?
    ORDER BY p.created_at DESC
");

$stmt->execute([$userId, $teamId, $userId]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvals | GROUPWARE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="approval-container">

    <header class="approval-header">
        <div>
            <h1>Proposal Approvals</h1>
            <p>Review and vote on your team's proposed changes.</p>
        </div>

        <a href="dashboard.php" class="approval-back">
            Back to Dashboard
        </a>
    </header>

    <?php if ($message): ?>
        <div class="approval-message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($proposals): ?>

        <?php foreach ($proposals as $proposal): ?>

            <article class="approval-card">

                <h2>
                    <?php echo htmlspecialchars($proposal["title"]); ?>
                </h2>

                <p class="approval-proposer">
                    Proposed by:
                    <?php echo htmlspecialchars($proposal["proposer"]); ?>
                </p>

                <p class="approval-description">
                    <?php echo nl2br(
                        htmlspecialchars($proposal["description"])
                    ); ?>
                </p>

                <div class="vote-counts">
                    <span>Approved: <?php echo $proposal["approvals"]; ?></span>
                    <span>Rejected: <?php echo $proposal["rejections"]; ?></span>
                </div>

                <?php if ($proposal["has_voted"]): ?>

                    <p class="already-voted">
                        You have already voted on this proposal.
                    </p>

                <?php else: ?>

                    <form method="POST" class="vote-actions">

                        <input
                            type="hidden"
                            name="proposal_id"
                            value="<?php echo (int) $proposal["id"]; ?>"
                        >

                        <button
                            type="submit"
                            name="vote"
                            value="approve"
                            class="approve-btn"
                        >
                            Approve
                        </button>

                        <button
                            type="submit"
                            name="vote"
                            value="reject"
                            class="reject-btn"
                        >
                            Reject
                        </button>

                    </form>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="approval-empty">
            <h2>No proposals to review</h2>
            <p>There are currently no pending proposals from other team members.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
