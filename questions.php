<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$tab    = $_GET['tab'] ?? 'newest';
$tag    = $_GET['tag'] ?? '';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 15;
$offset = ($page - 1) * $per;

$where  = ["q.deleted_at IS NULL"];
$params = [];

if ($tag) {
    $where[] = "EXISTS (SELECT 1 FROM question_tag qt JOIN tags t ON qt.tag_id=t.id WHERE qt.question_id=q.id AND t.name=?)";
    $params[] = $tag;
}
if ($search) {
    $where[] = "(q.title LIKE ? OR q.body LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
$is_pgsql = $GLOBALS['_is_pgsql'];
$bool_true = $GLOBALS['_sql_true'];
$group_concat = $is_pgsql ? "STRING_AGG(t.name, ',')" : "GROUP_CONCAT(t.name SEPARATOR ',')";

if ($tab === 'unanswered') $where[] = "(SELECT COUNT(*) FROM answers a WHERE a.question_id=q.id)=0";
if ($tab === 'solved')     $where[] = "q.is_solved=" . sql_bool(true);

$order = match($tab) {
    'popular' => "q.vote_count DESC, q.view_count DESC",
    'most_liked' => "COALESCE(q.like_count, 0) DESC, q.created_at DESC",
    default   => "q.created_at DESC"
};

$where_sql = implode(' AND ', $where);
$total = db_count("SELECT COUNT(*) FROM questions q WHERE $where_sql", $params);
$questions = db_rows("
    SELECT q.*, u.username, u.reputation,
           (SELECT COUNT(*) FROM answers a WHERE a.question_id=q.id) as answer_count,
           $group_concat as tag_names
    FROM questions q
    JOIN users u ON q.user_id=u.id
    LEFT JOIN question_tag qt ON q.id=qt.question_id
    LEFT JOIN tags t ON qt.tag_id=t.id
    WHERE $where_sql
    GROUP BY q.id, u.username, u.reputation
    ORDER BY $order
    LIMIT $per OFFSET $offset
", $params);

$popular_tags = get_popular_tags(20);
$total_pages  = ceil($total / $per);
$page_title   = 'Questions';
require_once 'includes/header.php';
?>

<div class="page-wrap">
<div class="page-grid">
<div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <div>
      <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">All Questions</h1>
      <p style="font-size:0.82rem;color:var(--text-d);margin-top:2px;"><?= number_format($total) ?> questions in the community</p>
    </div>
    <?php if(is_logged_in()): ?>
    <a href="ask.php" class="btn-gold"><i class="bi bi-plus-lg"></i> Ask Question</a>
    <?php endif; ?>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <?php foreach(['newest'=>'<i class="bi bi-clock"></i> Newest','popular'=>'<i class="bi bi-fire"></i> Popular','most_liked'=>'<i class="bi bi-heart"></i> Most Liked','unanswered'=>'<i class="bi bi-question-circle"></i> Unanswered','solved'=>'<i class="bi bi-check-circle"></i> Solved'] as $k=>$v): ?>
    <a href="questions.php?tab=<?=$k?><?=$tag?"&tag=".urlencode($tag):''?>" class="tab-link <?=$tab===$k?'active':''?>"><?=$v?></a>
    <?php endforeach; ?>
  </div>

  <?php if($tag): ?>
  <div class="alert alert-info" style="margin-bottom:14px;">
    Filtered by tag: <strong><?= e($tag) ?></strong>
    <a href="questions.php" style="margin-left:12px;color:var(--gold);font-size:0.82rem;">✕ Clear</a>
  </div>
  <?php endif; ?>

<!-- Question List -->
  <div class="q-list">
  <?php if($questions): foreach($questions as $q): 
    // Check if user has voted on this question
    $user_vote = 0;
    $bm = false;
    if (is_logged_in()) {
        $v = db_row("SELECT value FROM votes WHERE user_id=? AND voteable_id=? AND voteable_type='App\\\\Models\\\\Question'", [current_user_id(), $q['id']]);
        $user_vote = $v ? (int)$v['value'] : 0;
        $bm = is_bookmarked(current_user_id(), $q['id'], 'question');
    }
  ?>
  <div class="q-card <?=$q['is_solved']?'solved':''?>">
    <div class="q-votes">
      <!-- Interactive Vote Buttons wrapped in data-voteable container -->
      <div class="vote-wrap" data-voteable="question-<?=$q['id']?>">
        <button class="vote-btn up <?=$user_vote===1?'active':''?>" title="Upvote">▲</button>
        <span class="vote-count"><?=$q['vote_count']?></span>
        <button class="vote-btn down <?=$user_vote===-1?'active':''?>" title="Downvote">▼</button>
      </div>
      <div class="q-stat-box <?=$q['is_solved']?'green':''?>" style="margin-top:6px;">
        <span class="big"><?=$q['answer_count']?></span>
        <span class="lbl">ans</span>
      </div>
      <div class="q-stat-box" style="background:rgba(244,166,35,0.15);">
        <span class="big" style="font-size:0.85rem;color:var(--gold);"><?= $q['like_count'] ?? 0 ?></span>
        <span class="lbl">likes</span>
      </div>
      <div class="q-stat-box">
        <span class="big" style="font-size:0.85rem;"><?=$q['view_count']?></span>
        <span class="lbl">views</span>
      </div>
    </div>
    <div class="q-body">
      <a href="question.php?id=<?=$q['id']?>" class="q-title">
        <?=$q['is_solved']?'<i class="bi bi-check-circle-fill" style="color:var(--green);font-size:0.85rem;"></i> ':''?><?= e($q['title']) ?>
      </a>
      <div class="q-tags">
        <?php foreach(array_filter(explode(',',$q['tag_names']??'')) as $t): ?>
        <a href="questions.php?tag=<?=urlencode($t)?>" class="tag"><?= e($t) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="q-meta">
        <a href="profile.php?u=<?=urlencode($q['username'])?>" class="q-user">@<?= e($q['username']) ?></a>
        <span class="rep"><i class="bi bi-star-fill" style="font-size:0.75rem;"></i> <?=number_format($q['reputation'])?></span>
        <span class="dot">·</span>
        <span><?=time_ago($q['created_at'])?></span>
        <?php if(is_logged_in()): ?>
        <span class="dot">·</span>
        <form method="POST" action="api/bookmark.php" class="bookmark-form" data-type="question" data-id="<?=$q['id']?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="question">
          <input type="hidden" name="id" value="<?=$q['id']?>">
          <button type="submit" class="btn-ghost btn-sm bookmark-btn<?= $bm ? ' bookmarked' : '' ?>" title="<?=$bm?'Remove bookmark':'Bookmark'?>" style="padding:2px 7px;font-size:0.78rem;<?= $bm ? 'color:var(--gold);border-color:var(--gold);' : '' ?>">
            <i class="bi bi-bookmark<?= $bm ? '-fill' : '' ?>"></i> <span class="bm-label"><?= $bm ? 'Saved' : 'Save' ?></span>
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; else: ?>
  <div class="empty-state">
    <div class="empty-icon"><i class="bi bi-question-circle" style="font-size:3rem;color:var(--purple-l);"></i></div>
    <p>No questions found.</p>
    <?php if(is_logged_in()): ?><a href="ask.php" class="btn-gold">Ask the First Question</a><?php endif; ?>
  </div>
  <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if($total_pages > 1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++): ?>
    <a href="?tab=<?=$tab?>&page=<?=$i?><?=$tag?"&tag=".urlencode($tag):''?><?=$search?"&q=".urlencode($search):''?>"
       class="<?=$i===$page?'current':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</div>

<!-- Sidebar -->
<div>
  <div class="widget">
    <div class="widget-head"><i class="bi bi-tags"></i> Popular Tags</div>
    <div class="widget-body" style="display:flex;flex-wrap:wrap;gap:6px;">
      <?php foreach($popular_tags as $t): ?>
      <a href="questions.php?tag=<?=urlencode($t['name'])?>" class="tag">
        <?= e($t['name']) ?> <span style="opacity:0.6;">×<?=$t['usage_count']?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php $hot = get_hot_questions(); if($hot): ?>
  <div class="widget">
    <div class="widget-head"><i class="bi bi-fire"></i> Hot Questions</div>
    <div class="widget-body">
      <?php foreach($hot as $h): ?>
      <div style="margin-bottom:10px;">
        <a href="question.php?id=<?=$h['id']?>" style="font-size:0.84rem;color:var(--text-m);line-height:1.4;display:block;"><?= e($h['title']) ?></a>
      <span style="font-size:0.74rem;color:var(--text-d);"><i class="bi bi-star-fill" style="font-size:0.7rem;"></i><?=$h['vote_count']?> · <i class="bi bi-eye" style="font-size:0.7rem;"></i><?=$h['view_count']?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
