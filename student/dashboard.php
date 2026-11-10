<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('student', '../login.php');

$studentName = $_SESSION["full_name"];
$studentId = (int) $_SESSION["user_id"];

// Find the student's team
$teamQuery = $conn->prepare("
    SELECT t.id, t.team_name
    FROM teams t
    JOIN team_members tm ON t.id = tm.team_id
    WHERE tm.user_id = ?
    LIMIT 1
");
$teamQuery->execute([$studentId]);
$team = $teamQuery->fetch(PDO::FETCH_ASSOC);

$teamMembers = [];
$pendingProposalsCount = 0;
$activeTasksCount = 0;
$completedTasksCount = 0;
$recentTasks = [];
$teamActivities = [];

if ($team) {
    $teamId = (int) $team["id"];

    // Team members
    $membersQuery = $conn->prepare("
        SELECT u.id, u.full_name, u.email, tm.joined_at
        FROM team_members tm
        JOIN users u ON tm.user_id = u.id
        WHERE tm.team_id = ?
        ORDER BY (u.id = ?) DESC, u.full_name ASC
    ");
    $membersQuery->execute([$teamId, $studentId]);
    $teamMembers = $membersQuery->fetchAll(PDO::FETCH_ASSOC);

    // Pending proposals count for the team
    try {
        $pQuery = $conn->prepare("
            SELECT COUNT(*) FROM proposals
            WHERE team_id = ? AND status = 'pending'
        ");
        $pQuery->execute([$teamId]);
        $pendingProposalsCount = (int) $pQuery->fetchColumn();
    } catch (PDOException $e) {
        $pendingProposalsCount = 0;
    }

    // Tasks counts & recent tasks for this student
    try {
        $activeTasksStmt = $conn->prepare("
            SELECT COUNT(*) FROM tasks
            WHERE assigned_to = ? AND status IN ('pending', 'in_progress')
        ");
        $activeTasksStmt->execute([$studentId]);
        $activeTasksCount = (int) $activeTasksStmt->fetchColumn();

        $completedTasksStmt = $conn->prepare("
            SELECT COUNT(*) FROM tasks
            WHERE assigned_to = ? AND status = 'completed'
        ");
        $completedTasksStmt->execute([$studentId]);
        $completedTasksCount = (int) $completedTasksStmt->fetchColumn();

        // Top 4 recent tasks
        $tasksListStmt = $conn->prepare("
            SELECT t.id, t.title, t.description, t.status, t.due_date, t.created_at,
                   tm.team_name, u.full_name AS assigner_name
            FROM tasks t
            LEFT JOIN teams tm ON t.team_id = tm.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.assigned_to = ?
            ORDER BY
                CASE WHEN t.status = 'pending' THEN 0
                     WHEN t.status = 'in_progress' THEN 1
                     ELSE 2 END,
                t.due_date ASC
            LIMIT 4
        ");
        $tasksListStmt->execute([$studentId]);
        $recentTasks = $tasksListStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback gracefully
        $activeTasksCount = 0;
        $completedTasksCount = 0;
        $recentTasks = [];
    }

    // Recent activity logs
    $teamActivities = getRecentTeamActivities($conn, $teamId, 6);
}

$pageTitle = "Team Member Overview";
$pageTitleHeader = "Dashboard";
$activeNav = "overview";
$rootUrl = "../";

include __DIR__ . "/../includes/layout_head.php";
?>

<div class="app-shell">
    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <div class="app-main">
        <?php include __DIR__ . "/../includes/topbar.php"; ?>

        <main class="app-content">
            <!-- Welcome Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($studentName); ?></h1>
                    <p class="page-subtitle">
                        <?php echo $team ? 'Workspace: <strong>' . htmlspecialchars($team['team_name']) . '</strong> &bull; All services synced and active.' : 'You have not been assigned to a team workspace yet.'; ?>
                    </p>
                </div>
                <div class="page-actions">
                    <a href="proposals.php" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>New Proposal</span>
                    </a>
                    <a href="approvals.php" class="btn btn-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                        </svg>
                        <span>Review Approvals (<?php echo $pendingProposalsCount; ?>)</span>
                    </a>
                </div>
            </div>

            <!-- Statistics Grid (Real DB Values) -->
            <section class="stats-grid" aria-label="Key workspace metrics">
                <!-- Total Workspace Projects / Team -->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Active Workspace</span>
                        <div class="stat-icon-wrapper blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                <polyline points="2 17 12 22 22 17"></polyline>
                                <polyline points="2 12 12 17 22 12"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $team ? '1' : '0'; ?></div>
                    <div class="stat-trend">
                        <span><?php echo $team ? htmlspecialchars($team['team_name']) : 'No team assigned'; ?></span>
                    </div>
                </div>

                <!-- Active Tasks -->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Active Tasks</span>
                        <div class="stat-icon-wrapper blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--primary);"><?php echo $activeTasksCount; ?></div>
                    <div class="stat-trend">
                        <span><?php echo $activeTasksCount > 0 ? 'Assigned and in-progress' : 'All tasks up to date'; ?></span>
                    </div>
                </div>

                <!-- Completed Tasks -->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Completed Tasks</span>
                        <div class="stat-icon-wrapper green">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--success);"><?php echo $completedTasksCount; ?></div>
                    <div class="stat-trend">
                        <span>Verified deliverables</span>
                    </div>
                </div>

                <!-- Pending Proposals -->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-label">Pending Proposals</span>
                        <div class="stat-icon-wrapper amber">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--warning);"><?php echo $pendingProposalsCount; ?></div>
                    <div class="stat-trend">
                        <span><?php echo $pendingProposalsCount > 0 ? 'Awaiting peer consensus' : 'All proposals resolved'; ?></span>
                    </div>
                </div>
            </section>

            <!-- Main Content Layout (Tasks Overview & Activity Stream) -->
            <div class="grid-3-1">
                <!-- Left Column: Tasks Overview & Proposals -->
                <div>
                    <!-- Assigned Tasks Section -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">My Assigned Tasks</h3>
                                <p class="card-subtitle">Immediate deliverables requiring your contribution</p>
                            </div>
                            <a href="tasks.php" class="btn btn-ghost btn-sm">View All Tasks &rarr;</a>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (!empty($recentTasks)): ?>
                                <div class="table-container" style="border: none; border-radius: 0;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Task Title</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th style="text-align: right;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentTasks as $task): ?>
                                                <?php
                                                    $isOverdue = $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div style="font-weight: 600; color: var(--text-primary);">
                                                            <?php echo htmlspecialchars($task['title']); ?>
                                                        </div>
                                                        <div style="font-size: 0.78rem; color: var(--text-muted); max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 2px;">
                                                            <?php echo htmlspecialchars($task['description']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span style="font-size: 0.82rem; color: <?php echo $isOverdue ? 'var(--danger)' : 'var(--text-secondary)'; ?>; font-weight: <?php echo $isOverdue ? '700' : '500'; ?>;">
                                                            <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No deadline'; ?>
                                                            <?php if ($isOverdue): ?> (Overdue)<?php endif; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo htmlspecialchars($task['status']); ?>">
                                                            <?php echo str_replace('_', ' ', htmlspecialchars($task['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td style="text-align: right;">
                                                        <a href="tasks.php" class="btn btn-secondary btn-sm">Update</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">No pending tasks</h4>
                                    <p class="empty-state-text">You are fully up to date on all assigned tasks. Check back when your team lead creates new milestones.</p>
                                    <a href="tasks.php" class="btn btn-secondary btn-sm">Open Task Board</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Team Workspace Members Section -->
                    <div class="card" id="team-section">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Team Workspace Members</h3>
                                <p class="card-subtitle"><?php echo $team ? htmlspecialchars($team['team_name']) : 'Project Collaborators'; ?></p>
                            </div>
                            <span class="badge-role"><?php echo count($teamMembers); ?> Collaborators</span>
                        </div>
                        <div class="card-body" style="padding: 16px 22px;">
                            <?php if (!empty($teamMembers)): ?>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px;">
                                    <?php foreach ($teamMembers as $member): ?>
                                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--bg-elevated); border: 1px solid var(--border); border-radius: var(--radius-md);">
                                            <div class="user-avatar-circle" style="width: 36px; height: 36px; font-size: 0.82rem;">
                                                <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                                            </div>
                                            <div style="overflow: hidden; flex: 1;">
                                                <div style="font-weight: 600; font-size: 0.86rem; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo htmlspecialchars($member['full_name']); ?>
                                                    <?php if ((int) $member['id'] === $studentId): ?>
                                                        <span style="font-size: 0.68rem; padding: 1px 6px; background: var(--primary-subtle); color: var(--primary); border-radius: 4px; margin-left: 4px;">You</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo htmlspecialchars($member['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <h4 class="empty-state-title">No team assigned</h4>
                                    <p class="empty-state-text">You are not currently enrolled in any workspace team.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Recent Activity Feed & Quick Actions -->
                <div>
                    <!-- Quick Actions Card -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body" style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="proposals.php" class="btn btn-secondary" style="justify-content: flex-start;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="16"></line>
                                    <line x1="8" y1="12" x2="16" y2="12"></line>
                                </svg>
                                <span>Submit Change Proposal</span>
                            </a>
                            <a href="approvals.php" class="btn btn-secondary" style="justify-content: flex-start;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 11 12 14 22 4"></polyline>
                                </svg>
                                <span>Vote on Proposals</span>
                                <?php if ($pendingProposalsCount > 0): ?>
                                    <span class="nav-item-badge badge-amber" style="margin-left: auto;"><?php echo $pendingProposalsCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="tasks.php" class="btn btn-secondary" style="justify-content: flex-start;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                                <span>Update Task Status</span>
                            </a>
                            <a href="my_proposals.php" class="btn btn-ghost" style="justify-content: flex-start;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                </svg>
                                <span>My Submissions History</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activity Stream Connected to activity_logs -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Recent Activity</h3>
                                <p class="card-subtitle">Workspace audit trail</p>
                            </div>
                            <a href="activity.php" class="btn btn-ghost btn-sm">Full Log &rarr;</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($teamActivities)): ?>
                                <ul class="activity-feed">
                                    <?php foreach ($teamActivities as $act): ?>
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
                                                    <strong><?php echo htmlspecialchars($act['full_name'] ?? 'Teammate'); ?>:</strong>
                                                    <?php echo htmlspecialchars($act['description']); ?>
                                                </div>
                                                <div class="activity-time">
                                                    <?php echo date('M j, Y &bull; g:i A', strtotime($act['created_at'])); ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state" style="padding: 24px 10px;">
                                    <div class="empty-state-icon" style="width: 36px; height: 36px; margin-bottom: 10px;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                                    </div>
                                    <h4 class="empty-state-title" style="font-size: 0.9rem;">No recorded activity yet</h4>
                                    <p class="empty-state-text" style="font-size: 0.78rem;">Proposals, peer votes, and task completions will automatically log here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
