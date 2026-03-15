<?php
// suggestions.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    if (isset($_POST['action']) && $_POST['action'] === 'suggest') {
        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['body'] ?? '');
        
        if ($title && $body) {
            db_insert("INSERT INTO suggestions (user_id, title, body) VALUES (?, ?, ?)", [current_user_id(), $title, $body]);
            $_SESSION['success'] = "Thank you! Your suggestion has been submitted.";
            header("Location: suggestions.php");
            exit;
        }
    }
}

// Handle voting (simplified for this page)
if (isset($_GET['vote']) && is_logged_in()) {
    $sid = (int)$_GET['vote'];
    try {
        db_insert("INSERT INTO suggestion_votes (user_id, suggestion_id) VALUES (?, ?)", [current_user_id(), $sid]);
    } catch (Exception $e) { /* Already voted */ }
    header("Location: suggestions.php");
    exit;
}

$suggestions = db_rows("
    SELECT s.*, u.username, 
           (SELECT COUNT(*) FROM suggestion_votes sv WHERE sv.suggestion_id = s.id) as vote_count,
           EXISTS(SELECT 1 FROM suggestion_votes sv WHERE sv.suggestion_id = s.id AND sv.user_id = ?) as has_voted
    FROM suggestions s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY vote_count DESC, s.created_at DESC
", [current_user_id() ?? 0]);

$page_title = 'Upgrades & Suggestions';
require_once 'includes/header.php';
?>

<div class="page-wrap" style="max-width:900px;">
    <div style="text-align:center; margin-bottom:40px;">
        <h1 style="font-size:2.2rem; margin-bottom:8px;"><i class="bi bi-lightbulb" style="color:var(--gold);"></i> Upgrade Suggestions</h1>
        <p style="color:var(--text-m);">Help us shape the future of PANTHERVERSE. Suggest new features or improvements!</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="margin-bottom:24px;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="main-grid" style="grid-template-columns: 1fr 320px; gap:24px;">
        <div class="main-col">
            <?php if (!$suggestions): ?>
                <div class="empty-state" style="padding:60px 20px;">
                    <div class="empty-icon"><i class="bi bi-inbox" style="font-size:3rem; opacity:0.3;"></i></div>
                    <p>No suggestions yet. Be the first to suggest an upgrade!</p>
                </div>
            <?php else: ?>
                <?php foreach($suggestions as $s): ?>
                <div class="card" style="margin-bottom:16px; padding:20px; display:flex; gap:20px; align-items:flex-start;">
                    <div style="text-align:center;">
                        <a href="suggestions.php?vote=<?= $s['id'] ?>" class="btn-ghost" style="flex-direction:column; padding:8px 12px; gap:4px; border:1px solid <?= $s['has_voted']?'var(--gold)':'var(--border)' ?>; <?= $s['has_voted']?'color:var(--gold); background:rgba(244,166,35,0.05);':'' ?>" <?= !is_logged_in()?'disabled':'' ?>>
                            <i class="bi bi-caret-up-fill" style="font-size:1.2rem;"></i>
                            <span style="font-weight:700; font-family:'Rajdhani',sans-serif;"><?= $s['vote_count'] ?></span>
                        </a>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <h3 style="margin:0; font-size:1.1rem; color: #fff;"><?= e($s['title']) ?></h3>
                            <span class="badge-pill" style="font-size:0.7rem; padding:2px 8px; background:<?= match($s['status']){'planned'=>'rgba(124,58,237,0.15)','implemented'=>'rgba(16,185,129,0.15)','rejected'=>'rgba(239,68,68,0.15)',default=>'rgba(244,166,35,0.1)'} ?>; color:<?= match($s['status']){'planned'=>'var(--purple-l)','implemented'=>'var(--green)','rejected'=>'var(--red)',default=>'var(--gold)'} ?>;">
                                <?= strtoupper($s['status']) ?>
                            </span>
                        </div>
                        <p style="font-size:0.9rem; color:var(--text-m); line-height:1.5; margin-bottom:12px;"><?= nl2br(e($s['body'])) ?></p>
                        <div style="font-size:0.75rem; color:var(--text-d);">
                            Suggested by <a href="profile.php?u=<?= urlencode($s['username']) ?>" style="color:var(--purple-l);">@<?= e($s['username']) ?></a> &bull; <?= time_ago($s['created_at']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="side-col">
            <div class="card" style="position:sticky; top:80px; padding:20px;">
                <h2 style="font-size:1.1rem; margin-bottom:16px;"><i class="bi bi-plus-circle"></i> New Suggestion</h2>
                <?php if (!is_logged_in()): ?>
                    <p style="font-size:0.85rem; color:var(--text-d); text-align:center;">Please <a href="login.php">login</a> to submit a suggestion.</p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="suggest">
                        <div style="margin-bottom:12px;">
                            <label style="display:block; font-size:0.8rem; margin-bottom:4px; color:var(--text-m);">Quick Title</label>
                            <input type="text" name="title" required placeholder="Short & catchy..." style="width:100%; background:var(--bg); border:1px solid var(--border); border-radius:6px; color:#fff; padding:8px; outline:none;">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:0.8rem; margin-bottom:4px; color:var(--text-m);">Details</label>
                            <textarea name="body" required rows="4" placeholder="Explain your idea..." style="width:100%; background:var(--bg); border:1px solid var(--border); border-radius:6px; color:#fff; padding:8px; outline:none; resize:none;"></textarea>
                        </div>
                        <button type="submit" class="btn-gold" style="width:100%;"><i class="bi bi-send"></i> Submit Idea</button>
                    </form>
                <?php endif; ?>
                
                <hr style="opacity:0.3;">
                <div style="font-size:0.8rem; color:var(--text-d);">
                    <p style="margin-bottom:8px;">💡 <strong>Pro Tip:</strong> Check if someone else already suggested the same thing before posting!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
