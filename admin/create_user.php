
<?php
session_start();
require_once "../config/database.php";

// Only administrators can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$message = "";

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
    <title>Create Student Account | GROUPWARE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="create-user-container">
    <div class="create-user-card">

        <h2>Create Student Account</h2>
        <p>Register a new student in your GROUPWARE workspace.</p>

        <?php if ($message !== ""): ?>
            <p class="form-message">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name"
                   placeholder="Enter student's full name" required>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="Enter student's email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Create a password"
                   minlength="8" required>

            <button type="submit">Create Account</button>

        </form>

        <p><a href="dashboard.php">Back to Dashboard</a></p>

    </div>
</div>

</body>
</html>
