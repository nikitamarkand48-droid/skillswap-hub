<?php
$pageTitle = 'Manage Students';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$db = get_db_connection();

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($targetId && $targetId !== current_user_id()) {
        if ($action === 'toggle_status') {
            $currentStatus = $_POST['status'] ?? 'active';
            $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $targetId]);
            set_flash('success', "Student account status changed to '{$newStatus}'.");
        } elseif ($action === 'delete_user') {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            set_flash('info', 'Student account deleted.');
        }
    }
    header('Location: ' . app_path('admin/manage_users.php'));
    exit;
}

$search = trim($_GET['q'] ?? '');
$query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM skills WHERE user_id = u.id AND type = 'teach') as teach_count,
                 (SELECT COUNT(*) FROM skills WHERE user_id = u.id AND type = 'learn') as learn_count
          FROM users u 
          WHERE u.role = 'student'";
$params = [];

if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.department LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}

$query .= " ORDER BY u.id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <a href="<?= app_path('admin/dashboard.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <h3 class="fw-bold mb-0">Student Account Management</h3>
        <p class="text-muted small mb-0">Review enrolled students, moderate account access, and oversee activity.</p>
    </div>
    <div>
        <form method="GET" action="" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Search student name, email..." value="<?= sanitize($search); ?>">
            <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">Search</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Department / Year</th>
                    <th>Teaching</th>
                    <th>Learning</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No students found matching query.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td>#<?= $stu['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle avatar-circle-sm">
                                        <?= strtoupper(substr($stu['name'], 0, 1)); ?>
                                    </div>
                                    <span class="fw-bold"><?= sanitize($stu['name']); ?></span>
                                </div>
                            </td>
                            <td class="small text-muted"><?= sanitize($stu['email']); ?></td>
                            <td class="small"><?= sanitize($stu['department']); ?> &bull; <?= sanitize($stu['year_of_study']); ?></td>
                            <td><span class="badge bg-primary-subtle text-primary"><?= $stu['teach_count']; ?></span></td>
                            <td><span class="badge bg-warning-subtle text-dark"><?= $stu['learn_count']; ?></span></td>
                            <td>
                                <span class="badge <?= ($stu['status'] === 'active') ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-2 py-1">
                                    <?= ucfirst($stu['status']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $stu['id']; ?>">
                                    <input type="hidden" name="status" value="<?= $stu['status']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-0 me-1" title="Toggle Active / Suspended">
                                        <?= ($stu['status'] === 'active') ? 'Suspend' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Permanently delete this student account?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $stu['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0" title="Delete Account">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
