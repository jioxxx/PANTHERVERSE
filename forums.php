<?php
// forums.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$categories = db_rows("SELECT fc.*, COUNT(fp.id) as post_count FROM forum_categories fc LEFT JOIN forum_posts fp ON fc.id=fp.category_id AND fp.deleted_at IS NULL GROUP BY fc.id ORDER BY fc.display_order");
$page_title = 'Forums';
require_once 'includes/header.php';
?>
<div class="page-wrap">
  <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:20px;">📋 Community Forums</h1>
  <div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach($categories as $cat): ?>
    <a href="forum.php?id=<?= $cat['id'] ?>" class="card" style="padding:18px;display:flex;align-items:center;gap:16px;text-decoration:none;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
      <div style="width:50px;height:50px;background:rgba(124,58,237,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
        <i class="bi <?= e($cat['icon']) ?>"></i>
      </div>
      <div style="flex:1;">
        <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1rem;color:var(--text);"><?= e($cat['name']) ?></div>
        <div style="font-size:0.82rem;color:var(--text-d);margin-top:2px;"><?= e($cat['description']) ?></div>
      </div>
      <div style="text-align:center;min-width:60px;">
        <div style="font-family:'Rajdhani',sans-serif;font-size:1.3rem;font-weight:700;color:var(--gold);"><?= $cat['post_count'] ?></div>
        <div style="font-size:0.72rem;color:var(--text-d);">threads</div>
      </div>
      <i class="bi bi-chevron-right" style="color:var(--text-d);"></i>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
