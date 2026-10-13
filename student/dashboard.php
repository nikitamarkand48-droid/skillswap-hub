<?php
$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$db = get_db_connection();
$userId = current_user_id();

// Metrics
$teachCount = $db->query("SELECT COUNT(*) FROM skills WHERE user_id = {$userId} AND type = 'teach'")->fetchColumn();
$learnCount = $db->query("SELECT COUNT(*) FROM skills WHERE user_id = {$userId} AND type = 'learn'")->fetchColumn();
$pendingInCount = $db->query("SELECT COUNT(*) FROM swap_requests WHERE receiver_id = {$userId} AND status = 'pending'")->fetchColumn();
$completedCount = $db->query("SELECT COUNT(*) FROM swap_requests WHERE (sender_id = {$userId} OR receiver_id = {$userId}) AND status = 'completed'")->fetchColumn();

// Recent active or pending requests
$recentSwapsStmt = $db->prepare("
    SELECT sr.*, 
           u_sender.name as sender_name,
           u_rec.name as receiver_name,
           s_offered.skill_name as offered_skill,
           s_req.skill_name as requested_skill
    FROM swap_requests sr
    JOIN users u_sender ON sr.sender_id = u_sender.id
    JOIN users u_rec ON sr.receiver_id = u_rec.id
    JOIN skills s_offered ON sr.offered_skill_id = s_offered.id
    JOIN skills s_req ON sr.requested_skill_id = s_req.id
    WHERE sr.sender_id = ? OR sr.receiver_id = ?
    ORDER BY sr.updated_at DESC
    LIMIT 4
");
$recentSwapsStmt->execute([$userId, $userId]);
$recentSwaps = $recentSwapsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="hero-gradient mb-4 p-4 p-md-5">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h2 class="fw-bold mb-2">Welcome back, <?= sanitize($_SESSION['user_name']); ?>! 👋</h2>
            <p class="mb-3 opacity-90">Ready to learn something new or share your skills today? Check your matches or propose a peer exchange.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= app_path('student/match.php'); ?>" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="bi bi-stars me-1"></i> View Smart Matches
                </a>
                <a href="<?= app_path('student/my_skills.php'); ?>" class="btn btn-outline-light rounded-pill px-4">
                    <i class="bi bi-plus-circle me-1"></i> Add Skills
                </a>
                <a href="<?= app_path('student/search.php'); ?>" class="btn btn-light rounded-pill px-4 text-primary">
                    <i class="bi bi-search me-1"></i> Explore Peers
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Teaching Skills</span>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-mortarboard-fill fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $teachCount; ?></h3>
            <a href="<?= app_path('student/my_skills.php'); ?>" class="small text-primary text-decoration-none">Manage Skills &rarr;</a>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Learning Goals</span>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-journal-bookmark-fill fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $learnCount; ?></h3>
            <a href="<?= app_path('student/my_skills.php'); ?>" class="small text-warning text-decoration-none">View Goals &rarr;</a>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Pending Swaps</span>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-hourglass-split fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $pendingInCount; ?></h3>
            <a href="<?= app_path('student/swap_requests.php?tab=incoming'); ?>" class="small text-info text-decoration-none">Review Requests &rarr;</a>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Completed Swaps</span>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $completedCount; ?></h3>
            <a href="<?= app_path('student/swap_requests.php?tab=active'); ?>" class="small text-success text-decoration-none">Exchange History &rarr;</a>
        </div>
    </div>
</div>

<!-- Main Section: Recent Swaps & AI Advisor Promo -->
<div class="row g-4">
    <!-- Left: Recent Swaps -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Recent Swap Activity</h5>
                <a href="<?= app_path('student/swap_requests.php'); ?>" class="small text-primary text-decoration-none">View All Swaps &rarr;</a>
            </div>

            <?php if (empty($recentSwaps)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary"></i>
                    <p class="mb-2">No swap activity yet!</p>
                    <a href="<?= app_path('student/search.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Find Peers to Swap With</a>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentSwaps as $s): 
                        $isSender = ($s['sender_id'] == $userId);
                        $partner = $isSender ? $s['receiver_name'] : $s['sender_name'];
                    ?>
                        <div class="list-group-item px-0 py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">
                                    Exchange with <?= sanitize($partner); ?>
                                </h6>
                                <div class="small text-muted">
                                    Offered: <span class="text-primary fw-semibold"><?= sanitize($s['offered_skill']); ?></span> &bull; 
                                    Requested: <span class="text-success fw-semibold"><?= sanitize($s['requested_skill']); ?></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge status-<?= $s['status']; ?> rounded-pill px-3 py-1">
                                    <?= ucfirst($s['status']); ?>
                                </span>
                                <?php if ($s['status'] === 'accepted'): ?>
                                    <a href="<?= app_path('student/swap_detail.php?id=' . $s['id']); ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                        Room
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Quick AI Assistant & Tips -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-robot fs-5"></i>
                </div>
                <h5 class="fw-bold mb-0">Ask SwapBot AI</h5>
            </div>
            <p class="text-muted small">Need guidance on what skills to learn, how to structure a 1-hour swap, or write a proposal?</p>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm text-start py-2 rounded-3" onclick="document.getElementById('chatbotToggler').click();">
                    <i class="bi bi-chat-quote me-1"></i> "How do I propose a swap?"
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm text-start py-2 rounded-3" onclick="document.getElementById('chatbotToggler').click();">
                    <i class="bi bi-lightbulb me-1"></i> "What skills are popular?"
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-shield-check text-success me-1"></i> Campus Swapping Etiquette</h6>
            <ul class="small text-muted ps-3 mb-0">
                <li class="mb-1">Agree on mutual topics before the call.</li>
                <li class="mb-1">Meet at college library or via Google Meet.</li>
                <li>Leave an honest rating to help peer ranking!</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
