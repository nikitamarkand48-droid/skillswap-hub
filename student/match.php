<?php
$pageTitle = 'Smart Skill Matching';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$db = get_db_connection();
$userId = current_user_id();

$myTeach = [];
$myLearn = [];
$matches = [];

if ($userId) {
    // 1. Fetch current user's skills
    $myTeach = $db->query("SELECT * FROM skills WHERE user_id = {$userId} AND type = 'teach'")->fetchAll();
    $myLearn = $db->query("SELECT * FROM skills WHERE user_id = {$userId} AND type = 'learn'")->fetchAll();

    // 2. Fetch other users' skills
    $otherUsersStmt = $db->prepare("SELECT id, name, department, year_of_study, bio FROM users WHERE id != ? AND status = 'active'");
    $otherUsersStmt->execute([$userId]);
    $otherUsers = $otherUsersStmt->fetchAll();

    foreach ($otherUsers as $peer) {
        $peerId = $peer['id'];
        $peerTeach = $db->query("SELECT * FROM skills WHERE user_id = {$peerId} AND type = 'teach'")->fetchAll();
        $peerLearn = $db->query("SELECT * FROM skills WHERE user_id = {$peerId} AND type = 'learn'")->fetchAll();

        // Calculate matching score
        $matchingPeerTeachesForMe = [];
        $matchingITeachForPeer = [];

        // Check: What peer teaches that matches what I want to learn
        foreach ($peerTeach as $pt) {
            foreach ($myLearn as $ml) {
                if (stripos($pt['skill_name'], $ml['skill_name']) !== false || 
                    stripos($ml['skill_name'], $pt['skill_name']) !== false ||
                    strcasecmp($pt['category'], $ml['category']) === 0) {
                    $matchingPeerTeachesForMe[] = [
                        'peer_skill' => $pt,
                        'my_goal' => $ml
                    ];
                }
            }
        }

        // Check: What I teach that matches what peer wants to learn
        foreach ($myTeach as $mt) {
            foreach ($peerLearn as $pl) {
                if (stripos($mt['skill_name'], $pl['skill_name']) !== false || 
                    stripos($pl['skill_name'], $mt['skill_name']) !== false ||
                    strcasecmp($mt['category'], $pl['category']) === 0) {
                    $matchingITeachForPeer[] = [
                        'my_skill' => $mt,
                        'peer_goal' => $pl
                    ];
                }
            }
        }

        // Scoring algorithm:
        // Mutual Match: Peer teaches what I want AND wants what I teach -> 90-100%
        // Inbound Match: Peer teaches what I want -> 70%
        // Outbound Match: Peer wants what I teach -> 50%
        if (!empty($matchingPeerTeachesForMe) && !empty($matchingITeachForPeer)) {
            $score = 100;
            $matchType = 'Mutual Perfect Match';
            $matches[] = [
                'peer' => $peer,
                'score' => $score,
                'type' => $matchType,
                'teaches_you' => $matchingPeerTeachesForMe[0]['peer_skill'],
                'learns_from_you' => $matchingITeachForPeer[0]['my_skill']
            ];
        } elseif (!empty($matchingPeerTeachesForMe)) {
            $score = 75;
            $matchType = 'Mentor Match';
            $matches[] = [
                'peer' => $peer,
                'score' => $score,
                'type' => $matchType,
                'teaches_you' => $matchingPeerTeachesForMe[0]['peer_skill'],
                'learns_from_you' => (!empty($myTeach) ? $myTeach[0] : null)
            ];
        } elseif (!empty($matchingITeachForPeer)) {
            $score = 55;
            $matchType = 'Peer Learner Match';
            $matches[] = [
                'peer' => $peer,
                'score' => $score,
                'type' => $matchType,
                'teaches_you' => (!empty($peerTeach) ? $peerTeach[0] : null),
                'learns_from_you' => $matchingITeachForPeer[0]['my_skill']
            ];
        }
    }

    // Sort matches by highest score first
    usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-stars text-warning me-2"></i>Smart Skill Matching Engine
            </h3>
            <p class="text-muted small mb-0">Our reciprocal matching algorithm pairs your teaching skills with students seeking to learn them, and vice versa!</p>
        </div>
        <?php if ($userId): ?>
            <a href="<?= app_path('student/my_skills.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-gear me-1"></i> Update My Skills
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$userId): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <div class="avatar-circle avatar-circle-lg mx-auto mb-3 bg-warning text-dark">
            <i class="bi bi-stars"></i>
        </div>
        <h4 class="fw-bold">Sign In to View Your Tailored Matches</h4>
        <p class="text-muted small col-md-6 mx-auto mb-4">
            Once you log in and list your teaching and learning skills, our AI & matching algorithm will automatically detect reciprocal peer matches for you!
        </p>
        <div class="d-flex justify-content-center gap-2">
            <a href="<?= app_path('auth/login.php'); ?>" class="btn btn-primary rounded-pill px-4 shadow-sm">
                Login with Account
            </a>
            <a href="<?= app_path('auth/login.php?demo=aarav'); ?>" class="btn btn-outline-primary rounded-pill px-4">
                <i class="bi bi-lightning-fill text-warning me-1"></i> Try Aarav Demo Match
            </a>
        </div>
    </div>
