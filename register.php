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
:root{--bg:#08041a;--surface:#130930;--surface2:#1c1045;--border:rgba(124,58,237,0.25);--purple:#7c3aed;--purple-l:#a855f7;--gold:#f4a623;--gold-l:#fbbf24;--text:#ede9f8;--text-m:#b89fd8;--text-d:#6b5a8a;--red:#ef4444;--radius:14px;--shadow:0 20px 70px rgba(0,0,0,0.45);}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{
  font-family:'Nunito',sans-serif;
  background:var(--bg);
  color:var(--text);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
  background-image:
    radial-gradient(ellipse 120% 60% at 50% -20%, rgba(124,58,237,0.18) 0%, transparent 65%),
    radial-gradient(ellipse 60% 40% at 90% 15%, rgba(244,166,35,0.08) 0%, transparent 55%),
    radial-gradient(ellipse 40% 30% at 10% 60%, rgba(124,58,237,0.06) 0%, transparent 50%);
}
.auth-shell{
  width:100%;
  max-width:1050px;
  border-radius:20px;
  overflow:hidden;
  box-shadow:var(--shadow);
  border:1px solid rgba(124,58,237,0.22);
  background: rgba(19,9,48,0.72);
  display:grid;
  grid-template-columns: 1fr 520px;
}
@media(max-width: 1000px){
  .auth-shell{grid-template-columns:1fr;}
}
.left-hero{
  position:relative;
  min-height: 650px;
}
.left-photo{position:absolute;inset:0;background-image:url('<?= BASE_PATH ?>/assets/hero_bg.png');background-size:cover;background-position:center;filter:saturate(1.1) contrast(1.05);}
.left-photo::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg, rgba(8,4,26,0.95) 0%, rgba(8,4,26,0.55) 40%, rgba(8,4,26,0.85) 100%),linear-gradient(135deg, rgba(124,58,237,0.40) 0%, rgba(244,166,35,0.18) 55%, rgba(13,7,36,0.25) 100%);}
.left-content{position:relative;z-index:2;padding:34px 34px;height:100%;display:flex;flex-direction:column;justify-content:space-between;}
.left-top{display:flex;align-items:center;gap:14px;}
.brand-ring{width:72px;height:72px;border-radius:50%;border:2px solid rgba(244,166,35,0.6);background:rgba(124,58,237,0.15);box-shadow:0 0 30px rgba(244,166,35,0.22);display:flex;align-items:center;justify-content:center;flex-shrink:0;animation: glow-pulse 3s ease-in-out infinite;}
.brand-ring img{width:56px;height:56px;border-radius:50%;object-fit:cover;}
.brand-title{font-family:'Rajdhani',sans-serif;font-size:1.7rem;font-weight:700;line-height:1.1;}
.brand-title span{color:var(--gold);text-shadow:0 0 24px rgba(244,166,35,0.35);}
.left-copy h1{font-family:'Rajdhani',sans-serif;font-size:2.35rem;line-height:1.05;margin-bottom:10px;}
.left-copy h1 span{color:var(--gold);}
.left-copy p{color:rgba(237,233,248,0.85);font-size:0.95rem;line-height:1.65;max-width:420px;}
.left-badges{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;}
.pill{background:rgba(124,58,237,0.14);border:1px solid rgba(124,58,237,0.25);color:var(--purple-l);padding:8px 12px;border-radius:999px;font-weight:700;font-size:0.8rem;}
.pill.gold{background:rgba(244,166,35,0.10);border-color:rgba(244,166,35,0.25);color:var(--gold-l);}
.left-foot{color:rgba(237,233,248,0.75);font-size:0.85rem;display:flex;align-items:center;justify-content:space-between;gap:16px;}
.stamp{display:flex;gap:10px;align-items:center;}
.stamp-dot{width:10px;height:10px;border-radius:50%;background:var(--gold);box-shadow:0 0 14px rgba(244,166,35,0.4);}
@keyframes glow-pulse{0%,100%{box-shadow:0 0 8px rgba(124,58,237,0.5),0 0 16px rgba(124,58,237,0.1);}50%{box-shadow:0 0 16px rgba(124,58,237,0.5),0 0 32px rgba(124,58,237,0.2);}}

