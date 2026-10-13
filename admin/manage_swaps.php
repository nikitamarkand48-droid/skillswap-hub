<?php
$pageTitle = 'Manage Swaps';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $swapId = (int)($_POST['swap_id'] ?? 0);

    if ($swapId) {
        if ($action === 'delete_swap') {
            $stmt = $db->prepare("DELETE FROM swap_requests WHERE id = ?");
            $stmt->execute([$swapId]);
            set_flash('info', 'Swap record removed.');
        } elseif ($action === 'force_complete') {
            $stmt = $db->prepare("UPDATE swap_requests SET status = 'completed' WHERE id = ?");
            $stmt->execute([$swapId]);
            set_flash('success', 'Swap status updated to completed.');
        }
    }
    header('Location: ' . app_path('admin/manage_swaps.php'));
    exit;
}

$statusFilter = trim($_GET['status'] ?? '');
$query = "
    SELECT sr.*, 
           u_sender.name as sender_name, u_sender.email as sender_email,
           u_rec.name as receiver_name, u_rec.email as receiver_email,
           s_off.skill_name as offered_skill,
           s_req.skill_name as requested_skill,
           (SELECT COUNT(*) FROM swap_messages WHERE swap_id = sr.id) as message_count
    FROM swap_requests sr
    JOIN users u_sender ON sr.sender_id = u_sender.id
    JOIN users u_rec ON sr.receiver_id = u_rec.id
    JOIN skills s_off ON sr.offered_skill_id = s_off.id
    JOIN skills s_req ON sr.requested_skill_id = s_req.id
";
$params = [];

if ($statusFilter) {
    $query .= " WHERE sr.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY sr.id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$swaps = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <a href="<?= app_path('admin/dashboard.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <h3 class="fw-bold mb-0">Campus Swap Supervision</h3>
        <p class="text-muted small mb-0">Inspect all student exchange transactions, message logs, and dispute resolutions.</p>
    </div>
    <div class="d-flex flex-wrap gap-1">
        <?php foreach (['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $val => $lbl): ?>
            <a href="?status=<?= $val; ?>" class="btn btn-sm <?= ($statusFilter === $val) ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3">
                <?= $lbl; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Swap ID</th>
                    <th>Students (Sender &harr; Recipient)</th>
                    <th>Skill Exchange Pair</th>
                    <th>Chat Activity</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Admin Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($swaps)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No swaps matching criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($swaps as $sw): ?>
                        <tr>
                            <td>#<?= $sw['id']; ?></td>
                            <td>
                                <span class="fw-bold text-dark"><?= sanitize($sw['sender_name']); ?></span>
                                <span class="text-muted mx-1">&rarr;</span>
                                <span class="fw-bold text-dark"><?= sanitize($sw['receiver_name']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary"><?= sanitize($sw['offered_skill']); ?></span>
                                <span class="text-muted mx-1">&harr;</span>
                                <span class="badge bg-warning-subtle text-dark"><?= sanitize($sw['requested_skill']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-chat-text me-1"></i> <?= $sw['message_count']; ?> messages
                                </span>
                            </td>
                            <td>
                                <span class="badge status-<?= $sw['status']; ?> rounded-pill px-2 py-1">
                                    <?= ucfirst($sw['status']); ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= date('M d, Y', strtotime($sw['created_at'])); ?></td>
                            <td class="text-end">
                                <?php if ($sw['status'] === 'accepted'): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="action" value="force_complete">
                                        <input type="hidden" name="swap_id" value="<?= $sw['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0 me-1" title="Mark Completed">
                                            Complete
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this swap record?');">
                                    <input type="hidden" name="action" value="delete_swap">
                                    <input type="hidden" name="swap_id" value="<?= $sw['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0" title="Delete Swap">
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
