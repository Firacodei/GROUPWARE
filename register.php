<?php
session_start();
require_once "config/database.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate form fields
    if (
        empty($full_name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid work email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        try {
            // Check whether email already exists
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "This email is already registered. Please sign in.";
            } else {
                // Securely hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Register user into team workspace
                $conn->beginTransaction();

                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'student')"
                    );
                    $stmt->execute([$full_name, $email, $hashedPassword]);

                    $newUserId = (int) $conn->lastInsertId();

                    // Assign to initial team workspace (team_id = 1)
                    $teamStmt = $conn->prepare(
                        "INSERT INTO team_members (team_id, user_id) VALUES (1, ?)"
                    );
                    $teamStmt->execute([$newUserId]);

                    // Optional activity log
                    require_once __DIR__ . "/config/activity_logger.php";
                    logActivity(
                        $conn,
                        $newUserId,
                        1,
                        'user_joined',
                        "$full_name joined the workspace team",
                        'user',
                        $newUserId
                    );

                    $conn->commit();

                    $message = "Account created successfully! You can now sign in to your workspace.";
                    $success = true;

                } catch (PDOException $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    error_log("Registration transaction error: " . $e->getMessage());
                    $message = "Registration failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Registration DB error: " . $e->getMessage());
            $message = "An unexpected error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="landing-body">

<div class="auth-page-wrap">
    <div class="auth-box" style="max-width: 480px;">
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
            <h1 class="auth-box-title">Create your account</h1>
            <p class="auth-box-subtitle">Join your collaborative project workspace</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                <?php if ($success): ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                <?php else: ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" action="register.php" autocomplete="on">
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-control"
                        placeholder="e.g. Alex Morgan"
                        value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Work Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="alex@organization.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="At least 8 characters"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle-btn" data-target="password" title="Toggle password visibility">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle-btn" data-target="confirm_password" title="Toggle password visibility">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
                    Create Workspace Account
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin: 24px 0;">
                <a href="login.php" class="btn btn-primary btn-lg" style="display: inline-flex; width: 100%;">
                    Proceed to Sign In
                </a>
            </div>
        <?php endif; ?>

        <div class="auth-footer-nav">
            Already have an account?
            <a href="login.php" style="font-weight: 600; margin-left: 4px;">Sign in here</a>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