.right-panel{background:rgba(19,9,48,0.92);padding:30px 26px;display:flex;flex-direction:column;}
.panel-header{display:flex;flex-direction:column;align-items:center;margin-bottom:18px;}
.panel-header img{width:64px;height:64px;border-radius:50%;box-shadow:0 0 26px rgba(124,58,237,0.35);}
.panel-header h2{font-family:'Rajdhani',sans-serif;font-size:1.55rem;color:#fff;margin-top:10px;margin-bottom:6px;}
.panel-header p{color:var(--text-d);font-size:0.9rem;text-align:center;}

.form-card{border:1px solid rgba(124,58,237,0.22);border-radius:var(--radius);background:rgba(13,7,36,0.55);padding:22px;margin-top:6px;}
.section-label{font-size:0.72rem;font-weight:900;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-d);margin:18px 0 10px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
@media(max-width: 520px){.row2{grid-template-columns:1fr;}}
.fg{margin-bottom:14px;}
.fg label{display:block;font-size:0.82rem;font-weight:800;color:var(--text-m);margin-bottom:6px;}
.fg input,.fg select{width:100%;background:rgba(124,58,237,0.08);border:1px solid rgba(124,58,237,0.32);border-radius:10px;color:var(--text);padding:9px 12px;font-size:0.9rem;font-family:'Nunito',sans-serif;outline:none;transition:border-color .2s, box-shadow .2s;}
.fg input:focus,.fg select:focus{border-color:var(--gold-l);box-shadow:0 0 0 3px rgba(244,166,35,0.12);} 
.fg input::placeholder{color:var(--text-d);}
.fg select option{background:#1a0e38;}
.alert-error{background:rgba(239,68,68,0.10);border-left:3px solid var(--red);color:#fca5a5;padding:10px 12px;border-radius:8px;font-size:0.9rem;margin-bottom:14px;}
.btn-gold{background:linear-gradient(135deg,var(--gold-l),var(--gold));color:#1a0e38;font-weight:900;font-size:0.95rem;border:none;border-radius:10px;padding:11px 14px;width:100%;cursor:pointer;transition:all 0.15s;font-family:'Nunito',sans-serif;box-shadow:0 10px 26px rgba(244,166,35,0.18);margin-top:6px;}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 18px 44px rgba(244,166,35,0.28);} 
.link-row{text-align:center;font-size:0.9rem;color:var(--text-d);margin-top:16px;}
.link-row a{color:var(--gold);font-weight:800;}
.student-fields,.instructor-fields{display:none;}
.student-fields.active,.instructor-fields.active{display:block;}
.role-option{display:flex;gap:10px;align-items:center;margin-bottom:8px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background .2s,border-color .2s; background: rgba(124,58,237,0.08); border:1px solid rgba(124,58,237,0.18);} 
.role-option:hover{background:rgba(124,58,237,0.12);} 
.role-option.selected{background:rgba(124,58,237,0.18);border-color:rgba(168,85,247,0.9);} 
.role-option input[type=radio]{margin:0;}
.role-box{border:1px solid rgba(124,58,237,0.25);border-radius:10px;padding:14px;background:rgba(124,58,237,0.06);}
.note-green{font-size:0.82rem;color:var(--text-d);padding:10px 10px;background:rgba(16,185,129,0.10);border-radius:10px;border-left:3px solid #10b981;margin-top:8px;}
</style>
</head>
<body>
  <div class="auth-shell">
    <section class="left-hero" aria-hidden="true">
      <div class="left-photo"></div>
      <div class="left-content">
        <div class="left-top">
          <div class="brand-ring"><img src="<?= BASE_PATH ?>/assets/logo.png" alt="PANTHERVERSE"></div>
          <div class="brand-title">PANTHER<span>VERSE</span></div>
        </div>

        <div class="left-copy">
          <h1>Join the <span>Computing</span> Community</h1>
          <p>Create your JRMSU academic account and start sharing, learning, and collaborating.</p>
          <div class="left-badges">
            <span class="pill">Purple</span>
            <span class="pill gold">Gold</span>
            <span class="pill">JRMSU</span>
          </div>
        </div>

        <div class="left-foot">
          <div class="stamp"><span class="stamp-dot"></span><span>Designed for students</span></div>
          <div style="font-weight:900;color:rgba(244,166,35,0.9)">● Fast Setup</div>
        </div>
      </div>
    </section>

    <section class="right-panel">
      <div class="panel-header">
        <img src="<?= BASE_PATH ?>/assets/logo.png" alt="logo">
        <h2>Join PANTHERVERSE 🐆</h2>
        <p>Create your JRMSU academic account</p>
      </div>

      <div class="form-card">
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
          <div class="role-box">
            <label style="display:block;margin-bottom:10px;font-weight:900;color:var(--text-m);">I am a...</label>
            <label class="role-option <?= ($_POST['role']??'student')==='student' ? 'selected' : '' ?>">
              <input type="radio" name="role" value="student" id="role-student" <?= ($_POST['role']??'student')==='student' ? 'checked' : '' ?> required>
              <span>👨‍🎓 Student (select year level)</span>
            </label>
            <label class="role-option <?= ($_POST['role']??'')==='instructor' ? 'selected' : '' ?>">
              <input type="radio" name="role" value="instructor" id="role-instructor" <?= ($_POST['role']??'')==='instructor' ? 'checked' : '' ?> >
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

            <div class="note-green">
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
    </section>
  </div>

<script>
document.querySelectorAll('input[name=role]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('selected'));
    this.closest('.role-option').classList.add('selected');

    document.getElementById('student-fields').classList.toggle('active', this.value === 'student');
    document.getElementById('instructor-fields').classList.toggle('active', this.value === 'instructor');

    const yearSelect = document.querySelector('[name=year_level]');
    yearSelect.required = this.value === 'student';

    if (this.value === 'student') {
      document.querySelector('#regForm').querySelector('button[type=submit]').textContent = 'Create Student Account';
    } else {
      document.querySelector('#regForm').querySelector('button[type=submit]').textContent = 'Create Instructor Account';
    }
  });
});

<?php if ($_POST): ?>
document.querySelector('input[name=role][value="<?= e($_POST['role']??'student') ?>"]').dispatchEvent(new Event('change'));
<?php endif; ?>
</script>
</body>
</html>


