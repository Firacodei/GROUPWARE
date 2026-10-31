
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
}
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

        
<!-- MY TEAM SECTION -->
<section class="content-card team-section">

    <div class="section-heading">
        <div>
            <h3>My Team</h3>
            <p>
                <?php echo $team
                    ? htmlspecialchars($team["team_name"])
                    : "You are not assigned to a team yet."; ?>
            </p>
        </div>

        <span><?php echo count($teamMembers); ?> Members</span>
    </div>

    <?php if (!empty($teamMembers)): ?>

        <div class="team-members-list">

            <?php foreach ($teamMembers as $member): ?>

                <div class="team-member">

                    <div class="avatar">
                        <?php echo strtoupper(substr($member["full_name"], 0, 1)); ?>
                    </div>

                    <div class="member-details">
                        <strong>
                            <?php echo htmlspecialchars($member["full_name"]); ?>
                        </strong>

                        <small>
                            <?php echo htmlspecialchars($member["email"]); ?>
                        </small>
                    </div>

                    <?php if ($member["full_name"] === $studentName): ?>
                        <span class="member-you">You</span>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p>No team members found.</p>

    <?php endif; ?>

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

