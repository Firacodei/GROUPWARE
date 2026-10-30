
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}

$adminName = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | GROUPWARE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">G</div>
            <span>GROUPWARE</span>
        </div>

        <div class="sidebar-label">ADMINISTRATION</div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link active">
                Overview
            </a>

            <a href="create_user.php" class="nav-link">
                Create Student Account
            </a>

            <a href="../logout.php" class="nav-link">
                Sign Out
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h2>Administrator Dashboard</h2>
                <p>Manage your GROUPWARE workspace.</p>
            </div>

            <div class="profile">
                <div class="avatar">A</div>
                <div>
                    <strong>
                        <?php echo htmlspecialchars($adminName); ?>
                    </strong>
                    <small>Administrator</small>
                </div>
            </div>
        </header>

        <section class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($adminName); ?>!</h1>
            <p>Manage student accounts and support team collaboration.</p>
        </section>

        <section class="content-card">
            <div class="section-heading">
                <h3>Account Management</h3>
                <p>Create student accounts for your project team.</p>
            </div>

            <p style="margin-top: 25px;">
                <a href="create_user.php" style="color:#60a5fa;">
                    + Create Student Account
                </a>
            </p>
        </section>
    </main>

</div>

</body>
</html>
