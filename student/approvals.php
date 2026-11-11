<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('student', '../login.php');

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
    die("You are not assigned to a team workspace.");
}

$teamId = (int) $team["team_id"];

// Process a vote and calculate the majority decision
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
            $conn->beginTransaction();

            // Lock the proposal while processing its vote
            $check = $conn->prepare("
                SELECT id, title, proposed_by
                FROM proposals
                WHERE id = ?
                  AND team_id = ?
                  AND proposed_by <> ?
                  AND status = 'pending'
                FOR UPDATE
            ");

            $check->execute([$proposalId, $teamId, $userId]);
            $proposal = $check->fetch(PDO::FETCH_ASSOC);

            if (!$proposal) {
                throw new Exception("You cannot vote on this proposal.");
            }

            // Record the student's vote
            $insert = $conn->prepare("
                INSERT INTO proposal_votes
                (proposal_id, user_id, vote)
                VALUES (?, ?, ?)
            ");

            $insert->execute([$proposalId, $userId, $vote]);

            // Count eligible voters, excluding the proposer
            $eligibleQuery = $conn->prepare("
                SELECT COUNT(*)
                FROM team_members
                WHERE team_id = ?
                  AND user_id <> ?
            ");

            $eligibleQuery->execute([
                $teamId,
                (int) $proposal["proposed_by"]
            ]);

            $eligibleVoters = (int) $eligibleQuery->fetchColumn();

            // Calculate majority threshold
            $majority = (int) floor($eligibleVoters / 2) + 1;

            // Count approval and rejection votes
            $voteQuery = $conn->prepare("
                SELECT
                    SUM(CASE WHEN vote = 'approve' THEN 1 ELSE 0 END) AS approvals,
                    SUM(CASE WHEN vote = 'reject' THEN 1 ELSE 0 END) AS rejections
                FROM proposal_votes
                WHERE proposal_id = ?
            ");

            $voteQuery->execute([$proposalId]);
            $results = $voteQuery->fetch(PDO::FETCH_ASSOC);

            $approvals = (int) $results["approvals"];
            $rejections = (int) $results["rejections"];

            // Determine final status
            $newStatus = "pending";

            if ($approvals >= $majority) {
                $newStatus = "approved";
            } elseif ($rejections >= $majority) {
                $newStatus = "rejected";
            }

            // Update proposal status if majority reached
            $update = $conn->prepare("
                UPDATE proposals
                SET status = ?
                WHERE id = ?
            ");

            $update->execute([$newStatus, $proposalId]);

            $conn->commit();

            logActivity(
                $conn,
                $userId,
                $teamId,
                'proposal_voted',
                "Voted to $vote proposal: \"{$proposal['title']}\"",
                'proposal',
                $proposalId
            );

            if ($newStatus === 'approved') {
                logActivity(
                    $conn,
                    $userId,
                    $teamId,
                    'proposal_approved',
                    "Proposal \"{$proposal['title']}\" reached majority approval ($approvals/$majority votes)",
                    'proposal',
                    $proposalId
                );
            } elseif ($newStatus === 'rejected') {
                logActivity(
                    $conn,
                    $userId,
                    $teamId,
                    'proposal_rejected',
                    "Proposal \"{$proposal['title']}\" was rejected by majority vote",
                    'proposal',
                    $proposalId
                );
            }

            if ($newStatus === "approved") {
                $message = "Vote recorded. Proposal has officially reached majority approval!";
            } elseif ($newStatus === "rejected") {
                $message = "Vote recorded. Proposal has been rejected by majority decision.";
            } else {
                $message = "Your vote was recorded successfully. Waiting for remaining teammate votes.";
            }

            $isVoteSuccess = true;

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            if ($e->getCode() === "23000") {
                $message = "You have already cast a vote on this proposal.";
            } else {
                error_log("Voting error: " . $e->getMessage());
                $message = "A database error occurred. Please try again.";
            }

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $message = $e->getMessage();
        }
    }
}

// Fetch total eligible team voters count
$totalTeamCountQuery = $conn->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ?");
$totalTeamCountQuery->execute([$teamId]);
$totalTeamMembers = (int) $totalTeamCountQuery->fetchColumn();

// Fetch pending proposals and vote counts
$stmt = $conn->prepare("
    SELECT
        p.id,
        p.title,
        p.description,
        p.created_at,
        p.proposed_by,
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

$pageTitle = "Proposal Approvals";
$pageTitleHeader = "Approvals";
$activeNav = "approvals";
$rootUrl = "../";

include __DIR__ . "/../includes/layout_head.php";
?>

<div class="app-shell">
    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <div class="app-main">
        <?php include __DIR__ . "/../includes/topbar.php"; ?>

        <main class="app-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Proposal Approvals &amp; Consensus</h1>
                    <p class="page-subtitle">Evaluate peer suggestions, review architectural justifications, and cast your decision.</p>
                </div>
                <div class="page-actions">
                    <a href="proposals.php" class="btn btn-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Submit Proposal</span>
                    </a>
                </div>
            </div>

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

            <?php if (!empty($proposals)): ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($proposals as $prop): ?>
                        <?php
                            $approvals = (int) $prop["approvals"];
                            $rejections = (int) $prop["rejections"];
                            $eligibleVoters = max(1, $totalTeamMembers - 1);
                            $majorityThreshold = (int) floor($eligibleVoters / 2) + 1;
                            $approvalPct = min(100, round(($approvals / $majorityThreshold) * 100));
                        ?>
                        <article class="card" style="padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 14px; flex-wrap: wrap;">
                                <div>
                                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">
                                        <?php echo htmlspecialchars($prop["title"]); ?>
                                    </h2>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <div class="user-avatar-circle" style="width: 22px; height: 22px; font-size: 0.65rem;">
                                                <?php echo strtoupper(substr($prop["proposer"], 0, 1)); ?>
                                            </div>
                                            <span>Proposed by <strong><?php echo htmlspecialchars($prop["proposer"]); ?></strong></span>
                                        </div>
                                        <span>&bull;</span>
                                        <span><?php echo date("M d, Y &bull; g:i A", strtotime($prop["created_at"])); ?></span>
                                    </div>
                                </div>

                                <span class="status-badge status-pending">Pending Consensus</span>
                            </div>

                            <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; margin-bottom: 18px;">
                                <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap; margin: 0;"><?php echo htmlspecialchars($prop["description"]); ?></p>
                            </div>

                            <!-- Majority Progress & Consensus Tracker -->
                            <div class="voting-progress-box">
                                <div class="voting-stats-row">
                                    <span style="font-weight: 600; color: var(--text-primary);">
                                        Majority Progress: <?php echo $approvals; ?> of <?php echo $majorityThreshold; ?> required approvals
                                    </span>
                                    <span style="color: var(--text-muted); font-size: 0.78rem;">
                                        <?php echo $rejections; ?> rejection(s) recorded
                                    </span>
                                </div>
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill green" style="width: <?php echo $approvalPct; ?>%;"></div>
                                </div>
                            </div>

                            <!-- Voting Action Buttons -->
                            <?php if ($prop["has_voted"]): ?>
                                <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: var(--radius-md); color: var(--primary); font-size: 0.84rem; font-weight: 600;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span>Your vote has been cast. Waiting for the remaining team member decisions.</span>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="approvals.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                                    <input type="hidden" name="proposal_id" value="<?php echo (int) $prop["id"]; ?>">

                                    <button type="submit" name="vote" value="approve" class="btn btn-success">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span>Approve Proposal</span>
                                    </button>

                                    <button type="submit" name="vote" value="reject" class="btn btn-danger" data-confirm="Are you sure you want to vote to reject this proposal?">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        <span>Reject Proposal</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h3 class="empty-state-title">No pending proposals awaiting your vote</h3>
                        <p class="empty-state-text">All active proposals submitted by your teammates have reached a decision or have already been voted on by you.</p>
                        <a href="dashboard.php" class="btn btn-secondary">Return to Dashboard</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
