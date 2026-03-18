<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (is_logged_in()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name       = trim($_POST['name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm'] ?? '';
    $role       = $_POST['role'] ?? 'student';
    $campus_id  = (int)($_POST['campus_id'] ?? 0);
    $program_id = (int)($_POST['program_id'] ?? 0);
    $year_level = $role === 'student' ? (int)($_POST['year_level'] ?? 0) : null;
    $department = trim($_POST['department'] ?? '');

    if (!$name || !$username || !$email || !$password) {
        $error = 'All required fields must be filled.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores.';
    } elseif (db_count("SELECT COUNT(*) FROM users WHERE email = ?", [$email])) {
        $error = 'Email already registered.';
    } elseif (db_count("SELECT COUNT(*) FROM users WHERE username = ?", [$username])) {
        $error = 'Username already taken.';
    } elseif (!in_array($role, ['student', 'instructor'])) {
        $error = 'Invalid role selected.';
    } elseif ($role === 'student' && (!$year_level || $year_level < 1 || $year_level > 4)) {
        $error = 'Please select a valid year level (1-4).';
    } elseif ($role === 'instructor' && !str_contains($email, '@jrmsu.edu.ph')) {
        $error = 'Instructors must use official JRMSU email (@jrmsu.edu.ph).';
    } else {
        // Build dynamic INSERT
        $insert_data = [$name, $username, $email, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $role, $campus_id ?: null, $program_id ?: null];
        $extra_columns = [];
        $extra_values = [];
        $extra_params = [];

        if ($role === 'student' && $year_level) {
            $extra_columns[] = 'year_level';
            $extra_values[] = '?';
            $extra_params[] = $year_level;
        }
        if ($role === 'instructor' && $department) {
            $extra_columns[] = 'bio';
            $extra_values[] = '?';
            $extra_params[] = "Instructor - Department: $department";
        }

        $bool_true = $GLOBALS['_sql_true'];
        $columns = '(name, username, email, password, role, campus_id, program_id' . 
                   ($extra_columns ? ', ' . implode(', ', $extra_columns) : '') . 
                   ', reputation, is_active, created_at, updated_at)';
        $placeholders = '(?, ?, ?, ?, ?, ?, ?' . 
                        ($extra_values ? ', ' . implode(', ', $extra_values) : '') . 
                        ", 0, $bool_true, NOW(), NOW())";

        $all_params = array_merge($insert_data, $extra_params);

        $id = db_insert("INSERT INTO users $columns VALUES $placeholders", $all_params);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        flash('success', "Welcome to PANTHERVERSE, $name! 🎉 Role: " . ucfirst($role));
        redirect('index.php');
    }
}

$bool_true = $GLOBALS['_sql_true'];
$campuses = db_rows("SELECT id, name, code FROM campuses WHERE is_active=$bool_true ORDER BY name");
$programs = db_rows("SELECT id, name, code FROM programs ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Register — PANTHERVERSE</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0e0720;--surface:#1a0e38;--border:rgba(124,58,237,0.3);--purple:#7c3aed;--gold:#f4a623;--gold-d:#d97706;--text:#e8dff8;--text-m:#a78bca;--text-d:#6b4fa0;--red:#ef4444;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Nunito',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background-image:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(124,58,237,0.2) 0%,transparent 60%);}
.wrap{width:100%;max-width:480px;}
.logo-area{text-align:center;margin-bottom:24px;}
.logo-area h1{font-family:'Rajdhani',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;}
.logo-area h1 span{color:var(--gold);}
.logo-area p{font-size:0.82rem;color:var(--text-d);margin-top:4px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px;}
.card h2{font-family:'Rajdhani',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:20px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.fg{margin-bottom:14px;}
.fg label{display:block;font-size:0.82rem;font-weight:600;color:var(--text-m);margin-bottom:5px;}
.fg input,.fg select{width:100%;background:rgba(124,58,237,0.08);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:8px 11px;font-size:0.875rem;font-family:'Nunito',sans-serif;outline:none;transition:border-color 0.2s;}
.fg input:focus,.fg select:focus{border-color:var(--purple);}
.fg input::placeholder{color:var(--text-d);}
.fg select option{background:#1a0e38;}
.btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-d));color:#1a0e38;font-weight:700;font-size:0.95rem;border:none;border-radius:8px;padding:10px;width:100%;cursor:pointer;transition:all 0.15s;font-family:'Nunito',sans-serif;margin-top:4px;}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(244,166,35,0.4);}
.alert-error{background:rgba(239,68,68,0.1);border-left:3px solid var(--red);color:#fca5a5;padding:9px 12px;border-radius:7px;font-size:0.875rem;margin-bottom:14px;}
.link-row{text-align:center;font-size:0.875rem;color:var(--text-d);margin-top:16px;}
.link-row a{color:var(--gold);font-weight:600;}
.section-label{font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-d);margin:16px 0 8px;}
.student-fields, .instructor-fields {display: none;}
.student-fields.active, .instructor-fields.active {display: block;}
.role-option {display: flex; gap: 8px; align-items: center; margin-bottom: 4px; padding: 6px 8px; border-radius: 6px; cursor: pointer; transition: background 0.2s;}
.role-option:hover {background: rgba(124,58,237,0.1);}
.role-option input[type=radio] {margin: 0;}
.role-option.selected {background: rgba(124,58,237,0.2); border: 1px solid var(--purple);}
@media(max-width: 480px) {
  .row2{grid-template-columns:1fr;}
  .card{padding:20px;}
  .logo-area h1{font-size:1.6rem;}
  .wrap{padding:0 10px;}
}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo-area">
    <h1>PANTHER<span>VERSE</span></h1>
    <p>Create your JRMSU academic account</p>
  </div>
  <div class="card">
    <h2>Join PANTHERVERSE 🐆</h2>

    <?php if ($error): ?>
    <div class="alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="regForm">
      <?= csrf_field() ?>

      <div class="section-label">Personal Info</div>
      <div class="fg">
        <label>Full Name *</label>
        <input type="text" name="name" value="<?= e($_POST['name']??'') ?>" placeholder="e.g. Juan dela Cruz" required>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Username *</label>
          <input type="text" name="username" value="<?= e($_POST['username']??'') ?>" placeholder="juandc" required>
        </div>
        <div class="fg">
          <label>Email *</label>
          <input type="email" name="email" value="<?= e($_POST['email']??'') ?>" placeholder="you@jrmsu.edu.ph" required>
        </div>
      </div>

      <div class="section-label">User Role</div>
      <div style="border:1px solid var(--border);border-radius:8px;padding:12px;background:rgba(124,58,237,0.05);">
        <label style="display:block;margin-bottom:10px;font-weight:600;">I am a...</label>
        <label class="role-option <?= ($_POST['role']??'student')==='student' ? 'selected' : '' ?>">
          <input type="radio" name="role" value="student" id="role-student" <?= ($_POST['role']??'student')==='student' ? 'checked' : '' ?> required>
          <span>👨‍🎓 Student (select year level)</span>
        </label>
        <label class="role-option <?= ($_POST['role']??'')==='instructor' ? 'selected' : '' ?>">
          <input type="radio" name="role" value="instructor" id="role-instructor" <?= ($_POST['role']??'')==='instructor' ? 'checked' : '' ?>>
          <span>👨‍🏫 Instructor / Professor</span>
        </label>
      </div>

      <div class="student-fields <?= ($_POST['role']??'student')==='student' ? 'active' : '' ?>" id="student-fields">
        <div class="section-label">Academic Details</div>
        <div class="row2">
          <div class="fg">
            <label>Year Level *</label>
            <select name="year_level" required>
              <option value="">Select Year</option>
              <?php for($y=1;$y<=4;$y++): ?>
              <option value="<?= $y ?>" <?= ($_POST['year_level']??'')==$y ? 'selected' : '' ?>>
                <?= $y ?><?= $y==1 ? 'st' : ($y==2 ? 'nd' : ($y==3 ? 'rd' : 'th')) ?> Year
              </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="fg">
            <label>Campus</label>
            <select name="campus_id">
              <option value="">Any Campus</option>
              <?php foreach($campuses as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($_POST['campus_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="fg">
          <label>Program</label>
          <select name="program_id">
            <option value="">Any Program</option>
            <?php foreach($programs as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($_POST['program_id']??'')==$p['id']?'selected':'' ?>><?= e($p['code']) ?> - <?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="instructor-fields <?= ($_POST['role']??'')==='instructor' ? 'active' : '' ?>" id="instructor-fields" style="margin-top:8px;">
        <div class="fg">
          <label>Department / Specialization (optional)</label>
          <input type="text" name="department" value="<?= e($_POST['department']??'') ?>" placeholder="e.g. Computer Science, Information Systems">
        </div>
        <div class="row2">
          <div class="fg">
            <label>Campus</label>
            <select name="campus_id">
              <option value="">Select Campus</option>
              <?php foreach($campuses as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($_POST['campus_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label>Program you teach</label>
            <select name="program_id">
              <option value="">Select Program</option>
              <?php foreach($programs as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($_POST['program_id']??'')==$p['id']?'selected':'' ?>><?= e($p['code']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="fg" style="font-size:0.8rem;color:var(--text-d);padding:8px;background:rgba(16,185,129,0.1);border-radius:6px;border-left:3px solid #10b981;">
          <strong>Note:</strong> Your account will be <em>student</em> role initially. Instructors are admin-approved for full access.
        </div>
      </div>

      <div class="section-label">Password</div>
      <div class="row2">
        <div class="fg">
          <label>Password *</label>
          <input type="password" name="password" placeholder="Min. 8 characters" required>
        </div>
        <div class="fg">
          <label>Confirm Password *</label>
          <input type="password" name="confirm" placeholder="Repeat password" required>
        </div>
      </div>

      <button type="submit" class="btn-gold">Create Account & Join</button>
    </form>

    <div class="link-row">Already have an account? <a href="login.php">Login here</a></div>
  </div>
</div>

<script>
document.querySelectorAll('input[name=role]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('selected'));
    this.closest('.role-option').classList.add('selected');
    
    document.getElementById('student-fields').classList.toggle('active', this.value === 'student');
    document.getElementById('instructor-fields').classList.toggle('active', this.value === 'instructor');
    
    // Update required attrs
    const yearSelect = document.querySelector('[name=year_level]');
    yearSelect.required = this.value === 'student';
    
    if (this.value === 'student') {
      document.querySelector('#regForm').querySelector('button[type=submit]').textContent = 'Create Student Account';
    } else {
      document.querySelector('#regForm').querySelector('button[type=submit]').textContent = 'Create Instructor Account';
    }
  });
});

// Trigger on load if POST data
<?php if ($_POST): ?>
document.querySelector('input[name=role][value="<?= e($_POST['role']??'student') ?>"]').dispatchEvent(new Event('change'));
<?php endif; ?>
</script>
</body>
</html>

