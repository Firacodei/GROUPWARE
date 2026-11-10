<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('instructor', '../login.php');

$instructorName = $_SESSION["full_name"];
$instructorId = (int) $_SESSION["user_id"];

// Query real database metrics
$teamsCount = 0;
$membersCount = 0;
$totalProposals = 0;
$pendingProposals = 0;
$totalTasksCount = 0;
$teams = [];
$recentProposals = [];
$recentActivities = [];

try {
    // Monitored teams with member count
    $teamsQuery = $conn->query("
        SELECT t.id, t.team_name, COUNT(tm.user_id) AS member_count
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        GROUP BY t.id, t.team_name
        ORDER BY t.team_name ASC
    ");
    $teams = $teamsQuery->fetchAll(PDO::FETCH_ASSOC);
    $teamsCount = count($teams);

    // Active collaborators count
    $membersCount = (int) $conn->query("
        SELECT COUNT(*) FROM users WHERE role = 'student'
    ")->fetchColumn();

    // Total proposals and pending count
    $totalProposals = (int) $conn->query("
        SELECT COUNT(*) FROM proposals
    ")->fetchColumn();

    $pendingProposals = (int) $conn->query("
        SELECT COUNT(*) FROM proposals WHERE status = 'pending'
    ")->fetchColumn();

    // Total tasks count
    $totalTasksCount = (int) $conn->query("
        SELECT COUNT(*) FROM tasks
    ")->fetchColumn();

    // Recent proposals across all teams
    $recentProposalsQuery = $conn->query("
        SELECT p.id, p.title, p.status, p.created_at, t.team_name, u.full_name AS proposer
        FROM proposals p
        LEFT JOIN teams t ON p.team_id = t.id
        LEFT JOIN users u ON p.proposed_by = u.id
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $recentProposals = $recentProposalsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Recent activity
    $recentActivities = getAllRecentActivities($conn, 6);

} catch (PDOException $e) {
    error_log("Instructor dashboard DB error: " . $e->getMessage());
}

$pageTitle = "Project Lead Overview";
$pageTitleHeader = "Overview";
$activeNav = "overview";
$rootUrl = "../";

include __DIR__ . "/../includes/layout_head.php";
?>

<div class="app-shell">
    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <div class="app-main">
        <?php include __DIR__ . "/../includes/topbar.php"; ?>

        <main class="app-content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Project Oversight Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($instructorName); ?> &bull; Real-time team velocity and proposal monitoring.</p>
                </div>
                <div class="page-actions">
                    <a href="tasks.php" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Create &amp; Assign Task</span>
                    </a>
                    <a href="teams.php" class="btn btn-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        <span>Manage Teams (<?php echo $teamsCount; ?>)</span>
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Active Teams</span>
                        <div class="stat-icon-wrapper blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $teamsCount; ?></div>
                    <div class="stat-trend">Monitored workspaces</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Team Collaborators</span>
                        <div class="stat-icon-wrapper green">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <polyline points="17 11 19 13 23 9"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--success);"><?php echo $membersCount; ?></div>
                    <div class="stat-trend">Active registered members</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Total Tasks</span>
                        <div class="stat-icon-wrapper purple">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: #A855F7;"><?php echo $totalTasksCount; ?></div>
                    <div class="stat-trend">Tracked across teams</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Team Proposals</span>
                        <div class="stat-icon-wrapper amber">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--warning);"><?php echo $totalProposals; ?></div>
                    <div class="stat-trend"><?php echo $pendingProposals; ?> awaiting consensus vote</div>
                </div>
            </section>

            <!-- Teams & Proposals Grid -->
            <div class="grid-2">
                <!-- Teams Overview Table -->
                <div class="card" id="teams-overview">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Configured Teams</h3>
                            <p class="card-subtitle">Project groups and active enrollments</p>
                        </div>
                        <a href="teams.php" class="btn btn-ghost btn-sm">All Teams &rarr;</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (!empty($teams)): ?>
                            <div class="table-container" style="border: none; border-radius: 0;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Team Name</th>
                                            <th>ID</th>
                                            <th>Members</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teams as $t): ?>
                                            <tr>
                                                <td>
                                                    <strong style="color: var(--text-primary); font-size: 0.88rem;">
                                                        <?php echo htmlspecialchars($t['team_name']); ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span style="font-family: var(--font-mono); color: var(--text-muted); font-size: 0.8rem;">#<?php echo (int) $t['id']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge-role"><?php echo (int) $t['member_count']; ?> Members</span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-approved">Active</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <h4 class="empty-state-title">No teams created yet</h4>
                                <p class="empty-state-text">Workspaces will appear here once configured.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Proposal Monitoring Table -->
                <div class="card" id="proposals-monitoring">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Recent Change Proposals</h3>
                            <p class="card-subtitle">Cross-team change initiatives</p>
                        </div>
                        <a href="proposals.php" class="btn btn-ghost btn-sm">All Proposals &rarr;</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (!empty($recentProposals)): ?>
                            <div class="table-container" style="border: none; border-radius: 0;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Proposer / Team</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentProposals as $rp): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 600; font-size: 0.86rem; color: var(--text-primary);">
                                                        <?php echo htmlspecialchars($rp['title']); ?>
                                                    </div>
                                                    <span style="font-size: 0.72rem; color: var(--text-muted);">
                                                        <?php echo date("M d, Y", strtotime($rp['created_at'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-size: 0.82rem; color: var(--text-secondary);"><?php echo htmlspecialchars($rp['proposer'] ?? 'Member'); ?></div>
                                                    <span style="font-size: 0.72rem; color: var(--text-muted);"><?php echo htmlspecialchars($rp['team_name'] ?? 'General'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo htmlspecialchars($rp['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($rp['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <h4 class="empty-state-title">No proposals submitted</h4>
                                <p class="empty-state-text">Submitted team change proposals will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Activity Stream -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Live Workspace Activity</h3>
                        <p class="card-subtitle">Real-time team audit log across all projects</p>
                    </div>
                    <a href="activity.php" class="btn btn-ghost btn-sm">Full Activity Log &rarr;</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivities)): ?>
                        <ul class="activity-feed">
                            <?php foreach ($recentActivities as $act): ?>
                                <li class="activity-item">
                                    <div class="activity-icon-badge">
                                        <?php if (strpos($act['activity_type'], 'vote') !== false): ?>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline></svg>
                                        <?php elseif (strpos($act['activity_type'], 'task') !== false): ?>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path></svg>
                                        <?php else: ?>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <strong><?php echo htmlspecialchars($act['full_name'] ?? 'Member'); ?></strong>
                                            (<?php echo htmlspecialchars($act['team_name'] ?? 'Team'); ?>):
                                            <?php echo htmlspecialchars($act['description']); ?>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo date('M d, Y &bull; g:i:s A', strtotime($act['created_at'])); ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4 class="empty-state-title">No activity recorded</h4>
                            <p class="empty-state-text">Workspace events will appear here in real-time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
