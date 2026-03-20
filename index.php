<?php

// PANTHERVERSE DEPLOYMENT VERSION: 2.0.2 (Postgres + Session Fix)
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$is_pgsql = $GLOBALS['_is_pgsql'];
$bool_true = $GLOBALS['_sql_true'];
$bool_false = $GLOBALS['_sql_false'];

$group_concat = $is_pgsql ? "STRING_AGG(t.name, ',')" : "GROUP_CONCAT(t.name SEPARATOR ',')";

// Stats
$stats = [
    'questions' => db_count("SELECT COUNT(*) FROM questions"),
    'members'   => db_count("SELECT COUNT(*) FROM users"),
    'answers'   => db_count("SELECT COUNT(*) FROM answers"),
    'solved'    => db_count("SELECT COUNT(*) FROM questions WHERE is_solved=" . sql_bool(true)),
];

// Recent questions
$recent_questions = db_rows("
    SELECT q.*, u.username, u.reputation,
           (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answer_count,
           $group_concat as tag_names
    FROM questions q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN question_tag qt ON q.id = qt.question_id
    LEFT JOIN tags t ON qt.tag_id = t.id
    WHERE q.deleted_at IS NULL
    GROUP BY q.id, u.username, u.reputation
    ORDER BY q.created_at DESC
    LIMIT 8
");

// Most liked questions
$most_liked_questions = [];
try {
    $most_liked_questions = db_rows("
        SELECT q.*, u.username, u.reputation,
               (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answer_count,
               $group_concat as tag_names
        FROM questions q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN question_tag qt ON q.id = qt.question_id
        LEFT JOIN tags t ON qt.tag_id = t.id
        WHERE q.deleted_at IS NULL AND q.like_count > 0
        GROUP BY q.id, u.username, u.reputation
        ORDER BY q.like_count DESC, q.created_at DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    // Column doesn't exist yet - ignore
}

// Top contributors
$top_users = db_rows("SELECT id, username, reputation, role FROM users WHERE is_active=$bool_true ORDER BY reputation DESC LIMIT 5");

// Active announcements
// Active announcements with dismissal filter
$priority_order = $is_pgsql 
    ? "CASE WHEN a.priority='urgent' THEN 1 WHEN a.priority='important' THEN 2 ELSE 3 END" 
    : "FIELD(a.priority,'urgent','important','normal')";

$announcements = db_rows("SELECT a.*, u.username FROM announcements a JOIN users u ON a.user_id = u.id WHERE (a.expires_at IS NULL OR a.expires_at > NOW()) AND a.deleted_at IS NULL ORDER BY $priority_order, a.created_at DESC LIMIT 5");
$dismissed_ids = explode(',', $_COOKIE['dismissed_ann'] ?? '');
$announcements = array_filter($announcements, function($a) use ($dismissed_ids) {
    return !in_array($a['id'], $dismissed_ids);
});
$announcements = array_slice($announcements, 0, 2);

$page_title = "Home";
require_once 'includes/header.php';
?>

<!-- HERO -->
<div class="hero-banner" style="
  position:relative;
  background-image: url('/assets/hero_bg.png');
  background-size: cover;
  background-position: center 30%;
  background-repeat: no-repeat;
  overflow: hidden;
  padding: 0;
">
  <!-- Dark layered overlay -->
  <div style="
    position:absolute;inset:0;
    background: linear-gradient(
      135deg,
      rgba(14,7,32,0.65) 0%,
      rgba(90,33,182,0.45) 50%,
      rgba(14,7,32,0.7) 100%
    );
    z-index:1;
  "></div>
  <!-- Subtle gold bottom gradient -->
  <div style="position:absolute;bottom:0;left:0;right:0;height:120px;background:linear-gradient(to top,rgba(14,7,32,1),transparent);z-index:2;"></div>

  <div class="hero-glow" style="z-index:3;"></div>

  <div class="hero-content" style="position:relative;z-index:4;padding:64px 24px 40px;">
    <div class="hero-logo-ring" style="
      box-shadow: 0 0 48px rgba(244,166,35,0.5), 0 0 96px rgba(124,58,237,0.3);
      border-width:2px;
    ">
      <img src="/assets/logo.png" alt="PANTHERVERSE" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <div class="hero-logo-fallback" style="display:none"><i class="bi bi-grid-3x3-gap" style="font-size:3rem;color:var(--gold);"></i></div>
    </div>
    <div class="hero-text">
      <div style="display:inline-block;background:rgba(244,166,35,0.12);border:1px solid rgba(244,166,35,0.3);border-radius:20px;padding:4px 14px;font-size:0.75rem;font-weight:700;color:var(--gold);letter-spacing:0.1em;text-transform:uppercase;margin-bottom:14px;">
        JRMSU · College of Computing Studies
      </div>
      <h1 class="hero-title" style="text-shadow:0 2px 20px rgba(0,0,0,0.6);">PANTHER<span>VERSE</span></h1>
      <p class="hero-sub" style="color:rgba(232,223,248,0.85);text-shadow:0 1px 8px rgba(0,0,0,0.5);">
        The academic hub where Panther Minds connect — ask, learn, share, and grow together.
      </p>
      <?php if (!is_logged_in()): ?>
      <div class="hero-actions">
        <a href="register.php" class="btn-gold">
          <i class="bi bi-person-plus"></i> Join the Community
        </a>
        <a href="questions.php" class="btn-ghost">
          <i class="bi bi-question-circle"></i> Browse Q&amp;A
        </a>
      </div>
      <?php else: ?>
      <div class="hero-actions">
        <a href="ask.php" class="btn-gold">
          <i class="bi bi-plus-lg"></i> Ask a Question
        </a>
        <a href="resources.php" class="btn-ghost">
          <i class="bi bi-folder2"></i> Share a Resource
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats Bar -->
  <div class="stats-bar" style="position:relative;z-index:4;">
    <?php
    $stat_icons = ['Questions'=>'bi-question-circle','Members'=>'bi-people','Answers'=>'bi-chat-dots','Solved'=>'bi-check-circle'];
    foreach([['Questions',$stats['questions']],['Members',$stats['members']],['Answers',$stats['answers']],['Solved',$stats['solved']]] as [$label,$val]):
    ?>
    <div class="stat-item">
      <span class="stat-num"><?= number_format($val) ?></span>
      <span class="stat-label"><i class="bi <?= $stat_icons[$label] ?>" style="font-size:0.7rem;margin-right:3px;"></i><?= $label ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ANNOUNCEMENTS -->
<?php if ($announcements): ?>
<div class="section-wrap">
  <?php foreach($announcements as $ann): ?>
  <div class="announce-bar priority-<?= $ann['priority'] ?>" data-ann-id="<?= $ann['id'] ?>">
    <span class="announce-icon" style="font-size:0.7rem;"><?= $ann['priority']==='urgent'?'<i class="bi bi-exclamation-circle-fill" style="color:var(--red);"></i>':($ann['priority']==='important'?'<i class="bi bi-exclamation-triangle-fill" style="color:var(--gold);"></i>':'<i class="bi bi-info-circle-fill" style="color:var(--purple-l);"></i>') ?></span>
    <strong><?= e($ann['title']) ?></strong>
    <span class="announce-meta">by <?= e($ann['username']) ?></span>
    <div style="margin-left:auto; display:flex; align-items:center; gap:12px;">
      <a href="announcement.php?id=<?= $ann['id'] ?>" class="announce-link dismiss-ann" data-id="<?= $ann['id'] ?>">Read →</a>
      <button class="dismiss-ann" data-id="<?= $ann['id'] ?>" style="background:none; border:none; color:var(--text-d); cursor:pointer; font-size:1rem; padding:0 4px;" title="Dismiss">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MAIN GRID -->
<div class="main-grid">
  <!-- Recent Questions -->
  <div class="main-col">
    <div class="section-head">
      <h2><i class="bi bi-clock-history" style="color:var(--purple-l);"></i> Recent Questions</h2>
      <a href="questions.php" class="see-all">View All →</a>
    </div>

    <div class="q-list">
      <?php if ($recent_questions): ?>
      <?php foreach($recent_questions as $q): ?>
      <div class="q-card <?= $q['is_solved'] ? 'solved' : '' ?>">
        <div class="q-votes">
          <div class="q-stat-box <?= $q['is_solved'] ? 'green' : '' ?>">
            <span class="big"><?= $q['answer_count'] ?></span>
            <span class="lbl">ans</span>
          </div>
          <div class="q-stat-box">
            <span class="big"><?= $q['vote_count'] ?></span>
            <span class="lbl">votes</span>
          </div>
        </div>
        <div class="q-body">
          <a href="question.php?id=<?= $q['id'] ?>" class="q-title">
            <?= $q['is_solved'] ? '<i class="bi bi-check-circle-fill" style="color:var(--green);font-size:0.85rem;"></i> ' : '' ?><?= e($q['title']) ?>
          </a>
          <div class="q-tags">
            <?php foreach(array_filter(explode(',', $q['tag_names']??'')) as $tag): ?>
            <a href="questions.php?tag=<?= urlencode($tag) ?>" class="tag"><?= e($tag) ?></a>
            <?php endforeach; ?>
          </div>
          <div class="q-meta">
            <a href="profile.php?u=<?= urlencode($q['username']) ?>" class="q-user">@<?= e($q['username']) ?></a>
            <span class="rep"><i class="bi bi-star-fill" style="font-size:0.75rem;"></i> <?= number_format($q['reputation']) ?></span>
            <span class="dot">·</span>
            <span><?= time_ago($q['created_at']) ?></span>
            <span class="dot">·</span>
            <span><?= $q['view_count'] ?> views</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-question-circle" style="font-size:3rem;color:var(--purple-l);"></i></div>
        <p>No questions yet. Be the first to ask!</p>
        <a href="ask.php" class="btn-gold">Ask a Question</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="side-col">

    <!-- Most Liked Questions -->
    <?php if ($most_liked_questions): ?>
    <div class="widget">
      <div class="widget-head"><i class="bi bi-heart"></i> Most Liked</div>
      <div class="widget-body">
        <?php foreach($most_liked_questions as $mlq): ?>
        <div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--border);">
          <a href="question.php?id=<?= $mlq['id'] ?>" style="font-size:0.85rem;color:var(--text-m);line-height:1.4;display:block;">
            <?= e(mb_substr($mlq['title'], 0, 60)) ?><?= strlen($mlq['title']) > 60 ? '...' : '' ?>
          </a>
          <div style="font-size:0.72rem;color:var(--gold);margin-top:4px;">
            <i class="bi bi-heart-fill"></i> <?= $mlq['like_count'] ?? 0 ?> likes
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Top Contributors -->
    <div class="widget">
      <div class="widget-head"><i class="bi bi-trophy"></i> Top Contributors</div>
      <div class="widget-body">
        <?php foreach($top_users as $i => $u): ?>
        <div class="contrib-row">
          <span class="rank"><?= $i+1 ?></span>
          <div class="contrib-info">
            <a href="profile.php?u=<?= urlencode($u['username']) ?>" class="contrib-name">
              <?= e($u['username']) ?>
              <?php if($u['role']==='instructor'): ?><span class="role-tag">Prof</span><?php endif; ?>
              <?php if($u['role']==='admin'): ?><span class="role-tag admin">Admin</span><?php endif; ?>
            </a>
          </div>
          <span class="contrib-rep"><i class="bi bi-star-fill" style="font-size:0.75rem;"></i> <?= number_format($u['reputation']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="widget">
      <div class="widget-head"><i class="bi bi-compass"></i> Navigate</div>
      <div class="widget-body">
        <a href="questions.php" class="nav-link-item"><i class="bi bi-chat-right-text"></i> All Questions</a>
        <a href="questions.php?tab=most_liked" class="nav-link-item" style="color:var(--gold);"><i class="bi bi-heart"></i> Most Liked Questions</a>
        <a href="questions.php?tab=unanswered" class="nav-link-item"><i class="bi bi-question-circle"></i> Unanswered</a>
        <a href="forums.php" class="nav-link-item"><i class="bi bi-chat-square-text"></i> Forums</a>
        <a href="forums.php?sort=likes" class="nav-link-item" style="color:var(--gold);"><i class="bi bi-heart"></i> Most Liked Posts</a>
        <a href="resources.php" class="nav-link-item"><i class="bi bi-folder2"></i> Resources</a>
        <a href="resources.php?sort=likes" class="nav-link-item" style="color:var(--gold);"><i class="bi bi-heart"></i> Most Liked Resources</a>
        <a href="announcements.php?sort=likes" class="nav-link-item" style="color:var(--gold);"><i class="bi bi-heart"></i> Most Liked Announcements</a>
        <a href="showcase.php" class="nav-link-item"><i class="bi bi-lightning"></i> Project Showcase</a>
        <a href="tags.php" class="nav-link-item"><i class="bi bi-tags"></i> Tags</a>
        <a href="announcements.php" class="nav-link-item"><i class="bi bi-megaphone"></i> Announcements</a>
        <?php if (current_user_role() === 'admin'): ?>
        <a href="admin/" class="nav-link-item admin-link"><i class="bi bi-shield-check"></i> Admin Panel</a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
