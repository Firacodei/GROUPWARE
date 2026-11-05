<?php
session_start();
require_once "../config/database.php";

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}

$adminName = $_SESSION['full_name'];

// Query real system metrics
$totalUsers = 0;
$studentsCount = 0;
$instructorsCount = 0;
$teamsCount = 0;
$recentUsers = [];
$teamsList = [];

try {
    $totalUsers = (int) $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $studentsCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $instructorsCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
    $teamsCount = (int) $conn->query("SELECT COUNT(*) FROM teams")->fetchColumn();

    // Query recent users
    $usersQuery = $conn->query("
        SELECT id, full_name, email, role
        FROM users
        ORDER BY id DESC
        LIMIT 10
    ");
    $recentUsers = $usersQuery->fetchAll(PDO::FETCH_ASSOC);

    // Query teams with member counts
    $teamsQuery = $conn->query("
        SELECT t.id, t.team_name, COUNT(tm.user_id) AS member_count
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        GROUP BY t.id, t.team_name
        ORDER BY t.id ASC
    ");
    $teamsList = $teamsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin DB Query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-layout">

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
                <span class="brand-subtitle">Administration</span>
            </div>
        </div>

        <div class="sidebar-label">MANAGEMENT</div>

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

            <a href="#user-management" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </span>
                User Accounts
            </a>

            <a href="create_user.php" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                </span>
                Create Student Account
            </a>

            <a href="#team-management" class="nav-link">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </span>
                Team Management
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

    <main class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <h2>System Administration</h2>
                <p>Enterprise management console for accounts, roles, and project teams.</p>
            </div>

            <div class="topbar-right">
                <div class="profile">
                    <div class="avatar" style="background-color: var(--primary-navy-light); color: var(--primary-navy);">
                        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                    </div>
                    <div class="profile-details">
                        <strong><?php echo htmlspecialchars($adminName); ?></strong>
                        <small>Administrator</small>
                    </div>
                </div>
            </div>
        </header>

        <!-- WELCOME SECTION -->
        <section class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-tag">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span>System Administration Console</span>
                </div>
                <h1>Welcome, <?php echo htmlspecialchars($adminName); ?>!</h1>
                <p>
                    Manage users, onboard new students to collaborative workspaces, and maintain organization data across your university platform.
                </p>
                <div class="welcome-actions">
                    <a href="create_user.php" class="btn btn-white">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Create Student Account
                    </a>
                    <a href="#user-management" class="btn btn-trans">
                        View All Accounts (<?php echo $totalUsers; ?>)
                    </a>
                </div>
            </div>
        </section>

        <!-- SYSTEM STATISTICS GRID -->
        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Total Accounts</span>
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $totalUsers; ?></h3>
                <small class="stat-card-caption">All registered accounts</small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Student Users</span>
                    <div class="stat-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $studentsCount; ?></h3>
                <small class="stat-card-caption">Active student accounts</small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Instructors</span>
                    <div class="stat-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="7" r="4"></circle>
                            <path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $instructorsCount; ?></h3>
                <small class="stat-card-caption">Faculty members</small>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Project Teams</span>
                    <div class="stat-icon emerald">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                        </polyline>
                    </div>
                </div>
                <h3 class="stat-card-value"><?php echo $teamsCount; ?></h3>
                <small class="stat-card-caption">Configured workspaces</small>
            </div>

        </section>

        <!-- USER MANAGEMENT DATA TABLE -->
        <section id="user-management" class="content-card" style="margin-bottom: 24px;">

            <div class="section-heading">
                <div>
                    <h3>User Management</h3>
                    <p>Registered system users and role permissions</p>
                </div>
                <a href="create_user.php" class="btn btn-primary btn-sm">
                    + Create Student
                </a>
            </div>

            <?php if (!empty($recentUsers)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Institutional Email</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td>
                                        <span style="font-family: monospace; color: var(--text-muted);">#<?php echo (int) $u['id']; ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="avatar" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                                <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($u['email']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge badge-role" style="background: var(--deep-navy); color: #FFFFFF;">Admin</span>
                                        <?php elseif ($u['role'] === 'instructor'): ?>
                                            <span class="badge badge-teal">Instructor</span>
                                        <?php else: ?>
                                            <span class="badge badge-role">Student</span>
                                        <?php endif; ?>
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
                    <h3>No users found</h3>
                    <p>No user accounts exist in the database.</p>
                </div>
            <?php endif; ?>

        </section>

        <!-- TEAM MANAGEMENT -->
        <section id="team-management" class="content-card">

            <div class="section-heading">
                <div>
                    <h3>Team Management</h3>
                    <p>Configured project workspaces and current enrollments</p>
                </div>
                <span class="badge badge-teal"><?php echo $teamsCount; ?> Total Teams</span>
            </div>

            <?php if (!empty($teamsList)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Team ID</th>
                                <th>Team Name</th>
                                <th>Assigned Members</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teamsList as $team): ?>
                                <tr>
                                    <td>
                                        <span style="font-family: monospace; color: var(--text-muted);">#<?php echo (int) $team['id']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-role">
                                            <?php echo (int) $team['member_count']; ?> Students
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
                    <h3>No teams created</h3>
                    <p>Add student teams to assign group projects.</p>
                </div>
            <?php endif; ?>

        </section>

        <footer class="dashboard-footer">
            GROUPWARE &copy; 2026 &bull; System Administration &amp; Access Control Console
        </footer>

    </main>

</div>

</body>
</html>
