<?php
require_once __DIR__ . '/../config/db.php';

function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Please login to access this feature.');
        header('Location: ' . app_path('auth/login.php'));
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Access denied. Administrator privileges required.');
        header('Location: ' . app_path('student/dashboard.php'));
        exit;
    }
}

function get_current_user_data() {
    if (!is_logged_in()) return null;
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
