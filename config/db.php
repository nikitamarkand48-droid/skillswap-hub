<?php
// =======================================================
// SkillSwap - Database Connection & Global Configuration
// Supports MySQL (Primary) with Automatic SQLite Fallback
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');
define('DB_PORT', getenv('DB_PORT') ? getenv('DB_PORT') : '3306');
define('DB_USER', getenv('DB_USER') ? getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ? getenv('DB_NAME') : 'skillswap');
define('APP_NAME', 'Campus SkillSwap');

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // 1. Try MySQL Connection
    try {
        // Connect without db first to check/create if needed
        $dsnCheck = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $pdoInit = new PDO($dsnCheck, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);
        
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect to the specific database
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Check if tables exist, if not run initialization
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$tableCheck) {
            init_database_tables($pdo, 'mysql');
        }

        return $pdo;
    } catch (Throwable $e) {
        // 2. Fallback to SQLite (Guarantees zero-error execution in lab settings)
        $sqlitePath = __DIR__ . '/skillswap.sqlite';
        $isNewSqlite = !file_exists($sqlitePath);
        
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");

        if ($isNewSqlite) {
            init_database_tables($pdo, 'sqlite');
        }

        return $pdo;
    }
}

function init_database_tables($pdo, $driver = 'mysql') {
    if ($driver === 'mysql') {
        $sqlFile = dirname(__DIR__) . '/database.sql';
        if (file_exists($sqlFile)) {
            $queries = file_get_contents($sqlFile);
            $pdo->exec($queries);
        }
    } else {
        // SQLite Schema & Seeds
        $schema = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'student',
            college_id TEXT,
            department TEXT DEFAULT 'Computer Science',
            year_of_study TEXT DEFAULT '3rd Year',
            bio TEXT,
            contact_info TEXT,
            avatar TEXT DEFAULT 'default_avatar.png',
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS skills (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            skill_name TEXT NOT NULL,
            category TEXT NOT NULL,
            proficiency_level TEXT DEFAULT 'Intermediate',
            type TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS swap_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            offered_skill_id INTEGER NOT NULL,
            requested_skill_id INTEGER NOT NULL,
            message TEXT,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (offered_skill_id) REFERENCES skills(id) ON DELETE CASCADE,
            FOREIGN KEY (requested_skill_id) REFERENCES skills(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS swap_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            swap_id INTEGER NOT NULL,
            sender_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (swap_id) REFERENCES swap_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            swap_id INTEGER NOT NULL,
            reviewer_id INTEGER NOT NULL,
            reviewee_id INTEGER NOT NULL,
            rating INTEGER,
            comment TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (swap_id) REFERENCES swap_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
        );
        ";
        $pdo->exec($schema);

        // Preload Seed Data
        $passHash = password_hash('password123', PASSWORD_BCRYPT);
        $adminPass = password_hash('Admin@123', PASSWORD_BCRYPT);

        $pdo->exec("
        INSERT INTO users (id, name, email, password, role, college_id, department, year_of_study, bio, contact_info, status) VALUES
        (1, 'System Administrator', 'admin@skillswap.edu', '{$adminPass}', 'admin', 'ADM-001', 'Administration', 'Staff', 'Platform administrator overseeing peer swaps and moderation.', 'admin@skillswap.edu', 'active'),
        (2, 'Aarav Sharma', 'aarav@college.edu', '{$passHash}', 'student', 'CS-2024-042', 'Computer Engineering', '3rd Year', 'Passionate full-stack developer. Love teaching Python & Django, want to master UI/UX Design.', 'discord: @aarav_dev', 'active'),
        (3, 'Priya Patel', 'priya@college.edu', '{$passHash}', 'student', 'IT-2024-089', 'Information Technology', '3rd Year', 'Graphic Designer & Figma lover. Looking to learn Python for data analysis.', 'priya.design@gmail.com', 'active'),
        (4, 'Rohan Mehta', 'rohan@college.edu', '{$passHash}', 'student', 'AI-2025-015', 'AI & Data Science', '2nd Year', 'Machine Learning enthusiast. Can teach Scikit-Learn & Math for ML. Looking to learn React.', 'linkedin.com/in/rohanmehta', 'active'),
        (5, 'Ananya Sen', 'ananya@college.edu', '{$passHash}', 'student', 'EC-2024-102', 'Electronics', '4th Year', 'Frontend wizard with React & Tailwind CSS. Eager to learn Machine Learning fundamentals.', 'ananya@campus.in', 'active');

        INSERT INTO skills (id, user_id, skill_name, category, proficiency_level, type, description) VALUES
        (1, 2, 'Python Programming', 'Programming', 'Advanced', 'teach', 'Can teach Python from basics to OOP, web scraping, and building CLI apps.'),
        (2, 2, 'Django Web Framework', 'Programming', 'Intermediate', 'teach', 'Building REST APIs, databases, authentication and deployment.'),
        (3, 2, 'UI/UX Design', 'Design', 'Beginner', 'learn', 'Want to learn Figma, design systems, wireframing, and user research.'),
        (4, 2, 'Public Speaking', 'Communication', 'Beginner', 'learn', 'Looking to improve presentation and seminar speaking skills.'),
        (5, 3, 'Figma & UI/UX Design', 'Design', 'Advanced', 'teach', 'Complete Figma prototyping, auto-layout, mobile app mockups and design principles.'),
        (6, 3, 'Graphic Design & Canva', 'Design', 'Advanced', 'teach', 'Posters, social media graphics, branding, and color theory.'),
        (7, 3, 'Python Programming', 'Programming', 'Beginner', 'learn', 'Need help learning Python loops, functions, and pandas for college projects.'),
        (8, 3, 'Web Development', 'Programming', 'Beginner', 'learn', 'Want to build my own design portfolio website.'),
        (9, 4, 'Machine Learning & Python', 'Data Science', 'Advanced', 'teach', 'Linear regression, classification, clustering, numpy and pandas.'),
        (10, 4, 'Calculus & Linear Algebra', 'Academics', 'Intermediate', 'teach', 'College engineering mathematics and problem solving.'),
        (11, 4, 'React.js', 'Programming', 'Beginner', 'learn', 'Want to learn frontend component development with React.'),
        (12, 5, 'React.js & Frontend', 'Programming', 'Advanced', 'teach', 'Modern React, hooks, state management, and Bootstrap/Tailwind UI.'),
        (13, 5, 'HTML5 & Modern CSS', 'Programming', 'Advanced', 'teach', 'Responsive design, flexbox, CSS grid, and animations.'),
        (14, 5, 'Machine Learning & Python', 'Data Science', 'Beginner', 'learn', 'Eager to build predictive models and understand AI algorithms.');

        INSERT INTO swap_requests (id, sender_id, receiver_id, offered_skill_id, requested_skill_id, message, status, created_at) VALUES
        (1, 2, 3, 1, 5, 'Hey Priya! Saw your Figma portfolio, looks incredible. I can teach you Python for your projects if you can guide me on Figma design systems!', 'accepted', '2026-09-20 14:30:00'),
        (2, 4, 5, 9, 12, 'Hi Ananya! I can help you with ML & Python data structures in exchange for learning React frontend components.', 'pending', '2026-09-28 10:15:00'),
        (3, 5, 2, 13, 2, 'Hello Aarav, I would love to learn Django backend. I can help polish your CSS and responsive layouts in return!', 'completed', '2026-09-10 09:00:00');

        INSERT INTO swap_messages (swap_id, sender_id, message, created_at) VALUES
        (1, 2, 'Thanks for accepting Priya! Are you free this Thursday at 4 PM in the college library?', '2026-09-20 15:00:00'),
        (1, 3, 'Yes Aarav! Thursday 4 PM works great. Bring your laptop with Figma installed!', '2026-09-20 15:15:00'),
        (3, 5, 'Hey Aarav, thank you so much for the Django auth session today!', '2026-09-12 18:00:00'),
        (3, 2, 'Anytime Ananya! Your CSS grid tutorial was a game changer for my layout.', '2026-09-12 18:30:00');

        INSERT INTO reviews (swap_id, reviewer_id, reviewee_id, rating, comment, created_at) VALUES
        (3, 5, 2, 5, 'Aarav is a patient tutor. Explained Django models and migrations with crystal clarity! 10/10.', '2026-09-13 11:00:00'),
        (3, 2, 5, 5, 'Ananya completely transformed how I write modern CSS. Very knowledgeable and great communicator!', '2026-09-13 11:30:00');
        ");
    }
}

// Global Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function app_path($subpath = '') {
    // Dynamically calculate the root URL path relative to the host
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $curDir  = str_replace('\\', '/', dirname(__DIR__));
    $rel = str_replace($docRoot, '', $curDir);
    $rel = rtrim($rel, '/');
    return $rel . ($subpath ? '/' . ltrim($subpath, '/') : '');
}
