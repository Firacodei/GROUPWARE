<?php
/**
 * GROUPWARE - Layout Footer Component
 */
if (!isset($rootUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $rootUrl = (strpos($scriptDir, 'admin') !== false || strpos($scriptDir, 'instructor') !== false || strpos($scriptDir, 'student') !== false) ? '../' : './';
}
?>
    <!-- Global Toast Container -->
    <div id="global-toast-container" class="toast-container" aria-live="polite"></div>

    <!-- Core App JS -->
    <script src="<?php echo $rootUrl; ?>assets/js/app.js"></script>

    <script>
        // Initialize Lucide icons if loaded
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
