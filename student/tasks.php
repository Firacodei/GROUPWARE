<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'student'
) {
    header('Location: ../index.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['full_name'] ?? 'Student';

function student_task_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$successMessage = $_SESSION['student_task_flash'] ?? '';
unset($_SESSION['student_task_flash']);
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $newStatus = (string) ($_POST['status'] ?? '');
    $allowedStatuses = ['pending', 'in_progress', 'completed'];

    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $errorMessage = 'Your session check failed. Refresh the page and try again.';
    } elseif (!$taskId || $taskId < 1) {
        $errorMessage = 'Invalid task selected.';
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $errorMessage = 'Invalid task status.';
    } else {
        try {
            // This condition ensures students can update only tasks assigned to them.
            $update = $conn->prepare(
                'UPDATE tasks SET status = :status WHERE id = :task_id AND assigned_to = :student_id'
            );
            $update->execute([
                ':status' => $newStatus,
                ':task_id' => $taskId,
                ':student_id' => $studentId,
            ]);

            $check = $conn->prepare('SELECT COUNT(*) FROM tasks WHERE id = :task_id AND assigned_to = :student_id');
            $check->execute([':task_id' => $taskId, ':student_id' => $studentId]);

            if ((int) $check->fetchColumn() !== 1) {
                $errorMessage = 'That task was not assigned to your account.';
            } else {
                $_SESSION['student_task_flash'] = 'Task status updated successfully.';
                header('Location: tasks.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('GROUPWARE student task status error: ' . $e->getMessage());
            $errorMessage = 'The task status could not be updated. Please try again.';
        }
    }
}

$tasks = [];
try {
    $query = $conn->prepare(
        'SELECT t.id, t.title, t.description, t.status, t.due_date, t.created_at,
                tm.team_name, creator.full_name AS instructor_name
         FROM tasks t
         INNER JOIN teams tm ON tm.id = t.team_id
         LEFT JOIN users creator ON creator.id = t.created_by
         WHERE t.assigned_to = :student_id
         ORDER BY
            CASE WHEN t.status = \'pending\' THEN 0
                 WHEN t.status = \'in_progress\' THEN 1
                 ELSE 2 END,
            t.due_date ASC,
            t.created_at DESC'
    );
    $query->execute([':student_id' => $studentId]);
    $tasks = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('GROUPWARE student tasks page error: ' . $e->getMessage());
    $errorMessage = 'Your tasks could not be loaded. Please contact your instructor.';
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .student-tasks-wrap { max-width: 1100px; margin: 0 auto; padding: 30px 22px; }
        .student-tasks-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
        .student-tasks-head h1 { margin: 0 0 6px; color: #172554; font-size: 1.7rem; }
        .student-tasks-head p { margin: 0; color: #64748b; line-height: 1.5; }
        .student-back { color: #263b8f; font-weight: 600; text-decoration: none; font-size: .88rem; }
        .student-task-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 15px; padding: 20px; margin-bottom: 14px; box-shadow: 0 4px 14px rgba(15,23,42,.04); }
        .student-task-top { display: flex; justify-content: space-between; gap: 15px; align-items: start; }
        .student-task-card h2 { margin: 0 0 7px; color: #172554; font-size: 1.08rem; }
        .student-task-card p { margin: 0 0 14px; color: #64748b; line-height: 1.6; white-space: pre-wrap; }
        .student-task-meta { margin-bottom: 13px; color: #64748b; font-size: .82rem; }
        .student-task-status { display: inline-block; border-radius: 999px; padding: 5px 9px; font-size: .73rem; font-weight: 700; white-space: nowrap; }
        .student-task-status.pending { background: #fff7ed; color: #9a3412; }
        .student-task-status.in_progress { background: #eff6ff; color: #1d4ed8; }
        .student-task-status.completed { background: #ecfdf5; color: #047857; }
        .student-task-actions { display: flex; flex-wrap: wrap; align-items: end; gap: 9px; }
        .student-task-actions label { display: block; margin-bottom: 5px; color: #475569; font-size: .78rem; font-weight: 600; }
        .student-task-actions select { min-width: 170px; border: 1px solid #d8e0eb; border-radius: 8px; padding: 10px; background: #fff; font: inherit; }
        .student-task-actions button { border: 0; border-radius: 8px; padding: 11px 15px; background: #263b8f; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .student-task-alert { border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; font-size: .88rem; }
        .student-task-alert.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .student-task-alert.error { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; }
        .student-task-empty { padding: 45px 20px; background: #fff; border: 1px solid #e5eaf2; border-radius: 15px; text-align: center; color: #64748b; }
        .student-task-empty strong { display: block; color: #334155; margin-bottom: 6px; }
        @media (max-width: 600px) { .student-tasks-head, .student-task-top { align-items: flex-start; flex-direction: column; } .student-tasks-wrap { padding: 22px 14px; } }
    </style>
</head>
<body class="dashboard-body">
<main class="student-tasks-wrap">
    <header class="student-tasks-head">
        <div><h1>My Assigned Tasks</h1><p>Hello, <?php echo student_task_h($studentName); ?>. Update a task as you work on it.</p></div>
        <a class="student-back" href="dashboard.php">&larr; Back to dashboard</a>
    </header>

    <?php if ($successMessage): ?><div class="student-task-alert success" role="status"><?php echo student_task_h($successMessage); ?></div><?php endif; ?>
    <?php if ($errorMessage): ?><div class="student-task-alert error" role="alert"><?php echo student_task_h($errorMessage); ?></div><?php endif; ?>

    <?php if ($tasks): ?>
        <?php foreach ($tasks as $task): ?>
            <?php $status = (string) $task['status']; $isOverdue = $task['due_date'] && $task['due_date'] < $today && $status !== 'completed'; ?>
            <article class="student-task-card">
                <div class="student-task-top">
                    <div><h2><?php echo student_task_h($task['title']); ?></h2><div class="student-task-meta"><?php echo student_task_h($task['team_name']); ?> &bull; Assigned by <?php echo student_task_h($task['instructor_name'] ?? 'Instructor'); ?> &bull; Due <?php echo $task['due_date'] ? student_task_h(date('M j, Y', strtotime($task['due_date']))) : 'date not set'; ?><?php if ($isOverdue): ?> <strong style="color:#be123c;">&bull; Overdue</strong><?php endif; ?></div></div>
                    <span class="student-task-status <?php echo student_task_h($status); ?>"><?php echo student_task_h(ucwords(str_replace('_', ' ', $status))); ?></span>
                </div>
                <p><?php echo student_task_h($task['description']); ?></p>
                <form method="POST" action="tasks.php" class="student-task-actions">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="csrf_token" value="<?php echo student_task_h($csrfToken); ?>">
                    <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                    <div><label for="status-<?php echo (int) $task['id']; ?>">Update status</label><select id="status-<?php echo (int) $task['id']; ?>" name="status"><option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option><option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In progress</option><option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option></select></div>
                    <button type="submit">Save status</button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="student-task-empty"><strong>No tasks assigned yet</strong>Your instructor's assignments will appear here.</div>
    <?php endif; ?>
</main>
</body>
</html>