<?php elseif (empty($myTeach) && empty($myLearn)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <i class="bi bi-collection text-primary fs-1 mb-2"></i>
        <h5 class="fw-bold">Your Skill Portfolio is Empty</h5>
        <p class="text-muted small col-md-6 mx-auto mb-4">
            To generate matches, please add at least one skill you can teach and one skill you want to learn.
        </p>
        <div>
            <a href="<?= app_path('student/my_skills.php'); ?>" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Add Skills Now
            </a>
        </div>
    </div>
<?php elseif (empty($matches)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <i class="bi bi-search text-muted fs-1 mb-2"></i>
        <h5 class="fw-bold">No High-Confidence Matches Found Yet</h5>
        <p class="text-muted small col-md-6 mx-auto mb-4">
            Try adding more skills or broader categories to your teaching/learning portfolio, or explore the Search tab to browse all peers.
        </p>
        <div class="d-flex justify-content-center gap-2">
            <a href="<?= app_path('student/my_skills.php'); ?>" class="btn btn-primary rounded-pill px-3">Add More Skills</a>
            <a href="<?= app_path('student/search.php'); ?>" class="btn btn-outline-secondary rounded-pill px-3">Browse All Skills</a>
        </div>
    </div>
<?php else: ?>
    <!-- Algorithm Explanation Banner -->
    <div class="alert alert-info border-0 rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center gap-3">
        <div class="fs-3 text-primary"><i class="bi bi-lightbulb-fill"></i></div>
        <div class="small">
            <strong>Reciprocal Matching Active:</strong> We found <strong><?= count($matches); ?></strong> peer match<?= count($matches) > 1 ? 'es' : ''; ?>. Mutual matches (100%) represent double coincidence of learning needs!
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($matches as $m): ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 hover-shadow bg-white d-flex flex-column justify-content-between">
                    <div>
                        <!-- Header with Score -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle">
                                    <?= strtoupper(substr($m['peer']['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark"><?= sanitize($m['peer']['name']); ?></h5>
                                    <small class="text-muted">
                                        <?= sanitize($m['peer']['department']); ?> &bull; <?= sanitize($m['peer']['year_of_study']); ?>
                                    </small>
                                </div>
                            </div>
                            <span class="badge <?= ($m['score'] >= 90) ? 'bg-success' : 'bg-primary'; ?> rounded-pill px-3 py-2 fs-6">
                                <i class="bi bi-check-circle-fill me-1"></i> <?= $m['score']; ?>% Match
                            </span>
                        </div>

                        <!-- Match Details Box -->
                        <div class="compatibility-box mb-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-sm-6">
                                    <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 0.7rem;">They Teach You</small>
                                    <span class="badge bg-primary text-white fw-bold">
                                        <?= sanitize($m['teaches_you']['skill_name'] ?? 'Available on request'); ?>
                                    </span>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 0.7rem;">You Teach Them</small>
                                    <span class="badge bg-warning text-dark fw-bold">
                                        <?= sanitize($m['learns_from_you']['skill_name'] ?? 'Available on request'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($m['peer']['bio']): ?>
                            <p class="text-muted small mb-0">
                                <em>"<?= sanitize($m['peer']['bio']); ?>"</em>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Direct Propose Action -->
                    <div class="pt-3 border-top mt-3">
                        <?php if (!empty($m['teaches_you']) && !empty($m['learns_from_you'])): ?>
                            <form method="POST" action="<?= app_path('student/swap_requests.php'); ?>">
                                <input type="hidden" name="action" value="send_swap_request">
                                <input type="hidden" name="receiver_id" value="<?= $m['peer']['id']; ?>">
                                <input type="hidden" name="requested_skill_id" value="<?= $m['teaches_you']['id']; ?>">
                                <input type="hidden" name="offered_skill_id" value="<?= $m['learns_from_you']['id']; ?>">
                                <input type="hidden" name="message" value="Hi <?= addslashes(sanitize($m['peer']['name'])); ?>! The Smart Match system paired us. I'd love to swap my <?= addslashes(sanitize($m['learns_from_you']['skill_name'])); ?> for your <?= addslashes(sanitize($m['teaches_you']['skill_name'])); ?>!">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Quick Propose Mutual Swap
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= app_path('student/search.php?q=' . urlencode($m['peer']['name'])); ?>" class="btn btn-outline-primary w-100 rounded-pill py-2">
                                View Profile & Skills
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
