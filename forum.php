<?php
// forum.php — threads in a category
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$cat_id  = (int)($_GET['id'] ?? 0);
$cat     = db_row("SELECT * FROM forum_categories WHERE id=?", [$cat_id]);
if (!$cat) { flash('error','Category not found.'); redirect('forums.php'); }

$sort = $_GET['sort'] ?? 'latest';
$order_by = match($sort) {
    'likes' => 'COALESCE(fp.like_count, 0) DESC, fp.created_at DESC',
    'views' => 'fp.view_count DESC, fp.created_at DESC',
    'replies' => 'fp.reply_count DESC, fp.created_at DESC',
    default => 'fp.is_pinned DESC, fp.created_at DESC'
};

$page    = max(1,(int)($_GET['page']??1)); $per = 15; $offset = ($page-1)*$per;
$total   = db_count("SELECT COUNT(*) FROM forum_posts WHERE category_id=? AND deleted_at IS NULL", [$cat_id]);
$posts   = db_rows("SELECT fp.*,u.username,u.reputation FROM forum_posts fp JOIN users u ON fp.user_id=u.id WHERE fp.category_id=? AND fp.deleted_at IS NULL ORDER BY $order_by LIMIT $per OFFSET $offset", [$cat_id]);
$total_pages = ceil($total/$per);

// New post
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_post'])) {
    require_login(); csrf_check();
    $title = trim($_POST['title']??''); $body = trim($_POST['body']??'');
    if (strlen($title)<5) $error = 'Title too short.';
    elseif (strlen($body)<10) $error = 'Body too short.';
    else {
        $pid = db_insert("INSERT INTO forum_posts (category_id,user_id,title,body,is_pinned,is_locked,view_count,reply_count,created_at,updated_at) VALUES (?,?,?,?,0,0,0,0,NOW(),NOW())",
            [$cat_id, current_user_id(), $title, strip_tags($body,'<p><br><strong><em><ul><ol><li><code><pre>')]);
        flash('success','Post created!'); redirect("forum-post.php?id=$pid");
    }
}

$page_title = e($cat['name']);
require_once 'includes/header.php';
?>
<div class="page-wrap">
<div style="margin-bottom:18px;display:flex;align-items:center;gap:12px;justify-content:space-between;">
    <div>
      <nav style="font-size:0.8rem;color:var(--text-d);margin-bottom:6px;"><a href="forums.php" style="color:var(--text-d);">Forums</a> › <?= e($cat['name']) ?></nav>
      <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.3rem;font-weight:700;"><?= e($cat['name']) ?></h1>
    </div>
    <?php if(is_logged_in()): ?>
    <button onclick="document.getElementById('new-post-form').style.display=document.getElementById('new-post-form').style.display==='none'?'block':'none'" class="btn-gold">+ New Post</button>
    <?php endif; ?>
  </div>

  <!-- Sort Tabs -->
  <div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:8px;">
    <a href="?id=<?= $cat_id ?>&sort=latest" class="btn-ghost btn-sm <?= $sort==='latest'?'active':'' ?>" style="<?= $sort==='latest'?'background:var(--purple);color:#fff;':'' ?>">Latest</a>
    <a href="?id=<?= $cat_id ?>&sort=likes" class="btn-ghost btn-sm <?= $sort==='likes'?'active':'' ?>" style="<?= $sort==='likes'?'background:var(--purple);color:#fff;':'' ?>">❤️ Most Liked</a>
    <a href="?id=<?= $cat_id ?>&sort=views" class="btn-ghost btn-sm <?= $sort==='views'?'active':'' ?>" style="<?= $sort==='views'?'background:var(--purple);color:#fff;':'' ?>">👁 Most Viewed</a>
    <a href="?id=<?= $cat_id ?>&sort=replies" class="btn-ghost btn-sm <?= $sort==='replies'?'active':'' ?>" style="<?= $sort==='replies'?'background:var(--purple);color:#fff;':'' ?>">💬 Most Replies</a>
  </div>

  <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <!-- New Post Form (collapsed) -->
  <div id="new-post-form" style="display:none;" class="card" style="margin-bottom:18px;">
    <div class="card-body">
      <form method="POST">
        <?= csrf_field() ?><input type="hidden" name="new_post" value="1">
        <div class="form-group"><label>Post Title *</label><input type="text" name="title" placeholder="What's your topic?" required></div>
        <div class="form-group"><label>Content *</label><textarea name="body" rows="6" placeholder="Write your post..." required></textarea></div>
        <button type="submit" class="btn-gold">Post</button>
      </form>
    </div>
  </div>

  <!-- Post List -->
  <div style="display:flex;flex-direction:column;gap:10px;">
    <?php if($posts): foreach($posts as $p): ?>
    <div class="card" style="padding:14px 16px;display:flex;align-items:center;gap:14px;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
      <?php if($p['is_pinned']): ?><span title="Pinned" style="color:var(--gold);font-size:1rem;">📌</span><?php endif; ?>
      <?php if($p['is_locked']): ?><span title="Locked" style="color:var(--text-d);font-size:1rem;">🔒</span><?php endif; ?>
      <div style="flex:1;min-width:0;">
        <a href="forum-post.php?id=<?= $p['id'] ?>" style="font-family:'Rajdhani',sans-serif;font-weight:600;font-size:0.97rem;color:var(--text);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($p['title']) ?></a>
        <div class="q-meta" style="margin-top:4px;">
          <a href="profile.php?u=<?= urlencode($p['username']) ?>" class="q-user">@<?= e($p['username']) ?></a>
          <span class="dot">·</span><span><?= time_ago($p['created_at']) ?></span>
        </div>
      </div>
      <div style="text-align:center;min-width:50px;">
        <div style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;color:var(--gold);"><?= $p['like_count'] ?? 0 ?></div>
        <div style="font-size:0.7rem;color:var(--text-d);">likes</div>
      </div>
      <div style="text-align:center;min-width:50px;">
        <div style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;color:var(--purple-l);"><?= $p['reply_count'] ?></div>
        <div style="font-size:0.7rem;color:var(--text-d);">replies</div>
      </div>
      <div style="text-align:center;min-width:50px;">
        <div style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;color:var(--text-d);"><?= $p['view_count'] ?></div>
        <div style="font-size:0.7rem;color:var(--text-d);">views</div>
      </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state"><div class="empty-icon">📋</div><p>No posts yet. Start the conversation!</p></div>
    <?php endif; ?>
  </div>

  <?php if($total_pages>1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++): ?><a href="?id=<?=$cat_id?>&page=<?=$i?>" class="<?=$i===$page?'current':''?>"><?=$i?></a><?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
