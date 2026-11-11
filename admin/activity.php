<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('admin', '../login.php');

$activities = getAllRecentActivities($conn, 100);

$pageTitle = "System Activity Log";
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
                    <h1 class="page-title">Enterprise Activity &amp; Security Audit Trail</h1>
                    <p class="page-subtitle">Complete chronological event record across all workspaces, users, proposals, and tasks.</p>
                </div>
            </div>

            <div class="card" style="max-width: 980px;">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">System-Wide Audit Stream</h2>
                        <p class="card-subtitle">Real-time collaboration activity</p>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (!empty($activities)): ?>
                        <ul class="activity-feed">
                            <?php foreach ($activities as $act): ?>
                                <li class="activity-item">
                                    <div class="activity-icon-badge">
                                        <?php if (strpos($act['activity_type'], 'user') !== false): ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                                        <?php elseif (strpos($act['activity_type'], 'vote') !== false): ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline></svg>
                                        <?php elseif (strpos($act['activity_type'], 'task') !== false): ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path></svg>
                                        <?php else: ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <strong><?php echo htmlspecialchars($act['full_name'] ?? 'System'); ?></strong>
                                            <?php if (!empty($act['team_name'])): ?>
                                                <span style="color: var(--text-muted); font-size: 0.8rem;">(<?php echo htmlspecialchars($act['team_name']); ?>)</span>
                                            <?php endif; ?>:
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
                            <h3 class="empty-state-title">No system activities recorded yet</h3>
                            <p class="empty-state-text">Actions performed will appear here automatically.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
