<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('student', '../login.php');

$studentId = (int) $_SESSION["user_id"];
$studentName = $_SESSION["full_name"];

// Find team
$teamQuery = $conn->prepare("
    SELECT t.id, t.team_name
    FROM teams t
    JOIN team_members tm ON t.id = tm.team_id
    WHERE tm.user_id = ?
    LIMIT 1
");
$teamQuery->execute([$studentId]);
$team = $teamQuery->fetch(PDO::FETCH_ASSOC);

$members = [];
if ($team) {
    $teamId = (int) $team['id'];
    $mQuery = $conn->prepare("
        SELECT u.id, u.full_name, u.email, u.role, tm.joined_at,
               (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'completed') AS completed_tasks,
               (SELECT COUNT(*) FROM proposals WHERE proposed_by = u.id) AS proposals_count
        FROM team_members tm
        JOIN users u ON tm.user_id = u.id
        WHERE tm.team_id = ?
        ORDER BY (u.id = ?) DESC, u.full_name ASC
    ");
    $mQuery->execute([$teamId, $studentId]);
    $members = $mQuery->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = "Team Directory";
$pageTitleHeader = "My Team";
$activeNav = "team";
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
                    <h1 class="page-title">Team Workspace Directory</h1>
                    <p class="page-subtitle">
                        <?php echo $team ? 'Collaborators currently active in <strong>' . htmlspecialchars($team['team_name']) . '</strong>' : 'No team assigned yet.'; ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($members)): ?>
                <div class="card">
                    <div class="table-container" style="border: none;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Work Email</th>
                                    <th>Role</th>
                                    <th>Joined Workspace</th>
                                    <th>Completed Tasks</th>
                                    <th>Proposals</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $m): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div class="user-avatar-circle" style="width: 32px; height: 32px;">
                                                    <?php echo strtoupper(substr($m['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong style="color: var(--text-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($m['full_name']); ?></strong>
                                                    <?php if ((int) $m['id'] === $studentId): ?>
                                                        <span style="font-size: 0.68rem; padding: 1px 6px; background: var(--primary-subtle); color: var(--primary); border-radius: 4px; margin-left: 4px;">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="color: var(--text-secondary); font-size: 0.84rem;"><?php echo htmlspecialchars($m['email']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge-role"><?php echo ucfirst(htmlspecialchars($m['role'])); ?></span>
                                        </td>
                                        <td>
                                            <span style="color: var(--text-muted); font-size: 0.82rem;">
                                                <?php echo $m['joined_at'] ? date('M j, Y', strtotime($m['joined_at'])) : 'Active'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: var(--success); font-size: 0.88rem;"><?php echo (int) $m['completed_tasks']; ?></span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: var(--primary); font-size: 0.88rem;"><?php echo (int) $m['proposals_count']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <h3 class="empty-state-title">No team workspace configured</h3>
                        <p class="empty-state-text">You are not currently assigned to an active project group.</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
