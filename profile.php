<?php
// profile.php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$username = trim($_GET['u'] ?? '');
if (!$username) redirect('index.php');
$user = db_row("SELECT * FROM users WHERE username=? AND is_active=1", [$username]);
if (!$user) die('<div style="padding:40px;text-align:center;color:#f4a623;background:#0e0720;font-family:monospace;">User not found.</div>');

$questions = db_rows("
    SELECT q.id, q.title, q.vote_count, q.view_count, q.is_solved, q.created_at,
           (SELECT COUNT(*) FROM answers a WHERE a.question_id=q.id) as answer_count,
           GROUP_CONCAT(t.name SEPARATOR ',') as tag_names
    FROM questions q
    LEFT JOIN question_tag qt ON q.id=qt.question_id
    LEFT JOIN tags t ON qt.tag_id=t.id
    WHERE q.user_id=? AND q.deleted_at IS NULL
    GROUP BY q.id ORDER BY q.created_at DESC LIMIT 10
", [$user['id']]);

// Get liked content by this user
$liked_content = [];
try {
    $liked_content = db_rows("
        SELECT l.id, l.liked_type, l.liked_id, l.created_at as liked_at,
               CASE 
                   WHEN l.liked_type = 'question' THEN (SELECT title FROM questions WHERE id = l.liked_id)
                   WHEN l.liked_type = 'forum_post' THEN (SELECT title FROM forum_posts WHERE id = l.liked_id)
                   WHEN l.liked_type = 'resource' THEN (SELECT title FROM resources WHERE id = l.liked_id)
                   WHEN l.liked_type = 'announcement' THEN (SELECT title FROM announcements WHERE id = l.liked_id)
               END as content_title
        FROM likes l
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
        LIMIT 10
    ", [$user['id']]);
} catch (Exception $e) {
    // Table doesn't exist yet - ignore
}

$badges = db_rows("SELECT b.* FROM badges b JOIN user_badges ub ON b.id=ub.badge_id WHERE ub.user_id=?", [$user['id']]);
$stats  = [
    'questions' => db_count("SELECT COUNT(*) FROM questions WHERE user_id=? AND deleted_at IS NULL", [$user['id']]),
    'answers'   => db_count("SELECT COUNT(*) FROM answers WHERE user_id=? AND deleted_at IS NULL", [$user['id']]),
    'accepted'  => db_count("SELECT COUNT(*) FROM answers WHERE user_id=? AND is_accepted=1 AND deleted_at IS NULL", [$user['id']]),
    'resources' => db_count("SELECT COUNT(*) FROM resources WHERE user_id=? AND deleted_at IS NULL", [$user['id']]),
];

// Follow stats
$follower_count = db_count("SELECT COUNT(*) FROM user_follows WHERE following_id=?", [$user['id']]);
$following_count = db_count("SELECT COUNT(*) FROM user_follows WHERE follower_id=?", [$user['id']]);

// Check if current user is following
$is_following = false;
if (is_logged_in() && current_user_id() != $user['id']) {
    $is_following = db_count("SELECT 1 FROM user_follows WHERE follower_id=? AND following_id=?", [current_user_id(), $user['id']]) > 0;
}

$page_title = '@'.$user['username'];
require_once 'includes/header.php';
?>
<div class="page-wrap">
  <!-- Profile Header -->
  <div class="card" style="margin-bottom:20px;">
    <?php if(!empty($user['cover_photo'])): ?>
    <div style="height:180px;background:url('/assets/uploads/covers/<?= e($user['cover_photo']) ?>') center/cover;border-radius:10px 10px 0 0;"></div>
    <?php else: ?>
    <div style="height:120px;background:linear-gradient(135deg,#1a0938,#3d1680);border-radius:10px 10px 0 0;"></div>
    <?php endif; ?>
    <div style="padding:16px 20px 20px;">
      <div style="display:flex;align-items:flex-end;gap:16px;margin-top:-60px;flex-wrap:wrap;">
        <?php $avatar_src = !empty($user['profile_photo']) ? '/assets/uploads/profiles/'.e($user['profile_photo']) : avatar_url($user['username']); ?>
        <img src="<?= $avatar_src ?>" style="width:110px;height:110px;border-radius:50%;border:4px solid var(--surface);box-shadow:0 6px 16px rgba(0,0,0,0.6); object-fit:cover; position:relative; z-index:2;" alt="">
        <div style="padding-bottom:12px;flex:1;">
          <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;margin:0;"><?= e($user['name']) ?></h1>
          <div style="color:var(--text-d);font-size:0.85rem;">@<?= e($user['username']) ?>
            <?php $campus = $user['campus_id'] ? db_row("SELECT code FROM campuses WHERE id=?",[$user['campus_id']]) : null; ?>
            <?php if($campus): ?> · <?= e($campus['code']) ?><?php endif; ?>
          </div>
        </div>
        <span style="background:linear-gradient(135deg,var(--gold),#d97706);color:#1a0e38;font-weight:800;font-family:'Rajdhani',sans-serif;padding:6px 14px;border-radius:20px;font-size:0.9rem;">
          ⭐ <?= number_format($user['reputation']) ?> rep
        </span>
        <?php if(is_logged_in() && current_user_id()==$user['id']): ?>
        <a href="settings.php" class="btn-ghost btn-sm">⚙️ Edit Profile</a>
        <?php elseif(is_logged_in() && current_user_id()!=$user['id']): ?>
        <form method="POST" action="api/follow.php" style="display:inline;" id="follow-form-<?= $user['id'] ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
            <input type="hidden" name="action" value="<?= $is_following ? 'unfollow' : 'follow' ?>">
            <button type="submit" class="btn-<?= $is_following ? 'ghost' : 'gold' ?> btn-sm" id="follow-btn-<?= $user['id'] ?>">
                <?= $is_following ? '✓ Following' : '+ Follow' ?>
            </button>
        </form>
        <?php endif; ?>
      </div>
      <?php if($user['bio']): ?>
      <p style="margin:12px 0 0;color:var(--text-m);font-size:0.9rem;"><?= e($user['bio']) ?></p>
      <?php endif; ?>
      <!-- Stats row -->
      <div style="display:flex;gap:24px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);flex-wrap:wrap;">
        <?php foreach([['Questions',$stats['questions']],['Answers',$stats['answers']],['Accepted',$stats['accepted']],['Resources',$stats['resources']]] as [$lbl,$val]): ?>
        <div style="text-align:center;">
          <div style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;color:var(--gold);"><?= $val ?></div>
          <div style="font-size:0.75rem;color:var(--text-d);"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
        <div style="text-align:center;cursor:pointer;" onclick="window.location='followers.php?u=<?= urlencode($user['username']) ?>'">
          <div style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;color:var(--purple-l);"><?= $follower_count ?></div>
          <div style="font-size:0.75rem;color:var(--text-d);">Followers</div>
        </div>
        <div style="text-align:center;cursor:pointer;" onclick="window.location='following.php?u=<?= urlencode($user['username']) ?>'">
          <div style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;color:var(--purple-l);"><?= $following_count ?></div>
          <div style="font-size:0.75rem;color:var(--text-d);">Following</div>
        </div>
      </div>
      <!-- Badges -->
      <?php if($badges): ?>
      <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:6px;">
        <?php foreach($badges as $b): ?>
        <span title="<?= e($b['name'].': '.$b['description']) ?>" style="display:inline-flex;align-items:center;gap:4px;background:rgba(124,58,237,0.12);border:1px solid rgba(124,58,237,0.25);border-radius:20px;padding:3px 10px;font-size:0.75rem;color:var(--text-m);cursor:default;">
          <?= e($b['icon']) ?> <?= e($b['name']) ?>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Questions -->
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:12px;">Recent Questions</h2>
  <div class="q-list">
    <?php if($questions): foreach($questions as $q): ?>
    <div class="q-card <?= $q['is_solved']?'solved':'' ?>">
      <div class="q-votes">
        <div class="q-stat-box <?= $q['is_solved']?'green':'' ?>">
          <span class="big"><?= $q['answer_count'] ?></span><span class="lbl">ans</span>
        </div>
      </div>
      <div class="q-body">
        <a href="question.php?id=<?= $q['id'] ?>" class="q-title"><?= $q['is_solved']?'✅ ':'' ?><?= e($q['title']) ?></a>
        <div class="q-tags">
          <?php foreach(array_filter(explode(',',$q['tag_names']??'')) as $t): ?>
          <a href="questions.php?tag=<?= urlencode($t) ?>" class="tag"><?= e($t) ?></a>
          <?php endforeach; ?>
        </div>
        <div class="q-meta"><span><?= time_ago($q['created_at']) ?></span></div>
      </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state" style="padding:28px;"><p>No questions posted yet.</p></div>
    <?php endif; ?>
  </div>

  <!-- Liked Content -->
  <?php if($liked_content): ?>
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;margin:24px 0 12px;">❤️ Liked Content</h2>
  <div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach($liked_content as $like): ?>
    <?php 
    $link = match($like['liked_type']) {
        'question' => 'question.php?id=' . $like['liked_id'],
        'forum_post' => 'forum-post.php?id=' . $like['liked_id'],
        'resource' => 'resources.php',
        'announcement' => 'announcement.php?id=' . $like['liked_id'],
        default => '#'
    };
    $icon = match($like['liked_type']) {
        'question' => '❓',
        'forum_post' => '📋',
        'resource' => '📁',
        'announcement' => '📢',
        default => '❤️'
    };
    ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
      <span style="font-size:1.1rem;"><?= $icon ?></span>
      <div style="flex:1;min-width:0;">
        <a href="<?= $link ?>" style="font-size:0.9rem;color:var(--text-m);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= e($like['content_title'] ?? 'Content') ?>
        </a>
        <span style="font-size:0.75rem;color:var(--text-d);"><?= ucfirst($like['liked_type']) ?> · <?= time_ago($like['liked_at']) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
