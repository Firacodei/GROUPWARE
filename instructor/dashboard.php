
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | GROUPWARE</title>

    <link rel="stylesheet" href="/GROUPWARE/assets/css/style.css">
</head>

<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <h2>GROUPWARE</h2>
        <p>Instructor Portal</p>

        <nav>
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="create_task.php" class="nav-link">Create Tasks</a>
            <a href="#" class="nav-link">Manage Tasks</a>
            <a href="#" class="nav-link">Team Contributions</a>
        </nav>

        <a href="../logout.php" class="nav-link">Sign Out</a>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <h2>Instructor Dashboard</h2>
            <span><?php echo htmlspecialchars($instructorName); ?></span>
        </header>

        <section class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($instructorName); ?>!</h1>
            <p>Manage team activities, assign tasks, and monitor student contributions.</p>
        </section>

        <section class="stats-grid">

            <div class="stat-card">
                <h3>Total Tasks</h3>
                <p>0</p>
            </div>

            <div class="stat-card">
                <h3>Pending Tasks</h3>
                <p>0</p>
            </div>

            <div class="stat-card">
                <h3>Completed Tasks</h3>
                <p>0</p>
            </div>

            <div class="stat-card">
                <h3>Team Members</h3>
                <p>4</p>
            </div>

        </section>

        <section class="content-card">
            <h3>Task Management</h3>
            <p>Create tasks and assign responsibilities to your team members.</p>

            <a href="create_task.php" class="btn-primary">
                + Create New Task
            </a>
        </section>

    </main>

</div>

</body>
</html>
