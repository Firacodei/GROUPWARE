<?php
session_start();
require_once "config/database.php";

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
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
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = (int) $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
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
                $error = "Invalid credentials. Please verify your email and password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "A database connection error occurred. Please try again shortly.";
        }
    }
}

$pageTitle = "Sign In";
$rootUrl = "./";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="landing-body">

<div class="auth-page-wrap">
    <div class="auth-box">
        <div class="auth-brand-head">
            <a href="index.php" style="display: inline-flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <div class="brand-icon-symbol">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <span class="brand-name" style="font-size: 1.3rem;">GROUPWARE</span>
            </a>
            <h1 class="auth-box-title">Welcome back</h1>
            <p class="auth-box-subtitle">Sign in to your collaboration workspace</p>
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

        <form method="POST" action="login.php" autocomplete="on">
            <div class="form-group">
                <label for="email" class="form-label">Work Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="name@organization.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                    autocomplete="email"
                    autofocus
                >
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
                </div>
                <div class="password-input-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle-btn" data-target="password" title="Toggle password visibility">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 8px;">
                Sign In to Workspace
            </button>
        </form>

        <div class="auth-footer-nav">
            Don't have an account yet?
            <a href="register.php" style="font-weight: 600; margin-left: 4px;">Create an account</a>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
