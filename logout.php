<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the current session
session_destroy();

// Return to the login page
header("Location: index.php");
exit;
?>