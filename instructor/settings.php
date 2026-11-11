<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('instructor', '../login.php');

$userId = (int) $_SESSION["user_id"];
$message = "";
$isSuccess = false;

// Fetch current user details
$userStmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    die("User profile could not be loaded.");
}

// Handle Updates
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_profile") {
        $fullName = trim($_POST["full_name"] ?? "");
        if ($fullName === "") {
            $message = "Full name cannot be empty.";
        } else {
            $up = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $up->execute([$fullName, $userId]);
            $_SESSION["full_name"] = $fullName;
            $currentUser["full_name"] = $fullName;
            $message = "Profile details updated successfully.";
            $isSuccess = true;
        }
    } elseif ($action === "update_password") {
        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        $pwdStmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $pwdStmt->execute([$userId]);
        $hash = $pwdStmt->fetchColumn();

        if (!$hash || !password_verify($currentPassword, $hash)) {
            $message = "The current password you provided is incorrect.";
        } elseif (strlen($newPassword) < 8) {
            $message = "New password must be at least 8 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New passwords do not match.";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $up->execute([$newHash, $userId]);
            $message = "Password updated successfully.";
            $isSuccess = true;
        }
    }
}

$pageTitle = "Settings";
$pageTitleHeader = "Settings";
$activeNav = "settings";
$rootUrl = "../";

include __DIR__ . "/../includes/layout_head.php";
?>

<div class="app-shell">
    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <div class="app-main">
        <?php include __DIR__ . "/../includes/topbar.php"; ?>

        <main class="app-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Project Lead Settings</h1>
                    <p class="page-subtitle">Personal credentials, account preferences, and notification defaults.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $isSuccess ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <div class="grid-2" style="max-width: 1080px;">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Profile Information</h3>
                            <p class="card-subtitle">Your team oversight identity</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($currentUser['full_name']); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email Address (Read-only)</label>
                                <input
                                    type="email"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                                    disabled
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="Project Lead / Manager"
                                    disabled
                                >
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                                Save Profile Changes
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Security &amp; Password</h3>
                            <p class="card-subtitle">Change your sign-in credentials</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_password">

                            <div class="form-group">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="password-input-wrapper">
                                    <input
                                        type="password"
                                        id="current_password"
                                        name="current_password"
                                        class="form-control"
                                        placeholder="Enter current password"
                                        required
                                    >
                                    <button type="button" class="password-toggle-btn" data-target="current_password">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="password-input-wrapper">
                                    <input
                                        type="password"
                                        id="new_password"
                                        name="new_password"
                                        class="form-control"
                                        placeholder="Minimum 8 characters"
                                        minlength="8"
                                        required
                                    >
                                    <button type="button" class="password-toggle-btn" data-target="new_password">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="password-input-wrapper">
                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        class="form-control"
                                        placeholder="Re-enter new password"
                                        minlength="8"
                                        required
                                    >
                                    <button type="button" class="password-toggle-btn" data-target="confirm_password">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-secondary" style="margin-top: 10px;">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
