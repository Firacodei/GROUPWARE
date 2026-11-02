-- ========================================================================
-- GROUPWARE Database Schema & Initial Seed Data
-- Ambo University • Department of Information Technology • IT-402
-- ========================================================================

CREATE DATABASE IF NOT EXISTS `groupware_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `groupware_db`;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(50) NULL,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NULL,
    `password_hash` VARCHAR(255) NULL,
    `role` ENUM('student', 'instructor', 'admin', 'staff_evaluator', 'dean') NOT NULL DEFAULT 'student',
    `avatar` VARCHAR(255) NULL,
    `bio` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TEAMS TABLE
CREATE TABLE IF NOT EXISTS `teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `team_name` VARCHAR(150) NULL,
    `project_title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `course_code` VARCHAR(50) DEFAULT 'IT-402',
    `academic_year` VARCHAR(20) DEFAULT '2026/2027',
    `max_assignment_score` DECIMAL(6,2) DEFAULT 50.00,
    `weight_proposal_created` DECIMAL(6,2) DEFAULT 25.00,
    `weight_proposal_committed` DECIMAL(6,2) DEFAULT 35.00,
    `weight_review_cast` DECIMAL(6,2) DEFAULT 30.00,
    `weight_task_completed` DECIMAL(6,2) DEFAULT 10.00,
    `free_rider_threshold` DECIMAL(5,2) DEFAULT 15.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TEAM MEMBERS TABLE
CREATE TABLE IF NOT EXISTS `team_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role_in_team` ENUM('lead', 'member') DEFAULT 'member',
    `is_active` TINYINT(1) DEFAULT 1,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. PROPOSALS TABLE
CREATE TABLE IF NOT EXISTS `proposals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `proposed_by` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`proposed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. PROPOSAL VOTES TABLE
CREATE TABLE IF NOT EXISTS `proposal_votes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proposal_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `vote` ENUM('approve', 'reject') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_vote` (`proposal_id`, `user_id`),
    FOREIGN KEY (`proposal_id`) REFERENCES `proposals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TASKS TABLE
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `assigned_to` INT NULL,
    `status` ENUM('open', 'in_progress', 'review_pending', 'completed') DEFAULT 'open',
    `line_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. AUDIT LOGS TABLE
CREATE TABLE IF NOT EXISTS `contribution_audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `block_index` INT NOT NULL,
    `team_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `points_earned` DECIMAL(6,2) NOT NULL,
    `details` TEXT NOT NULL,
    `prev_hash` VARCHAR(64) NOT NULL,
    `current_hash` VARCHAR(64) NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================================
-- INITIAL SEED DATA
-- Default Password for all seeded accounts: password123
-- ========================================================================

-- Seed Users
INSERT IGNORE INTO `users` (`id`, `student_id`, `full_name`, `email`, `password`, `password_hash`, `role`) VALUES
(1, 'UGR/1842/14', 'Abebe Bikila', 'abebe.bikila@ambou.edu.et', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', 'student'),
(2, 'UGR/2051/14', 'Chaltu Tolosa', 'chaltu.tolosa@ambou.edu.et', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', 'student'),
(3, 'UGR/1930/14', 'Dawit Kebede', 'dawit.kebede@ambou.edu.et', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', 'student'),
(4, 'UGR/2205/14', 'Bilen Haile', 'bilen.haile@ambou.edu.et', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', 'student'),
(5, 'INST/0042', 'Firaol Kapita Daba', 'firaol.kapita@ambou.edu.et', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', 'instructor'),
(6, 'ADMIN/0001', 'System Administrator', 'admin@ambou.edu.et', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', '$2y$10$BbtkezRMmHKGIVYoG3ISd.LPhgudHEX2hCXQmy5swoPA/FAbTLNKS', 'admin');

-- Seed Team
INSERT IGNORE INTO `teams` (`id`, `name`, `team_name`, `project_title`, `description`) VALUES
(1, 'Group 04 - Distributed Systems', 'Group 04 - Distributed Systems', 'GROUPWARE: Autonomous Accountability Platform', 'Remote collaboration and fair grading engine for university teams.');

-- Seed Team Members
INSERT IGNORE INTO `team_members` (`id`, `team_id`, `user_id`, `role_in_team`) VALUES
(1, 1, 1, 'lead'),
(2, 1, 2, 'member'),
(3, 1, 3, 'member'),
(4, 1, 4, 'member');

-- Seed Sample Proposals
INSERT IGNORE INTO `proposals` (`id`, `team_id`, `proposed_by`, `title`, `description`, `status`, `created_at`) VALUES
(1, 1, 1, 'Implement Democratic Majority Consensus Quorum', 'Establishes state machine requiring >50% peer quorum before deliverables commit to master.', 'approved', '2026-10-31 10:00:00'),
(2, 1, 2, 'SHA-256 Tamper-Evident Hash Audit Logging', 'Chained hash block generator to ensure past participation records cannot be manipulated.', 'pending', '2026-11-01 14:30:00'),
(3, 1, 3, 'High-Density Activity Ledger & Responsive UI', 'Replaces generic card padding with high-contrast typography and clear participation metrics.', 'pending', '2026-11-02 09:15:00');

-- Seed Sample Votes
INSERT IGNORE INTO `proposal_votes` (`proposal_id`, `user_id`, `vote`, `created_at`) VALUES
(1, 2, 'approve', '2026-10-31 11:30:00'),
(1, 3, 'approve', '2026-10-31 12:15:00'),
(2, 1, 'approve', '2026-11-01 16:00:00');
