<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/activity_logger.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth('instructor', '../login.php');

$instructorId = (int) $_SESSION['user_id'];
$instructorName = $_SESSION['full_name'] ?? 'Project Lead';

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

// Create a task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_task') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $errors[] = 'Session validation token failed. Please refresh the page and try again.';
    }

    $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
    $studentId = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT);
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $dueDate = trim((string) ($_POST['due_date'] ?? ''));

    if (!$teamId || $teamId < 1) {
        $errors[] = 'Please select a team workspace.';
    }
    if (!$studentId || $studentId < 1) {
        $errors[] = 'Please select a team member to assign this task to.';
    }
    if ($title === '') {
        $errors[] = 'Task title is required.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($title) : strlen($title)) > 150) {
        $errors[] = 'Task title must be 150 characters or fewer.';
    }
    if ($description === '') {
        $errors[] = 'Please provide a task description and acceptance criteria.';
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $dueDate);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $dueDate) {
        $errors[] = 'Please choose a valid deadline date.';
    } elseif ($dueDate < date('Y-m-d')) {
        $errors[] = 'Due date cannot be set in the past.';
    }

    if (!$errors) {
        try {
            // Verify assigned user is indeed in selected team
            $memberCheck = $conn->prepare("
                SELECT COUNT(*)
                FROM team_members tm
                INNER JOIN users u ON u.id = tm.user_id
                WHERE tm.team_id = :team_id
                  AND tm.user_id = :student_id
                  AND u.role = 'student'
            ");
            $memberCheck->execute([
                ':team_id' => $teamId,
                ':student_id' => $studentId,
            ]);

            if ((int) $memberCheck->fetchColumn() !== 1) {
                $errors[] = 'The selected member does not belong to this team workspace.';
            } else {
                $insertTask = $conn->prepare("
                    INSERT INTO tasks
                        (team_id, title, description, assigned_to, created_by, due_date, status)
                    VALUES
                        (:team_id, :title, :description, :assigned_to, :created_by, :due_date, 'pending')
                ");
                $insertTask->execute([
                    ':team_id' => $teamId,
                    ':title' => $title,
                    ':description' => $description,
                    ':assigned_to' => $studentId,
                    ':created_by' => $instructorId,
                    ':due_date' => $dueDate,
                ]);

                $newTaskId = (int) $conn->lastInsertId();

                logActivity(
                    $conn,
                    $instructorId,
                    (int) $teamId,
                    'task_assigned',
                    "Assigned task: \"$title\"",
                    'task',
                    $newTaskId
                );

                $_SESSION['task_flash'] = 'Task successfully created and assigned to team member.';
                header('Location: tasks.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('GROUPWARE task creation error: ' . $e->getMessage());
            $errors[] = 'The task could not be saved. Please try again.';
        }
    }
}

// Filters
$filterTeam = isset($_GET['team_id']) ? (int) $_GET['team_id'] : 0;
$filterStatus = (string) ($_GET['status'] ?? 'all');
$allowedStatuses = ['all', 'pending', 'in_progress', 'completed'];
if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = 'all';
}

try {
    $teams = $conn->query('SELECT id, team_name FROM teams ORDER BY team_name ASC')->fetchAll(PDO::FETCH_ASSOC);

    $statsQuery = $conn->query("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'pending') AS pending,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'completed') AS completed
        FROM tasks
    ");
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

    $sql .= " ORDER BY
                CASE WHEN t.status = 'pending' THEN 0
                     WHEN t.status = 'in_progress' THEN 1
                     ELSE 2 END,
                t.due_date ASC,
                t.created_at DESC";

    $taskQuery = $conn->prepare($sql);
    $taskQuery->execute($params);
    $tasks = $taskQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('GROUPWARE instructor tasks page error: ' . $e->getMessage());
    $pageError = 'Failed to load task records.';
}

$today = date('Y-m-d');
$pageTitle = "Task Management";
$pageTitleHeader = "Tasks";
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
                    <h1 class="page-title">Task Management &amp; Delegation</h1>
                    <p class="page-subtitle">Assign deliverables to team members, set due dates, and monitor real-time execution.</p>
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

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endforeach; ?>

            <!-- Task Statistics Grid -->
            <section class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Total Assigned Tasks</span>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-trend">Across all active workspaces</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Pending</span>
                    <div class="stat-value" style="color: var(--warning);"><?php echo $stats['pending']; ?></div>
                    <div class="stat-trend">Awaiting member kickoff</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">In Progress</span>
                    <div class="stat-value" style="color: var(--primary);"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-trend">Active development</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Completed</span>
                    <div class="stat-value" style="color: var(--success);"><?php echo $stats['completed']; ?></div>
                    <div class="stat-trend">Finished deliverables</div>
                </div>
            </section>

            <!-- Columns: Create Task Form & Task Monitoring List -->
            <div class="grid-1-2">
                <!-- Create Task Form -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Create &amp; Assign Task</h3>
                            <p class="card-subtitle">Select team, assign to member, set deadline</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teams)): ?>
                            <div class="empty-state">
                                <h4 class="empty-state-title">No teams available</h4>
                                <p class="empty-state-text">Create a team workspace first before assigning tasks.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="tasks.php" autocomplete="off">
                                <input type="hidden" name="action" value="create_task">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                                <div class="form-group">
                                    <label for="team_id" class="form-label">Team Workspace</label>
                                    <select name="team_id" id="team_id" class="form-select" required>
                                        <option value="">Select team workspace...</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo (int) $team['id']; ?>" <?php echo (isset($teamId) && (int) $teamId === (int) $team['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($team['team_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="assigned_to" class="form-label">Assignee</label>
                                    <select name="assigned_to" id="assigned_to" class="form-select" required disabled>
                                        <option value="">Select team workspace first</option>
                                    </select>
                                    <span class="form-hint" id="student-help">Only members enrolled in the selected workspace can be assigned.</span>
                                </div>

                                <div class="form-group">
                                    <label for="title" class="form-label">Task Title</label>
                                    <input
                                        type="text"
                                        id="title"
                                        name="title"
                                        class="form-control"
                                        maxlength="150"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                        placeholder="e.g. Architect PostgreSQL schema for analytics"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="description" class="form-label">Description &amp; Acceptance Criteria</label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        class="form-textarea"
                                        required
                                        placeholder="Specific implementation details, requirements, and deliverables..."
                                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input
                                        type="date"
                                        id="due_date"
                                        name="due_date"
                                        class="form-control"
                                        min="<?php echo htmlspecialchars($today); ?>"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>"
                                    >
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 6px;">
                                    Create and Assign Task
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Task Monitoring Table & Filters -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Task Monitoring</h3>
                            <p class="card-subtitle">Review status updates from assigned collaborators</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" action="tasks.php" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px;">
                            <div style="flex: 1; min-width: 160px;">
                                <label for="filter-team" class="form-label">Filter by Team</label>
                                <select name="team_id" id="filter-team" class="form-select">
                                    <option value="0">All Workspaces</option>
                                    <?php foreach ($teams as $team): ?>
                                        <option value="<?php echo (int) $team['id']; ?>" <?php echo $filterTeam === (int) $team['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($team['team_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div style="flex: 1; min-width: 140px;">
                                <label for="filter-status" class="form-label">Filter by Status</label>
                                <select name="status" id="filter-status" class="form-select">
                                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Apply Filters</button>
                            <a href="tasks.php" class="btn btn-ghost btn-sm" style="height: 38px;">Reset</a>
                        </form>

                        <!-- Tasks Table -->
                        <?php if (!empty($tasks)): ?>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Task Details</th>
                                            <th>Workspace / Assignee</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                            <?php
                                                $status = (string) $task['status'];
                                                $due = $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'Not set';
                                                $isOverdue = $task['due_date'] && $task['due_date'] < $today && $status !== 'completed';
                                            ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 2px;">
                                                        <?php echo htmlspecialchars($task['title']); ?>
                                                    </div>
                                                    <div style="font-size: 0.78rem; color: var(--text-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?php echo htmlspecialchars($task['description']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-size: 0.84rem; color: var(--text-primary); font-weight: 500;">
                                                        <?php echo htmlspecialchars($task['assignee_name']); ?>
                                                    </div>
                                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                                        <?php echo htmlspecialchars($task['team_name']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="font-size: 0.82rem; color: <?php echo $isOverdue ? 'var(--danger)' : 'var(--text-secondary)'; ?>; font-weight: <?php echo $isOverdue ? '700' : '500'; ?>;">
                                                        <?php echo htmlspecialchars($due); ?>
                                                        <?php if ($isOverdue): ?><br><span style="color: var(--danger); font-size: 0.72rem;">Overdue</span><?php endif; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo htmlspecialchars($status); ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($status))); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <h4 class="empty-state-title">No tasks found</h4>
                                <p class="empty-state-text">No tasks match your filter criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
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
            studentSelect.add(new Option('Select team workspace first', ''));
            if (helpText) helpText.textContent = 'Only members belonging to the selected team can be assigned.';
            return;
        }

        studentSelect.add(new Option('Loading members...', ''));
        if (helpText) helpText.textContent = 'Querying workspace members...';

        try {
            const response = await fetch('get_team_students.php?team_id=' + encodeURIComponent(teamId), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            studentSelect.innerHTML = '';

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Could not load team members.');
            }

            if (!data.students.length) {
                studentSelect.add(new Option('No members in this team', ''));
                if (helpText) helpText.textContent = 'Onboard members to this team before assigning tasks.';
                return;
            }

            studentSelect.add(new Option('Choose a team member...', ''));
            data.students.forEach((student) => {
                const option = new Option(student.full_name + ' (' + student.email + ')', student.id);
                studentSelect.add(option);
            });
            studentSelect.disabled = false;
            if (helpText) helpText.textContent = data.students.length + ' collaborator(s) available for task assignment.';
        } catch (error) {
            studentSelect.innerHTML = '';
            studentSelect.add(new Option('Unable to load team members', ''));
            if (helpText) helpText.textContent = error.message;
        }
    }

    teamSelect.addEventListener('change', loadTeamStudents);
    if (teamSelect.value) loadTeamStudents();
})();
</script>

<?php include __DIR__ . "/../includes/layout_footer.php"; ?>
