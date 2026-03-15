<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_role('admin');

$stats = [
    'users'     => db_count("SELECT COUNT(*) FROM users"),
    'questions' => db_count("SELECT COUNT(*) FROM questions WHERE deleted_at IS NULL"),
    'answers'   => db_count("SELECT COUNT(*) FROM answers WHERE deleted_at IS NULL"),
    'reports'   => db_count("SELECT COUNT(*) FROM reports WHERE status='pending'"),
];

$users = db_rows("SELECT u.*, c.code as campus_code FROM users u LEFT JOIN campuses c ON u.campus_id=c.id ORDER BY u.created_at DESC LIMIT 20");

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_role'])) {
    csrf_check();
    $uid  = (int)$_POST['user_id'];
    $role = $_POST['role'] ?? '';
    if ($uid != current_user_id() && in_array($role,['student','instructor','admin'])) {
        db_exec("UPDATE users SET role=? WHERE id=?", [$role,$uid]);
        flash('success','Role updated.');
    }
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_status'])) {
    csrf_check();
    $uid = (int)$_POST['user_id'];
    if ($uid != current_user_id()) {
        db_exec("UPDATE users SET is_active = 1 - is_active WHERE id=?", [$uid]);
        flash('success','User status updated.');
    }
    redirect('index.php');
}

$page_title = 'Admin Panel';
// Use base path for header includes
require_once '../includes/header.php';
?>
<div class="page-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">🛡️ Admin Panel</h1>
    <a href="../index.php" class="btn-ghost btn-sm">← Back to Site</a>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;">
    <?php foreach([['Users',$stats['users'],'👥'],['Questions',$stats['questions'],'❓'],['Answers',$stats['answers'],'💬'],['Reports',$stats['reports'],'🚨']] as [$lbl,$val,$icon]): ?>
    <div class="card" style="padding:16px;text-align:center;">
      <div style="font-size:1.5rem;"><?= $icon ?></div>
      <div style="font-family:'Rajdhani',sans-serif;font-size:1.8rem;font-weight:700;color:var(--gold);"><?= number_format($val) ?></div>
      <div style="font-size:0.78rem;color:var(--text-d);"><?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- User Management -->
  <div class="card">
    <div class="card-head"><span class="card-title">👥 User Management</span></div>
    <div style="overflow-x:auto;">
      <table class="pv-table">
        <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Campus</th><th>Rep</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <img src="<?= avatar_url($u['username']) ?>" style="width:28px;height:28px;border-radius:50%;" alt="">
                <div>
                  <div style="font-weight:600;font-size:0.85rem;"><?= e($u['name']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-d);">@<?= e($u['username']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:0.82rem;color:var(--text-m);"><?= e($u['email']) ?></td>
            <td>
              <form method="POST" action="index.php" style="display:inline;">
                <?= csrf_field() ?><input type="hidden" name="change_role" value="1"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <select name="role" onchange="this.form.submit()" style="background:var(--bg3);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:0.8rem;padding:3px 6px;font-family:'Nunito',sans-serif;">
                  <?php foreach(['student','instructor','admin'] as $r): ?>
                  <option value="<?=$r?>" <?=$u['role']===$r?'selected':''?>><?= ucfirst($r) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td style="font-size:0.82rem;color:var(--text-d);"><?= e($u['campus_code']??'—') ?></td>
            <td style="font-size:0.82rem;color:var(--gold);">⭐<?= number_format($u['reputation']) ?></td>
            <td>
              <span style="font-size:0.78rem;padding:2px 8px;border-radius:10px;<?= $u['is_active']?'background:rgba(16,185,129,0.15);color:var(--green);':'background:rgba(239,68,68,0.15);color:var(--red);' ?>">
                <?= $u['is_active']?'Active':'Suspended' ?>
              </span>
            </td>
            <td>
              <?php if($u['id'] != current_user_id()): ?>
              <form method="POST" action="index.php" style="display:inline;">
                <?= csrf_field() ?><input type="hidden" name="toggle_status" value="1"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-danger" style="font-size:0.75rem;padding:3px 10px;"><?= $u['is_active']?'Suspend':'Activate' ?></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
