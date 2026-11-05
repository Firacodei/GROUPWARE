<?php
session_start();
require_once "../config/database.php";

// Make sure the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$message = "";
$isSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($title === "" || $description === "") {
        $message = "Please complete all fields.";
    } else {
        // Find the student's team
        $teamQuery = $conn->prepare(
            "SELECT team_id FROM team_members WHERE user_id = ? LIMIT 1"
        );
        $teamQuery->execute([$user_id]);
        $team = $teamQuery->fetch(PDO::FETCH_ASSOC);

        if ($team) {
            $stmt = $conn->prepare(
                "INSERT INTO proposals
                (team_id, proposed_by, title, description)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $team['team_id'],
                $user_id,
                $title,
                $description
            ]);

            $newProposalId = (int) $conn->lastInsertId();

            require_once __DIR__ . "/../config/activity_logger.php";
            logActivity(
                $conn,
                $user_id,
                (int) $team['team_id'],
                'proposal_created',
                "Submitted change proposal: \"$title\"",
                'proposal',
                $newProposalId
            );

            $message = "Your proposal was submitted successfully and is now open for team review!";
            $isSuccess = true;
        } else {
            $message = "You are not assigned to a team yet.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Proposal | GROUPWARE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="proposal-container">

    <div class="proposal-header">
        <div>
            <h1>Submit Change Proposal</h1>
            <p>Suggest modifications, new features, or architectural adjustments to your group project.</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <div class="proposal-card">

        <?php if ($message): ?>
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

        <form method="POST" action="" class="proposal-form">

            <div class="form-group">
                <label for="title">Proposal Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    placeholder="e.g. Implement user authentication using JWT"
                    value="<?php echo isset($_POST['title']) && !$isSuccess ? htmlspecialchars($_POST['title']) : ''; ?>"
                    required
                >
                <small style="color: var(--text-muted);">Provide a concise, descriptive title for your proposed change.</small>
            </div>

            <div class="form-group">
                <label for="description">Detailed Description &amp; Rationale</label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    placeholder="Describe what needs to change, why it benefits the group project, and any technical considerations..."
                    required
                ><?php echo isset($_POST['description']) && !$isSuccess ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                <small style="color: var(--text-muted);">Explain the context clearly so your team members can make an informed voting decision.</small>
            </div>

            <div style="display: flex; gap: 12px; align-items: center; margin-top: 10px;">
                <button type="submit" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    Submit Proposal
                </button>

                <a href="my_proposals.php" class="btn btn-secondary">
                    View My Proposals
                </a>
            </div>

        </form>

    </div>

</div>

</body>
</html>
