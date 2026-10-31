
<?php
session_start();
require_once "config/database.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate form fields
    if (
        empty($full_name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {

        try {
            // Check whether email already exists
            $check = $conn->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "This email is already registered.";
            } else {

                // Securely hash the password
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Always register as a student
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role)
                    VALUES (?, ?, ?, 'student')"
                );

                $conn->beginTransaction();

try {
    $stmt->execute([$full_name, $email, $hashedPassword]);

    $newUserId = $conn->lastInsertId();

    $teamStmt = $conn->prepare(
        "INSERT INTO team_members (team_id, user_id)
         VALUES (1, ?)"
    );

    $teamStmt->execute([$newUserId]);

    $conn->commit();

    $message = "Account created successfully! You can now log in.";
    $success = true;

} catch (PDOException $e) {
    $conn->rollBack();
    $message = "Registration failed. Please try again.";
}
            }

        } catch (PDOException $e) {
            $message = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Registration | GROUPWARE</title>

    <link rel="stylesheet" href="/GROUPWARE/assets/css/style.css">
</head>

<body>

<div class="create-user-container">

    <div class="create-user-card">

        <h2>Join GROUPWARE</h2>

        <p>Create your student account to collaborate with your team.</p>

        <?php if ($message !== ""): ?>
            <p class="form-message"
               style="color: <?php echo $success ? '#22c55e' : '#f87171'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if (!$success): ?>

        <form method="POST" action="">

            <label for="full_name">Full Name</label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                placeholder="Enter your full name"
                required
            >

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email address"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Create a password"
                minlength="8"
                required
            >

            <label for="confirm_password">Confirm Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Enter your password again"
                minlength="8"
                required
            >

            <button type="submit">Create Student Account</button>

        </form>

        <?php endif; ?>

        <p>
            Already have an account?
            <a href="/GROUPWARE/index.php">Login here</a>
        </p>

    </div>

</div>

</body>
</html>
