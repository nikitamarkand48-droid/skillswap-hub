<?php
$pageTitle = 'Home - Peer-to-Peer College Skill Exchange';
require_once __DIR__ . '/config/db.php';

$db = get_db_connection();

// Live Counters
$studentCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$skillCount   = $db->query("SELECT COUNT(*) FROM skills")->fetchColumn();
$swapCount    = $db->query("SELECT COUNT(*) FROM swap_requests WHERE status IN ('accepted', 'completed')")->fetchColumn();

// Featured Skills (Top 6)
$featuredSkills = $db->query("
    SELECT s.*, u.name as user_name, u.department, u.year_of_study 
    FROM skills s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.type = 'teach' AND u.status = 'active'
    ORDER BY s.id DESC 
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<div class="hero-gradient mb-5 text-center text-md-start">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">
                <i class="bi bi-mortarboard-fill me-1"></i> Campus Peer Learning MVP
            </span>
            <h1 class="display-4 fw-extrabold mb-3" style="letter-spacing: -1px; font-weight: 800;">
                Trade Skills You Have.<br>Master Skills You Want.
            </h1>
            <p class="lead mb-4 opacity-90" style="max-width: 580px;">
                Connect with college peers for 1-on-1 reciprocal learning exchanges. Teach Python to learn Figma; teach Calculus to learn React. No tuition, just peer exchange!
            </p>

            <!-- Hero Action Buttons -->
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                <?php if (is_logged_in()): ?>
                    <a href="<?= app_path('student/dashboard.php'); ?>" class="btn btn-warning text-dark fw-bold rounded-pill px-4 py-3 shadow-sm">
                        <i class="bi bi-speedometer2 me-1"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?= app_path('auth/register.php'); ?>" class="btn btn-warning text-dark fw-bold rounded-pill px-4 py-3 shadow-sm">
                        Join Campus Exchange
                    </a>
                    <a href="<?= app_path('auth/login.php?demo=aarav'); ?>" class="btn btn-light rounded-pill px-4 py-3 text-primary fw-semibold shadow-sm">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> 1-Click Demo
                    </a>
                <?php endif; ?>
                <a href="<?= app_path('student/match.php'); ?>" class="btn btn-outline-light rounded-pill px-4 py-3">
                    <i class="bi bi-stars text-warning me-1"></i> Smart Matching
                </a>
            </div>
        </div>

        <div class="col-lg-5 d-none d-lg-block">
            <div class="card border-0 bg-white text-dark rounded-4 p-4 shadow-lg">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="p-2 bg-success text-white rounded-3"><i class="bi bi-stars"></i></span>
                        <h6 class="fw-bold mb-0">Live Match Example</h6>
                    </div>
                    <span class="badge bg-success rounded-pill px-2 py-1">100% Mutual</span>
                </div>

                <div class="mb-2 p-2 bg-light rounded-3">
                    <small class="text-muted d-block">Aarav Sharma (CS 3rd Year)</small>
                    <span class="badge bg-primary me-1">Teaches: Python</span>
                    <span class="badge bg-warning text-dark">Wants: Figma UI</span>
                </div>

                <div class="text-center my-1 text-primary fs-5">
                    <i class="bi bi-arrow-down-up"></i>
                </div>

                <div class="mb-3 p-2 bg-light rounded-3">
                    <small class="text-muted d-block">Priya Patel (IT 3rd Year)</small>
                    <span class="badge bg-primary me-1">Teaches: Figma UI</span>
                    <span class="badge bg-warning text-dark">Wants: Python</span>
                </div>

                <div class="text-center">
                    <small class="text-success fw-bold"><i class="bi bi-check2-circle me-1"></i> Reciprocal Exchange Connected</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Campus Stats Bar -->
<div class="row g-3 mb-5 text-center">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-subtle text-primary mx-auto mb-2">
                <i class="bi bi-people-fill"></i>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $studentCount; ?></h3>
            <span class="text-muted small">Campus Students</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-subtle text-warning mx-auto mb-2">
                <i class="bi bi-collection-fill"></i>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $skillCount; ?></h3>
            <span class="text-muted small">Skills Listed</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-subtle text-success mx-auto mb-2">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $swapCount; ?></h3>
            <span class="text-muted small">Swaps Connected</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-subtle text-info mx-auto mb-2">
                <i class="bi bi-robot"></i>
            </div>
            <h3 class="fw-bold text-dark mb-0">24/7</h3>
            <span class="text-muted small">AI Campus Advisor</span>
        </div>
    </div>
</div>

<!-- 8 Core MVP Features Section -->
<div class="mb-5">
    <div class="text-center mb-5">
        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold">Comprehensive MVP</span>
        <h2 class="fw-bold mt-2">8 Core Features Built For College Peer Learning</h2>
        <p class="text-muted col-md-7 mx-auto small">Engineered specifically with modern frontend (HTML5, Bootstrap 5, JS), responsive backend (PHP, MySQL), Chart.js analytics, and AI API chatbot.</p>
    </div>

    <div class="row g-4">
        <!-- Feature 1 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-primary text-white mb-3">
                    <i class="bi bi-key-fill"></i>
                </div>
                <h5 class="fw-bold">1. Registration / Login</h5>
                <p class="text-muted small mb-0">Secure student authentication with bcrypt password hashing, department details, and session persistence.</p>
            </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-success text-white mb-3">
                    <i class="bi bi-person-bounding-box"></i>
                </div>
                <h5 class="fw-bold">2. Student Profile</h5>
                <p class="text-muted small mb-0">Personal profile with academic year, department, bio, rating badges, and history of completed exchanges.</p>
            </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-warning text-dark mb-3">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h5 class="fw-bold">3. Add Skills (Teach)</h5>
                <p class="text-muted small mb-0">List skills you can teach with proficiency tags (Beginner, Intermediate, Advanced) and custom syllabus details.</p>
            </div>
        </div>

        <!-- Feature 4 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-info text-white mb-3">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <h5 class="fw-bold">4. Learning Skills</h5>
                <p class="text-muted small mb-0">Publish desired learning targets so the matching algorithm can find matching tutors among fellow students.</p>
            </div>
        </div>

        <!-- Feature 5 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-danger text-white mb-3">
                    <i class="bi bi-search"></i>
                </div>
                <h5 class="fw-bold">5. Search & Filters</h5>
                <p class="text-muted small mb-0">Filter peers by subject category, skill type, and keyword search to find the perfect learning partner.</p>
            </div>
        </div>

        <!-- Feature 6 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-primary text-white mb-3">
                    <i class="bi bi-stars"></i>
                </div>
                <h5 class="fw-bold">6. Skill Matching</h5>
                <p class="text-muted small mb-0">Reciprocal algorithm calculates mutual compatibility scores (up to 100%) for instant double-swap agreements.</p>
            </div>
        </div>

        <!-- Feature 7 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-secondary text-white mb-3">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h5 class="fw-bold">7. Swap Requests & Collab</h5>
                <p class="text-muted small mb-0">Proposal workflow (Pending, Accepted, Rejected), collaboration room for meeting links, and peer ratings.</p>
            </div>
        </div>

        <!-- Feature 8 -->
        <div class="col-md-6 col-lg-3">
            <div class="swap-card h-100 p-4">
                <div class="stat-icon bg-dark text-white mb-3">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h5 class="fw-bold">8. Admin Panel & Charts</h5>
                <p class="text-muted small mb-0">Supervisory dashboard with Chart.js analytics (category supply/demand, swap status) and user moderation.</p>
            </div>
        </div>
    </div>
</div>

<!-- Featured Skills Section -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Trending Teaching Offers</h3>
            <p class="text-muted small mb-0">Explore what students are currently offering across campus.</p>
        </div>
        <a href="<?= app_path('student/search.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Browse All &rarr;
        </a>
    </div>

    <div class="row g-4">
        <?php foreach ($featuredSkills as $fs): ?>
            <div class="col-md-6 col-lg-4">
                <div class="swap-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-circle avatar-circle-sm">
                                <?= strtoupper(substr($fs['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <span class="fw-bold text-dark small"><?= sanitize($fs['user_name']); ?></span>
                                <small class="text-muted d-block" style="font-size: 0.72rem;"><?= sanitize($fs['department']); ?></small>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-1"><?= sanitize($fs['skill_name']); ?></h5>
                        <div class="d-flex gap-1 mb-2">
                            <span class="badge badge-teach small"><?= sanitize($fs['category']); ?></span>
                            <span class="badge badge-level badge-<?= sanitize($fs['proficiency_level']); ?>">
                                <?= sanitize($fs['proficiency_level']); ?>
                            </span>
                        </div>
                        <p class="text-muted small mb-0"><?= sanitize($fs['description']); ?></p>
                    </div>

                    <div class="pt-3 border-top mt-3">
                        <a href="<?= app_path('student/search.php?q=' . urlencode($fs['skill_name'])); ?>" class="btn btn-sm btn-outline-primary w-100 rounded-pill">
                            <i class="bi bi-arrow-left-right me-1"></i> Request Swap
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- How It Works Section -->
<div class="bg-white rounded-4 p-4 p-md-5 border mb-5">
    <div class="text-center mb-4">
        <h3 class="fw-bold">How Peer Skill Swapping Works</h3>
        <p class="text-muted small">Learn faster together through structured 3-step reciprocal tutoring</p>
    </div>

    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="bg-primary-subtle text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: bold;">
                1
            </div>
            <h5 class="fw-bold">List Your Skills</h5>
            <p class="text-muted small">Add 1 skill you're confident teaching and 1 skill you are eager to learn.</p>
        </div>
        <div class="col-md-4">
            <div class="bg-warning-subtle text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: bold;">
                2
            </div>
            <h5 class="fw-bold">Get Matched</h5>
            <p class="text-muted small">Our algorithm finds mutual matches where both students have reciprocal needs.</p>
        </div>
        <div class="col-md-4">
            <div class="bg-success-subtle text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: bold;">
                3
            </div>
            <h5 class="fw-bold">Exchange & Grow</h5>
            <p class="text-muted small">Connect in the swap room, arrange sessions in library or online, and rate each other!</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
