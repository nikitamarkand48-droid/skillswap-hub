<?php
$pageTitle = 'Student Registration';
require_once __DIR__ . '/../config/db.php';

if (is_logged_in()) {
    header('Location: ' . app_path('student/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $confirm_pass  = trim($_POST['confirm_password'] ?? '');
    $college_id    = trim($_POST['college_id'] ?? '');
    $department    = trim($_POST['department'] ?? 'Computer Science');
    $year_of_study = trim($_POST['year_of_study'] ?? '3rd Year');
    $bio           = trim($_POST['bio'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Passwords do not match.';
    } else {
        $db = get_db_connection();
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'An account with this email address already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                INSERT INTO users (name, email, password, role, college_id, department, year_of_study, bio, contact_info, status)
                VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, 'active')
            ");
            $contactInfo = $email;
            $stmt->execute([$name, $email, $hash, $college_id, $department, $year_of_study, $bio, $contactInfo]);
            $newUserId = $db->lastInsertId();

            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'student';

            set_flash('success', 'Account created successfully! Now add skills you can teach and skills you want to learn.');
            header('Location: ' . app_path('student/my_skills.php'));
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex p-3 bg-success bg-opacity-10 text-success rounded-circle mb-2">
                    <i class="bi bi-person-plus-fill fs-2"></i>
                </div>
                <h3 class="fw-bold">Create Student Account</h3>
                <p class="text-muted small">Join campus peers, share what you know, and learn what you need!</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i> <?= sanitize($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Verma" value="<?= sanitize($_POST['name'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">College Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="student@college.edu" value="<?= sanitize($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">College Roll / ID</label>
                        <input type="text" name="college_id" class="form-control" placeholder="e.g. CS-2025-055" value="<?= sanitize($_POST['college_id'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Department</label>
                        <select name="department" class="form-select">
                            <option value="Computer Engineering">Computer Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="AI & Data Science">AI & Data Science</option>
                            <option value="Electronics & Telecom">Electronics & Telecom</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Design & Media">Design & Media</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Year of Study</label>
                        <select name="year_of_study" class="form-select">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year" selected>3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="Postgraduate">Postgraduate</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Short Bio & Learning Interests</label>
                        <textarea name="bio" class="form-control" rows="2" placeholder="Tell peers what you love doing and what you're excited to learn..."><?= sanitize($_POST['bio'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold shadow-sm mt-4">
                    Complete Registration
                </button>
            </form>

            <div class="text-center mt-3">
                <p class="small text-muted mb-0">
                    Already registered? 
                    <a href="<?= app_path('auth/login.php'); ?>" class="fw-bold text-primary text-decoration-none">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
