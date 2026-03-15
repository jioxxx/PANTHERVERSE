<?php
// submit-project.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title  = trim($_POST['title'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $stack  = trim($_POST['tech_stack'] ?? '');
    $repo   = trim($_POST['repo_url'] ?? '');
    $demo   = trim($_POST['demo_url'] ?? '');
    if (!$title || !$desc) $error = 'Title and description are required.';
    else {
        db_insert("INSERT INTO projects (user_id,title,description,tech_stack,repo_url,demo_url,is_endorsed,like_count,created_at,updated_at) VALUES (?,?,?,?,?,?,0,0,NOW(),NOW())",
            [current_user_id(),$title,$desc,$stack,$repo?:null,$demo?:null]);
        flash('success','Project submitted!'); redirect('showcase.php');
    }
}

$page_title = 'Submit Project';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:640px;">
  <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:20px;">🚀 Submit Your Project</h1>
  <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <div class="card"><div class="card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group"><label>Project Title *</label><input type="text" name="title" value="<?= e($_POST['title']??'') ?>" required></div>
      <div class="form-group"><label>Description *</label><textarea name="description" rows="5" required><?= e($_POST['description']??'') ?></textarea></div>
      <div class="form-group"><label>Tech Stack <span class="form-hint" style="display:inline;">(comma-separated)</span></label><input type="text" name="tech_stack" value="<?= e($_POST['tech_stack']??'') ?>" placeholder="e.g. Laravel, MySQL, Bootstrap 5"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group"><label>GitHub URL</label><input type="url" name="repo_url" value="<?= e($_POST['repo_url']??'') ?>" placeholder="https://github.com/..."></div>
        <div class="form-group"><label>Demo URL</label><input type="url" name="demo_url" value="<?= e($_POST['demo_url']??'') ?>" placeholder="https://..."></div>
      </div>
      <button type="submit" class="btn-gold">Submit Project</button>
      <a href="showcase.php" class="btn-ghost" style="margin-left:10px;">Cancel</a>
    </form>
  </div></div>
</div>
<?php require_once 'includes/footer.php'; ?>
