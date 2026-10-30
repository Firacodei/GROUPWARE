
<?php
session_start();
require_once "config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: student/dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ?"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "student") {
            header("Location: student/dashboard.php");
        } elseif ($user["role"] === "instructor") {
            header("Location: instructor/dashboard.php");
        } else {
            header("Location: admin/dashboard.php");
        }

        exit;

    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GROUPWARE | Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-container">

    <div class="login-card">

        <h1>GROUPWARE</h1>
        <p>Collaborate. Contribute. Get Recognized.</p>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">

            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>

            <button type="submit">Sign In</button>

        </form>

    </div>

</div>

</body>
</html>
