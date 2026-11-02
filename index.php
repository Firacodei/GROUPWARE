<?php
session_start();
require_once "config/database.php";

if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] === 'student') {
        header("Location: student/dashboard.php");

    } elseif ($_SESSION['role'] === 'instructor') {
        header("Location: instructor/dashboard.php");

    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    }

    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ?"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "student") {
            header("Location: student/dashboard.php");
        } elseif ($user["role"] === "instructor") {
            header("Location: instructor/dashboard.php");
        } else {
            header("Location: admin/dashboard.php");
        }

        exit;

    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | GROUPWARE Academic Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-container">

    <div class="login-card">

        <div class="auth-header">
            <div class="auth-brand">
                <div class="auth-brand-logo">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <span class="auth-brand-name">GROUPWARE</span>
            </div>
            <div>
                <span class="auth-meta-badge">University Collaboration Portal</span>
            </div>
            <h1>Sign in to your workspace</h1>
            <p>Access your group projects, proposals, and team contributions.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="email">Institutional Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="student@university.edu"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn-primary">
                Sign In to Platform
            </button>

        </form>

        <div class="auth-footer">
            <p>
                New student?
                <a href="register.php">Create student account</a>
            </p>
        </div>

    </div>

</div>

</body>
</html>
