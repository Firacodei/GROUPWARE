<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Instructor-only page.
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'instructor'
) {
    header('Location: ../index.php');
    exit;
}

$instructorId = (int) $_SESSION['user_id'];
$instructorName = $_SESSION['full_name'] ?? 'Instructor';

function task_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$errors = [];
$successMessage = $_SESSION['task_flash'] ?? '';
unset($_SESSION['task_flash']);
$pageError = '';
$teams = [];
$tasks = [];
$stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];

// Create a task.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_task') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $errors[] = 'Your session check failed. Refresh the page and try again.';
    }

    $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
    $studentId = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT);
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $dueDate = trim((string) ($_POST['due_date'] ?? ''));

    if (!$teamId || $teamId < 1) {
        $errors[] = 'Please select a team.';
    }
    if (!$studentId || $studentId < 1) {
        $errors[] = 'Please select a student.';
    }
    if ($title === '') {
        $errors[] = 'Task title is required.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($title) : strlen($title)) > 150) {
        $errors[] = 'Task title must be 150 characters or fewer.';
    }
    if ($description === '') {
        $errors[] = 'Please provide a task description.';
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $dueDate);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $dueDate) {
        $errors[] = 'Please choose a valid due date.';
    } elseif ($dueDate < date('Y-m-d')) {
        $errors[] = 'The due date cannot be earlier than today.';
    }

    if (!$errors) {
        try {
            // Verify that the selected student is actually a student in this team.
            $memberCheck = $conn->prepare(
                "SELECT COUNT(*)
                 FROM team_members tm
                 INNER JOIN users u ON u.id = tm.user_id
                 WHERE tm.team_id = :team_id
                   AND tm.user_id = :student_id
                   AND u.role = 'student'"
            );
            $memberCheck->execute([
                ':team_id' => $teamId,
                ':student_id' => $studentId,
            ]);

            if ((int) $memberCheck->fetchColumn() !== 1) {
                $errors[] = 'That student is not a member of the selected team.';
            } else {
                $insertTask = $conn->prepare(
                    "INSERT INTO tasks
                        (team_id, title, description, assigned_to, created_by, due_date, status)
                     VALUES
                        (:team_id, :title, :description, :assigned_to, :created_by, :due_date, 'pending')"
                );
                $insertTask->execute([
                    ':team_id' => $teamId,
                    ':title' => $title,
                    ':description' => $description,
                    ':assigned_to' => $studentId,
                    ':created_by' => $instructorId,
                    ':due_date' => $dueDate,
                ]);

                $newTaskId = (int) $conn->lastInsertId();
                require_once __DIR__ . '/../config/activity_logger.php';
                logActivity(
                    $conn,
                    $instructorId,
                    (int) $teamId,
                    'task_assigned',
                    "Assigned task: \"$title\"",
                    'task',
                    $newTaskId
                );

                $_SESSION['task_flash'] = 'Task created and assigned successfully.';
                header('Location: tasks.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('GROUPWARE task creation error: ' . $e->getMessage());
            $errors[] = 'The task could not be saved. Check the tasks table structure and try again.';
        }
    }
}

// Filters for the monitoring table.
$filterTeam = isset($_GET['team_id']) ? (int) $_GET['team_id'] : 0;
$filterStatus = (string) ($_GET['status'] ?? 'all');
$allowedStatuses = ['all', 'pending', 'in_progress', 'completed'];
if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = 'all';
}

