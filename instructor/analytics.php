<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('instructor', '../login.php');

$totalTeams = 0;
$totalStudents = 0;
$totalTasks = 0;
$completedTasks = 0;
$inProgressTasks = 0;
$pendingTasks = 0;
$totalProposals = 0;
$approvedProposals = 0;
$rejectedProposals = 0;
$pendingProposals = 0;

try {
    $totalTeams = (int) $conn->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    $totalStudents = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

    $taskRow = $conn->query("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'completed') AS completed,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'pending') AS pending
        FROM tasks
    ")->fetch(PDO::FETCH_ASSOC);

    if ($taskRow) {
        $totalTasks = (int) $taskRow['total'];
        $completedTasks = (int) ($taskRow['completed'] ?? 0);
        $inProgressTasks = (int) ($taskRow['in_progress'] ?? 0);
        $pendingTasks = (int) ($taskRow['pending'] ?? 0);
    }

    $propRow = $conn->query("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'approved') AS approved,
            SUM(status = 'rejected') AS rejected,
            SUM(status = 'pending') AS pending
        FROM proposals
    ")->fetch(PDO::FETCH_ASSOC);

    if ($propRow) {
        $totalProposals = (int) $propRow['total'];
        $approvedProposals = (int) ($propRow['approved'] ?? 0);
        $rejectedProposals = (int) ($propRow['rejected'] ?? 0);
        $pendingProposals = (int) ($propRow['pending'] ?? 0);
    }
} catch (PDOException $e) {
    error_log("Analytics error: " . $e->getMessage());
}

$taskCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$proposalApprovalRate = $totalProposals > 0 ? round(($approvedProposals / $totalProposals) * 100) : 0;

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
                    <h1 class="page-title">Project &amp; Team Analytics</h1>
                    <p class="page-subtitle">Cross-workspace velocity, deliverable completion rates, and consensus metrics.</p>
                </div>
            </div>

            <section class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Task Completion Rate</span>
                    <div class="stat-value" style="color: var(--success);"><?php echo $taskCompletionRate; ?>%</div>
                    <div class="stat-trend"><?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> finished</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Proposal Approval Rate</span>
                    <div class="stat-value" style="color: var(--primary);"><?php echo $proposalApprovalRate; ?>%</div>
                    <div class="stat-trend"><?php echo $approvedProposals; ?> accepted by majority</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Collaborators Enrolled</span>
                    <div class="stat-value"><?php echo $totalStudents; ?></div>
                    <div class="stat-trend">Across <?php echo $totalTeams; ?> teams</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Pending Decisions</span>
                    <div class="stat-value" style="color: var(--warning);"><?php echo $pendingProposals; ?></div>
                    <div class="stat-trend">Open for voting</div>
                </div>
            </section>

            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Overall Task Distribution</h3>
                            <p class="card-subtitle">Real-time deliverable statuses</p>
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
                            <?php $pendPct = $totalTasks > 0 ? round(($pendingTasks / $totalTasks) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Pending (<?php echo $pendingTasks; ?>)</span>
                                <strong style="color: var(--warning);"><?php echo $pendPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill amber" style="width: <?php echo $pendPct; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Proposal Consensus Outcomes</h3>
                            <p class="card-subtitle">Breakdown of proposals across teams</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 20px;">
                            <?php $appPct = $totalProposals > 0 ? round(($approvedProposals / $totalProposals) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Approved (<?php echo $approvedProposals; ?>)</span>
                                <strong style="color: var(--success);"><?php echo $appPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill green" style="width: <?php echo $appPct; ?>%;"></div>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <?php $pendPct2 = $totalProposals > 0 ? round(($pendingProposals / $totalProposals) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Pending Consensus (<?php echo $pendingProposals; ?>)</span>
                                <strong style="color: var(--warning);"><?php echo $pendPct2; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill amber" style="width: <?php echo $pendPct2; ?>%;"></div>
                            </div>
                        </div>

                        <div>
                            <?php $rejPct = $totalProposals > 0 ? round(($rejectedProposals / $totalProposals) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Rejected (<?php echo $rejectedProposals; ?>)</span>
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
