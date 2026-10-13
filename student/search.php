<?php
$pageTitle = 'Explore & Search Skills';
require_once __DIR__ . '/../config/db.php';

$db = get_db_connection();
$currentUserId = current_user_id();

// Search & Filter parameters
$search   = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$type     = trim($_GET['type'] ?? 'teach'); // default to finding people who teach
$level    = trim($_GET['level'] ?? '');

$query = "
    SELECT s.*, u.name as user_name, u.department, u.year_of_study, u.contact_info, u.avatar
    FROM skills s
    JOIN users u ON s.user_id = u.id
    WHERE u.status = 'active'
";
$params = [];

if ($currentUserId) {
    $query .= " AND s.user_id != ?";
    $params[] = $currentUserId;
}

if (!empty($search)) {
    $query .= " AND (s.skill_name LIKE ? OR s.description LIKE ? OR u.name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($category)) {
    $query .= " AND s.category = ?";
    $params[] = $category;
}

if (!empty($type) && in_array($type, ['teach', 'learn'])) {
    $query .= " AND s.type = ?";
    $params[] = $type;
}

if (!empty($level) && in_array($level, ['Beginner', 'Intermediate', 'Advanced'])) {
    $query .= " AND s.proficiency_level = ?";
    $params[] = $level;
}

$query .= " ORDER BY s.id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$skills = $stmt->fetchAll();

// Get categories for dropdown
$categories = [
    'Programming', 'Web Development', 'Data Science', 'Mobile Dev',
    'Design & UI/UX', 'Academics & Math', 'Engineering', 'Languages',
    'Communication & Soft Skills', 'Music & Creative Arts', 'Business & Marketing'
];

