<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    if (!$title) { $error = 'Title is required.'; }
    elseif (empty($_FILES['file']['name'])) { $error = 'Please select a file to upload.'; }
    else {
        $file     = $_FILES['file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf','doc','docx','txt','py','java','php','js','sql','zip','pptx','xlsx','csv','png','jpg'];
        if (!in_array($ext, $allowed)) {
            $error = 'File type not allowed. Allowed: ' . implode(', ', $allowed);
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'File too large. Maximum 10MB.';
        } else {
            $upload_dir = 'uploads/resources/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $safe_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $path      = $upload_dir . $safe_name;
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $rid = db_insert(
                    "INSERT INTO resources (user_id,title,description,file_path,file_name,file_type,file_size,download_count,is_instructor_verified,created_at,updated_at)
                     VALUES (?,?,?,?,?,?,?,0,0,NOW(),NOW())",
                    [current_user_id(),$title,$desc,$path,$file['name'],$ext,$file['size']]
                );
                flash('success','Resource uploaded successfully!');
                redirect('resources.php');
            } else {
                $error = 'Upload failed. Check folder permissions.';
            }
        }
    }
}

$page_title = 'Upload Resource';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:640px;">
  <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:20px;">📤 Upload Resource</h1>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <div class="card">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Resource Title *</label>
          <input type="text" name="title" value="<?= e($_POST['title']??'') ?>" placeholder="e.g. Java OOP Complete Notes" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="4" placeholder="Describe what this resource covers..."><?= e($_POST['description']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label>File * <span class="form-hint" style="display:inline;">(Max 10MB — PDF, DOCX, ZIP, code files, etc.)</span></label>
          <input type="file" name="file" accept=".pdf,.doc,.docx,.txt,.py,.java,.php,.js,.sql,.zip,.pptx,.xlsx,.csv,.png,.jpg" required style="color:var(--text-m);">
        </div>
        <button type="submit" class="btn-gold">📤 Upload Resource</button>
        <a href="resources.php" class="btn-ghost" style="margin-left:10px;">Cancel</a>
      </form>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
