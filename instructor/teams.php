<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('instructor', '../login.php');

$teams = [];
try {
    $stmt = $conn->query("
        SELECT t.id, t.team_name, COUNT(tm.user_id) AS member_count,
               (SELECT COUNT(*) FROM tasks WHERE team_id = t.id) AS total_tasks,
               (SELECT COUNT(*) FROM tasks WHERE team_id = t.id AND status = 'completed') AS completed_tasks,
               (SELECT COUNT(*) FROM proposals WHERE team_id = t.id) AS proposals_count
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        GROUP BY t.id, t.team_name
        ORDER BY t.team_name ASC
    ");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Instructor teams query error: " . $e->getMessage());
}

$pageTitle = "Teams Overview";
$pageTitleHeader = "Teams";
$activeNav = "teams";
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
                    <h1 class="page-title">Project Teams &amp; Workspaces</h1>
                    <p class="page-subtitle">Directory of active collaboration groups, enrollments, and progress metrics.</p>
                </div>
                <div class="page-actions">
                    <a href="tasks.php" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Assign Task</span>
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="table-container" style="border: none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Team Workspace</th>
                                <th>Group ID</th>
                                <th>Assigned Members</th>
                                <th>Active Tasks</th>
                                <th>Proposals</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $t): ?>
                                <tr>
                                    <td>
                                        <strong style="font-size: 0.95rem; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($t['team_name']); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span style="font-family: var(--font-mono); color: var(--text-muted); font-size: 0.8rem;">#<?php echo (int) $t['id']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-role"><?php echo (int) $t['member_count']; ?> Collaborators</span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.85rem; color: var(--text-secondary);">
                                            <?php echo (int) $t['completed_tasks']; ?> / <?php echo (int) $t['total_tasks']; ?> completed
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700; color: var(--primary);"><?php echo (int) $t['proposals_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-approved">Active</span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="tasks.php?team_id=<?php echo (int) $t['id']; ?>" class="btn btn-secondary btn-sm">
                                            View Tasks
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
