<?php
$pageTitle = 'Swap Collaboration Room';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$db = get_db_connection();
$userId = current_user_id();
$swapId = (int)($_GET['id'] ?? 0);

if (!$swapId) {
    header('Location: ' . app_path('student/swap_requests.php'));
    exit;
}

// Fetch Swap
$stmt = $db->prepare("
    SELECT sr.*, 
           u_sender.name as sender_name, u_sender.email as sender_email, u_sender.contact_info as sender_contact,
           u_rec.name as receiver_name, u_rec.email as receiver_email, u_rec.contact_info as receiver_contact,
           s_offered.skill_name as offered_skill,
           s_req.skill_name as requested_skill
    FROM swap_requests sr
    JOIN users u_sender ON sr.sender_id = u_sender.id
    JOIN users u_rec ON sr.receiver_id = u_rec.id
    JOIN skills s_offered ON sr.offered_skill_id = s_offered.id
    JOIN skills s_req ON sr.requested_skill_id = s_req.id
    WHERE sr.id = ? AND (sr.sender_id = ? OR sr.receiver_id = ?)
");
$stmt->execute([$swapId, $userId, $userId]);
$swap = $stmt->fetch();

if (!$swap) {
    set_flash('danger', 'Swap session not found or access denied.');
    header('Location: ' . app_path('student/swap_requests.php'));
    exit;
}

// Handle new chat message / note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if (!empty($msg)) {
        $chatStmt = $db->prepare("INSERT INTO swap_messages (swap_id, sender_id, message) VALUES (?, ?, ?)");
        $chatStmt->execute([$swapId, $userId, $msg]);
        header('Location: ' . app_path('student/swap_detail.php?id=' . $swapId));
        exit;
    }
}

// Fetch Messages
$msgStmt = $db->prepare("
    SELECT sm.*, u.name as sender_name 
    FROM swap_messages sm 
    JOIN users u ON sm.sender_id = u.id 
    WHERE sm.swap_id = ? 
    ORDER BY sm.created_at ASC
");
$msgStmt->execute([$swapId]);
$messages = $msgStmt->fetchAll();

$isSender = ($swap['sender_id'] == $userId);
$partnerName = $isSender ? $swap['receiver_name'] : $swap['sender_name'];
$partnerContact = $isSender ? $swap['receiver_contact'] : $swap['sender_contact'];
$partnerEmail = $isSender ? $swap['receiver_email'] : $swap['sender_email'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?= app_path('student/swap_requests.php?tab=active'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Swaps
    </a>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
        <div>
            <h3 class="fw-bold mb-0">Collaboration Room: <?= sanitize($partnerName); ?></h3>
            <span class="badge status-<?= $swap['status']; ?> mt-1">Status: <?= ucfirst($swap['status']); ?></span>
        </div>
        <div>
            <?php if ($swap['status'] === 'accepted'): ?>
                <form method="POST" action="<?= app_path('student/swap_requests.php'); ?>">
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="swap_id" value="<?= $swap['id']; ?>">
                    <button type="submit" class="btn btn-success rounded-pill btn-sm px-3 shadow-sm" onclick="return confirm('Complete this exchange session?');">
                        <i class="bi bi-check2-all me-1"></i> Mark Swap Completed
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-arrow-left-right text-primary me-2"></i>Exchange Agreement</h5>
            <div class="p-3 bg-light rounded-3 mb-3">
                <small class="text-muted d-block mb-1">Your Contribution:</small>
                <strong class="text-primary fs-6"><?= sanitize($isSender ? $swap['offered_skill'] : $swap['requested_skill']); ?></strong>
            </div>

            <div class="p-3 bg-light rounded-3 mb-3">
                <small class="text-muted d-block mb-1">Partner's Contribution:</small>
                <strong class="text-success fs-6"><?= sanitize($isSender ? $swap['requested_skill'] : $swap['offered_skill']); ?></strong>
            </div>

            <hr>

            <h6 class="fw-bold mb-2">Partner Contact Info</h6>
            <p class="small text-muted mb-1"><i class="bi bi-envelope me-2"></i> <?= sanitize($partnerEmail); ?></p>
            <p class="small text-muted mb-0"><i class="bi bi-chat-text me-2"></i> <?= sanitize($partnerContact ?: 'Coordinate in chat below'); ?></p>
        </div>
    </div>

    <!-- Right Column: Collaboration Notes & Chat -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 bg-white d-flex flex-column" style="height: 580px;">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-chat-dots-fill text-primary fs-5"></i>
                    <h6 class="fw-bold mb-0">Coordination Notes & Chat</h6>
                </div>
                <small class="text-muted">Use this to share notes, timings, and meeting links</small>
            </div>

            <div class="p-3 flex-grow-1 overflow-auto bg-light d-flex flex-column gap-3" id="roomMessages">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5 text-muted small">
                        <i class="bi bi-chat-left-dots fs-2 d-block mb-2"></i>
                        No messages yet. Send a greeting or propose a study session time!
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $m): 
                        $isMe = ($m['sender_id'] == $userId);
                    ?>
                        <div class="d-flex flex-column <?= $isMe ? 'align-items-end' : 'align-items-start'; ?>">
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <small class="fw-semibold text-dark" style="font-size: 0.75rem;"><?= sanitize($m['sender_name']); ?></small>
                                <small class="text-muted" style="font-size: 0.7rem;">&bull; <?= date('h:i A, M d', strtotime($m['created_at'])); ?></small>
                            </div>
                            <div class="p-3 rounded-4 shadow-sm text-break <?= $isMe ? 'bg-primary text-white' : 'bg-white text-dark border'; ?>" style="max-width: 80%;">
                                <?= nl2br(sanitize($m['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="p-3 border-top bg-white">
                <form method="POST" action="" class="d-flex gap-2">
                    <input type="text" name="message" class="form-control rounded-pill px-3" placeholder="Type a message, suggest Google Meet link, or learning note..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('roomMessages');
    if (el) el.scrollTop = el.scrollHeight;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
