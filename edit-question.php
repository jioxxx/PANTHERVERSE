<?php
// edit-question.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$q  = db_row("SELECT * FROM questions WHERE id=? AND deleted_at IS NULL", [$id]);
if (!$q || ($q['user_id'] != current_user_id() && current_user_role() !== 'admin'))
    { flash('error','Not found or access denied.'); redirect('questions.php'); }

$error = '';
$current_tags = db_rows("SELECT tag_id FROM question_tag WHERE question_id=?", [$id]);
$current_tag_ids = array_column($current_tags,'tag_id');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $title   = trim($_POST['title']??'');
    $body    = trim($_POST['body']??'');
    $tag_ids = array_map('intval', $_POST['tags']??[]);
    if (strlen($title)<15) $error = 'Title too short.';
    elseif (strlen($body)<30) $error = 'Body too short.';
    else {
        db_exec("UPDATE questions SET title=?,body=?,updated_at=NOW() WHERE id=?",
            [$title, strip_tags($body,'<p><br><strong><em><ul><ol><li><code><pre><h2><h3><blockquote><a>'), $id]);
        db_exec("DELETE FROM question_tag WHERE question_id=?", [$id]);
        foreach($tag_ids as $tid) db_exec("INSERT IGNORE INTO question_tag (question_id,tag_id) VALUES (?,?)", [$id,$tid]);
        flash('success','Question updated!'); redirect("question.php?id=$id");
    }
}

$all_tags = db_rows("SELECT id,name FROM tags ORDER BY usage_count DESC");
$page_title = 'Edit Question';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:800px;">
  <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:20px;">✏️ Edit Question</h1>
  <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <div class="card"><div class="card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group"><label>Title *</label>
        <input type="text" name="title" value="<?= e($_POST['title']??$q['title']) ?>" required>
      </div>
      <div class="form-group"><label>Body *</label>
        <textarea name="body" rows="10"><?= e($_POST['body']??$q['body']) ?></textarea>
      </div>
      <div class="form-group"><label>Tags</label>
        <div style="display:flex;flex-wrap:wrap;gap:6px;padding:12px;background:rgba(124,58,237,0.06);border:1px solid var(--border);border-radius:8px;">
          <?php foreach($all_tags as $t):
            $sel = in_array($t['id'], $_POST['tags']??$current_tag_ids);
          ?>
          <label style="cursor:pointer;">
            <input type="checkbox" name="tags[]" value="<?=$t['id']?>" <?=$sel?'checked':''?> style="display:none"
              onchange="this.nextElementSibling.style.background=this.checked?'var(--purple)':'';this.nextElementSibling.style.color=this.checked?'#fff':''">
            <span class="tag" style="<?=$sel?'background:var(--purple);color:#fff;':''?>"><?= e($t['name']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" class="btn-gold">Save Changes</button>
      <a href="question.php?id=<?=$id?>" class="btn-ghost" style="margin-left:10px;">Cancel</a>
    </form>
  </div></div>
</div>
<?php require_once 'includes/footer.php'; ?>
