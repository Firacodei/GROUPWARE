<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('admin', '../login.php');

$totalUsers = 0;
$studentsCount = 0;
$instructorsCount = 0;
$adminsCount = 0;
$teamsCount = 0;
$totalTasks = 0;
$completedTasks = 0;
$totalProposals = 0;
$approvedProposals = 0;

try {
    $totalUsers = (int) $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $studentsCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $instructorsCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
    $adminsCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $teamsCount = (int) $conn->query("SELECT COUNT(*) FROM teams")->fetchColumn();

    $taskRow = $conn->query("
        SELECT COUNT(*) AS total, SUM(status = 'completed') AS completed FROM tasks
    ")->fetch(PDO::FETCH_ASSOC);
    if ($taskRow) {
        $totalTasks = (int) $taskRow['total'];
        $completedTasks = (int) ($taskRow['completed'] ?? 0);
    }

    $propRow = $conn->query("
        SELECT COUNT(*) AS total, SUM(status = 'approved') AS approved FROM proposals
    ")->fetch(PDO::FETCH_ASSOC);
    if ($propRow) {
        $totalProposals = (int) $propRow['total'];
        $approvedProposals = (int) ($propRow['approved'] ?? 0);
    }
} catch (PDOException $e) {}

$taskCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$proposalApprovalRate = $totalProposals > 0 ? round(($approvedProposals / $totalProposals) * 100) : 0;

$pageTitle = "Enterprise Analytics";
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
                    <h1 class="page-title">Enterprise System Analytics</h1>
                    <p class="page-subtitle">Platform adoption, workspace health, and organization-wide execution velocity.</p>
                </div>
            </div>

            <section class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Total Platform Users</span>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-trend"><?php echo $studentsCount; ?> collaborators &bull; <?php echo $instructorsCount; ?> leads</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">System Task Completion</span>
                    <div class="stat-value" style="color: var(--success);"><?php echo $taskCompletionRate; ?>%</div>
                    <div class="stat-trend"><?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> finished</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Proposal Consensus Rate</span>
                    <div class="stat-value" style="color: var(--primary);"><?php echo $proposalApprovalRate; ?>%</div>
                    <div class="stat-trend"><?php echo $approvedProposals; ?> approved of <?php echo $totalProposals; ?></div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Total Workspaces</span>
                    <div class="stat-value" style="color: #A855F7;"><?php echo $teamsCount; ?></div>
                    <div class="stat-trend">Configured project teams</div>
                </div>
            </section>

            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">User Role Distribution</h3>
                            <p class="card-subtitle">Active accounts by privilege level</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 20px;">
                            <?php $mPct = $totalUsers > 0 ? round(($studentsCount / $totalUsers) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Team Members (<?php echo $studentsCount; ?>)</span>
                                <strong style="color: var(--primary);"><?php echo $mPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill blue" style="width: <?php echo $mPct; ?>%;"></div>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <?php $lPct = $totalUsers > 0 ? round(($instructorsCount / $totalUsers) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Project Leads (<?php echo $instructorsCount; ?>)</span>
                                <strong style="color: var(--warning);"><?php echo $lPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill amber" style="width: <?php echo $lPct; ?>%;"></div>
                            </div>
                        </div>

                        <div>
                            <?php $aPct = $totalUsers > 0 ? round(($adminsCount / $totalUsers) * 100) : 0; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Administrators (<?php echo $adminsCount; ?>)</span>
                                <strong style="color: #A855F7;"><?php echo $aPct; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="background: #A855F7; width: <?php echo $aPct; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Milestone Completion &amp; Proposals</h3>
                            <p class="card-subtitle">Global platform throughput</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Tasks Delivered (<?php echo $completedTasks; ?> / <?php echo $totalTasks; ?>)</span>
                                <strong style="color: var(--success);"><?php echo $taskCompletionRate; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill green" style="width: <?php echo $taskCompletionRate; ?>%;"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 6px;">
                                <span>Consensus Reached (<?php echo $approvedProposals; ?> / <?php echo $totalProposals; ?>)</span>
                                <strong style="color: var(--primary);"><?php echo $proposalApprovalRate; ?>%</strong>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill blue" style="width: <?php echo $proposalApprovalRate; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
