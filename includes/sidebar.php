<?php
/**
 * GROUPWARE - Collapsible Responsive Sidebar Component
 */
if (!isset($rootUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $rootUrl = (strpos($scriptDir, 'admin') !== false || strpos($scriptDir, 'instructor') !== false || strpos($scriptDir, 'student') !== false) ? '../' : './';
}

$userRole = $_SESSION['role'] ?? 'student';
$userName = $_SESSION['full_name'] ?? 'User';
$userId = $_SESSION['user_id'] ?? 0;
$activeNav = $activeNav ?? 'overview';

// Pending proposals count query if not already passed
if (!isset($pendingProposalsCount) && isset($conn) && $userRole === 'student') {
    try {
        $pStmt = $conn->prepare("
            SELECT COUNT(*)
            FROM proposals p
            JOIN team_members tm ON p.team_id = tm.team_id
            WHERE tm.user_id = ? AND p.status = 'pending' AND p.proposed_by <> ?
        ");
        $pStmt->execute([$userId, $userId]);
        $pendingProposalsCount = (int) $pStmt->fetchColumn();
    } catch (Exception $e) {
        $pendingProposalsCount = 0;
    }
}
$pendingProposalsCount = $pendingProposalsCount ?? 0;

$roleBadgeTitle = 'Team Member';
$workspaceName = 'General Workspace';
if ($userRole === 'admin') {
    $roleBadgeTitle = 'Workspace Admin';
    $workspaceName = 'Enterprise Admin';
} elseif ($userRole === 'instructor') {
    $roleBadgeTitle = 'Project Lead';
    $workspaceName = 'Project Workspace';
}
?>
<aside class="app-sidebar" id="app-sidebar">
    <!-- Header: Logo & Wordmark -->
    <div class="sidebar-header">
        <a href="<?php echo $rootUrl . $userRole; ?>/dashboard.php" class="brand-wrapper">
            <div class="brand-icon-symbol">
                <!-- Clean Minimal Geometric Interlocking Flow Symbol -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <span class="brand-name">GROUPWARE</span>
        </a>
        <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" title="Collapse sidebar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="9" y1="3" x2="9" y2="21"></line>
                <path d="M15 10l-2 2 2 2"></path>
            </svg>
        </button>
    </div>

    <!-- Workspace Switcher -->
    <div class="workspace-selector">
        <div class="workspace-badge-btn">
            <div class="workspace-info">
                <div class="workspace-avatar">
                    <?php echo strtoupper(substr($workspaceName, 0, 1)); ?>
                </div>
                <div class="workspace-details">
                    <span class="workspace-name"><?php echo htmlspecialchars($workspaceName); ?></span>
                    <span class="workspace-role-tag"><?php echo $roleBadgeTitle; ?></span>
                </div>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
    </div>

    <!-- Nav List -->
    <div class="sidebar-body">
        <div class="nav-section-title">Core Navigation</div>
        <ul class="sidebar-nav-list">
            <!-- Overview -->
            <li>
                <a href="<?php echo $rootUrl . $userRole; ?>/dashboard.php" class="nav-item-link <?php echo $activeNav === 'overview' ? 'active' : ''; ?>" title="Overview">
                    <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span class="nav-item-text">Overview</span>
                </a>
            </li>

            <?php if ($userRole === 'student'): ?>
                <!-- Tasks -->
                <li>
                    <a href="<?php echo $rootUrl; ?>student/tasks.php" class="nav-item-link <?php echo $activeNav === 'tasks' ? 'active' : ''; ?>" title="My Tasks">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                        <span class="nav-item-text">My Tasks</span>
                    </a>
                </li>
                <!-- Submit Proposal -->
                <li>
                    <a href="<?php echo $rootUrl; ?>student/proposals.php" class="nav-item-link <?php echo $activeNav === 'proposals_new' ? 'active' : ''; ?>" title="Submit Proposal">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                        <span class="nav-item-text">Submit Proposal</span>
                    </a>
                </li>
                <!-- My Proposals -->
                <li>
                    <a href="<?php echo $rootUrl; ?>student/my_proposals.php" class="nav-item-link <?php echo $activeNav === 'my_proposals' ? 'active' : ''; ?>" title="My Proposals">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span class="nav-item-text">My Proposals</span>
                    </a>
                </li>
                <!-- Approvals / Voting -->
                <li>
                    <a href="<?php echo $rootUrl; ?>student/approvals.php" class="nav-item-link <?php echo $activeNav === 'approvals' ? 'active' : ''; ?>" title="Approvals">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        <span class="nav-item-text">Approvals</span>
                        <?php if ($pendingProposalsCount > 0): ?>
                            <span class="nav-item-badge badge-amber"><?php echo $pendingProposalsCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <!-- My Team -->
                <li>
                    <a href="<?php echo $rootUrl; ?>student/team.php" class="nav-item-link <?php echo $activeNav === 'team' ? 'active' : ''; ?>" title="My Team">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="nav-item-text">My Team</span>
                    </a>
                </li>
            <?php elseif ($userRole === 'instructor'): ?>
                <!-- Task Management -->
                <li>
                    <a href="<?php echo $rootUrl; ?>instructor/tasks.php" class="nav-item-link <?php echo $activeNav === 'tasks' ? 'active' : ''; ?>" title="Task Management">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        <span class="nav-item-text">Tasks</span>
                    </a>
                </li>
                <!-- Teams Overview -->
                <li>
                    <a href="<?php echo $rootUrl; ?>instructor/teams.php" class="nav-item-link <?php echo $activeNav === 'teams' ? 'active' : ''; ?>" title="Teams">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="nav-item-text">Teams</span>
                    </a>
                </li>
                <!-- Proposals Monitor -->
                <li>
                    <a href="<?php echo $rootUrl; ?>instructor/proposals.php" class="nav-item-link <?php echo $activeNav === 'proposals' ? 'active' : ''; ?>" title="Proposals Monitor">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                        </svg>
                        <span class="nav-item-text">Proposals</span>
                    </a>
                </li>
            <?php elseif ($userRole === 'admin'): ?>
                <!-- User Accounts -->
                <li>
                    <a href="<?php echo $rootUrl; ?>admin/dashboard.php#user-management" class="nav-item-link <?php echo $activeNav === 'users' ? 'active' : ''; ?>" title="User Accounts">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <polyline points="17 11 19 13 23 9"></polyline>
                        </svg>
                        <span class="nav-item-text">User Accounts</span>
                    </a>
                </li>
                <!-- Create User -->
                <li>
                    <a href="<?php echo $rootUrl; ?>admin/create_user.php" class="nav-item-link <?php echo $activeNav === 'create_user' ? 'active' : ''; ?>" title="Create User">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        <span class="nav-item-text">Add Member</span>
                    </a>
                </li>
                <!-- Teams & Workspaces -->
                <li>
                    <a href="<?php echo $rootUrl; ?>admin/dashboard.php#team-management" class="nav-item-link <?php echo $activeNav === 'teams' ? 'active' : ''; ?>" title="Teams">
                        <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                        </svg>
                        <span class="nav-item-text">Teams</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="nav-section-title">Insights &amp; Settings</div>
        <ul class="sidebar-nav-list">
            <!-- Activity History -->
            <li>
                <a href="<?php echo $rootUrl . $userRole; ?>/activity.php" class="nav-item-link <?php echo $activeNav === 'activity' ? 'active' : ''; ?>" title="Activity History">
                    <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span class="nav-item-text">Activity History</span>
                </a>
            </li>
            <!-- Analytics -->
            <li>
                <a href="<?php echo $rootUrl . $userRole; ?>/analytics.php" class="nav-item-link <?php echo $activeNav === 'analytics' ? 'active' : ''; ?>" title="Analytics">
                    <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span class="nav-item-text">Analytics</span>
                </a>
            </li>
            <!-- Settings -->
            <li>
                <a href="<?php echo $rootUrl . $userRole; ?>/settings.php" class="nav-item-link <?php echo $activeNav === 'settings' ? 'active' : ''; ?>" title="Settings">
                    <svg class="nav-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span class="nav-item-text">Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar User Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user-card">
            <div class="user-avatar-circle">
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>
            <div class="user-details-text" style="overflow: hidden; flex: 1;">
                <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">
                    <?php echo htmlspecialchars($userName); ?>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: capitalize;">
                    <?php echo htmlspecialchars($userRole); ?>
                </div>
            </div>
            <a href="<?php echo $rootUrl; ?>logout.php" title="Sign Out" style="color: var(--text-muted); padding: 4px; display: flex; align-items: center;" data-confirm="Sign out of your session?">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </div>
</aside>
