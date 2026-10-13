<?php
$pageTitle = 'Admin Management Dashboard';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$db = get_db_connection();

// 1. Overall Metrics
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalSkillsTeach = $db->query("SELECT COUNT(*) FROM skills WHERE type = 'teach'")->fetchColumn();
$totalSkillsLearn = $db->query("SELECT COUNT(*) FROM skills WHERE type = 'learn'")->fetchColumn();
$totalSwaps = $db->query("SELECT COUNT(*) FROM swap_requests")->fetchColumn();
$completedSwaps = $db->query("SELECT COUNT(*) FROM swap_requests WHERE status = 'completed'")->fetchColumn();
$completionRate = ($totalSwaps > 0) ? round(($completedSwaps / $totalSwaps) * 100, 1) : 0;

// 2. Category Breakdown for Chart.js
$categories = ['Programming', 'Web Development', 'Data Science', 'Design & UI/UX', 'Academics & Math', 'Languages'];
$teachData = [];
$learnData = [];

foreach ($categories as $cat) {
    $teachStmt = $db->prepare("SELECT COUNT(*) FROM skills WHERE category LIKE ? AND type = 'teach'");
    $teachStmt->execute(["%{$cat}%"]);
    $teachData[] = (int)$teachStmt->fetchColumn();

    $learnStmt = $db->prepare("SELECT COUNT(*) FROM skills WHERE category LIKE ? AND type = 'learn'");
    $learnStmt->execute(["%{$cat}%"]);
    $learnData[] = (int)$learnStmt->fetchColumn();
}

// 3. Status Breakdown for Chart.js Doughnut
$statuses = ['completed', 'accepted', 'pending', 'rejected', 'cancelled'];
$statusCounts = [];
foreach ($statuses as $st) {
    $stStmt = $db->prepare("SELECT COUNT(*) FROM swap_requests WHERE status = ?");
    $stStmt->execute([$st]);
    $statusCounts[] = (int)$stStmt->fetchColumn();
}

$chartDataJson = json_encode([
    'categories' => [
        'labels' => $categories,
        'teachData' => $teachData,
        'learnData' => $learnData
    ],
    'statuses' => [
        'labels' => ['Completed', 'Accepted', 'Pending', 'Rejected', 'Cancelled'],
        'counts' => $statusCounts
    ]
]);

// 4. Recent Swaps for Table
$recentSwaps = $db->query("
    SELECT sr.*, 
           u_sender.name as sender_name,
           u_rec.name as receiver_name,
           s_off.skill_name as offered_skill,
           s_req.skill_name as requested_skill
    FROM swap_requests sr
    JOIN users u_sender ON sr.sender_id = u_sender.id
    JOIN users u_rec ON sr.receiver_id = u_rec.id
    JOIN skills s_off ON sr.offered_skill_id = s_off.id
    JOIN skills s_req ON sr.requested_skill_id = s_req.id
    ORDER BY sr.id DESC
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger rounded-pill px-3 py-1">Admin Portal</span>
            <h3 class="fw-bold mb-0">Platform Overview & Analytics</h3>
        </div>
        <p class="text-muted small mb-0">Monitor campus skill liquidity, user engagement, and peer exchange operations.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= app_path('admin/manage_users.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="bi bi-people-fill me-1"></i> Manage Users
        </a>
        <a href="<?= app_path('admin/manage_skills.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-collection-fill me-1"></i> Moderate Skills
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Active Students</span>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-people-fill fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $totalUsers; ?></h3>
            <small class="text-success"><i class="bi bi-shield-check"></i> Registered</small>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Skills in Pool</span>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-collection-play-fill fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= ($totalSkillsTeach + $totalSkillsLearn); ?></h3>
            <small class="text-muted"><?= $totalSkillsTeach; ?> teach &bull; <?= $totalSkillsLearn; ?> learn</small>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Total Swap Proposals</span>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-arrow-left-right fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $totalSwaps; ?></h3>
            <small class="text-muted"><?= $completedSwaps; ?> completed</small>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Completion Rate</span>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-pie-chart-fill fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= $completionRate; ?>%</h3>
            <small class="text-success"><i class="bi bi-arrow-up-right"></i> Healthy Exchange</small>
        </div>
    </div>
</div>

<!-- Chart.js Section -->
<div class="row g-4 mb-4">
    <!-- Bar Chart: Category Demand vs Supply -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Skills by Category (Supply vs Demand)</h5>
                <small class="text-muted">Offered (Teach) vs Desired (Learn)</small>
            </div>
            <div style="position: relative; height: 280px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Doughnut Chart: Swap Status Breakdown -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="fw-bold mb-3">Swap Status Distribution</h5>
            <div style="position: relative; height: 280px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Swaps Management Table -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Live Peer Exchange Stream</h5>
        <a href="<?= app_path('admin/manage_swaps.php'); ?>" class="small text-primary text-decoration-none">View All Swaps &rarr;</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Initiator (Sender)</th>
                    <th scope="col">Recipient</th>
                    <th scope="col">Offered Skill</th>
                    <th scope="col">Requested Skill</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentSwaps)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No swaps recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentSwaps as $s): ?>
                        <tr>
                            <td>#<?= $s['id']; ?></td>
                            <td class="fw-semibold"><?= sanitize($s['sender_name']); ?></td>
                            <td><?= sanitize($s['receiver_name']); ?></td>
                            <td><span class="badge bg-primary-subtle text-primary"><?= sanitize($s['offered_skill']); ?></span></td>
                            <td><span class="badge bg-warning-subtle text-dark"><?= sanitize($s['requested_skill']); ?></span></td>
                            <td>
                                <span class="badge status-<?= $s['status']; ?> rounded-pill px-2 py-1">
                                    <?= ucfirst($s['status']); ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($s['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Script Logic -->
<script src="<?= app_path('assets/js/admin_charts.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartData = <?= $chartDataJson; ?>;
    initAdminCharts(chartData);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
