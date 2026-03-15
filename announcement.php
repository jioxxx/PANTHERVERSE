<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$ann = null;
if ($id > 0) {
    $ann = db_row("SELECT a.*, u.username FROM announcements a JOIN users u ON a.user_id = u.id WHERE a.id = ?", [$id]);
}

$page_title = $ann ? e($ann['title']) : 'Announcement Not Found';
require_once 'includes/header.php';
?>

<div class="page-wrap">
    <?php if($ann): ?>
    <div class="card">
        <div class="card-head">
            <h1 class="card-title"><?= e($ann['title']) ?></h1>
            <span class="badge-pill badge-<?= $ann['priority'] === 'urgent' ? 'red' : ($ann['priority'] === 'important' ? 'gold' : 'purple') ?>">
                <?= ucfirst($ann['priority']) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="prose">
                <?= $ann['body'] ?>
            </div>
            <hr>
            <!-- Like Button -->
            <?php 
            $announcement_liked = false;
            if (is_logged_in()) {
                $announcement_liked = is_liked(current_user_id(), $ann['id'], 'announcement');
            }
            ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
              <?php if(is_logged_in()): ?>
              <form method="POST" action="api/like.php" class="like-form" data-type="announcement" data-id="<?= $ann['id'] ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="announcement">
                <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                <button type="submit" class="btn-<?= $announcement_liked ? 'gold' : 'ghost' ?> btn-sm" id="like-btn-<?= $ann['id'] ?>">
                  <i class="bi bi-heart<?= $announcement_liked ? '-fill' : '' ?>"></i> 
                  <?= $announcement_liked ? 'Liked' : 'Like' ?>
                </button>
              </form>
              <?php else: ?>
              <a href="login.php" class="btn-ghost btn-sm"><i class="bi bi-heart"></i> Like</a>
              <?php endif; ?>
              <span style="font-size:0.85rem;color:var(--text-d);" id="like-count-<?= $ann['id'] ?>">
                <?= $ann['like_count'] ?? 0 ?> likes
              </span>
            </div>
            <div style="display:flex;justify-content:space-between;color:var(--text-d);font-size:0.85rem;">
                <span>Posted by <?= e($ann['username']) ?></span>
                <span><?= date('M j, Y g:i A', strtotime($ann['created_at'])) ?></span>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-error">Announcement not found.</div>
    <?php endif; ?>
    
    <div style="margin-top:20px;">
        <a href="announcements.php" class="btn-ghost">← Back to Announcements</a>
        <a href="index.php" class="btn-ghost">← Back to Home</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

