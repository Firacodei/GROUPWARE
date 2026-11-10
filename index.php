<?php
session_start();
require_once "config/database.php";

// Redirect authenticated users to their corresponding dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student/dashboard.php");
    } elseif ($_SESSION['role'] === 'instructor') {
        header("Location: instructor/dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    }
    exit;
}

// Preserve POST login support if submitted directly to index.php
$loginError = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $loginError = "Please enter both your email address and password.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int) $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
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
                $loginError = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $loginError = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GROUPWARE — Work Together. Build Better.</title>
    <meta name="description" content="GROUPWARE is a collaborative workspace that helps teams organize projects, manage tasks, share ideas, make decisions, track contributions, and monitor project progress.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="landing-body">

<!-- Landing Navigation -->
<header class="landing-nav">
    <div style="display: flex; align-items: center; gap: 36px;">
        <a href="index.php" class="brand-wrapper">
            <div class="brand-icon-symbol">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <span class="brand-name" style="font-size: 1.15rem; font-weight: 700;">GROUPWARE</span>
        </a>

        <ul class="landing-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#workflow">Workflow</a></li>
            <li><a href="#decisions">Consensus Engine</a></li>
            <li><a href="#analytics">Analytics</a></li>
        </ul>
    </div>

    <div style="display: flex; align-items: center; gap: 12px;">
        <a href="login.php" class="btn btn-secondary btn-sm">Sign In</a>
        <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
    </div>
</header>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-pill">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
        </svg>
        <span>Modern Collaboration Platform</span>
    </div>

    <h1 class="hero-title">Work Together.<br><span style="color: var(--primary);">Build Better.</span></h1>

    <p class="hero-subtitle">
        GROUPWARE is a unified collaborative workspace designed for high-performing teams, developers, and project collaborators to organize tasks, propose architectural changes, reach consensus, and track progress effortlessly.
    </p>

    <div class="hero-actions">
        <a href="register.php" class="btn btn-primary btn-lg">
            <span>Join Workspace Free</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </a>
        <a href="login.php" class="btn btn-secondary btn-lg">
            <span>Sign In to Platform</span>
        </a>
    </div>

    <!-- Product Mockup / Interactive Preview -->
    <div class="product-preview-frame">
        <div class="preview-topbar">
            <div class="preview-dot" style="background:#EF4444;"></div>
            <div class="preview-dot" style="background:#F59E0B;"></div>
            <div class="preview-dot" style="background:#22C55E;"></div>
            <span style="font-size:0.75rem; color:var(--text-muted); margin-left: 10px; font-family:var(--font-mono);">groupware.internal / workspace / engineering</span>
        </div>

        <div style="padding: 24px; text-align: left; background: var(--bg-primary);">
            <!-- Mini Dashboard Snapshot -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
                <div>
                    <h4 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 4px;">Engineering Workspace Overview</h4>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">Sprint 12 &bull; Active Collaboration &bull; 94% Participation Rate</p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <span class="status-badge status-approved">All Systems Operational</span>
                </div>
            </div>

            <!-- Mini Stats Row -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px;">
                <div class="stat-card" style="padding: 14px;">
                    <span class="stat-label" style="font-size: 0.72rem;">Active Projects</span>
                    <div class="stat-value" style="font-size: 1.4rem;">4</div>
                </div>
                <div class="stat-card" style="padding: 14px;">
                    <span class="stat-label" style="font-size: 0.72rem;">Active Tasks</span>
                    <div class="stat-value" style="font-size: 1.4rem; color: var(--primary);">12</div>
                </div>
                <div class="stat-card" style="padding: 14px;">
                    <span class="stat-label" style="font-size: 0.72rem;">Completed Tasks</span>
                    <div class="stat-value" style="font-size: 1.4rem; color: var(--success);">28</div>
                </div>
                <div class="stat-card" style="padding: 14px;">
                    <span class="stat-label" style="font-size: 0.72rem;">Pending Proposals</span>
                    <div class="stat-value" style="font-size: 1.4rem; color: var(--warning);">3</div>
                </div>
            </div>

            <!-- Mini Task List Row -->
            <div class="card" style="padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <strong style="font-size: 0.88rem; color: var(--text-primary);">Recent Proposals Awaiting Decision</strong>
                    <span style="font-size: 0.75rem; color: var(--primary);">Real-time Majority Voting Active</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--bg-elevated); border-radius: 8px; border: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="status-badge status-pending">Pending Vote</span>
                            <span style="font-size: 0.85rem; font-weight: 600;">Migrate authentication pipeline to JWT tokens</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; font-size: 0.78rem; color: var(--text-secondary);">
                            <span>2 / 3 approvals required</span>
                            <span class="status-badge status-in_progress" style="font-size: 0.7rem;">Voting Open</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--bg-elevated); border-radius: 8px; border: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="status-badge status-approved">Approved</span>
                            <span style="font-size: 0.85rem; font-weight: 600;">Implement automated database activity logging</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; font-size: 0.78rem; color: var(--success);">
                            <span>Consensus Reached &bull; Passed by 4 votes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature Section -->
