<?php
// my-questions.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$questions = db_rows("SELECT q.*,(SELECT COUNT(*) FROM answers a WHERE a.question_id=q.id) as answer_count, GROUP_CONCAT(t.name SEPARATOR ',') as tag_names FROM questions q LEFT JOIN question_tag qt ON q.id=qt.question_id LEFT JOIN tags t ON qt.tag_id=t.id WHERE q.user_id=? AND q.deleted_at IS NULL GROUP BY q.id ORDER BY q.created_at DESC", [current_user_id()]);
$page_title = 'My Questions';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:800px;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;">❓ My Questions</h1>
    <a href="ask.php" class="btn-gold">+ Ask New</a>
  </div>
  <div class="q-list">
    <?php if($questions): foreach($questions as $q): ?>
    <div class="q-card <?=$q['is_solved']?'solved':''?>">
      <div class="q-votes">
        <div class="q-stat-box <?=$q['is_solved']?'green':''?>"><span class="big"><?=$q['answer_count']?></span><span class="lbl">ans</span></div>
      </div>
      <div class="q-body">
        <a href="question.php?id=<?=$q['id']?>" class="q-title"><?=$q['is_solved']?'✅ ':''?><?= e($q['title']) ?></a>
        <div class="q-tags"><?php foreach(array_filter(explode(',',$q['tag_names']??'')) as $t): ?><a href="questions.php?tag=<?=urlencode($t)?>" class="tag"><?= e($t) ?></a><?php endforeach; ?></div>
        <div class="q-meta"><span><?=time_ago($q['created_at'])?></span><span class="dot">·</span><span><?=$q['view_count']?> views</span></div>
      </div>
      <form method="POST" action="delete-question.php" onsubmit="return confirm('Delete?')" style="align-self:center;">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?=$q['id']?>">
        <button type="submit" class="btn-danger">🗑</button>
      </form>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state"><div class="empty-icon">❓</div><p>You haven't asked any questions yet.</p><a href="ask.php" class="btn-gold">Ask Your First Question</a></div>
    <?php endif; ?>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
