<?php
// ask.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    $tag_ids = array_map('intval', $_POST['tags'] ?? []);

    if (strlen($title) < 15)        $error = 'Title must be at least 15 characters.';
    elseif (strlen($body) < 30)     $error = 'Question body must be at least 30 characters.';
    elseif (count($tag_ids) < 1)    $error = 'Select at least 1 tag.';
    elseif (count($tag_ids) > 5)    $error = 'Maximum 5 tags allowed.';
    else {
        $slug = slugify($title) . '-' . time();
        $qid  = db_insert(
            "INSERT INTO questions (user_id,title,body,slug,status,is_solved,vote_count,view_count,created_at,updated_at)
             VALUES (?,?,?,'$slug','open',0,0,0,NOW(),NOW())",
            [current_user_id(), $title, strip_tags($body,'<p><br><strong><em><ul><ol><li><code><pre><h2><h3><h4><blockquote><a>')]
        );
        foreach($tag_ids as $tid) {
            db_exec("INSERT IGNORE INTO question_tag (question_id,tag_id) VALUES (?,?)", [$qid,$tid]);
            db_exec("UPDATE tags SET usage_count=usage_count+1 WHERE id=?", [$tid]);
        }
        flash('success','Your question has been posted!');
        redirect("question.php?id=$qid");
    }
}

$all_tags = db_rows("SELECT id,name,usage_count FROM tags ORDER BY usage_count DESC");
$page_title = 'Ask a Question';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:800px;">
  <div class="card">
    <div class="card-head"><span class="card-title">❓ Ask a Question</span></div>
    <div class="card-body">
      <div class="alert alert-info" style="margin-bottom:18px;">
        💡 <strong>Tips:</strong> Be specific, include error messages, and show what you've already tried.
      </div>
      <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Question Title * <span class="form-hint" style="display:inline;">(min 15 characters)</span></label>
          <input type="text" name="title" value="<?= e($_POST['title']??'') ?>" placeholder="e.g. How do I reverse a string in Python?" required>
        </div>
        <div class="form-group">
          <label>Details * <span class="form-hint" style="display:inline;">(min 30 characters)</span></label>
          <textarea name="body" rows="10" placeholder="Describe your problem in detail. Include relevant code, error messages, and what you've tried."><?= e($_POST['body']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label>Tags * <span class="form-hint" style="display:inline;">(1–5 tags)</span></label>
          <div style="display:flex;flex-wrap:wrap;gap:6px;padding:12px;background:rgba(124,58,237,0.06);border:1px solid var(--border);border-radius:8px;">
            <?php foreach($all_tags as $t):
              $checked = in_array($t['id'], array_map('intval',$_POST['tags']??[]));
            ?>
            <label style="cursor:pointer;">
              <input type="checkbox" name="tags[]" value="<?= $t['id'] ?>" <?= $checked?'checked':'' ?> style="display:none;"
                onchange="this.closest('label').querySelector('.tag-lbl').classList.toggle('sel',this.checked)">
              <span class="tag tag-lbl <?= $checked?'sel':'' ?>" style="<?= $checked?'background:var(--purple);color:#fff;':'' ?>">
                <?= e($t['name']) ?>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="btn-gold">Post Question 🚀</button>
        <a href="questions.php" class="btn-ghost" style="margin-left:10px;">Cancel</a>
      </form>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.tag-lbl').forEach(el => {
  el.addEventListener('click', () => {
    const inp = el.previousElementSibling;
    el.style.background = inp.checked ? 'var(--purple)' : '';
    el.style.color = inp.checked ? '#fff' : '';
  });
});
</script>
<?php require_once 'includes/footer.php'; ?>
