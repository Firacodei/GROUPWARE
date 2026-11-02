<?php
session_start();
require_once "../config/database.php";

// Only administrators can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$message = "";
$isSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($full_name) || empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        try {
            $check = $conn->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "This email is already registered.";
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO users (full_name, email, password, role)
                     VALUES (?, ?, ?, 'student')"
                );

                $stmt->execute([
                    $full_name,
                    $email,
                    $hashedPassword
                ]);

                $message = "Student account created successfully!";
                $isSuccess = true;
            }
        } catch (PDOException $e) {
            $message = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student Account | GROUPWARE Administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="proposal-container" style="max-width: 650px;">

    <div class="proposal-header">
        <div>
            <h1>Create Student Account</h1>
            <p>Onboard a new student into your GROUPWARE workspace with default student privileges.</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Dashboard
            </a>
        </div>
    </div>

    <div class="proposal-card">

        <?php if ($message !== ""): ?>
            <div class="alert <?php echo $isSuccess ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                <?php if ($isSuccess): ?>
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

        <form method="POST" action="">

            <div class="form-group">
                <label for="full_name">Student Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="e.g. Jordan Smith"
                    value="<?php echo isset($_POST['full_name']) && !$isSuccess ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Institutional Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="student@university.edu"
                    value="<?php echo isset($_POST['email']) && !$isSuccess ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Initial Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Minimum 8 characters"
                    minlength="8"
                    required
                >
                <small style="color: var(--text-muted);">The student will use this password to sign in to their dashboard.</small>
            </div>

            <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
                <button type="submit" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Create Student Account
                </button>

                <a href="dashboard.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>

    <div>
        <a href="dashboard.php" class="proposal-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Administrator Dashboard
        </a>
    </div>

</div>

</body>
</html>
