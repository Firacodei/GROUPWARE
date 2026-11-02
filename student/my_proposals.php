<?php
session_start();
require_once "../config/database.php";

// Allow logged-in students only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch only proposals submitted by this student
$stmt = $conn->prepare("
    SELECT title, description, status, created_at
    FROM proposals
    WHERE proposed_by = ?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate status counts
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;

foreach ($proposals as $p) {
    if ($p['status'] === 'pending') $pendingCount++;
    elseif ($p['status'] === 'approved') $approvedCount++;
    elseif ($p['status'] === 'rejected') $rejectedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Proposals | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="my-proposals-container">

    <div class="my-proposals-header">
        <div>
            <h1>My Submitted Proposals</h1>
            <p>Track review progress and decisions on the change proposals you submitted.</p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="dashboard.php" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Dashboard
            </a>
            <a href="proposals.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                New Proposal
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
        <div class="stat-card" style="padding: 16px 20px;">
            <span class="stat-card-label">Total Submitted</span>
            <h3 class="stat-card-value" style="font-size: 1.5rem;"><?php echo count($proposals); ?></h3>
        </div>
        <div class="stat-card" style="padding: 16px 20px;">
            <span class="stat-card-label">Pending Review</span>
            <h3 class="stat-card-value" style="font-size: 1.5rem; color: var(--warning);"><?php echo $pendingCount; ?></h3>
        </div>
        <div class="stat-card" style="padding: 16px 20px;">
            <span class="stat-card-label">Approved</span>
            <h3 class="stat-card-value" style="font-size: 1.5rem; color: var(--success);"><?php echo $approvedCount; ?></h3>
        </div>
        <div class="stat-card" style="padding: 16px 20px;">
            <span class="stat-card-label">Rejected</span>
            <h3 class="stat-card-value" style="font-size: 1.5rem; color: var(--danger);"><?php echo $rejectedCount; ?></h3>
        </div>
    </div>

    <?php if (count($proposals) > 0): ?>

        <div class="proposal-list">

            <?php foreach ($proposals as $proposal): ?>

                <div class="proposal-item">

                    <div class="proposal-item-top">
                        <h2><?php echo htmlspecialchars($proposal['title']); ?></h2>

                        <span class="status-badge status-<?php echo htmlspecialchars($proposal['status']); ?>">
                            <?php if ($proposal['status'] === 'approved'): ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            <?php elseif ($proposal['status'] === 'rejected'): ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            <?php else: ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            <?php endif; ?>
                            <?php echo ucfirst(htmlspecialchars($proposal['status'])); ?>
                        </span>
                    </div>

                    <p class="proposal-description">
                        <?php echo nl2br(htmlspecialchars($proposal['description'])); ?>
                    </p>

                    <div class="proposal-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Submitted on: <?php echo date("M d, Y &bull; h:i A", strtotime($proposal['created_at'])); ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-proposals">
            <div class="empty-icon" style="margin-bottom: 14px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
            <h2>No proposals submitted yet</h2>
            <p>You haven't submitted any project proposals to your team.</p>

            <a href="proposals.php" class="btn btn-primary">
                Submit Your First Proposal
            </a>
        </div>

    <?php endif; ?>

    <div>
        <a href="dashboard.php" class="proposal-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>
