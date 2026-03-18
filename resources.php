<?php
// resources.php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page   = max(1,(int)($_GET['page']??1));
$per    = 12; $offset = ($page-1)*$per;
$search = trim($_GET['q']??'');
$sort = $_GET['sort'] ?? 'latest';
$order_by = match($sort) {
    'likes' => 'COALESCE(r.like_count, 0) DESC, r.created_at DESC',
    'downloads' => 'r.download_count DESC, r.created_at DESC',
    default => 'r.created_at DESC'
};
$where  = ["r.deleted_at IS NULL"]; $params = [];
if ($search) { $where[] = "(r.title LIKE ? OR r.description LIKE ?)"; $params[]= "%$search%"; $params[]= "%$search%"; }
$w = implode(' AND ',$where);
$total     = db_count("SELECT COUNT(*) FROM resources r WHERE $w", $params);
$resources = db_rows("SELECT r.*,u.username FROM resources r JOIN users u ON r.user_id=u.id WHERE $w ORDER BY $order_by LIMIT $per OFFSET $offset", $params);
$total_pages = ceil($total/$per);

$page_title = 'Resources';
require_once 'includes/header.php';
?>
<div class="page-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div>
      <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;">📁 Resources</h1>
      <p style="font-size:0.82rem;color:var(--text-d);"><?= number_format($total) ?> shared learning materials</p>
    </div>
    <?php if(is_logged_in()): ?>
    <a href="upload-resource.php" class="btn-gold"><i class="bi bi-upload"></i> Upload</a>
    <?php endif; ?>
  </div>

  <form method="GET" style="margin-bottom:12px;display:flex;gap:10px;">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search resources..." style="flex:1;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:8px 12px;font-size:0.875rem;font-family:'Nunito',sans-serif;outline:none;">
    <button type="submit" class="btn-purple">Search</button>
  </form>

  <!-- Sort Tabs -->
  <div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:8px;">
    <a href="?sort=latest<?= $search ? '&q=' . urlencode($search) : '' ?>" class="btn-ghost btn-sm <?= $sort==='latest'?'active':'' ?>" style="<?= $sort==='latest'?'background:var(--purple);color:#fff;':'' ?>">Latest</a>
    <a href="?sort=likes<?= $search ? '&q=' . urlencode($search) : '' ?>" class="btn-ghost btn-sm <?= $sort==='likes'?'active':'' ?>" style="<?= $sort==='likes'?'background:var(--purple);color:#fff;':'' ?>">❤️ Most Liked</a>
    <a href="?sort=downloads<?= $search ? '&q=' . urlencode($search) : '' ?>" class="btn-ghost btn-sm <?= $sort==='downloads'?'active':'' ?>" style="<?= $sort==='downloads'?'background:var(--purple);color:#fff;':'' ?>">⬇ Most Downloaded</a>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
    <?php if($resources): foreach($resources as $r):
      $icon = match(strtolower($r['file_type'])) { 'pdf'=>'📄','py'=>'🐍','java'=>'☕','php'=>'🐘','js','ts'=>'⚡','sql'=>'🗄️','zip'=>'🗜️', default=>'📎' };
      $size = $r['file_size'] >= 1048576 ? round($r['file_size']/1048576,1).'MB' : round($r['file_size']/1024,1).'KB';
    ?>
    <div class="card" style="padding:16px;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
        <span style="font-size:1.5rem;"><?= $icon ?></span>
        <div>
          <span style="font-size:0.72rem;background:rgba(124,58,237,0.15);color:var(--purple-l);border-radius:4px;padding:1px 7px;font-weight:700;"><?= strtoupper(e($r['file_type'])) ?></span>
          <?php if($r['is_instructor_verified']): ?><span style="font-size:0.72rem;background:rgba(16,185,129,0.15);color:var(--green);border-radius:4px;padding:1px 7px;margin-left:4px;">🎓 Verified</span><?php endif; ?>
        </div>
      </div>
      <h3 style="font-family:'Rajdhani',sans-serif;font-size:0.975rem;font-weight:700;margin-bottom:6px;line-height:1.3;color:var(--text);"><?= e($r['title']) ?></h3>
      <?php if($r['description']): ?>
      <p style="font-size:0.82rem;color:var(--text-d);margin-bottom:10px;line-height:1.4;"><?= e(mb_substr($r['description'],0,80)).(strlen($r['description'])>80?'...':'') ?></p>
      <?php endif; ?>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;">
        <div style="font-size:0.75rem;color:var(--text-d);">
          <?= $size ?> · <?= $r['download_count'] ?> downloads · <span id="like-count-<?= $r['id'] ?>"><?= $r['like_count'] ?? 0 ?></span> likes
        </div>
        <div style="display:flex;gap:8px;">
          <?php if(is_logged_in()): ?>
          <?php 
          $resource_liked = is_liked(current_user_id(), $r['id'], 'resource');
          ?>
          <form method="POST" action="api/like.php" class="like-form" data-type="resource" data-id="<?= $r['id'] ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="resource">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn-<?= $resource_liked ? 'gold' : 'ghost' ?> btn-sm" id="like-btn-<?= $r['id'] ?>">
              <i class="bi bi-heart<?= $resource_liked ? '-fill' : '' ?>"></i>
            </button>
          </form>
          <a href="download-resource.php?id=<?= $r['id'] ?>" class="btn-gold btn-sm">⬇ Download</a>
          <?php else: ?>
          <a href="login.php" class="btn-ghost btn-sm">Login</a>
          <?php endif; ?>
        </div>
      </div>
      <div style="font-size:0.75rem;color:var(--text-d);margin-top:8px;">
        by <a href="profile.php?u=<?= urlencode($r['username']) ?>" style="color:var(--purple-l);">@<?= e($r['username']) ?></a>
        · <?= time_ago($r['created_at']) ?>
      </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">📁</div><p>No resources yet.</p></div>
    <?php endif; ?>
  </div>

  <?php if($total_pages>1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++): ?>
    <a href="?page=<?=$i?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $sort !== 'latest' ? '&sort=' . $sort : '' ?>" class="<?=$i===$page?'current':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
