<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('student', '../login.php');

$studentId = (int) $_SESSION["user_id"];

// Find team
$teamQuery = $conn->prepare("SELECT team_id FROM team_members WHERE user_id = ? LIMIT 1");
$teamQuery->execute([$studentId]);
$team = $teamQuery->fetch(PDO::FETCH_ASSOC);

$totalTasks = 0;
$completedTasks = 0;
$inProgressTasks = 0;
$pendingTasks = 0;
$myProposalsTotal = 0;
$myProposalsApproved = 0;
$myProposalsRejected = 0;
$myProposalsPending = 0;
$teamProposalsTotal = 0;

if ($team) {
    $teamId = (int) $team['team_id'];

    // Tasks stats for this user
    try {
        $taskStats = $conn->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'completed') AS completed,
                SUM(status = 'in_progress') AS in_progress,
                SUM(status = 'pending') AS pending
            FROM tasks
            WHERE assigned_to = ?
        ");
        $taskStats->execute([$studentId]);
        $row = $taskStats->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $totalTasks = (int) $row['total'];
            $completedTasks = (int) ($row['completed'] ?? 0);
            $inProgressTasks = (int) ($row['in_progress'] ?? 0);
            $pendingTasks = (int) ($row['pending'] ?? 0);
        }
    } catch (PDOException $e) {}

    // Proposals stats for this user
    try {
        $propStats = $conn->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'approved') AS approved,
                SUM(status = 'rejected') AS rejected,
                SUM(status = 'pending') AS pending
            FROM proposals
            WHERE proposed_by = ?
        ");
        $propStats->execute([$studentId]);
        $pRow = $propStats->fetch(PDO::FETCH_ASSOC);
        if ($pRow) {
            $myProposalsTotal = (int) $pRow['total'];
            $myProposalsApproved = (int) ($pRow['approved'] ?? 0);
            $myProposalsRejected = (int) ($pRow['rejected'] ?? 0);
            $myProposalsPending = (int) ($pRow['pending'] ?? 0);
        }

        $teamPropStmt = $conn->prepare("SELECT COUNT(*) FROM proposals WHERE team_id = ?");
        $teamPropStmt->execute([$teamId]);
        $teamProposalsTotal = (int) $teamPropStmt->fetchColumn();
    } catch (PDOException $e) {}
}

$taskCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$proposalApprovalRate = $myProposalsTotal > 0 ? round(($myProposalsApproved / $myProposalsTotal) * 100) : 0;

$pageTitle = "Analytics";
$pageTitleHeader = "Analytics";
$activeNav = "analytics";
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
                    <h1 class="page-title">Personal Contribution Analytics</h1>
                    <p class="page-subtitle">Real-time performance metrics, task velocity, and consensus acceptance rates.</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <section class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Task Completion Rate</span>
                    <div class="stat-value" style="color: var(--success);"><?php echo $taskCompletionRate; ?>%</div>
                    <div class="stat-trend"><?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> tasks finished</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Proposal Approval Rate</span>
                    <div class="stat-value" style="color: var(--primary);"><?php echo $proposalApprovalRate; ?>%</div>
                    <div class="stat-trend"><?php echo $myProposalsApproved; ?> accepted by majority vote</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Active Tasks In Progress</span>
                    <div class="stat-value" style="color: var(--info);"><?php echo $inProgressTasks; ?></div>
                    <div class="stat-trend"><?php echo $pendingTasks; ?> pending commencement</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Total Team Proposals</span>
                    <div class="stat-value"><?php echo $teamProposalsTotal; ?></div>
                    <div class="stat-trend">Workspace collaborative initiatives</div>
                </div>
            </section>

            <!-- Detailed Visual Distributions -->
            <div class="grid-2">
                <!-- Task Progress Card -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Task Completion Distribution</h3>
                            <p class="card-subtitle">Real breakdown of your individual tasks</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Completed (<?php echo $completedTasks; ?>)</span>
                                <strong style="color: var(--success);"><?php echo $taskCompletionRate; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill green" style="width: <?php echo $taskCompletionRate; ?>%;"></div>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <?php $inProgPct = $totalTasks > 0 ? round(($inProgressTasks / $totalTasks) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>In Progress (<?php echo $inProgressTasks; ?>)</span>
                                <strong style="color: var(--primary);"><?php echo $inProgPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill blue" style="width: <?php echo $inProgPct; ?>%;"></div>
                            </div>
                        </div>

                        <div>
                            <?php $pendingPct = $totalTasks > 0 ? round(($pendingTasks / $totalTasks) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Pending (<?php echo $pendingTasks; ?>)</span>
                                <strong style="color: var(--warning);"><?php echo $pendingPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill amber" style="width: <?php echo $pendingPct; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Proposal Acceptance Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Proposal Acceptance Breakdown</h3>
                            <p class="card-subtitle">Review outcomes of your submitted proposals</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 20px;">
                            <?php $appPct = $myProposalsTotal > 0 ? round(($myProposalsApproved / $myProposalsTotal) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Approved by Consensus (<?php echo $myProposalsApproved; ?>)</span>
                                <strong style="color: var(--success);"><?php echo $appPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill green" style="width: <?php echo $appPct; ?>%;"></div>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <?php $pendPct = $myProposalsTotal > 0 ? round(($myProposalsPending / $myProposalsTotal) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Pending Peer Voting (<?php echo $myProposalsPending; ?>)</span>
                                <strong style="color: var(--warning);"><?php echo $pendPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill amber" style="width: <?php echo $pendPct; ?>%;"></div>
                            </div>
                        </div>

                        <div>
                            <?php $rejPct = $myProposalsTotal > 0 ? round(($myProposalsRejected / $myProposalsTotal) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Rejected by Majority (<?php echo $myProposalsRejected; ?>)</span>
                                <strong style="color: var(--danger);"><?php echo $rejPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="background: var(--danger); width: <?php echo $rejPct; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
