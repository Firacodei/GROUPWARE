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
$isVoteSuccess = false;

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

                $message = "Your vote has been recorded successfully!";
                $isVoteSuccess = true;
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
    <title>Proposal Approvals | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="approval-container">

    <header class="approval-header">
        <div>
            <h1>Proposal Approvals</h1>
            <p>Review change requests proposed by your team members and cast your vote.</p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="dashboard.php" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Dashboard
            </a>
            <a href="proposals.php" class="btn btn-primary">
                + New Proposal
            </a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert <?php echo $isVoteSuccess ? 'alert-success' : 'alert-warning'; ?>" role="alert">
            <?php if ($isVoteSuccess): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($proposals): ?>

        <?php foreach ($proposals as $proposal): ?>

            <article class="approval-card">

                <div class="proposal-item-top">
                    <div>
                        <h2><?php echo htmlspecialchars($proposal["title"]); ?></h2>
                        <div class="approval-proposer" style="margin-top: 4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Proposed by: <strong><?php echo htmlspecialchars($proposal["proposer"]); ?></strong></span>
                            <span style="color: var(--text-muted);">&bull;</span>
                            <span style="color: var(--text-muted);"><?php echo date("M d, Y", strtotime($proposal["created_at"])); ?></span>
                        </div>
                    </div>
                    <span class="status-badge status-pending">Pending Vote</span>
                </div>

                <p class="approval-description">
                    <?php echo nl2br(htmlspecialchars($proposal["description"])); ?>
                </p>

                <div class="vote-counts">
                    <div class="vote-count-chip approved">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Approved: <strong><?php echo (int) $proposal["approvals"]; ?></strong></span>
                    </div>

                    <div class="vote-count-chip rejected">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Rejected: <strong><?php echo (int) $proposal["rejections"]; ?></strong></span>
                    </div>
                </div>

                <?php if ($proposal["has_voted"]): ?>

                    <div class="already-voted">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>You have already cast your vote on this proposal.</span>
                    </div>

                <?php else: ?>

                    <form method="POST" action="" class="vote-actions">

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
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Approve Proposal
                        </button>

                        <button
                            type="submit"
                            name="vote"
                            value="reject"
                            class="reject-btn"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Reject Proposal
                        </button>

                    </form>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="approval-empty">
            <div class="empty-icon" style="margin-bottom: 14px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h2>No pending proposals to review</h2>
            <p>All change proposals submitted by other team members have been reviewed.</p>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

    <?php endif; ?>

    <div>
        <a href="dashboard.php" class="approval-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>
