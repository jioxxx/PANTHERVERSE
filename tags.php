<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = "Tags";
require_once 'includes/header.php';

$tags = db_rows("SELECT t.*, COUNT(qt.question_id) as question_count 
    FROM tags t 
    LEFT JOIN question_tag qt ON t.id = qt.tag_id 
    GROUP BY t.id 
    ORDER BY t.usage_count DESC");
?>

<div class="page-wrap">
    <div class="section-head">
        <h2>🏷️ All Tags</h2>
    </div>
    
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        <?php foreach($tags as $tag): ?>
        <a href="questions.php?tag=<?= urlencode($tag['name']) ?>" class="tag" style="padding:8px 14px;font-size:0.9rem;">
            <?= e($tag['name']) ?>
            <span style="opacity:0.7;font-size:0.8em;">× <?= $tag['question_count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    
    <?php if(empty($tags)): ?>
    <div class="empty-state">
        <div class="empty-icon">🏷️</div>
        <p>No tags found.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

