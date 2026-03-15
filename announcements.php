<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = "Announcements";
require_once 'includes/header.php';

// Sorting
$sort = $_GET['sort'] ?? 'latest';
$order_by = match($sort) {
    'likes' => 'COALESCE(a.like_count, 0) DESC, a.created_at DESC',
    default => 'FIELD(a.priority,\'urgent\',\'important\',\'normal\'), a.created_at DESC'
};

$announcements = db_rows("
    SELECT a.*, u.username 
    FROM announcements a 
    JOIN users u ON a.user_id = u.id 
    WHERE (a.expires_at IS NULL OR a.expires_at > NOW()) 
    AND a.deleted_at IS NULL 
    ORDER BY $order_by
");
?>

<div class="page-wrap">
    <div class="section-head" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h2>📢 Announcements</h2>
    </div>
    
    <!-- Sort Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:8px;">
        <a href="?sort=latest" class="btn-ghost btn-sm <?= $sort==='latest'?'active':'' ?>" style="<?= $sort==='latest'?'background:var(--purple);color:#fff;':'' ?>">Latest</a>
        <a href="?sort=likes" class="btn-ghost btn-sm <?= $sort==='likes'?'active':'' ?>" style="<?= $sort==='likes'?'background:var(--purple);color:#fff;':'' ?>">❤️ Most Liked</a>
    </div>
    
    <?php if(!empty($announcements)): ?>
        <?php foreach($announcements as $ann): ?>
        <div class="card" style="margin-bottom:16px;">
            <div class="announce-bar priority-<?= e($ann['priority']) ?>">
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                        <strong><?= e($ann['title']) ?></strong>
                        <span class="badge-pill badge-<?= $ann['priority'] === 'urgent' ? 'red' : ($ann['priority'] === 'important' ? 'gold' : 'purple') ?>">
                            <?= ucfirst($ann['priority']) ?>
                        </span>
                    </div>
                    <span class="announce-meta">by <?= e($ann['username']) ?> · <?= time_ago($ann['created_at']) ?> · <?= $ann['like_count'] ?? 0 ?> likes</span>
                </div>
                <a href="announcement.php?id=<?= $ann['id'] ?>" class="announce-link">Read →</a>
            </div>
            <div class="card-body">
                <?= mb_strimwidth(strip_tags($ann['body']), 0, 200, '...') ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📢</div>
        <p>No announcements yet.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

