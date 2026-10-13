<?php
$pageTitle = 'Login to SkillSwap';
require_once __DIR__ . '/../config/db.php';

// Quick Demo Login Handler for Viva & Testing
if (isset($_GET['demo'])) {
    $demoEmail = match($_GET['demo']) {
        'aarav' => 'aarav@college.edu',
        'priya' => 'priya@college.edu',
        'admin' => 'admin@skillswap.edu',
        default => null
    };

    if ($demoEmail) {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$demoEmail]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            set_flash('success', "Welcome back, {$user['name']}! (Demo login active)");
            header('Location: ' . ($user['role'] === 'admin' ? app_path('admin/dashboard.php') : app_path('student/dashboard.php')));
            exit;
        }
    }
}

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . (is_admin() ? app_path('admin/dashboard.php') : app_path('student/dashboard.php')));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in both email and password.';
    } else {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended by an administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                set_flash('success', "Welcome back, {$user['name']}!");

                if ($user['role'] === 'admin') {
                    header('Location: ' . app_path('admin/dashboard.php'));
                } else {
                    header('Location: ' . app_path('student/dashboard.php'));
                }
                exit;
            }
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex p-3 bg-primary bg-opacity-10 text-primary rounded-circle mb-2">
                    <i class="bi bi-box-arrow-in-right fs-2"></i>
                </div>
                <h3 class="fw-bold">Welcome Back</h3>
                <p class="text-muted small">Sign in to exchange skills with peers on campus</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i> <?= sanitize($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">College / Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control border-start-0" placeholder="student@college.edu" value="<?= sanitize($_POST['email'] ?? ''); ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-semibold mb-0">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold shadow-sm mb-3">
                    Sign In
                </button>
            </form>

            <!-- Quick Demo Credentials Box for College Viva -->
            <div class="bg-light border rounded-3 p-3 mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-dark"><i class="bi bi-stars text-warning me-1"></i> Quick Demo Logins:</span>
                    <span class="badge bg-secondary-subtle text-secondary small">Viva Ready</span>
                </div>
                <div class="d-grid gap-2">
                    <a href="<?= app_path('auth/login.php?demo=aarav'); ?>" class="btn btn-sm btn-outline-primary text-start">
                        <i class="bi bi-person me-1"></i> <strong>Aarav Sharma</strong> (Python Dev)
                    </a>
                    <a href="<?= app_path('auth/login.php?demo=priya'); ?>" class="btn btn-sm btn-outline-success text-start">
                        <i class="bi bi-person me-1"></i> <strong>Priya Patel</strong> (UI/UX Designer)
                    </a>
                    <a href="<?= app_path('auth/login.php?demo=admin'); ?>" class="btn btn-sm btn-outline-dark text-start">
                        <i class="bi bi-shield-lock me-1"></i> <strong>Admin Panel</strong> (admin@skillswap.edu)
                    </a>
                </div>
                <div class="mt-2 text-center text-muted" style="font-size: 0.75rem;">
                    Default password for all demo accounts: <code>password123</code> (Admin: <code>Admin@123</code>)
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">
                    Don't have an account yet? 
                    <a href="<?= app_path('auth/register.php'); ?>" class="fw-bold text-primary text-decoration-none">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
