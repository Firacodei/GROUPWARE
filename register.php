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
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {

        try {
            // Check whether email already exists
            $check = $conn->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "This email is already registered.";
            } else {

                // Securely hash the password
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Always register as a student
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role)
                    VALUES (?, ?, ?, 'student')"
                );

                $conn->beginTransaction();

                try {
                    $stmt->execute([$full_name, $email, $hashedPassword]);

                    $newUserId = $conn->lastInsertId();

                    $teamStmt = $conn->prepare(
                        "INSERT INTO team_members (team_id, user_id)
                         VALUES (1, ?)"
                    );

                    $teamStmt->execute([$newUserId]);

                    $conn->commit();

                    $message = "Account created successfully! You can now log in.";
                    $success = true;

                } catch (PDOException $e) {
                    $conn->rollBack();
                    $message = "Registration failed. Please try again.";
                }
            }

        } catch (PDOException $e) {
            $message = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | GROUPWARE Academic Portal</title>
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
                <span class="auth-meta-badge">Student Registration</span>
            </div>
            <h1>Create your student account</h1>
            <p>Join your collaborative group workspace and project team.</p>
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
            <form method="POST" action="">

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="e.g. Alex Morgan"
                        value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Institutional Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="student@university.edu"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        minlength="8"
                        required
                    >
                </div>

                <button type="submit" class="btn-primary">
                    Register Student Account
                </button>

            </form>
        <?php else: ?>
            <div style="text-align: center; margin: 20px 0;">
                <a href="index.php" class="btn-primary" style="display: inline-block;">
                    Proceed to Sign In
                </a>
            </div>
        <?php endif; ?>

        <div class="auth-footer">
            <p>
                Already have an account?
                <a href="index.php">Sign in here</a>
            </p>
        </div>

    </div>

</div>

</body>
</html>
