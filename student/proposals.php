
<?php
session_start();
require_once "../config/database.php";

// Make sure the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$message = "";

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

            $message = "Your proposal was submitted successfully!";
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="proposal-container">

    <div class="proposal-header">
        <h1>Change Proposals</h1>
        <p>Share your ideas and help improve your team's project.</p>
    </div>

    <div class="proposal-card">

        <h2>Submit a New Proposal</h2>

        <?php if ($message): ?>
            <div class="proposal-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="proposal-form">

            <label for="title">Proposal Title</label>
            <input
                type="text"
                id="title"
                name="title"
                maxlength="150"
                placeholder="e.g. Add a search feature"
                required
            >

            <label for="description">Proposal Description</label>
            <textarea
                id="description"
                name="description"
                placeholder="Explain your proposed change and why it would benefit the project..."
                required
            ></textarea>

            <button type="submit" class="proposal-submit">
                Submit Proposal
            </button>

        </form>

        <a href="dashboard.php" class="proposal-back">
            ← Back to Dashboard
        </a>

    </div>
</div>

</body>
</html>
