<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('instructor', '../login.php');

$statusFilter = $_GET['status'] ?? 'all';
$validStatuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'all';
}

$teamFilter = isset($_GET['team_id']) ? (int) $_GET['team_id'] : 0;

$teams = [];
$proposals = [];

try {
    $teams = $conn->query("SELECT id, team_name FROM teams ORDER BY team_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT p.id, p.title, p.description, p.status, p.created_at,
               t.team_name, u.full_name AS proposer,
               (SELECT COUNT(*) FROM proposal_votes WHERE proposal_id = p.id AND vote = 'approve') AS approvals,
               (SELECT COUNT(*) FROM proposal_votes WHERE proposal_id = p.id AND vote = 'reject') AS rejections
        FROM proposals p
        LEFT JOIN teams t ON p.team_id = t.id
        LEFT JOIN users u ON p.proposed_by = u.id
        WHERE 1 = 1
    ";
    $params = [];

    if ($statusFilter !== 'all') {
        $sql .= " AND p.status = :status";
        $params[':status'] = $statusFilter;
    }
    if ($teamFilter > 0) {
        $sql .= " AND p.team_id = :team_id";
        $params[':team_id'] = $teamFilter;
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Instructor proposals error: " . $e->getMessage());
}

$pageTitle = "Proposals Monitor";
$pageTitleHeader = "Proposals";
$activeNav = "proposals";
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
                    <h1 class="page-title">Proposals Monitoring &amp; Decision Audit</h1>
                    <p class="page-subtitle">Supervise cross-team change proposals, peer consensus rates, and majority decisions.</p>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="proposals.php" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 22px;">
                <div style="flex: 1; min-width: 160px;">
                    <label for="team_id" class="form-label">Team Workspace</label>
                    <select name="team_id" id="team_id" class="form-select">
                        <option value="0">All Workspaces</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?php echo (int) $t['id']; ?>" <?php echo $teamFilter === (int) $t['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['team_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label for="status" class="form-label">Proposal Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending Vote</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Apply Filters</button>
                <a href="proposals.php" class="btn btn-ghost btn-sm" style="height: 38px;">Reset</a>
            </form>

            <!-- Proposals List -->
            <?php if (!empty($proposals)): ?>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($proposals as $prop): ?>
                        <article class="card" style="padding: 22px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                                <div>
                                    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($prop['title']); ?>
                                    </h2>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span>Proposer: <strong><?php echo htmlspecialchars($prop['proposer'] ?? 'Member'); ?></strong></span>
                                        <span>&bull;</span>
                                        <span>Team: <strong><?php echo htmlspecialchars($prop['team_name'] ?? 'General'); ?></strong></span>
                                        <span>&bull;</span>
                                        <span>Submitted <?php echo date("M d, Y &bull; g:i A", strtotime($prop['created_at'])); ?></span>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="status-badge status-<?php echo htmlspecialchars($prop['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($prop['status'])); ?>
                                    </span>
                                </div>
                            </div>

                            <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap; margin-bottom: 14px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px;"><?php echo htmlspecialchars($prop['description']); ?></p>

                            <div style="display: flex; gap: 14px; align-items: center; font-size: 0.8rem;">
                                <div style="display: flex; align-items: center; gap: 6px; color: var(--success); font-weight: 600;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>Approvals: <?php echo (int) $prop['approvals']; ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: var(--danger); font-weight: 600;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    <span>Rejections: <?php echo (int) $prop['rejections']; ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                        </div>
                        <h3 class="empty-state-title">No proposals found</h3>
                        <p class="empty-state-text">No change proposals match your current filter parameters.</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
