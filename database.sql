-- =======================================================
-- SkillSwap - College Peer-to-Peer Skill Exchange Platform
-- Database Schema for MySQL
-- =======================================================

CREATE DATABASE IF NOT EXISTS `skillswap` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `skillswap`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'admin') DEFAULT 'student',
    `college_id` VARCHAR(50) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT 'Computer Science',
    `year_of_study` VARCHAR(20) DEFAULT '3rd Year',
    `bio` TEXT DEFAULT NULL,
    `contact_info` VARCHAR(255) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT 'default_avatar.png',
    `status` ENUM('active', 'suspended') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Skills Table (For both Offered and Learning Skills)
CREATE TABLE IF NOT EXISTS `skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `skill_name` VARCHAR(120) NOT NULL,
    `category` VARCHAR(60) NOT NULL,
    `proficiency_level` ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Intermediate',
    `type` ENUM('teach', 'learn') NOT NULL, -- 'teach' = Offered Skill, 'learn' = Desired Skill
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Swap Requests Table
CREATE TABLE IF NOT EXISTS `swap_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `offered_skill_id` INT NOT NULL,
    `requested_skill_id` INT NOT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`offered_skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`requested_skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Swap Messages / Collaboration Notes Table
CREATE TABLE IF NOT EXISTS `swap_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `swap_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`swap_id`) REFERENCES `swap_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Reviews / Ratings Table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `swap_id` INT NOT NULL,
    `reviewer_id` INT NOT NULL,
    `reviewee_id` INT NOT NULL,
    `rating` INT CHECK (`rating` BETWEEN 1 AND 5),
    `comment` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`swap_id`) REFERENCES `swap_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- SEED DATA (For immediate testing and college presentation)
-- Default passwords are all: password123
-- Password hash: $2y$10$wTfV96y3uW.O7o.Bsk755e37m33L85l2b6R09u7vjK0pL.rD21sQe
-- Admin password: Admin@123 -> $2y$10$wTfV96y3uW.O7o.Bsk755e37m33L85l2b6R09u7vjK0pL.rD21sQe
-- =======================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `college_id`, `department`, `year_of_study`, `bio`, `contact_info`, `status`) VALUES
(1, 'System Administrator', 'admin@skillswap.edu', '$2y$10$wTfV96y3uW.O7o.Bsk755e37m33L85l2b6R09u7vjK0pL.rD21sQe', 'admin', 'ADM-001', 'Administration', 'Staff', 'Platform administrator overseeing peer swaps and moderation.', 'admin@skillswap.edu', 'active'),
(2, 'Aarav Sharma', 'aarav@college.edu', '$2y$10$wTfV96y3uW.O7o.Bsk755e37m33L85l2b6R09u7vjK0pL.rD21sQe', 'student', 'CS-2024-042', 'Computer Engineering', '3rd Year', 'Passionate full-stack developer. Love teaching Python & Django, want to master UI/UX Design.', 'discord: @aarav_dev', 'active'),
(3, 'Priya Patel', 'priya@college.edu', '$2y$10$wTfV96y3uW.O7o.Bsk755e37m33L85l2b6R09u7vjK0pL.rD21sQe', 'student', 'IT-2024-089', 'Information Technology', '3rd Year', 'Graphic Designer & Figma lover. Looking to learn Python for data analysis.', 'priya.design@gmail.com', 'active'),
(4, 'Rohan Mehta', 'rohan@college.edu', '$2y$10$wTfV96y3uW.O7o.Bsk755e37m33L85l2b6R09u7vjK0pL.rD21sQe', 'student', 'AI-2025-015', 'AI & Data Science', '2nd Year', 'Machine Learning enthusiast. Can teach Scikit-Learn & Math for ML. Looking to learn React.', 'linkedin.com/in/rohanmehta', 'active'),
(5, 'Ananya Sen', 'ananya@college.edu', '$2y$10$wTfV96y3uW.O7o.Bsk755e37m33L85l2b6R09u7vjK0pL.rD21sQe', 'student', 'EC-2024-102', 'Electronics', '4th Year', 'Frontend wizard with React & Tailwind CSS. Eager to learn Machine Learning fundamentals.', 'ananya@campus.in', 'active');