try {
    // There is currently no instructor-to-team assignment table in this project,
    // so instructors can see all teams, consistent with the existing dashboard.
    $teams = $conn->query(
        'SELECT id, team_name FROM teams ORDER BY team_name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    $statsQuery = $conn->query(
        "SELECT
            COUNT(*) AS total,
            SUM(status = 'pending') AS pending,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'completed') AS completed
         FROM tasks"
    );
    $statsRow = $statsQuery->fetch(PDO::FETCH_ASSOC) ?: [];
    foreach ($stats as $key => $value) {
        $stats[$key] = (int) ($statsRow[$key] ?? 0);
    }

    $sql = "SELECT
                t.id,
                t.team_id,
                t.title,
                t.description,
                t.status,
                t.due_date,
                t.created_at,
                tm.team_name,
                assignee.full_name AS assignee_name,
                creator.full_name AS creator_name
            FROM tasks t
            INNER JOIN teams tm ON tm.id = t.team_id
            INNER JOIN users assignee ON assignee.id = t.assigned_to
            LEFT JOIN users creator ON creator.id = t.created_by
            WHERE 1 = 1";
    $params = [];

    if ($filterTeam > 0) {
        $sql .= ' AND t.team_id = :team_id';
        $params[':team_id'] = $filterTeam;
    }
    if ($filterStatus !== 'all') {
        $sql .= ' AND t.status = :status';
        $params[':status'] = $filterStatus;
    }

    $sql .= ' ORDER BY
                CASE WHEN t.status = \'pending\' THEN 0
                     WHEN t.status = \'in_progress\' THEN 1
                     ELSE 2 END,
                t.due_date ASC,
                t.created_at DESC';

    $taskQuery = $conn->prepare($sql);
    $taskQuery->execute($params);
    $tasks = $taskQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('GROUPWARE instructor tasks page error: ' . $e->getMessage());
    $pageError = 'Task information could not be loaded. Please check your database connection and table structure.';
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .task-page-content { max-width: 1440px; }
        .task-intro { margin: 0 0 22px; color: var(--text-muted, #64748b); line-height: 1.6; }
        .task-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .task-stat { background: #fff; border: 1px solid #e7ebf2; border-radius: 14px; padding: 18px; box-shadow: 0 4px 14px rgba(15, 23, 42, .04); }
        .task-stat-label { color: #64748b; font-size: .82rem; font-weight: 600; }
        .task-stat-value { display: block; margin-top: 8px; color: #172554; font-size: 1.8rem; font-weight: 700; }
        .task-columns { display: grid; grid-template-columns: minmax(280px, .82fr) minmax(0, 1.6fr); gap: 22px; align-items: start; }
        .task-panel { min-width: 0; background: #fff; border: 1px solid #e7ebf2; border-radius: 16px; padding: 22px; box-shadow: 0 4px 14px rgba(15, 23, 42, .04); }
        .task-panel h3 { margin: 0 0 6px; color: #172554; }
        .task-panel-subtitle { margin: 0 0 20px; color: #64748b; font-size: .88rem; line-height: 1.5; }
        .task-form-group { margin-bottom: 15px; }
        .task-form-group label { display: block; margin-bottom: 7px; color: #334155; font-size: .85rem; font-weight: 600; }
        .task-form-group input, .task-form-group select, .task-form-group textarea, .task-filters select {
            width: 100%; box-sizing: border-box; border: 1px solid #d8e0eb; border-radius: 9px; padding: 11px 12px;
            background: #fff; color: #172033; font: inherit; font-size: .9rem;
        }
        .task-form-group textarea { min-height: 112px; resize: vertical; }
        .task-form-group input:focus, .task-form-group select:focus, .task-form-group textarea:focus, .task-filters select:focus { outline: 3px solid rgba(37, 99, 235, .12); border-color: #4777d8; }
        .task-help { display: block; margin-top: 6px; color: #64748b; font-size: .76rem; }
        .task-submit { width: 100%; border: 0; border-radius: 9px; padding: 12px 16px; background: #263b8f; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .task-submit:hover { background: #1e2f76; }
        .task-alert { border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: .88rem; line-height: 1.5; }
        .task-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .task-alert-error { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; }
        .task-filters { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; margin-bottom: 16px; }
        .task-filters label { display: block; margin-bottom: 5px; color: #475569; font-size: .78rem; font-weight: 600; }
        .task-filters .filter-field { min-width: 145px; }
        .task-filter-button, .task-reset-link { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; border-radius: 8px; padding: 0 14px; font-size: .84rem; font-weight: 600; text-decoration: none; }
        .task-filter-button { border: 0; background: #263b8f; color: #fff; cursor: pointer; }
        .task-reset-link { border: 1px solid #d8e0eb; color: #475569; background: #fff; }
        .task-table-wrap { width: 100%; overflow-x: auto; }
        .task-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .task-table th { padding: 12px 10px; text-align: left; color: #64748b; background: #f8fafc; font-size: .73rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
        .task-table td { padding: 14px 10px; border-top: 1px solid #edf0f5; color: #334155; font-size: .83rem; vertical-align: top; }
        .task-title { display: block; color: #172554; font-weight: 700; margin-bottom: 4px; }
        .task-description { display: block; max-width: 300px; color: #64748b; line-height: 1.45; overflow-wrap: anywhere; }
        .task-meta { display: block; margin-top: 5px; color: #94a3b8; font-size: .75rem; }
        .task-status { display: inline-block; border-radius: 999px; padding: 5px 9px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
        .task-status-pending { background: #fff7ed; color: #9a3412; }
        .task-status-in_progress { background: #eff6ff; color: #1d4ed8; }
        .task-status-completed { background: #ecfdf5; color: #047857; }
        .task-overdue { display: inline-block; margin-top: 5px; color: #be123c; font-size: .72rem; font-weight: 700; }
        .task-empty { padding: 36px 16px; text-align: center; color: #64748b; }
        .task-empty strong { display: block; margin-bottom: 6px; color: #334155; }
        @media (max-width: 1050px) { .task-columns { grid-template-columns: 1fr; } }
        @media (max-width: 700px) { .task-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .task-panel { padding: 16px; } }
    </style>
</head>
<body class="dashboard-body">
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">GW</div>
            <div class="brand-text"><span class="brand-title">GROUPWARE</span><span class="brand-subtitle">Instructor Portal</span></div>
        </div>
        <div class="sidebar-label">ACADEMIC OVERSIGHT</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link"><span class="nav-icon">▦</span>Overview</a>
            <a href="tasks.php" class="nav-link active"><span class="nav-icon">✓</span>Task Management</a>
            <a href="dashboard.php#teams-overview" class="nav-link"><span class="nav-icon">♧</span>Team Overview</a>
            <a href="dashboard.php#proposals-monitoring" class="nav-link"><span class="nav-icon">▤</span>Proposals Monitor</a>
            <a href="dashboard.php#contribution-tracking" class="nav-link"><span class="nav-icon">▥</span>Contribution Tracking</a>
            <a href="dashboard.php#assessments-section" class="nav-link"><span class="nav-icon">☆</span>Assessments &amp; Marks</a>
        </nav>
        <div class="sidebar-bottom"><a href="../logout.php" class="logout-link"><span class="nav-icon">↪</span>Sign Out</a></div>
    </aside>

    <main class="main-content task-page-content">
        <header class="topbar">
            <div class="topbar-left"><h2>Task Management</h2><p>Create assignments and monitor student progress.</p></div>
            <div class="topbar-right"><div class="profile"><div class="avatar"><?php echo task_h(strtoupper(substr($instructorName, 0, 1))); ?></div><div class="profile-details"><strong><?php echo task_h($instructorName); ?></strong><small>Instructor / Faculty</small></div></div></div>
        </header>

        <p class="task-intro">Create tasks for a selected team, assign each task to one of its student members, and monitor progress from pending to completed.</p>

        <?php if ($successMessage): ?>
            <div class="task-alert task-alert-success" role="status"><?php echo task_h($successMessage); ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="task-alert task-alert-error" role="alert"><?php echo task_h($error); ?></div>
        <?php endforeach; ?>
        <?php if ($pageError): ?>
            <div class="task-alert task-alert-error" role="alert"><?php echo task_h($pageError); ?></div>
        <?php endif; ?>

        <section class="task-stats" aria-label="Task summary">
            <div class="task-stat"><span class="task-stat-label">Total tasks</span><strong class="task-stat-value"><?php echo $stats['total']; ?></strong></div>
            <div class="task-stat"><span class="task-stat-label">Pending</span><strong class="task-stat-value"><?php echo $stats['pending']; ?></strong></div>
            <div class="task-stat"><span class="task-stat-label">In progress</span><strong class="task-stat-value"><?php echo $stats['in_progress']; ?></strong></div>
            <div class="task-stat"><span class="task-stat-label">Completed</span><strong class="task-stat-value"><?php echo $stats['completed']; ?></strong></div>
        </section>

        <section class="task-columns">
            <div class="task-panel">
                <h3>Create a task</h3>
                <p class="task-panel-subtitle">Choose a team first. The student list will show only members of that team.</p>

                <?php if (empty($teams)): ?>
                    <div class="task-empty"><strong>No teams are available</strong>Create a team before assigning a task.</div>
                <?php else: ?>
                    <form method="POST" action="tasks.php" autocomplete="off">
                        <input type="hidden" name="action" value="create_task">
                        <input type="hidden" name="csrf_token" value="<?php echo task_h($csrfToken); ?>">

                        <div class="task-form-group">
                            <label for="team_id">Team</label>
                            <select name="team_id" id="team_id" required>
                                <option value="">Select a team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo (int) $team['id']; ?>" <?php echo (isset($teamId) && (int) $teamId === (int) $team['id']) ? 'selected' : ''; ?>><?php echo task_h($team['team_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="task-form-group">
                            <label for="assigned_to">Assign to student</label>
                            <select name="assigned_to" id="assigned_to" required disabled>
                                <option value="">Select a team first</option>
                            </select>
                            <small class="task-help" id="student-help">Only students belonging to the selected team can be assigned.</small>
                        </div>

                        <div class="task-form-group">
                            <label for="title">Task title</label>
                            <input type="text" id="title" name="title" maxlength="150" required value="<?php echo task_h($_POST['title'] ?? ''); ?>" placeholder="e.g. Build the login interface">
                        </div>

                        <div class="task-form-group">
                            <label for="description">Task description</label>
                            <textarea id="description" name="description" required placeholder="Describe what the student needs to complete..."><?php echo task_h($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="task-form-group">
                            <label for="due_date">Due date</label>
                            <input type="date" id="due_date" name="due_date" min="<?php echo task_h($today); ?>" required value="<?php echo task_h($_POST['due_date'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="task-submit">Create and assign task</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="task-panel">
                <h3>Task monitoring</h3>
                <p class="task-panel-subtitle">Review assignments and their latest student-reported status.</p>

                <form method="GET" action="tasks.php" class="task-filters">
                    <div class="filter-field">
                        <label for="filter-team">Team</label>
                        <select name="team_id" id="filter-team">
                            <option value="0">All teams</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo (int) $team['id']; ?>" <?php echo $filterTeam === (int) $team['id'] ? 'selected' : ''; ?>><?php echo task_h($team['team_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="filter-status">Status</label>
                        <select name="status" id="filter-status">
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All statuses</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>In progress</option>
                            <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <button type="submit" class="task-filter-button">Apply filters</button>
                    <a href="tasks.php" class="task-reset-link">Reset</a>
                </form>

                <?php if (!empty($tasks)): ?>
                    <div class="task-table-wrap">
                        <table class="task-table">
                            <thead><tr><th>Task</th><th>Team / Student</th><th>Due date</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <?php
                                    $status = (string) $task['status'];
                                    $due = $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'Not set';
                                    $isOverdue = $task['due_date'] && $task['due_date'] < $today && $status !== 'completed';
                                ?>
                                <tr>
                                    <td><span class="task-title"><?php echo task_h($task['title']); ?></span><span class="task-description"><?php echo task_h($task['description']); ?></span><span class="task-meta">Created <?php echo task_h(date('M j, Y', strtotime($task['created_at']))); ?><?php if ($task['creator_name']): ?> by <?php echo task_h($task['creator_name']); ?><?php endif; ?></span></td>
                                    <td><?php echo task_h($task['team_name']); ?><span class="task-meta"><?php echo task_h($task['assignee_name']); ?></span></td>
                                    <td><?php echo task_h($due); ?><?php if ($isOverdue): ?><span class="task-overdue">Overdue</span><?php endif; ?></td>
                                    <td><span class="task-status task-status-<?php echo task_h($status); ?>"><?php echo task_h(ucwords(str_replace('_', ' ', $status))); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="task-empty"><strong>No tasks found</strong>Tasks you create will appear here. Change or reset the filters to see other assignments.</div>
                <?php endif; ?>
            </div>
        </section>

        <footer class="dashboard-footer">GROUPWARE &copy; 2026 &bull; Task Creation &amp; Monitoring</footer>
    </main>
</div>

<script>
(() => {
    const teamSelect = document.getElementById('team_id');
    const studentSelect = document.getElementById('assigned_to');
    const helpText = document.getElementById('student-help');

    if (!teamSelect || !studentSelect) return;

    async function loadTeamStudents() {
        const teamId = teamSelect.value;
        studentSelect.innerHTML = '';
        studentSelect.disabled = true;

        if (!teamId) {
            studentSelect.add(new Option('Select a team first', ''));
            helpText.textContent = 'Only students belonging to the selected team can be assigned.';
            return;
        }

        studentSelect.add(new Option('Loading students...', ''));
        helpText.textContent = 'Loading team members...';

        try {
            const response = await fetch('get_team_students.php?team_id=' + encodeURIComponent(teamId), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            studentSelect.innerHTML = '';

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Could not load students.');
            }

            if (!data.students.length) {
                studentSelect.add(new Option('No students in this team', ''));
                helpText.textContent = 'Add student members to this team before assigning tasks.';
                return;
            }

            studentSelect.add(new Option('Choose a student', ''));
            data.students.forEach((student) => {
                const option = new Option(student.full_name + ' (' + student.email + ')', student.id);
                studentSelect.add(option);
            });
            studentSelect.disabled = false;
            helpText.textContent = data.students.length + ' student member(s) found.';
        } catch (error) {
            studentSelect.innerHTML = '';
            studentSelect.add(new Option('Unable to load students', ''));
            helpText.textContent = error.message;
        }
    }

    teamSelect.addEventListener('change', loadTeamStudents);
    if (teamSelect.value) loadTeamStudents();
})();
</script>
</body>
</html>
