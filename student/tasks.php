<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/activity_logger.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth('student', '../login.php');

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['full_name'] ?? 'Student';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$successMessage = $_SESSION['student_task_flash'] ?? '';
unset($_SESSION['student_task_flash']);
$errorMessage = '';

// Handle Task Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $newStatus = (string) ($_POST['status'] ?? '');
    $allowedStatuses = ['pending', 'in_progress', 'completed'];

    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $errorMessage = 'Security token validation failed. Please refresh the page.';
    } elseif (!$taskId || $taskId < 1) {
        $errorMessage = 'Invalid task selection.';
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $errorMessage = 'Invalid task status selected.';
    } else {
        try {
            // Ensure student can only update their own assigned tasks
            $checkStmt = $conn->prepare("
                SELECT id, title, team_id, status FROM tasks
                WHERE id = :task_id AND assigned_to = :student_id
            ");
            $checkStmt->execute([':task_id' => $taskId, ':student_id' => $studentId]);
            $taskData = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$taskData) {
                $errorMessage = 'That task was not assigned to your account.';
            } else {
                $oldStatus = $taskData['status'];
                $update = $conn->prepare(
                    'UPDATE tasks SET status = :status WHERE id = :task_id AND assigned_to = :student_id'
                );
                $update->execute([
                    ':status' => $newStatus,
                    ':task_id' => $taskId,
                    ':student_id' => $studentId,
                ]);

                // Log activity to audit trail
                $statusLabel = ucwords(str_replace('_', ' ', $newStatus));
                logActivity(
                    $conn,
                    $studentId,
                    (int) $taskData['team_id'],
                    $newStatus === 'completed' ? 'task_completed' : 'task_updated',
                    "Updated task \"{$taskData['title']}\" status to $statusLabel",
                    'task',
                    $taskId
                );

                $_SESSION['student_task_flash'] = "Task status successfully updated to $statusLabel.";
                header('Location: tasks.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('GROUPWARE student task status error: ' . $e->getMessage());
            $errorMessage = 'Database error while updating task status. Please try again.';
        }
    }
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$validFilters = ['all', 'pending', 'in_progress', 'completed'];
if (!in_array($statusFilter, $validFilters, true)) {
    $statusFilter = 'all';
}

$tasks = [];
try {
    $sql = "
        SELECT t.id, t.title, t.description, t.status, t.due_date, t.created_at,
               tm.team_name, creator.full_name AS instructor_name
        FROM tasks t
        INNER JOIN teams tm ON tm.id = t.team_id
        LEFT JOIN users creator ON creator.id = t.created_by
        WHERE t.assigned_to = :student_id
    ";

    if ($statusFilter !== 'all') {
        $sql .= " AND t.status = :status";
    }

    $sql .= " ORDER BY
        CASE WHEN t.status = 'pending' THEN 0
             WHEN t.status = 'in_progress' THEN 1
             ELSE 2 END,
        t.due_date ASC,
        t.created_at DESC";

    $query = $conn->prepare($sql);
    $params = [':student_id' => $studentId];
    if ($statusFilter !== 'all') {
        $params[':status'] = $statusFilter;
    }
    $query->execute($params);
    $tasks = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('GROUPWARE student tasks page error: ' . $e->getMessage());
    $errorMessage = 'Failed to load task assignments.';
}

$today = date('Y-m-d');
$pageTitle = "My Assigned Tasks";
$pageTitleHeader = "My Tasks";
$activeNav = "tasks";
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
                    <h1 class="page-title">My Assigned Tasks</h1>
                    <p class="page-subtitle">Track, execute, and update the status of your project deliverables.</p>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span><?php echo htmlspecialchars($successMessage); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($errorMessage); ?></span>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div style="display: flex; gap: 8px; margin-bottom: 22px; flex-wrap: wrap;">
                <a href="tasks.php?status=all" class="btn <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">All Tasks</a>
                <a href="tasks.php?status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Pending</a>
                <a href="tasks.php?status=in_progress" class="btn <?php echo $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">In Progress</a>
                <a href="tasks.php?status=completed" class="btn <?php echo $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Completed</a>
            </div>

            <?php if (!empty($tasks)): ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($tasks as $task): ?>
                        <?php
                            $status = (string) $task['status'];
                            $isOverdue = $task['due_date'] && $task['due_date'] < $today && $status !== 'completed';
                        ?>
                        <article class="card" style="padding: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 12px;">
                                <div>
                                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </h3>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span><?php echo htmlspecialchars($task['team_name']); ?></span>
                                        <span>&bull;</span>
                                        <span>Assigned by <?php echo htmlspecialchars($task['instructor_name'] ?? 'Project Lead'); ?></span>
                                        <span>&bull;</span>
                                        <span>Due <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No deadline'; ?></span>
                                        <?php if ($isOverdue): ?>
                                            <span style="color: var(--danger); font-weight: 700;">&bull; Overdue</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <span class="status-badge status-<?php echo htmlspecialchars($status); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $status)); ?>
                                </span>
                            </div>

                            <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 18px; white-space: pre-wrap;"><?php echo htmlspecialchars($task['description']); ?></p>

                            <!-- Task Status Update Control -->
                            <form method="POST" action="tasks.php" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding-top: 14px; border-top: 1px solid var(--border);">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">

                                <label for="status-<?php echo (int) $task['id']; ?>" style="font-size: 0.82rem; font-weight: 600; color: var(--text-secondary);">
                                    Update Status:
                                </label>
                                <select id="status-<?php echo (int) $task['id']; ?>" name="status" class="form-select" style="width: auto; min-width: 160px; padding: 6px 10px; font-size: 0.82rem;">
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    Save Status
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <h3 class="empty-state-title">No tasks found</h3>
                        <p class="empty-state-text">There are no tasks matching this filter. Once tasks are assigned to you, they will appear here.</p>
                        <?php if ($statusFilter !== 'all'): ?>
                            <a href="tasks.php?status=all" class="btn btn-secondary btn-sm">Reset Filter</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