-- Skills Data
INSERT INTO `skills` (`id`, `user_id`, `skill_name`, `category`, `proficiency_level`, `type`, `description`) VALUES
-- Aarav (User 2)
(1, 2, 'Python Programming', 'Programming', 'Advanced', 'teach', 'Can teach Python from basics to OOP, web scraping, and building CLI apps.'),
(2, 2, 'Django Web Framework', 'Programming', 'Intermediate', 'teach', 'Building REST APIs, databases, authentication and deployment.'),
(3, 2, 'UI/UX Design', 'Design', 'Beginner', 'learn', 'Want to learn Figma, design systems, wireframing, and user research.'),
(4, 2, 'Public Speaking', 'Communication', 'Beginner', 'learn', 'Looking to improve presentation and seminar speaking skills.'),

-- Priya (User 3) - Perfect match for Aarav!
(5, 3, 'Figma & UI/UX Design', 'Design', 'Advanced', 'teach', 'Complete Figma prototyping, auto-layout, mobile app mockups and design principles.'),
(6, 3, 'Graphic Design & Canva', 'Design', 'Advanced', 'teach', 'Posters, social media graphics, branding, and color theory.'),
(7, 3, 'Python Programming', 'Programming', 'Beginner', 'learn', 'Need help learning Python loops, functions, and pandas for college projects.'),
(8, 3, 'Web Development', 'Programming', 'Beginner', 'learn', 'Want to build my own design portfolio website.'),

-- Rohan (User 4)
(9, 4, 'Machine Learning & Python', 'Data Science', 'Advanced', 'teach', 'Linear regression, classification, clustering, numpy and pandas.'),
(10, 4, 'Calculus & Linear Algebra', 'Academics', 'Intermediate', 'teach', 'College engineering mathematics and problem solving.'),
(11, 4, 'React.js', 'Programming', 'Beginner', 'learn', 'Want to learn frontend component development with React.'),

-- Ananya (User 5) - Perfect match for Rohan!
(12, 5, 'React.js & Frontend', 'Programming', 'Advanced', 'teach', 'Modern React, hooks, state management, and Bootstrap/Tailwind UI.'),
(13, 5, 'HTML5 & Modern CSS', 'Programming', 'Advanced', 'teach', 'Responsive design, flexbox, CSS grid, and animations.'),
(14, 5, 'Machine Learning & Python', 'Data Science', 'Beginner', 'learn', 'Eager to build predictive models and understand AI algorithms.');

-- Swap Requests Data
INSERT INTO `swap_requests` (`id`, `sender_id`, `receiver_id`, `offered_skill_id`, `requested_skill_id`, `message`, `status`, `created_at`) VALUES
(1, 2, 3, 1, 5, 'Hey Priya! Saw your Figma portfolio, looks incredible. I can teach you Python for your projects if you can guide me on Figma design systems!', 'accepted', '2026-09-20 14:30:00'),
(2, 4, 5, 9, 12, 'Hi Ananya! I can help you with ML & Python data structures in exchange for learning React frontend components.', 'pending', '2026-09-28 10:15:00'),
(3, 5, 2, 13, 2, 'Hello Aarav, I would love to learn Django backend. I can help polish your CSS and responsive layouts in return!', 'completed', '2026-09-10 09:00:00');

-- Swap Messages Data
INSERT INTO `swap_messages` (`swap_id`, `sender_id`, `message`, `created_at`) VALUES
(1, 2, 'Thanks for accepting Priya! Are you free this Thursday at 4 PM in the college library?', '2026-09-20 15:00:00'),
(1, 3, 'Yes Aarav! Thursday 4 PM works great. Bring your laptop with Figma installed!', '2026-09-20 15:15:00'),
(3, 5, 'Hey Aarav, thank you so much for the Django auth session today!', '2026-09-12 18:00:00'),
(3, 2, 'Anytime Ananya! Your CSS grid tutorial was a game changer for my layout.', '2026-09-12 18:30:00');

-- Reviews Data
INSERT INTO `reviews` (`swap_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`, `created_at`) VALUES
(3, 5, 2, 5, 'Aarav is a patient tutor. Explained Django models and migrations with crystal clarity! 10/10.', '2026-09-13 11:00:00'),
(3, 2, 5, 5, 'Ananya completely transformed how I write modern CSS. Very knowledgeable and great communicator!', '2026-09-13 11:30:00');
