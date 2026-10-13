<?php
$pageTitle = 'Moderate Skills';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$db = get_db_connection();

// Delete Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skillId = (int)($_POST['skill_id'] ?? 0);
    if ($skillId) {
        $stmt = $db->prepare("DELETE FROM skills WHERE id = ?");
        $stmt->execute([$skillId]);
        set_flash('info', 'Skill removed by administrator.');
    }
    header('Location: ' . app_path('admin/manage_skills.php'));
    exit;
}

$type = trim($_GET['type'] ?? '');
$category = trim($_GET['category'] ?? '');

$query = "SELECT s.*, u.name as user_name, u.email FROM skills s JOIN users u ON s.user_id = u.id WHERE 1=1";
$params = [];

if ($type) {
    $query .= " AND s.type = ?";
    $params[] = $type;
}

if ($category) {
    $query .= " AND s.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY s.id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$skills = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <a href="<?= app_path('admin/dashboard.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <h3 class="fw-bold mb-0">Campus Skill Directory Moderation</h3>
        <p class="text-muted small mb-0">Review all skills listed for teaching or requested for learning.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?type=" class="btn btn-sm <?= empty($type) ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3">All</a>
        <a href="?type=teach" class="btn btn-sm <?= ($type === 'teach') ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3">Offered (Teach)</a>
        <a href="?type=learn" class="btn btn-sm <?= ($type === 'learn') ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3">Desired (Learn)</a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Skill Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Level</th>
                    <th>Posted By</th>
                    <th>Description</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($skills)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No skills found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($skills as $sk): ?>
                        <tr>
                            <td>#<?= $sk['id']; ?></td>
                            <td class="fw-bold"><?= sanitize($sk['skill_name']); ?></td>
                            <td>
                                <span class="badge badge-<?= $sk['type']; ?>">
                                    <?= ($sk['type'] === 'teach') ? 'Teach' : 'Learn'; ?>
                                </span>
                            </td>
                            <td><span class="badge bg-light text-secondary border"><?= sanitize($sk['category']); ?></span></td>
                            <td>
                                <span class="badge badge-level badge-<?= sanitize($sk['proficiency_level']); ?>">
                                    <?= sanitize($sk['proficiency_level']); ?>
                                </span>
                            </td>
                            <td class="small">
                                <span class="fw-semibold"><?= sanitize($sk['user_name']); ?></span><br>
                                <span class="text-muted" style="font-size: 0.72rem;"><?= sanitize($sk['email']); ?></span>
                            </td>
                            <td class="small text-muted" style="max-width: 250px;">
                                <?= sanitize($sk['description'] ?: 'None'); ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="" onsubmit="return confirm('Remove this skill from the platform?');">
                                    <input type="hidden" name="skill_id" value="<?= $sk['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0">
                                        <i class="bi bi-trash"></i> Remove
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
