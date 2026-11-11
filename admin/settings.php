<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('admin', '../login.php');

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
            $message = "Profile name updated successfully.";
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
            $message = "New password must be at least 8 characters long.";
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

$pageTitle = "System Settings";
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
                    <h1 class="page-title">Enterprise System Settings</h1>
                    <p class="page-subtitle">Configure organization-level preferences, administrative credentials, and workspace defaults.</p>
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
                            <h3 class="card-title">Administrator Profile</h3>
                            <p class="card-subtitle">Organization owner identity</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label for="full_name" class="form-label">Administrator Full Name</label>
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
                                <label class="form-label">Privilege Level</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="Full System Administrator"
                                    disabled
                                >
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                                Save Administrator Changes
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Security &amp; Password</h3>
                            <p class="card-subtitle">Change administrative password</p>
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
                                        placeholder="Confirm new password"
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
