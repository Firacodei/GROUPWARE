<?php
/**
 * GROUPWARE - Application Topbar Navigation Component
 */
if (!isset($rootUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $rootUrl = (strpos($scriptDir, 'admin') !== false || strpos($scriptDir, 'instructor') !== false || strpos($scriptDir, 'student') !== false) ? '../' : './';
}

$userRole = $_SESSION['role'] ?? 'student';
$userName = $_SESSION['full_name'] ?? 'User';
$breadcrumbs = $breadcrumbs ?? [
    ['label' => 'Workspace', 'url' => $rootUrl . $userRole . '/dashboard.php'],
    ['label' => $pageTitleHeader ?? 'Dashboard', 'url' => null]
];
?>
<header class="app-topbar">
    <div class="topbar-left">
        <!-- Mobile hamburger menu button -->
        <button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open mobile navigation">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">/</span>
                <?php endif; ?>
                <?php if (!empty($crumb['url'])): ?>
                    <a href="<?php echo htmlspecialchars($crumb['url']); ?>"><?php echo htmlspecialchars($crumb['label']); ?></a>
                <?php else: ?>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($crumb['label']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="topbar-right">
        <!-- Quick Search Trigger -->
        <button type="button" class="search-trigger" id="global-search-btn" title="Quick Search (Ctrl + K)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>Search workspace...</span>
            <span class="search-shortcut-key">Ctrl K</span>
        </button>

        <!-- Notification Bell -->
        <button type="button" class="topbar-icon-btn" title="Notifications" onclick="window.showToast('You are all caught up! No unread notifications.', 'info');">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <?php if (isset($pendingProposalsCount) && $pendingProposalsCount > 0): ?>
                <span class="notification-dot"></span>
            <?php endif; ?>
        </button>

        <!-- Profile Dropdown -->
        <div class="profile-dropdown-wrap">
            <button type="button" class="profile-trigger-btn" id="profile-dropdown-trigger" aria-haspopup="true">
                <div class="user-avatar-circle">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div style="display: flex; flex-direction: column; text-align: left; line-height: 1.2;">
                    <span class="user-meta-name"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="user-meta-role"><?php echo ucfirst(htmlspecialchars($userRole)); ?></span>
                </div>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--text-muted); margin-left: 2px;">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div class="dropdown-menu" id="profile-dropdown-menu">
                <div style="padding: 8px 12px; border-bottom: 1px solid var(--border); margin-bottom: 4px;">
                    <strong style="display: block; font-size: 0.84rem; color: var(--text-primary);"><?php echo htmlspecialchars($userName); ?></strong>
                    <span style="font-size: 0.72rem; color: var(--text-muted);"><?php echo htmlspecialchars($_SESSION['email'] ?? ($userRole . '@groupware.io')); ?></span>
                </div>
                <a href="<?php echo $rootUrl . $userRole; ?>/settings.php" class="dropdown-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>Profile &amp; Settings</span>
                </a>
                <button type="button" class="dropdown-item" onclick="window.showToast('Dark SaaS theme is active.', 'info');">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <span>Theme: Dark SaaS</span>
                </button>
                <div class="dropdown-divider"></div>
                <a href="<?php echo $rootUrl; ?>logout.php" class="dropdown-item danger-item" data-confirm="Are you sure you want to sign out?">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Sign Out</span>
                </a>
            </div>
        </div>
    </div>
</header>
