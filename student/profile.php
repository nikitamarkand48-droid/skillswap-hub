<?php
$pageTitle = 'Student Profile';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$db = get_db_connection();
$userId = current_user_id();

// Handle Profile Update
$updateSuccess = false;
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name         = trim($_POST['name'] ?? '');
        $department   = trim($_POST['department'] ?? '');
        $year_of_study= trim($_POST['year_of_study'] ?? '');
        $college_id   = trim($_POST['college_id'] ?? '');
        $contact_info = trim($_POST['contact_info'] ?? '');
        $bio          = trim($_POST['bio'] ?? '');

        if (empty($name)) {
            $updateError = 'Name cannot be empty.';
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET name = ?, department = ?, year_of_study = ?, college_id = ?, contact_info = ?, bio = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $department, $year_of_study, $college_id, $contact_info, $bio, $userId]);
            $_SESSION['user_name'] = $name;
            set_flash('success', 'Profile updated successfully!');
            header('Location: ' . app_path('student/profile.php'));
            exit;
        }
    } elseif ($action === 'change_password') {
        $currPass = $_POST['current_password'] ?? '';
        $newPass  = $_POST['new_password'] ?? '';
        $confPass = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch();

        if (!password_verify($currPass, $userRow['password'])) {
            $updateError = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $updateError = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confPass) {
            $updateError = 'New passwords do not match.';
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$hash, $userId]);
            set_flash('success', 'Password changed successfully!');
            header('Location: ' . app_path('student/profile.php'));
            exit;
        }
    }
}

// Fetch user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Stats
$teachCount = $db->query("SELECT COUNT(*) FROM skills WHERE user_id = {$userId} AND type = 'teach'")->fetchColumn();
$learnCount = $db->query("SELECT COUNT(*) FROM skills WHERE user_id = {$userId} AND type = 'learn'")->fetchColumn();
$swapsCount = $db->query("SELECT COUNT(*) FROM swap_requests WHERE (sender_id = {$userId} OR receiver_id = {$userId}) AND status = 'completed'")->fetchColumn();

// Rating & Reviews
$revStmt = $db->prepare("
    SELECT r.*, u.name as reviewer_name 
    FROM reviews r 
    JOIN users u ON r.reviewer_id = u.id 
    WHERE r.reviewee_id = ? 
    ORDER BY r.created_at DESC
");
$revStmt->execute([$userId]);
$reviews = $revStmt->fetchAll();

$avgRating = 0;
if (count($reviews) > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($sum / count($reviews), 1);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Left Column: User Summary Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="mx-auto mb-3 avatar-circle avatar-circle-lg">
                <?= strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <h4 class="fw-bold mb-1"><?= sanitize($user['name']); ?></h4>
            <p class="text-muted small mb-2">
                <i class="bi bi-mortarboard me-1"></i> <?= sanitize($user['department']); ?> &bull; <?= sanitize($user['year_of_study']); ?>
            </p>
            <div class="mb-3">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                    <i class="bi bi-person-badge me-1"></i> ID: <?= sanitize($user['college_id'] ?: 'Not Specified'); ?>
                </span>
            </div>

            <!-- Reputation / Rating -->
            <div class="p-3 bg-light rounded-3 mb-3">
                <div class="d-flex align-items-center justify-content-center gap-1 text-warning fs-5">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star<?= ($i <= round($avgRating)) ? '-fill' : ''; ?>"></i>
                    <?php endfor; ?>
                    <span class="text-dark fw-bold ms-2 fs-6"><?= $avgRating > 0 ? $avgRating : 'New'; ?> / 5</span>
                </div>
                <small class="text-muted"><?= count($reviews); ?> peer reviews received</small>
            </div>

            <!-- Quick Counter Stats -->
            <div class="row text-center g-2 border-top pt-3">
                <div class="col-4">
                    <h5 class="fw-bold mb-0 text-primary"><?= $teachCount; ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;">Teaching</small>
                </div>
                <div class="col-4 border-start border-end">
                    <h5 class="fw-bold mb-0 text-warning"><?= $learnCount; ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;">Learning</small>
                </div>
                <div class="col-4">
                    <h5 class="fw-bold mb-0 text-success"><?= $swapsCount; ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;">Completed</small>
                </div>
            </div>
        </div>

        <!-- Peer Reviews list -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mt-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-chat-heart text-danger me-2"></i>Peer Reviews</h5>
            <?php if (empty($reviews)): ?>
                <p class="text-muted small mb-0">No reviews yet. Complete your first skill swap to receive ratings!</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small"><?= sanitize($rev['reviewer_name']); ?></span>
                                <span class="text-warning small">
                                    <?= str_repeat('★', $rev['rating']); ?>
                                </span>
                            </div>
                            <p class="mb-1 text-muted small fst-italic">"<?= sanitize($rev['comment']); ?>"</p>
                            <small class="text-muted" style="font-size: 0.7rem;"><?= date('M d, Y', strtotime($rev['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Profile Editor & Tabs -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <?php if ($updateError): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i> <?= sanitize($updateError); ?>
                </div>
            <?php endif; ?>

            <ul class="nav nav-pills mb-4" id="profileTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4" id="info-tab" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                        <i class="bi bi-person-gear me-1"></i> General Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4" id="security-tab" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                        <i class="bi bi-shield-lock me-1"></i> Security & Password
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="profileTabContent">
                <!-- Info Tab -->
                <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email Address (Read-Only)</label>
                                <input type="email" class="form-control bg-light" value="<?= sanitize($user['email']); ?>" disabled>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">College ID</label>
                                <input type="text" name="college_id" class="form-control" value="<?= sanitize($user['college_id']); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Department</label>
                                <input type="text" name="department" class="form-control" value="<?= sanitize($user['department']); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Year of Study</label>
                                <select name="year_of_study" class="form-select">
                                    <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year', 'Postgraduate'] as $yr): ?>
                                        <option value="<?= $yr; ?>" <?= ($user['year_of_study'] === $yr) ? 'selected' : ''; ?>><?= $yr; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">Contact / Social Handle (Discord, LinkedIn, Phone)</label>
                                <input type="text" name="contact_info" class="form-control" placeholder="e.g. discord: @aarav_dev, phone: +91 98765..." value="<?= sanitize($user['contact_info']); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">About You / Learning Philosophy</label>
                                <textarea name="bio" class="form-control" rows="4" placeholder="Tell other students what you are passionate about..."><?= sanitize($user['bio']); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="tab-security" role="tabpanel">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row g-3" style="max-width: 500px;">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="At least 6 characters" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-outline-danger px-4 rounded-3">
                                    <i class="bi bi-key me-1"></i> Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
