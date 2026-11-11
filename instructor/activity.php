<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('instructor', '../login.php');

$activities = getAllRecentActivities($conn, 60);

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
                    <h1 class="page-title">Project Activity &amp; Audit Trail</h1>
                    <p class="page-subtitle">Cross-team collaboration events, proposal consensus tracking, and task status logs.</p>
                </div>
            </div>

            <div class="card" style="max-width: 960px;">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Live Workspace Events</h2>
                        <p class="card-subtitle">Real-time audit log of team actions</p>
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
                            <h3 class="empty-state-title">No activity events recorded yet</h3>
                            <p class="empty-state-text">Actions performed across teams will stream here automatically.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
