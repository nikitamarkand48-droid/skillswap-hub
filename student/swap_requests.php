<?php
$pageTitle = 'Swap Requests Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$db = get_db_connection();
$userId = current_user_id();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Send Request
    if ($action === 'send_swap_request') {
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $offeredSkillId = (int)($_POST['offered_skill_id'] ?? 0);
        $requestedSkillId = (int)($_POST['requested_skill_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($receiverId && $offeredSkillId && $requestedSkillId) {
            // Verify not sending to self
            if ($receiverId === $userId) {
                set_flash('danger', 'You cannot send a swap request to yourself.');
            } else {
                $stmt = $db->prepare("
                    INSERT INTO swap_requests (sender_id, receiver_id, offered_skill_id, requested_skill_id, message, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$userId, $receiverId, $offeredSkillId, $requestedSkillId, $message]);
                set_flash('success', 'Swap proposal sent successfully! The recipient will be notified.');
            }
        } else {
            set_flash('danger', 'Missing required swap details. Please select an offered skill.');
        }
        header('Location: ' . app_path('student/swap_requests.php?tab=outgoing'));
        exit;
    }

    // 2. Accept Request
    if ($action === 'accept') {
        $swapId = (int)($_POST['swap_id'] ?? 0);
        $stmt = $db->prepare("UPDATE swap_requests SET status = 'accepted' WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$swapId, $userId]);

        // Insert initial system message into swap chat
        $chatStmt = $db->prepare("INSERT INTO swap_messages (swap_id, sender_id, message) VALUES (?, ?, ?)");
        $chatStmt->execute([$swapId, $userId, "I have accepted your skill swap request! Let's arrange our first session."]);

        set_flash('success', 'Swap accepted! You can now coordinate learning in the Swap Room.');
        header('Location: ' . app_path('student/swap_requests.php?tab=active'));
        exit;
    }

    // 2b. Quick Demo: Simulate Peer Acceptance (Viva helper)
    if ($action === 'simulate_accept') {
        $swapId = (int)($_POST['swap_id'] ?? 0);
        $recId  = (int)($_POST['receiver_id'] ?? 0);
        $stmt = $db->prepare("UPDATE swap_requests SET status = 'accepted' WHERE id = ? AND sender_id = ?");
        $stmt->execute([$swapId, $userId]);

        $chatStmt = $db->prepare("INSERT INTO swap_messages (swap_id, sender_id, message) VALUES (?, ?, ?)");
        $chatStmt->execute([$swapId, $recId, "Hey! I reviewed your proposal and accepted the swap. Let's schedule our first session!"]);

        set_flash('success', 'Peer acceptance simulated! Swap is now Active. You can enter the Collaboration Room.');
        header('Location: ' . app_path('student/swap_requests.php?tab=active'));
        exit;
    }

    // 3. Reject Request
    if ($action === 'reject') {
        $swapId = (int)($_POST['swap_id'] ?? 0);
        $stmt = $db->prepare("UPDATE swap_requests SET status = 'rejected' WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$swapId, $userId]);
        set_flash('info', 'Swap request was declined.');
        header('Location: ' . app_path('student/swap_requests.php?tab=incoming'));
        exit;
    }

    // 4. Cancel Request (Sender cancels pending request)
    if ($action === 'cancel') {
        $swapId = (int)($_POST['swap_id'] ?? 0);
        $stmt = $db->prepare("UPDATE swap_requests SET status = 'cancelled' WHERE id = ? AND sender_id = ?");
        $stmt->execute([$swapId, $userId]);
        set_flash('info', 'Swap proposal cancelled.');
        header('Location: ' . app_path('student/swap_requests.php?tab=outgoing'));
        exit;
    }

    // 5. Complete Swap
    if ($action === 'complete') {
        $swapId = (int)($_POST['swap_id'] ?? 0);
        $stmt = $db->prepare("UPDATE swap_requests SET status = 'completed' WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
        $stmt->execute([$swapId, $userId, $userId]);
        set_flash('success', 'Exchange marked as completed! Please leave a review for your peer.');
        header('Location: ' . app_path('student/swap_requests.php?tab=active&review=' . $swapId));
        exit;
    }

    // 6. Submit Review
    if ($action === 'submit_review') {
        $swapId = (int)($_POST['swap_id'] ?? 0);
        $revieweeId = (int)($_POST['reviewee_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating >= 1 && $rating <= 5 && $swapId && $revieweeId) {
            $stmt = $db->prepare("INSERT INTO reviews (swap_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$swapId, $userId, $revieweeId, $rating, $comment]);
            set_flash('success', 'Thank you! Your rating and feedback have been published.');
        }
        header('Location: ' . app_path('student/swap_requests.php?tab=active'));
        exit;
    }
}

$activeTab = $_GET['tab'] ?? 'incoming';

// Fetch Incoming Requests
$inStmt = $db->prepare("
    SELECT sr.*, u.name as sender_name, u.department, u.contact_info,
           s_offered.skill_name as offered_skill,
           s_req.skill_name as requested_skill
    FROM swap_requests sr
    JOIN users u ON sr.sender_id = u.id
    JOIN skills s_offered ON sr.offered_skill_id = s_offered.id
    JOIN skills s_req ON sr.requested_skill_id = s_req.id
    WHERE sr.receiver_id = ?
    ORDER BY sr.id DESC
");
$inStmt->execute([$userId]);
$incoming = $inStmt->fetchAll();

// Fetch Outgoing Requests
$outStmt = $db->prepare("
    SELECT sr.*, u.name as receiver_name, u.department,
           s_offered.skill_name as offered_skill,
           s_req.skill_name as requested_skill
    FROM swap_requests sr
    JOIN users u ON sr.receiver_id = u.id
    JOIN skills s_offered ON sr.offered_skill_id = s_offered.id
    JOIN skills s_req ON sr.requested_skill_id = s_req.id
    WHERE sr.sender_id = ?
    ORDER BY sr.id DESC
");
$outStmt->execute([$userId]);
$outgoing = $outStmt->fetchAll();

// Fetch Active / Completed Swaps (Both roles)
$actStmt = $db->prepare("
    SELECT sr.*, 
           u_sender.name as sender_name, u_sender.contact_info as sender_contact,
           u_rec.name as receiver_name, u_rec.contact_info as receiver_contact,
           s_offered.skill_name as offered_skill,
           s_req.skill_name as requested_skill
    FROM swap_requests sr
    JOIN users u_sender ON sr.sender_id = u_sender.id
    JOIN users u_rec ON sr.receiver_id = u_rec.id
    JOIN skills s_offered ON sr.offered_skill_id = s_offered.id
    JOIN skills s_req ON sr.requested_skill_id = s_req.id
    WHERE (sr.sender_id = ? OR sr.receiver_id = ?) AND sr.status IN ('accepted', 'completed')
    ORDER BY sr.updated_at DESC
");
$actStmt->execute([$userId, $userId]);
$activeSwaps = $actStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">Peer Swap Requests</h3>
    <p class="text-muted small">Manage incoming swap invitations, track outgoing proposals, and collaborate with peers.</p>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mt-3" id="swapTabs">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 <?= ($activeTab === 'incoming') ? 'active' : ''; ?>" href="?tab=incoming">
                <i class="bi bi-inbox-fill me-1"></i> Incoming Requests
                <span class="badge bg-white text-dark ms-1"><?= count(array_filter($incoming, fn($r) => $r['status'] === 'pending')); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 <?= ($activeTab === 'outgoing') ? 'active' : ''; ?>" href="?tab=outgoing">
                <i class="bi bi-send-fill me-1"></i> Sent Proposals
                <span class="badge bg-secondary ms-1"><?= count($outgoing); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 <?= ($activeTab === 'active') ? 'active' : ''; ?>" href="?tab=active">
                <i class="bi bi-arrow-repeat me-1"></i> Active & Completed Swaps
                <span class="badge bg-success ms-1"><?= count($activeSwaps); ?></span>
            </a>
        </li>
    </ul>
</div>

<!-- Tab 1: Incoming -->
<?php if ($activeTab === 'incoming'): ?>
    <?php if (empty($incoming)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-inbox text-muted fs-1 mb-2"></i>
            <h5 class="fw-bold">No Incoming Requests Yet</h5>
            <p class="text-muted small">When other students request to learn from you, their proposals will show up here.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($incoming as $req): ?>
                <div class="col-md-6 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">
                                        <?= strtoupper(substr($req['sender_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?= sanitize($req['sender_name']); ?></h6>
                                        <small class="text-muted"><?= sanitize($req['department']); ?></small>
                                    </div>
                                </div>
                                <span class="badge status-<?= $req['status']; ?> rounded-pill px-3 py-1">
                                    <?= ucfirst($req['status']); ?>
                                </span>
                            </div>

                            <div class="p-3 bg-light rounded-3 mb-3 small">
                                <div class="mb-1">
                                    <span class="text-muted">They will teach you:</span>
                                    <strong class="text-primary"><?= sanitize($req['offered_skill']); ?></strong>
                                </div>
                                <div>
                                    <span class="text-muted">They want to learn:</span>
                                    <strong class="text-success"><?= sanitize($req['requested_skill']); ?></strong>
                                </div>
                            </div>

                            <?php if ($req['message']): ?>
                                <p class="text-muted small mb-0 fst-italic">"<?= sanitize($req['message']); ?>"</p>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">
                                Received: <?= date('M d, Y h:i A', strtotime($req['created_at'])); ?>
                            </small>
                        </div>

                        <?php if ($req['status'] === 'pending'): ?>
                            <div class="pt-3 border-top mt-3 d-flex gap-2">
                                <form method="POST" action="" class="flex-fill">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="swap_id" value="<?= $req['id']; ?>">
                                    <button type="submit" class="btn btn-success w-100 rounded-pill btn-sm py-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> Accept Swap
                                    </button>
                                </form>
                                <form method="POST" action="" class="flex-fill">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="swap_id" value="<?= $req['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger w-100 rounded-pill btn-sm py-2" onclick="return confirm('Decline this swap request?');">
                                        <i class="bi bi-x-circle me-1"></i> Decline
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($req['status'] === 'accepted'): ?>
                            <div class="pt-3 border-top mt-3">
                                <a href="<?= app_path('student/swap_detail.php?id=' . $req['id']); ?>" class="btn btn-primary w-100 rounded-pill btn-sm py-2">
                                    <i class="bi bi-chat-dots-fill me-1"></i> Open Swap Collaboration Room
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<!-- Tab 2: Outgoing -->
<?php elseif ($activeTab === 'outgoing'): ?>
    <?php if (empty($outgoing)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-send text-muted fs-1 mb-2"></i>
            <h5 class="fw-bold">No Sent Proposals Yet</h5>
            <p class="text-muted small">Explore skills or visit the matching page to send your first proposal!</p>
            <div>
                <a href="<?= app_path('student/search.php'); ?>" class="btn btn-primary rounded-pill px-4">Find Skills</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($outgoing as $req): ?>
                <div class="col-md-6 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">
                                        <?= strtoupper(substr($req['receiver_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?= sanitize($req['receiver_name']); ?></h6>
                                        <small class="text-muted"><?= sanitize($req['department']); ?></small>
                                    </div>
                                </div>
                                <span class="badge status-<?= $req['status']; ?> rounded-pill px-3 py-1">
                                    <?= ucfirst($req['status']); ?>
                                </span>
                            </div>

                            <div class="p-3 bg-light rounded-3 mb-3 small">
                                <div class="mb-1">
                                    <span class="text-muted">You offered to teach:</span>
                                    <strong class="text-primary"><?= sanitize($req['offered_skill']); ?></strong>
                                </div>
                                <div>
                                    <span class="text-muted">You requested to learn:</span>
                                    <strong class="text-success"><?= sanitize($req['requested_skill']); ?></strong>
                                </div>
                            </div>

                            <?php if ($req['message']): ?>
                                <p class="text-muted small mb-0 fst-italic">"<?= sanitize($req['message']); ?>"</p>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">
                                Sent: <?= date('M d, Y h:i A', strtotime($req['created_at'])); ?>
                            </small>
                        </div>

                        <div class="pt-3 border-top mt-3">
                            <?php if ($req['status'] === 'pending'): ?>
                                <div class="d-flex flex-column gap-2">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="swap_id" value="<?= $req['id']; ?>">
                                        <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill btn-sm" onclick="return confirm('Cancel this request?');">
                                            Cancel Proposal
                                        </button>
                                    </form>
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="simulate_accept">
                                        <input type="hidden" name="swap_id" value="<?= $req['id']; ?>">
                                        <input type="hidden" name="receiver_id" value="<?= $req['receiver_id']; ?>">
                                        <button type="submit" class="btn btn-outline-success w-100 rounded-pill btn-sm" title="Simulate peer accepting for viva demonstration">
                                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Demo: Simulate <?= sanitize($req['receiver_name']); ?> Accepting
                                        </button>
                                    </form>
                                </div>
                            <?php elseif ($req['status'] === 'accepted'): ?>
                                <a href="<?= app_path('student/swap_detail.php?id=' . $req['id']); ?>" class="btn btn-primary w-100 rounded-pill btn-sm">
                                    <i class="bi bi-chat-dots-fill me-1"></i> Open Swap Collaboration Room
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<!-- Tab 3: Active & Completed -->
<?php elseif ($activeTab === 'active'): ?>
    <?php if (empty($activeSwaps)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-arrow-repeat text-muted fs-1 mb-2"></i>
            <h5 class="fw-bold">No Active or Completed Swaps</h5>
            <p class="text-muted small">Once you or a peer accept a proposal, your collaboration session will show here!</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($activeSwaps as $as): 
                $isSender = ($as['sender_id'] == $userId);
                $partnerName = $isSender ? $as['receiver_name'] : $as['sender_name'];
                $partnerId   = $isSender ? $as['receiver_id'] : $as['sender_id'];
                $partnerContact = $isSender ? $as['receiver_contact'] : $as['sender_contact'];
            ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">
                                        <?= strtoupper(substr($partnerName, 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark"><?= sanitize($partnerName); ?></h5>
                                        <small class="text-muted"><i class="bi bi-person-badge me-1"></i> Partner Contact: <?= sanitize($partnerContact ?: 'In Swap Room'); ?></small>
                                    </div>
                                </div>
                                <span class="badge status-<?= $as['status']; ?> rounded-pill px-3 py-1">
                                    <?= ucfirst($as['status']); ?>
                                </span>
                            </div>

                            <div class="p-3 bg-light rounded-3 mb-3">
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Teaching:</span>
                                        <strong class="text-primary"><?= sanitize($isSender ? $as['offered_skill'] : $as['requested_skill']); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Learning:</span>
                                        <strong class="text-success"><?= sanitize($isSender ? $as['requested_skill'] : $as['offered_skill']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-3 d-flex flex-wrap gap-2">
                            <a href="<?= app_path('student/swap_detail.php?id=' . $as['id']); ?>" class="btn btn-primary rounded-pill btn-sm flex-fill">
                                <i class="bi bi-chat-dots-fill me-1"></i> Collaboration Room & Notes
                            </a>

                            <?php if ($as['status'] === 'accepted'): ?>
                                <form method="POST" action="" class="flex-fill">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="swap_id" value="<?= $as['id']; ?>">
                                    <button type="submit" class="btn btn-success rounded-pill btn-sm w-100" onclick="return confirm('Mark this skill exchange as successfully completed?');">
                                        <i class="bi bi-check2-all me-1"></i> Complete Swap
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-outline-warning text-dark rounded-pill btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $as['id']; ?>">
                                    <i class="bi bi-star-fill text-warning me-1"></i> Rate Partner
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Review Modal for completed swaps -->
                <div class="modal fade" id="reviewModal<?= $as['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow rounded-4">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Rate Your Exchange with <?= sanitize($partnerName); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="submit_review">
                                    <input type="hidden" name="swap_id" value="<?= $as['id']; ?>">
                                    <input type="hidden" name="reviewee_id" value="<?= $partnerId; ?>">

                                    <div class="mb-3 text-center">
                                        <label class="form-label small fw-semibold d-block">Rating (Stars)</label>
                                        <select name="rating" class="form-select form-select-lg text-center fw-bold">
                                            <option value="5">★★★★★ (5/5) - Outstanding!</option>
                                            <option value="4">★★★★☆ (4/5) - Very Good</option>
                                            <option value="3">★★★☆☆ (3/5) - Average</option>
                                            <option value="2">★★☆☆☆ (2/5) - Needs Improvement</option>
                                            <option value="1">★☆☆☆☆ (1/5) - Poor</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Feedback / Review Comment</label>
                                        <textarea name="comment" class="form-control" rows="3" placeholder="Share how helpful they were, clarity in explanation, etc..." required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4">Submit Review</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
