<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('student', '../login.php');

$userId = (int) $_SESSION['user_id'];

// Fetch proposals submitted by this user
$stmt = $conn->prepare("
    SELECT id, title, description, status, created_at
    FROM proposals
    WHERE proposed_by = ?
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;

foreach ($proposals as $p) {
    if ($p['status'] === 'pending') $pendingCount++;
    elseif ($p['status'] === 'approved') $approvedCount++;
    elseif ($p['status'] === 'rejected') $rejectedCount++;
}

$pageTitle = "My Proposals";
$pageTitleHeader = "My Proposals";
$activeNav = "my_proposals";
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
                    <h1 class="page-title">My Submitted Proposals</h1>
                    <p class="page-subtitle">Track the consensus status and peer voting decisions on your change requests.</p>
                </div>
                <div class="page-actions">
                    <a href="proposals.php" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Submit New Proposal</span>
                    </a>
                </div>
            </div>

            <!-- Summary Metrics Cards -->
            <section class="stats-grid" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <span class="stat-label">Total Submitted</span>
                    <div class="stat-value"><?php echo count($proposals); ?></div>
                    <div class="stat-trend">All time change requests</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Pending Review</span>
                    <div class="stat-value" style="color: var(--warning);"><?php echo $pendingCount; ?></div>
                    <div class="stat-trend">Actively open for voting</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Approved</span>
                    <div class="stat-value" style="color: var(--success);"><?php echo $approvedCount; ?></div>
                    <div class="stat-trend">Consensus reached</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Rejected</span>
                    <div class="stat-value" style="color: var(--danger);"><?php echo $rejectedCount; ?></div>
                    <div class="stat-trend">Declined by majority</div>
                </div>
            </section>

            <!-- Proposals List -->
            <?php if (!empty($proposals)): ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($proposals as $prop): ?>
                        <article class="card" style="padding: 22px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                                <div>
                                    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">
                                        <?php echo htmlspecialchars($prop['title']); ?>
                                    </h2>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span>Submitted on <?php echo date("M d, Y &bull; g:i A", strtotime($prop['created_at'])); ?></span>
                                    </div>
                                </div>

                                <span class="status-badge status-<?php echo htmlspecialchars($prop['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($prop['status'])); ?>
                                </span>
                            </div>

                            <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap; margin: 0;"><?php echo htmlspecialchars($prop['description']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </div>
                        <h3 class="empty-state-title">No proposals submitted yet</h3>
                        <p class="empty-state-text">You haven't authored any change proposals for your team workspace.</p>
                        <a href="proposals.php" class="btn btn-primary">Create Your First Proposal</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