<section id="features" style="padding: 80px 24px; border-top: 1px solid var(--border);">
    <div style="text-align: center; max-width: 650px; margin: 0 auto 50px auto;">
        <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--primary);">Capabilities</span>
        <h2 style="font-size: 2.2rem; font-weight: 700; letter-spacing: -0.02em; color: var(--text-primary); margin-top: 8px;">Everything Your Team Needs to Build</h2>
        <p style="font-size: 0.95rem; color: var(--text-secondary); margin-top: 10px;">Replace fragmented chat threads and scattered spreadsheets with an integrated, consensus-driven platform.</p>
    </div>

    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </div>
            <h3 class="feature-title">Democratic Consensus</h3>
            <p class="feature-desc">Propose project changes, review technical rationale, and vote transparently. The system automatically computes thresholds and updates proposal statuses.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
            </div>
            <h3 class="feature-title">Real-Time Task Tracking</h3>
            <p class="feature-desc">Assign tasks directly to team members with precise due dates, priority tags, and status tracking from pending to completed.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <h3 class="feature-title">Audit Log &amp; Activity Stream</h3>
            <p class="feature-desc">Every vote cast, proposal submitted, and task status updated is logged in an immutable activity timeline for complete visibility.</p>
        </div>
    </div>
</section>

<!-- Workflow Explanation -->
<section id="workflow" style="padding: 80px 24px; background: var(--bg-secondary); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div style="max-width: 1040px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--primary);">How It Works</span>
            <h2 style="font-size: 2.2rem; font-weight: 700; letter-spacing: -0.02em; color: var(--text-primary); margin-top: 8px;">A Cohesive Product Lifecycle</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 22px;">
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; margin-bottom: 8px;">01</span>
                <strong style="display: block; font-size: 1.02rem; color: var(--text-primary); margin-bottom: 6px;">Propose</strong>
                <p style="font-size: 0.84rem; color: var(--text-secondary); line-height: 1.5;">Create structured change proposals with technical descriptions and strategic rationale.</p>
            </div>

            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 22px;">
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; margin-bottom: 8px;">02</span>
                <strong style="display: block; font-size: 1.02rem; color: var(--text-primary); margin-bottom: 6px;">Vote</strong>
                <p style="font-size: 0.84rem; color: var(--text-secondary); line-height: 1.5;">Teammates evaluate proposals and cast approval or rejection votes with verified majority math.</p>
            </div>

            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 22px;">
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; margin-bottom: 8px;">03</span>
                <strong style="display: block; font-size: 1.02rem; color: var(--text-primary); margin-bottom: 6px;">Execute</strong>
                <p style="font-size: 0.84rem; color: var(--text-secondary); line-height: 1.5;">Approved proposals convert to actionable tasks assigned to individual team members.</p>
            </div>

            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 22px;">
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; margin-bottom: 8px;">04</span>
                <strong style="display: block; font-size: 1.02rem; color: var(--text-primary); margin-bottom: 6px;">Measure</strong>
                <p style="font-size: 0.84rem; color: var(--text-secondary); line-height: 1.5;">Monitor team participation, completed milestones, and individual contributions via real analytics.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section style="padding: 90px 24px; text-align: center;">
    <div style="max-width: 680px; margin: 0 auto;">
        <h2 style="font-size: 2.3rem; font-weight: 800; letter-spacing: -0.02em; color: var(--text-primary);">Ready to elevate your collaboration?</h2>
        <p style="font-size: 1rem; color: var(--text-secondary); margin: 14px 0 28px 0; line-height: 1.6;">
            Experience the clarity, velocity, and transparency of modern team management with GROUPWARE.
        </p>
        <div style="display: flex; justify-content: center; gap: 14px;">
            <a href="register.php" class="btn btn-primary btn-lg">Create Free Account</a>
            <a href="login.php" class="btn btn-secondary btn-lg">Sign In to Platform</a>
        </div>
    </div>
</section>

<!-- SaaS Footer -->
<footer style="padding: 40px 40px 30px 40px; border-top: 1px solid var(--border); background: var(--bg-sidebar);">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="brand-icon-symbol" style="width: 28px; height: 28px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                </svg>
            </div>
            <strong style="color: var(--text-primary); font-size: 1rem;">GROUPWARE</strong>
            <span style="color: var(--text-muted); font-size: 0.82rem; margin-left: 12px;">Work Together. Build Better.</span>
        </div>

        <div style="font-size: 0.8rem; color: var(--text-muted);">
            &copy; <?php echo date("Y"); ?> GROUPWARE Inc. All rights reserved.
        </div>
    </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
