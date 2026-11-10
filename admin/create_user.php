<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/activity_logger.php";
require_once __DIR__ . "/../includes/auth.php";

requireAuth('admin', '../login.php');

$message = "";
$isSuccess = false;

// Fetch teams for optional workspace assignment
$teams = [];
try {
    $teams = $conn->query("SELECT id, team_name FROM teams ORDER BY team_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "student";
    $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);

    $allowedRoles = ['student', 'instructor', 'admin'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'student';
    }

    if (empty($full_name) || empty($email) || empty($password)) {
        $message = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please provide a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
    } else {
        try {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "This email is already registered in the system.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $conn->beginTransaction();

                try {
                    $stmt = $conn->prepare("
                        INSERT INTO users (full_name, email, password, role)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$full_name, $email, $hashedPassword, $role]);
                    $newUserId = (int) $conn->lastInsertId();

                    // If assigned to a team and role is student
                    if ($teamId && $teamId > 0) {
                        $teamStmt = $conn->prepare("
                            INSERT INTO team_members (team_id, user_id)
                            VALUES (?, ?)
                        ");
                        $teamStmt->execute([$teamId, $newUserId]);
                    }

                    // Log activity
                    logActivity(
                        $conn,
                        (int) $_SESSION['user_id'],
                        $teamId > 0 ? $teamId : 1,
                        'user_created',
                        "Created new $role account for \"$full_name\" ($email)",
                        'user',
                        $newUserId
                    );

                    $conn->commit();

                    $message = "User account created successfully!";
                    $isSuccess = true;
                } catch (PDOException $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    error_log("Admin user creation error: " . $e->getMessage());
                    $message = "Database error while creating user. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Admin create user DB error: " . $e->getMessage());
            $message = "An unexpected database error occurred.";
        }
    }
}

$pageTitle = "Add Member";
$pageTitleHeader = "Create User";
$activeNav = "create_user";
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
                    <h1 class="page-title">Onboard Workspace Member</h1>
                    <p class="page-subtitle">Provision a new account with specified role permissions and team assignment.</p>
                </div>
                <div class="page-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
            </div>

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

            <div class="card" style="max-width: 680px;">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Account Credentials &amp; Permissions</h2>
                        <p class="card-subtitle">Set up user details and initial credentials</p>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="create_user.php" autocomplete="off">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-control"
                                placeholder="e.g. Jordan Smith"
                                value="<?php echo isset($_POST['full_name']) && !$isSuccess ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                required
                                autofocus
                            >
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Work Email Address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="name@organization.com"
                                value="<?php echo isset($_POST['email']) && !$isSuccess ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Account Role Permission</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Team Member (Collaborator)</option>
                                <option value="instructor" <?php echo (isset($_POST['role']) && $_POST['role'] === 'instructor') ? 'selected' : ''; ?>>Project Lead (Manager)</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Workspace Administrator</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="team_id" class="form-label">Initial Team Workspace (Optional)</label>
                            <select name="team_id" id="team_id" class="form-select">
                                <option value="">Assign later / No team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo (int) $team['id']; ?>" <?php echo (isset($_POST['team_id']) && (int) $_POST['team_id'] === (int) $team['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($team['team_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Initial Temporary Password</label>
                            <div class="password-input-wrapper">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Minimum 8 characters"
                                    minlength="8"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="password-toggle-btn" data-target="password">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                            <span class="form-hint">The member will use this password to sign in to the platform.</span>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                                <span>Provision User Account</span>
                            </button>

                            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