// Fetch current user's offered skills for the Swap modal
$myOfferedSkills = [];
if ($currentUserId) {
    $mySkillsStmt = $db->prepare("SELECT * FROM skills WHERE user_id = ? AND type = 'teach'");
    $mySkillsStmt->execute([$currentUserId]);
    $myOfferedSkills = $mySkillsStmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">Discover Campus Skills</h3>
    <p class="text-muted small">Search skills taught by peers or see what others want to learn.</p>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search skill or student name..." value="<?= sanitize($search); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat; ?>" <?= ($category === $cat) ? 'selected' : ''; ?>><?= $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="teach" <?= ($type === 'teach') ? 'selected' : ''; ?>>Offers (Teach)</option>
                    <option value="learn" <?= ($type === 'learn') ? 'selected' : ''; ?>>Needs (Learn)</option>
                    <option value="" <?= ($type === '') ? 'selected' : ''; ?>>All Types</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="level" class="form-select">
                    <option value="">All Levels</option>
                    <option value="Beginner" <?= ($level === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                    <option value="Intermediate" <?= ($level === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="Advanced" <?= ($level === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                </select>
            </div>

            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary rounded-3">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Results Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small">Showing <strong><?= count($skills); ?></strong> available skills</span>
    <?php if (!empty($search) || !empty($category) || !empty($level)): ?>
        <a href="<?= app_path('student/search.php'); ?>" class="small text-danger text-decoration-none">
            <i class="bi bi-x-circle me-1"></i> Clear Filters
        </a>
    <?php endif; ?>
</div>

<?php if (empty($skills)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <i class="bi bi-search text-muted fs-1 mb-2"></i>
        <h5 class="fw-bold">No Skills Matched Your Criteria</h5>
        <p class="text-muted small">Try broadening your keywords or clearing selected filters.</p>
        <div>
            <a href="<?= app_path('student/search.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                Reset Search
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($skills as $s): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 swap-card border-0 p-4 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Header with User info -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-circle">
                                <?= strtoupper(substr($s['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= sanitize($s['user_name']); ?></h6>
                                <small class="text-muted">
                                    <?= sanitize($s['department']); ?> &bull; <?= sanitize($s['year_of_study']); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Skill Information -->
                        <div class="mb-3">
                            <h5 class="fw-bold text-dark mb-1"><?= sanitize($s['skill_name']); ?></h5>
                            <div class="d-flex flex-wrap gap-1 align-items-center mb-2">
                                <span class="badge badge-<?= $s['type']; ?>">
                                    <?= ($s['type'] === 'teach') ? '<i class="bi bi-mortarboard-fill me-1"></i> Teaches' : '<i class="bi bi-book-half me-1"></i> Wants to Learn'; ?>
                                </span>
                                <span class="badge bg-light text-secondary border small"><?= sanitize($s['category']); ?></span>
                                <span class="badge badge-level badge-<?= sanitize($s['proficiency_level']); ?>">
                                    <?= sanitize($s['proficiency_level']); ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-0" style="line-height: 1.4;">
                                <?= sanitize($s['description'] ?: 'No additional description provided.'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-3 border-top mt-3">
                        <?php if ($currentUserId): ?>
                            <?php if ($s['type'] === 'teach'): ?>
                                <button class="btn btn-primary w-100 rounded-pill btn-sm py-2 shadow-sm" 
                                        onclick="openSwapModal(<?= $s['id']; ?>, '<?= addslashes(sanitize($s['skill_name'])); ?>', <?= $s['user_id']; ?>, '<?= addslashes(sanitize($s['user_name'])); ?>')">
                                    <i class="bi bi-arrow-left-right me-1"></i> Request Skill Swap
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-warning text-dark w-100 rounded-pill btn-sm py-2 shadow-sm"
                                        onclick="openSwapModal(<?= $s['id']; ?>, '<?= addslashes(sanitize($s['skill_name'])); ?>', <?= $s['user_id']; ?>, '<?= addslashes(sanitize($s['user_name'])); ?>')">
                                    <i class="bi bi-person-check-fill me-1"></i> Offer to Teach This
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?= app_path('auth/login.php'); ?>" class="btn btn-outline-primary w-100 rounded-pill btn-sm py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login to Swap
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Send Swap Request Modal -->
<div class="modal fade" id="swapRequestModal" tabindex="-1" aria-labelledby="swapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="swapModalLabel">Initiate Skill Swap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= app_path('student/swap_requests.php'); ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" value="send_swap_request">
                    <input type="hidden" name="receiver_id" id="modalReceiverId">
                    <input type="hidden" name="requested_skill_id" id="modalSkillId">

                    <div class="p-3 bg-light rounded-3 mb-3">
                        <small class="text-muted d-block mb-1">You are requesting an exchange with:</small>
                        <h6 class="fw-bold mb-0 text-dark" id="modalReceiverName"></h6>
                        <span class="badge bg-primary-subtle text-primary mt-1" id="modalSkillName"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Which of your skills will you teach in return? <span class="text-danger">*</span></label>
                        <?php if (empty($myOfferedSkills)): ?>
                            <div class="alert alert-warning small py-2">
                                You haven't added any skills to teach yet. 
                                <a href="<?= app_path('student/my_skills.php'); ?>" class="fw-bold">Add an offered skill first</a>.
                            </div>
                        <?php else: ?>
                            <select name="offered_skill_id" class="form-select" required>
                                <option value="">-- Choose your offered skill --</option>
                                <?php foreach ($myOfferedSkills as $ms): ?>
                                    <option value="<?= $ms['id']; ?>"><?= sanitize($ms['skill_name']); ?> (<?= sanitize($ms['proficiency_level']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Personalized Message / Proposed Timing</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Hi! I saw you teach this skill. I would love to learn from you, and in exchange I can help you with..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" <?= empty($myOfferedSkills) ? 'disabled' : ''; ?>>
                        <i class="bi bi-send-fill me-1"></i> Send Proposal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openSwapModal(skillId, skillName, receiverId, receiverName) {
    document.getElementById('modalSkillId').value = skillId;
    document.getElementById('modalReceiverId').value = receiverId;
    document.getElementById('modalReceiverName').innerText = receiverName;
    document.getElementById('modalSkillName').innerText = skillName;
    const modal = new bootstrap.Modal(document.getElementById('swapRequestModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
