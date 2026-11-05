<?php
session_start();

require_once "../config/database.php";

// Allow instructors only
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "instructor"
) {
    header("Location: ../index.php");
    exit;
}

$instructorName = $_SESSION["full_name"];

// Query real database metrics
$teamsCount = 0;
$studentsCount = 0;
$totalProposals = 0;
$pendingProposals = 0;
$teams = [];
$recentProposals = [];

try {
    // Monitored teams with member count
    $teamsQuery = $conn->query("
        SELECT t.id, t.team_name, COUNT(tm.user_id) AS member_count
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        GROUP BY t.id, t.team_name
        ORDER BY t.team_name ASC
    ");
    $teams = $teamsQuery->fetchAll(PDO::FETCH_ASSOC);
    $teamsCount = count($teams);

    // Enrolled students count
    $studentsCount = (int) $conn->query("
        SELECT COUNT(*) FROM users WHERE role = 'student'
    ")->fetchColumn();

    // Total proposals and pending count
    $totalProposals = (int) $conn->query("
        SELECT COUNT(*) FROM proposals
    ")->fetchColumn();

    $pendingProposals = (int) $conn->query("
        SELECT COUNT(*) FROM proposals WHERE status = 'pending'
    ")->fetchColumn();

    // Recent proposals across all teams
    $recentProposalsQuery = $conn->query("
        SELECT p.id, p.title, p.status, p.created_at, t.team_name, u.full_name AS proposer
        FROM proposals p
        LEFT JOIN teams t ON p.team_id = t.id
        LEFT JOIN users u ON p.proposed_by = u.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $recentProposals = $recentProposalsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Instructor dashboard DB error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | GROUPWARE</title>
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
                <span class="brand-subtitle">Instructor Portal</span>
            </div>
        </div>

        <div class="sidebar-label">ACADEMIC OVERSIGHT</div>

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

            <a href="#teams-overview" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </span>
                Team Overview
            </a>

            <a href="#proposals-monitoring" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </span>
                Proposals Monitor
            </a>

            <a href="#contribution-tracking" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </span>
                Contribution Tracking
            </a>

            <a href="#assessments-section" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </span>
                Assessments &amp; Marks
            </a>
    <a href="tasks.php" class="nav-link">
    Task Management
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
                <h2>Instructor Dashboard</h2>
                <p>Course collaboration metrics, group progress, and student contribution oversight.</p>
            </div>

            <div class="topbar-right">
                <div class="profile">
                    <div class="avatar" style="background-color: var(--secondary-teal-light); color: var(--secondary-teal); border-color: rgba(15, 118, 110, 0.2);">
                        <?php echo strtoupper(substr($instructorName, 0, 1)); ?>
                    </div>
                    <div class="profile-details">
                        <strong><?php echo htmlspecialchars($instructorName); ?></strong>
                        <small>Instructor / Faculty</small>
                    </div>
                </div>
            </div>

        </header>

        <!-- WELCOME BANNER -->
        <section class="welcome-section">

            <div class="welcome-content">
                <div class="welcome-tag">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Academic Management Portal</span>
                </div>
                <h1>Welcome, <?php echo htmlspecialchars($instructorName); ?>!</h1>
                <p>
                    Monitor student teams, supervise student change proposals, and ensure equitable collaborative contribution across your course groups.
                </p>
                <div class="welcome-actions">
                    <a href="#teams-overview" class="btn btn-white">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        Review Teams (<?php echo $teamsCount; ?>)
                    </a>
                    <a href="#proposals-monitoring" class="btn btn-trans">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                        </svg>
                        Pending Proposals (<?php echo $pendingProposals; ?>)
                    </a>
                </div>
            </div>

        </section>

        <!-- STATISTICS GRID -->
        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Active Teams</span>
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $teamsCount; ?></h3>
                <small class="stat-card-caption">Monitored course groups</small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Enrolled Students</span>
                    <div class="stat-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <polyline points="17 11 19 13 23 9"></polyline>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $studentsCount; ?></h3>
                <small class="stat-card-caption">Registered student accounts</small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Team Proposals</span>
                    <div class="stat-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $totalProposals; ?></h3>
                <small class="stat-card-caption">
                    <?php echo $pendingProposals; ?> awaiting student votes
                </small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Participation Rate</span>
                    <div class="stat-icon emerald">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value">
                    <?php echo $studentsCount > 0 ? "Active" : "Pending"; ?>
                </h3>
                <small class="stat-card-caption">Based on peer voting &amp; submissions</small>
            </div>

        </section>

        <!-- TEAMS OVERVIEW -->
        <section id="teams-overview" class="content-card" style="margin-bottom: 24px;">

            <div class="section-heading">
                <div>
                    <h3>Team Overview</h3>
                    <p>Student groups configured in the system</p>
                </div>
                <span class="badge badge-teal"><?php echo $teamsCount; ?> Teams</span>
            </div>

            <?php if (!empty($teams)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Group ID</th>
                                <th>Members</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $t): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($t['team_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span style="color: var(--text-muted); font-family: monospace;">#<?php echo (int) $t['id']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-role">
                                            <?php echo (int) $t['member_count']; ?> Students
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-approved">Active</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3>No teams configured yet</h3>
                    <p>Student teams will appear here once registered or created by the system administrator.</p>
                </div>
            <?php endif; ?>

        </section>

        <!-- PROPOSAL MONITORING & PARTICIPATION -->
        <section class="content-grid">

            <!-- Proposal Monitoring -->
            <div id="proposals-monitoring" class="content-card">

                <div class="section-heading">
                    <div>
                        <h3>Proposal Monitoring</h3>
                        <p>Recent change proposals submitted across teams</p>
                    </div>
                </div>

                <?php if (!empty($recentProposals)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Proposal Title</th>
                                    <th>Proposer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProposals as $rp): ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size: 0.85rem;"><?php echo htmlspecialchars($rp['title']); ?></strong>
                                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                                <?php echo htmlspecialchars($rp['team_name'] ?? 'Team'); ?> &bull; <?php echo date("M d", strtotime($rp['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.8125rem;"><?php echo htmlspecialchars($rp['proposer'] ?? 'Student'); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($rp['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($rp['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            </svg>
                        </div>
                        <h3>No proposals submitted</h3>
                        <p>When student groups submit change proposals, they will be listed here for monitoring.</p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Contribution & Assessment Tracking -->
            <div id="contribution-tracking" class="content-card">

                <div class="section-heading">
                    <div>
                        <h3>Assessment &amp; Marks Overview</h3>
                        <p>Rubrics and participation tracking</p>
                    </div>
                </div>

                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </div>
                    <h3>Assessment Module</h3>
                    <p>Course marks and individual contribution scores will calculate here based on peer voting activity and project milestone delivery.</p>
                </div>

            </div>

        </section>

        <footer class="dashboard-footer">
            GROUPWARE &copy; 2026 &bull; Faculty Academic Supervision &amp; Assessment Management
        </footer>

    </main>

</div>

</body>
</html>
