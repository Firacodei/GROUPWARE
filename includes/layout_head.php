<?php
/**
 * GROUPWARE - HTML Head Component
 */
if (!isset($rootUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $rootUrl = (strpos($scriptDir, 'admin') !== false || strpos($scriptDir, 'instructor') !== false || strpos($scriptDir, 'student') !== false) ? '../' : './';
}
$pageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | GROUPWARE' : 'GROUPWARE — Work Together. Build Better.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="GROUPWARE is a modern SaaS collaboration workspace that helps teams organize projects, manage tasks, share ideas, and track progress.">

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Core SaaS Design System Stylesheet -->
    <link rel="stylesheet" href="<?php echo $rootUrl; ?>assets/css/style.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="<?php echo isset($bodyClass) ? htmlspecialchars($bodyClass) : 'app-body'; ?>">
