<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('admin', '../login.php');

$adminName = $_SESSION['full_name'];

// Query real system metrics
$totalUsers = 0;
$membersCount = 0;
$leadsCount = 0;
$teamsCount = 0;
$recentUsers = [];
$teamsList = [];
$systemActivities = [];

try {
    $totalUsers = (int) $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $membersCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $leadsCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
    $teamsCount = (int) $conn->query("SELECT COUNT(*) FROM teams")->fetchColumn();

    // Recent users
    $usersQuery = $conn->query("
        SELECT id, full_name, email, role
        FROM users
        ORDER BY id DESC
        LIMIT 10
    ");
    $recentUsers = $usersQuery->fetchAll(PDO::FETCH_ASSOC);

    // Teams with member counts
    $teamsQuery = $conn->query("
        SELECT t.id, t.team_name, COUNT(tm.user_id) AS member_count
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        GROUP BY t.id, t.team_name
        ORDER BY t.id ASC
    ");
    $teamsList = $teamsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Recent system activity
    $systemActivities = getAllRecentActivities($conn, 6);

} catch (PDOException $e) {
    error_log("Admin DB Query error: " . $e->getMessage());
}

$pageTitle = "System Administration";
$pageTitleHeader = "Admin Console";
$activeNav = "overview";
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
                    <h1 class="page-title">Workspace Administration Console</h1>
                    <p class="page-subtitle">Welcome, <?php echo htmlspecialchars($adminName); ?> &bull; Organization-wide accounts, permissions, and workspace management.</p>
                </div>
                <div class="page-actions">
                    <a href="create_user.php" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Member</span>
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Total Accounts</span>
                        <div class="stat-icon-wrapper blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-trend">Registered accounts</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Team Members</span>
                        <div class="stat-icon-wrapper green">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <polyline points="17 11 19 13 23 9"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--success);"><?php echo $membersCount; ?></div>
                    <div class="stat-trend">Active collaborators</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Project Leads</span>
                        <div class="stat-icon-wrapper amber">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--warning);"><?php echo $leadsCount; ?></div>
                    <div class="stat-trend">Managers &amp; leads</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Workspaces</span>
                        <div class="stat-icon-wrapper purple">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                <polyline points="2 17 12 22 22 17"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: #A855F7;"><?php echo $teamsCount; ?></div>
                    <div class="stat-trend">Configured project teams</div>
                </div>
            </section>

            <!-- User Accounts Table -->
            <div class="card" id="user-management" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">User Accounts Directory</h3>
                        <p class="card-subtitle">Active member profiles and security permissions</p>
                    </div>
                    <a href="create_user.php" class="btn btn-primary btn-sm">+ Add Member</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (!empty($recentUsers)): ?>
                        <div class="table-container" style="border: none; border-radius: 0;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Member Name</th>
                                        <th>Work Email</th>
                                        <th>Role Permission</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $u): ?>
                                        <tr>
                                            <td>
                                                <span style="font-family: var(--font-mono); color: var(--text-muted); font-size: 0.8rem;">#<?php echo (int) $u['id']; ?></span>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div class="user-avatar-circle" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                                        <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                                    </div>
                                                    <strong style="color: var(--text-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="color: var(--text-secondary); font-size: 0.84rem;"><?php echo htmlspecialchars($u['email']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="badge-role" style="background: rgba(59, 130, 246, 0.15); color: var(--primary); border-color: rgba(59, 130, 246, 0.3);">Admin</span>
                                                <?php elseif ($u['role'] === 'instructor'): ?>
                                                    <span class="badge-role" style="background: rgba(245, 158, 11, 0.15); color: var(--warning); border-color: rgba(245, 158, 11, 0.3);">Project Lead</span>
                                                <?php else: ?>
                                                    <span class="badge-role">Team Member</span>
                                                <?php endif; ?>
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
                            <h4 class="empty-state-title">No users found</h4>
                            <p class="empty-state-text">No accounts exist in the database.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teams & System Activity Grid -->
            <div class="grid-2">
                <!-- Teams Table -->
                <div class="card" id="team-management">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Team Workspaces</h3>
                            <p class="card-subtitle">Active project groups</p>
                        </div>
                        <span class="badge-role"><?php echo $teamsCount; ?> Workspaces</span>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (!empty($teamsList)): ?>
                            <div class="table-container" style="border: none; border-radius: 0;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Team Name</th>
                                            <th>Members</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teamsList as $team): ?>
                                            <tr>
                                                <td>
                                                    <span style="font-family: var(--font-mono); color: var(--text-muted); font-size: 0.8rem;">#<?php echo (int) $team['id']; ?></span>
                                                </td>
                                                <td>
                                                    <strong style="color: var(--text-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($team['team_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge-role"><?php echo (int) $team['member_count']; ?> Collaborators</span>
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
                                <h4 class="empty-state-title">No teams created</h4>
                                <p class="empty-state-text">Configure workspaces to organize project collaborators.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent System Activity -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">System Audit Log</h3>
                            <p class="card-subtitle">Recent administrative and user actions</p>
                        </div>
                        <a href="activity.php" class="btn btn-ghost btn-sm">Full Audit Trail &rarr;</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($systemActivities)): ?>
                            <ul class="activity-feed">
                                <?php foreach ($systemActivities as $act): ?>
                                    <li class="activity-item">
                                        <div class="activity-icon-badge">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <strong><?php echo htmlspecialchars($act['full_name'] ?? 'User'); ?>:</strong>
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
                                <h4 class="empty-state-title">No activity logged</h4>
                                <p class="empty-state-text">Actions will stream here in real-time.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
