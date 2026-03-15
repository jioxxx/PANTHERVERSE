<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$id   = (int)($_GET['id'] ?? 0);
$post = db_row("SELECT fp.*,u.username,u.id as author_id,fc.name as cat_name,fc.id as cat_id FROM forum_posts fp JOIN users u ON fp.user_id=u.id JOIN forum_categories fc ON fp.category_id=fc.id WHERE fp.id=? AND fp.deleted_at IS NULL", [$id]);
if (!$post) { flash('error','Post not found.'); redirect('forums.php'); }
db_exec("UPDATE forum_posts SET view_count=view_count+1 WHERE id=?", [$id]);

// Check if user liked this post
$user_liked = false;
$user_bookmarked = false;
if (is_logged_in()) {
    $user_liked = db_count("SELECT 1 FROM likes WHERE user_id = ? AND liked_type = 'forum_post' AND liked_id = ?", [current_user_id(), $id]) > 0;
    $user_bookmarked = is_bookmarked(current_user_id(), $id, 'forum_post');
}

$replies = db_rows("SELECT c.*,u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.commentable_id=? AND c.commentable_type='App\\\\Models\\\\ForumPost' AND c.deleted_at IS NULL ORDER BY c.created_at", [$id]);

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && !$post['is_locked']) {
    require_login(); csrf_check();
    $body = trim($_POST['reply_body']??'');
    if (strlen($body)<3) $error = 'Reply too short.';
    else {
        db_insert("INSERT INTO comments (user_id,commentable_id,commentable_type,body,created_at,updated_at) VALUES (?,?,'App\\\\Models\\\\ForumPost',?,NOW(),NOW())",
            [current_user_id(),$id,strip_tags($body)]);
        db_exec("UPDATE forum_posts SET reply_count=reply_count+1 WHERE id=?", [$id]);
        redirect("forum-post.php?id=$id#replies");
    }
}

$page_title = e($post['title']);
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:800px;">
  <nav style="font-size:0.8rem;color:var(--text-d);margin-bottom:12px;">
    <a href="forums.php" style="color:var(--text-d);">Forums</a> ›
    <a href="forum.php?id=<?= $post['cat_id'] ?>" style="color:var(--text-d);"><?= e($post['cat_name']) ?></a> › Thread
  </nav>

  <div class="card" style="margin-bottom:16px;">
    <div class="card-head">
      <div>
        <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.2rem;font-weight:700;margin:0;">
          <?= $post['is_pinned']?'<i class="bi bi-pin-angle-fill" style="color:var(--gold);"></i> ':'' ?><?= $post['is_locked']?'<i class="bi bi-lock-fill" style="color:var(--text-d);"></i> ':'' ?><?= e($post['title']) ?>
        </h1>
        <div style="font-size:0.78rem;color:var(--text-d);margin-top:4px;">
          <a href="profile.php?u=<?= urlencode($post['username']) ?>" style="color:var(--purple-l);">@<?= e($post['username']) ?></a>
          · <?= time_ago($post['created_at']) ?> · <?= $post['view_count'] ?> views · <?= $post['reply_count'] ?> replies
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="prose"><?= nl2br(e($post['body'])) ?></div>
      <!-- Like + Bookmark -->
      <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <?php if(is_logged_in()): ?>
        <form method="POST" action="api/like.php" class="like-form" data-type="forum_post" data-id="<?= $id ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="forum_post">
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn-<?= $user_liked ? 'gold' : 'ghost' ?> btn-sm" id="like-btn-<?= $id ?>">
            <i class="bi bi-heart<?= $user_liked ? '-fill' : '' ?>"></i> 
            <?= $user_liked ? 'Liked' : 'Like' ?>
          </button>
        </form>
        <span style="font-size:0.85rem;color:var(--text-d);" id="like-count-<?= $id ?>">
          <?= $post['like_count'] ?? 0 ?> likes
        </span>
        <form method="POST" action="api/bookmark.php" class="bookmark-form" data-type="forum_post" data-id="<?= $id ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="forum_post">
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn-ghost btn-sm bookmark-btn<?= $user_bookmarked ? ' bookmarked' : '' ?>" title="<?= $user_bookmarked ? 'Remove bookmark' : 'Bookmark this post' ?>" style="<?= $user_bookmarked ? 'color:var(--gold);border-color:var(--gold);' : '' ?>">
            <i class="bi bi-bookmark<?= $user_bookmarked ? '-fill' : '' ?>"></i> <span class="bm-label"><?= $user_bookmarked ? 'Saved' : 'Save' ?></span>
          </button>
        </form>
        <?php else: ?>
        <a href="login.php" class="btn-ghost btn-sm"><i class="bi bi-heart"></i> Like</a>
        <a href="login.php" class="btn-ghost btn-sm"><i class="bi bi-bookmark"></i> Bookmark</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Replies -->
  <h2 id="replies" style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:12px;"><i class="bi bi-chat-dots"></i> <?= count($replies) ?> Replies</h2>
  <?php foreach($replies as $r): ?>
  <div class="card" style="padding:14px 16px;margin-bottom:8px;display:flex;gap:12px;">
    <img src="<?= avatar_url($r['username']) ?>" style="width:34px;height:34px;border-radius:50%;flex-shrink:0;" alt="">
    <div style="flex:1;">
      <div style="margin-bottom:6px;">
        <a href="profile.php?u=<?= urlencode($r['username']) ?>" style="font-weight:700;font-size:0.83rem;color:var(--purple-l);">@<?= e($r['username']) ?></a>
        <span style="font-size:0.75rem;color:var(--text-d);margin-left:8px;"><?= time_ago($r['created_at']) ?></span>
      </div>
      <div style="font-size:0.9rem;color:var(--text);line-height:1.6;"><?= nl2br(e($r['body'])) ?></div>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Reply Box -->
  <?php if(!$post['is_locked'] && is_logged_in()): ?>
  <div class="card" style="margin-top:18px;">
    <div class="card-head"><span class="card-title">✏️ Post a Reply</span></div>
    <div class="card-body">
      <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
          <textarea name="reply_body" rows="4" placeholder="Write your reply..." required></textarea>
        </div>
        <button type="submit" class="btn-gold">Post Reply</button>
      </form>
    </div>
  </div>
  <?php elseif($post['is_locked']): ?>
  <div class="alert alert-warn" style="margin-top:16px;"><i class="bi bi-lock"></i> This thread is locked and no longer accepts replies.</div>
  <?php elseif(!is_logged_in()): ?>
  <div class="card" style="margin-top:16px;padding:20px;text-align:center;">
    <p style="color:var(--text-d);margin-bottom:12px;">Login to reply.</p>
    <a href="login.php" class="btn-gold">Login</a>
  </div>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
