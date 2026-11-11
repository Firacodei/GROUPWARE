<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('student', '../login.php');

$userId = (int) $_SESSION['user_id'];
$message = "";
$isSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === "" || $description === "") {
        $message = "Please complete all required fields.";
    } elseif (strlen($title) > 150) {
        $message = "Proposal title must be 150 characters or fewer.";
    } else {
        // Find user's team
        $teamQuery = $conn->prepare("SELECT team_id FROM team_members WHERE user_id = ? LIMIT 1");
        $teamQuery->execute([$userId]);
        $team = $teamQuery->fetch(PDO::FETCH_ASSOC);

        if ($team) {
            $teamId = (int) $team['team_id'];
            $stmt = $conn->prepare("
                INSERT INTO proposals (team_id, proposed_by, title, description)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$teamId, $userId, $title, $description]);
            $newProposalId = (int) $conn->lastInsertId();

            logActivity(
                $conn,
                $userId,
                $teamId,
                'proposal_created',
                "Submitted change proposal: \"$title\"",
                'proposal',
                $newProposalId
            );

            $message = "Your proposal has been submitted successfully and is now open for peer voting!";
            $isSuccess = true;
        } else {
            $message = "You are not assigned to a team workspace yet.";
        }
    }
}

$pageTitle = "Submit Proposal";
$pageTitleHeader = "Submit Proposal";
$activeNav = "proposals_new";
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
                    <h1 class="page-title">Submit Change Proposal</h1>
                    <p class="page-subtitle">Propose new features, architectural improvements, or adjustments for peer review.</p>
                </div>
                <div class="page-actions">
                    <a href="my_proposals.php" class="btn btn-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span>View My Proposals</span>
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $isSuccess ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                    <?php if ($isSuccess): ?>
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

            <div class="card" style="max-width: 820px;">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Proposal Details</h2>
                        <p class="card-subtitle">Clearly articulate your proposed change so teammates can cast an informed vote.</p>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="proposals.php">
                        <div class="form-group">
                            <label for="title" class="form-label">Proposal Title</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control"
                                maxlength="150"
                                placeholder="e.g. Integrate Redis caching layer for API performance"
                                value="<?php echo isset($_POST['title']) && !$isSuccess ? htmlspecialchars($_POST['title']) : ''; ?>"
                                required
                                autofocus
                            >
                            <span class="form-hint">A concise, descriptive summary of the proposal (up to 150 characters).</span>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Technical Rationale &amp; Scope</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-textarea"
                                rows="7"
                                placeholder="Explain why this change is necessary, architectural implications, potential risks, and expected outcomes..."
                                required
                            ><?php echo isset($_POST['description']) && !$isSuccess ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <span class="form-hint">Detailed descriptions receive 40% faster approvals during peer reviews.</span>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 26px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                                <span>Publish Proposal for Review</span>
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
