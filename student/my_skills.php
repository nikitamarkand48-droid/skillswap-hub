<?php
$pageTitle = 'Manage My Skills';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$db = get_db_connection();
$userId = current_user_id();

$categories = [
    'Programming', 'Web Development', 'Data Science', 'Mobile Dev',
    'Design & UI/UX', 'Academics & Math', 'Engineering', 'Languages',
    'Communication & Soft Skills', 'Music & Creative Arts', 'Business & Marketing'
];

// Handle Add Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_skill') {
        $skillName   = trim($_POST['skill_name'] ?? '');
        $category    = trim($_POST['category'] ?? 'Programming');
        $proficiency = trim($_POST['proficiency_level'] ?? 'Intermediate');
        $type        = trim($_POST['type'] ?? 'teach');
        $description = trim($_POST['description'] ?? '');

        if (!empty($skillName)) {
            $stmt = $db->prepare("
                INSERT INTO skills (user_id, skill_name, category, proficiency_level, type, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $skillName, $category, $proficiency, $type, $description]);
            set_flash('success', ($type === 'teach' ? 'Offered' : 'Learning') . " skill '{$skillName}' added successfully!");
        } else {
            set_flash('danger', 'Skill name cannot be empty.');
        }
        header('Location: ' . app_path('student/my_skills.php'));
        exit;
    }

    if ($action === 'delete_skill') {
        $skillId = (int)($_POST['skill_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM skills WHERE id = ? AND user_id = ?");
        $stmt->execute([$skillId, $userId]);
        set_flash('info', 'Skill removed.');
        header('Location: ' . app_path('student/my_skills.php'));
        exit;
    }
}

// Fetch user's skills
$teachSkillsStmt = $db->prepare("SELECT * FROM skills WHERE user_id = ? AND type = 'teach' ORDER BY id DESC");
$teachSkillsStmt->execute([$userId]);
$teachSkills = $teachSkillsStmt->fetchAll();

$learnSkillsStmt = $db->prepare("SELECT * FROM skills WHERE user_id = ? AND type = 'learn' ORDER BY id DESC");
$learnSkillsStmt->execute([$userId]);
$learnSkills = $learnSkillsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">My Skill Exchange Portfolio</h3>
        <p class="text-muted small mb-0">Manage skills you can teach to peers and skills you wish to learn in return.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSkillModal" onclick="setSkillType('teach')">
            <i class="bi bi-mortarboard-fill me-1"></i> Add Skill to Teach
        </button>
        <button class="btn btn-outline-warning text-dark rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSkillModal" onclick="setSkillType('learn')">
            <i class="bi bi-book-half me-1"></i> Add Skill to Learn
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- 1. Skills I Can Teach -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-person-workspace fs-6"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Skills I Can Teach</h5>
                        <small class="text-muted">Offered to other students</small>
                    </div>
                </div>
                <span class="badge bg-primary rounded-pill"><?= count($teachSkills); ?> Active</span>
            </div>

            <?php if (empty($teachSkills)): ?>
                <div class="text-center py-5 bg-light rounded-3">
                    <i class="bi bi-lightbulb text-muted fs-1 mb-2 d-block"></i>
                    <h6 class="fw-semibold text-dark">No Teaching Skills Added Yet</h6>
                    <p class="text-muted small mb-3">What are you good at? Python, Figma, Math, or Public Speaking?</p>
                    <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addSkillModal" onclick="setSkillType('teach')">
                        <i class="bi bi-plus me-1"></i> Add Teaching Skill
                    </button>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($teachSkills as $s): ?>
                        <div class="p-3 border rounded-3 bg-white hover-shadow position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1"><?= sanitize($s['skill_name']); ?></h6>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <span class="badge badge-teach small"><?= sanitize($s['category']); ?></span>
                                        <span class="badge badge-level badge-<?= sanitize($s['proficiency_level']); ?>">
                                            <?= sanitize($s['proficiency_level']); ?>
                                        </span>
                                    </div>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('Remove this skill?');">
                                    <input type="hidden" name="action" value="delete_skill">
                                    <input type="hidden" name="skill_id" value="<?= $s['id']; ?>">
                                    <button type="submit" class="btn btn-sm text-danger p-0" title="Delete Skill">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                            <?php if ($s['description']): ?>
                                <p class="text-muted small mb-0"><?= sanitize($s['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Skills I Want to Learn -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning text-dark rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-journal-bookmark-fill fs-6"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Skills I Want to Learn</h5>
                        <small class="text-muted">Skills you are seeking from peers</small>
                    </div>
                </div>
                <span class="badge bg-warning text-dark rounded-pill"><?= count($learnSkills); ?> Active</span>
            </div>

            <?php if (empty($learnSkills)): ?>
                <div class="text-center py-5 bg-light rounded-3">
                    <i class="bi bi-compass text-muted fs-1 mb-2 d-block"></i>
                    <h6 class="fw-semibold text-dark">No Learning Goals Added Yet</h6>
                    <p class="text-muted small mb-3">Add skills you wish to learn to get smart matched with mentors!</p>
                    <button class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addSkillModal" onclick="setSkillType('learn')">
                        <i class="bi bi-plus me-1"></i> Add Desired Skill
                    </button>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($learnSkills as $s): ?>
                        <div class="p-3 border rounded-3 bg-white hover-shadow position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1"><?= sanitize($s['skill_name']); ?></h6>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <span class="badge badge-learn small"><?= sanitize($s['category']); ?></span>
                                        <span class="badge badge-level badge-<?= sanitize($s['proficiency_level']); ?>">
                                            Target: <?= sanitize($s['proficiency_level']); ?>
                                        </span>
                                    </div>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('Remove this learning goal?');">
                                    <input type="hidden" name="action" value="delete_skill">
                                    <input type="hidden" name="skill_id" value="<?= $s['id']; ?>">
                                    <button type="submit" class="btn btn-sm text-danger p-0" title="Delete Skill">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                            <?php if ($s['description']): ?>
                                <p class="text-muted small mb-0"><?= sanitize($s['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Skill Modal -->
<div class="modal fade" id="addSkillModal" tabindex="-1" aria-labelledby="addSkillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="addSkillModalLabel">Add New Skill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_skill">

                    <!-- Type Selector -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Skill Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="typeTeach" value="teach" checked>
                            <label class="btn btn-outline-primary" for="typeTeach">
                                <i class="bi bi-person-workspace me-1"></i> I Can Teach This
                            </label>

                            <input type="radio" class="btn-check" name="type" id="typeLearn" value="learn">
                            <label class="btn btn-outline-warning text-dark" for="typeLearn">
                                <i class="bi bi-journal-bookmark me-1"></i> I Want to Learn This
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Skill Name <span class="text-danger">*</span></label>
                        <input type="text" name="skill_name" class="form-control" placeholder="e.g. Python, Figma, React, Calculus, Guitar" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat; ?>"><?= $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Proficiency / Target Level</label>
                            <select name="proficiency_level" class="form-select">
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate" selected>Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description / What will you cover?</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe what topics, tools, or syllabus you can help with..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-lg me-1"></i> Save Skill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setSkillType(type) {
    if (type === 'teach') {
        document.getElementById('typeTeach').checked = true;
        document.getElementById('addSkillModalLabel').innerText = 'Add Skill to Teach (Offered)';
    } else {
        document.getElementById('typeLearn').checked = true;
        document.getElementById('addSkillModalLabel').innerText = 'Add Skill to Learn (Desired)';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
