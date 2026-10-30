
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: ../index.php");
    exit;
}

$studentName = $_SESSION["full_name"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard | GROUPWARE</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="dashboard-body">

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <div class="brand-icon">G</div>
            <span>GROUPWARE</span>
        </div>

        <div class="sidebar-label">WORKSPACE</div>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-link active">
                <span>▦</span> Overview
            </a>

            <a href="#" class="nav-link">
                <span>👥</span> My Team
            </a>

            <a href="#" class="nav-link">
                <span>✓</span> My Tasks
            </a>

            <a href="#" class="nav-link">
                <span>◈</span> Proposals
            </a>

            <a href="#" class="nav-link">
                <span>☑</span> Approvals
            </a>

            <a href="#" class="nav-link">
                <span>▤</span> Contributions
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php" class="logout-link">
                ⇥ Sign Out
            </a>
        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">

            <div>
                <h2>Overview</h2>
                <p>Your team workspace at a glance.</p>
            </div>

            <div class="profile">
                <div class="avatar">
                    <?php echo strtoupper(substr($studentName, 0, 1)); ?>
                </div>

                <div>
                    <strong>
                        <?php echo htmlspecialchars($studentName); ?>
                    </strong>
                    <small>Student</small>
                </div>
            </div>

        </header>


        <!-- WELCOME -->
        <section class="welcome-section">

            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($studentName); ?>!</h1>

                <p>
                    Collaborate with your team, track your tasks,
                    and get recognized for your contributions.
                </p>
            </div>

            <div class="welcome-decoration">G</div>

        </section>


        <!-- STATISTICS -->
        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon blue">▦</div>
                <p>Active Projects</p>
                <h2>0</h2>
                <small>No projects assigned yet</small>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">✓</div>
                <p>My Tasks</p>
                <h2>0</h2>
                <small>No tasks assigned yet</small>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">◈</div>
                <p>Pending Proposals</p>
                <h2>0</h2>
                <small>No pending proposals</small>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">★</div>
                <p>Contribution Score</p>
                <h2>0%</h2>
                <small>Awaiting project activity</small>
            </div>

        </section>


        <!-- LOWER CONTENT -->
        <section class="content-grid">

            <div class="content-card">

                <div class="section-heading">
                    <div>
                        <h3>My Projects</h3>
                        <p>Projects you're currently working on</p>
                    </div>
                </div>

                <div class="empty-state">
                    <div class="empty-icon">▦</div>
                    <h3>No projects yet</h3>
                    <p>
                        Your assigned group projects will appear here.
                    </p>
                </div>

            </div>


            <div class="content-card">

                <div class="section-heading">
                    <div>
                        <h3>Recent Activity</h3>
                        <p>Your latest workspace updates</p>
                    </div>
                </div>

                <div class="empty-state">
                    <div class="empty-icon">◷</div>
                    <h3>No recent activity</h3>
                    <p>
                        Your team activities will appear here.
                    </p>
                </div>

            </div>

        </section>


        <footer class="dashboard-footer">
            GROUPWARE © 2026 | Team Collaboration & Contribution Management
        </footer>

    </main>

</div>

</body>
</html>

