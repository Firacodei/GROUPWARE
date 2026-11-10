<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('student', '../login.php');

$studentId = (int) $_SESSION["user_id"];

// Find team
$teamQuery = $conn->prepare("SELECT team_id FROM team_members WHERE user_id = ? LIMIT 1");
$teamQuery->execute([$studentId]);
$team = $teamQuery->fetch(PDO::FETCH_ASSOC);

$activities = [];
if ($team) {
    $teamId = (int) $team['team_id'];
    $stmt = $conn->prepare("
        SELECT a.id, a.activity_type, a.description, a.created_at,
               u.full_name, u.role
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.team_id = ?
        ORDER BY a.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$teamId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = "Activity History";
$pageTitleHeader = "Activity History";
$activeNav = "activity";
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
                    <h1 class="page-title">Activity History &amp; Audit Trail</h1>
                    <p class="page-subtitle">A chronological record of proposals, peer voting consensus, and task progress.</p>
                </div>
            </div>

            <div class="card" style="max-width: 900px;">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Recent Workspace Events</h2>
                        <p class="card-subtitle">Real-time collaboration audit log</p>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (!empty($activities)): ?>
                        <ul class="activity-feed">
                            <?php foreach ($activities as $act): ?>
                                <li class="activity-item">
                                    <div class="activity-icon-badge">
                                        <?php if (strpos($act['activity_type'], 'vote') !== false): ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline></svg>
                                        <?php elseif (strpos($act['activity_type'], 'task') !== false): ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path></svg>
                                        <?php elseif (strpos($act['activity_type'], 'approved') !== false): ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <?php else: ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <strong><?php echo htmlspecialchars($act['full_name'] ?? 'Teammate'); ?>:</strong>
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
                            <div class="empty-state-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <h3 class="empty-state-title">No activity events recorded yet</h3>
                            <p class="empty-state-text">Actions performed across your team will appear here in real-time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
