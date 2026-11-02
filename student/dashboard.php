<?php
session_start();

require_once "../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: ../index.php");
    exit;
}

$studentName = $_SESSION["full_name"];
$studentId = $_SESSION["user_id"];

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
$activeProjectsCount = 0;
$tasksCount = 0;
$contributionScore = "0%";

if ($team) {
    $membersQuery = $conn->prepare("
        SELECT u.full_name, u.email, tm.joined_at
        FROM team_members tm
        JOIN users u ON tm.user_id = u.id
        WHERE tm.team_id = ?
        ORDER BY u.full_name ASC
    ");

    $membersQuery->execute([$team["id"]]);
    $teamMembers = $membersQuery->fetchAll(PDO::FETCH_ASSOC);

    // Query real pending proposals count for the team
    try {
        $pQuery = $conn->prepare("
            SELECT COUNT(*) FROM proposals
            WHERE team_id = ? AND status = 'pending'
        ");
        $pQuery->execute([$team["id"]]);
        $pendingProposalsCount = (int) $pQuery->fetchColumn();
    } catch (PDOException $e) {
        $pendingProposalsCount = 0;
    }

    $activeProjectsCount = $team ? 1 : 0;
    try {
        $tQuery = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
        $tQuery->execute([$studentId]);
        $tasksCount = (int)$tQuery->fetchColumn();
    } catch (PDOException $e) {
        $tasksCount = 0;
    }

    try {
        $myPointsQuery = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) * 25 FROM proposals WHERE proposed_by = ?) +
                (SELECT COUNT(*) * 35 FROM proposals WHERE proposed_by = ? AND status = 'approved') +
                (SELECT COUNT(*) * 30 FROM proposal_votes WHERE user_id = ?) AS user_pts,
                (SELECT COUNT(*) * 25 FROM proposals WHERE team_id = ?) +
                (SELECT COUNT(*) * 35 FROM proposals WHERE team_id = ? AND status = 'approved') +
                (SELECT COUNT(*) * 30 FROM proposal_votes pv JOIN proposals p ON pv.proposal_id = p.id WHERE p.team_id = ?) AS team_pts
        ");
        $myPointsQuery->execute([$studentId, $studentId, $studentId, $team['id'], $team['id'], $team['id']]);
        $pts = $myPointsQuery->fetch(PDO::FETCH_ASSOC);
        if ($pts && (int)$pts['team_pts'] > 0) {
            $pct = round(((int)$pts['user_pts'] / (int)$pts['team_pts']) * 100, 1);
            $contributionScore = $pct . "%";
        }
    } catch (PDOException $e) {
        $contributionScore = "0%";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="dashboard-body">

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <div class="brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-title">GROUPWARE</span>
                <span class="brand-subtitle">Student Portal</span>
            </div>
        </div>

        <div class="sidebar-label">WORKSPACE</div>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-link active">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </span>
                Overview
            </a>

            <a href="#team-section" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </span>
                My Team
            </a>

            <a href="#" class="nav-link" title="Task management module">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </span>
                My Tasks
            </a>

            <a href="proposals.php" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                </span>
                Submit Proposal
            </a>

            <a href="my_proposals.php" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </span>
                My Proposals
            </a>

            <a href="approvals.php" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </span>
                Approvals
                <?php if ($pendingProposalsCount > 0): ?>
                    <span class="badge badge-pending" style="margin-left: auto; padding: 2px 7px; font-size: 0.7rem;">
                        <?php echo $pendingProposalsCount; ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="#contributions-section" class="nav-link" title="Contribution analytics">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </span>
                Contributions
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php" class="logout-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </span>
                Sign Out
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">

            <div class="topbar-left">
                <h2>Student Workspace</h2>
                <p>Welcome to your academic collaboration hub.</p>
            </div>

            <div class="topbar-right">
                <div class="profile">
                    <div class="avatar">
                        <?php echo strtoupper(substr($studentName, 0, 1)); ?>
                    </div>
                    <div class="profile-details">
                        <strong><?php echo htmlspecialchars($studentName); ?></strong>
                        <small>Student</small>
                    </div>
                </div>
            </div>

        </header>

        <!-- WELCOME SECTION -->
        <section class="welcome-section">

            <div class="welcome-content">
                <div class="welcome-tag">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    </svg>
                    <span>Term 2026 • Group Collaboration</span>
                </div>
                <h1>Welcome back, <?php echo htmlspecialchars($studentName); ?>!</h1>
                <p>
                    Collaborate with your team members, submit project change proposals, and vote on peer suggestions to keep your project moving forward.
                </p>
                <div class="welcome-actions">
                    <a href="proposals.php" class="btn btn-white">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Submit Proposal
                    </a>
                    <a href="approvals.php" class="btn btn-trans">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                        </svg>
                        Review Approvals (<?php echo $pendingProposalsCount; ?>)
                    </a>
                    <a href="my_proposals.php" class="btn btn-trans">
                        My Proposals
                    </a>
                </div>
            </div>

        </section>

        <!-- STATISTICS GRID -->
        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Active Projects</span>
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $activeProjectsCount; ?></h3>
                <small class="stat-card-caption"><?php echo $activeProjectsCount > 0 ? "Active group project" : "Awaiting project assignment"; ?></small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Assigned Tasks</span>
                    <div class="stat-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $tasksCount; ?></h3>
                <small class="stat-card-caption"><?php echo $tasksCount > 0 ? $tasksCount . " active deliverables" : "No tasks currently assigned"; ?></small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Pending Proposals</span>
                    <div class="stat-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $pendingProposalsCount; ?></h3>
                <small class="stat-card-caption">
                    <?php echo $pendingProposalsCount > 0 ? "Requires team voting" : "All proposals reviewed"; ?>
                </small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label" id="contributions-section">Contribution Score</span>
                    <div class="stat-icon emerald">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $contributionScore; ?></h3>
                <small class="stat-card-caption"><?php echo $contributionScore !== "0%" ? "Peer activity and quorum share" : "Awaiting project activity"; ?></small>
            </div>

        </section>

        <!-- MY TEAM SECTION -->
        <section id="team-section" class="content-card team-section">

            <div class="section-heading">
                <div>
                    <h3>Team Collaboration</h3>
                    <p>
                        <?php echo $team
                            ? "Assigned to: " . htmlspecialchars($team["team_name"])
                            : "You are not assigned to a team yet."; ?>
                    </p>
                </div>

                <span class="badge badge-teal">
                    <?php echo count($teamMembers); ?> Members
                </span>
            </div>

            <?php if (!empty($teamMembers)): ?>

                <div class="team-members-list">

                    <?php foreach ($teamMembers as $member): ?>

                        <div class="team-member">

                            <div class="avatar">
                                <?php echo strtoupper(substr($member["full_name"], 0, 1)); ?>
                            </div>

                            <div class="member-details">
                                <strong><?php echo htmlspecialchars($member["full_name"]); ?></strong>
                                <small><?php echo htmlspecialchars($member["email"]); ?></small>
                            </div>

                            <?php if ($member["full_name"] === $studentName): ?>
                                <span class="member-you">You</span>
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3>No Team Assigned</h3>
                    <p>Contact your course instructor or administrator to be assigned to a collaborative project group.</p>
                </div>

            <?php endif; ?>

        </section>

        <!-- LOWER CONTENT: PROJECTS & ACTIVITY -->
        <section class="content-grid">

            <div class="content-card">

                <div class="section-heading">
                    <div>
                        <h3>My Projects</h3>
                        <p>Projects you are currently collaborating on</p>
                    </div>
                </div>

                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3>No Active Projects</h3>
                    <p>Assigned course projects will appear here once released by your instructor.</p>
                </div>

            </div>

            <div class="content-card">

                <div class="section-heading">
                    <div>
                        <h3>Recent Activity</h3>
                        <p>Latest updates and audit log across your workspace</p>
                    </div>
                </div>

                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h3>No Recent Activity</h3>
                    <p>New proposals, votes, and task completions will automatically log here.</p>
                </div>

            </div>

        </section>

        <footer class="dashboard-footer">
            GROUPWARE &copy; 2026 &bull; University Group Collaboration &amp; Contribution Management Platform
        </footer>

    </main>

</div>

</body>
</html>
