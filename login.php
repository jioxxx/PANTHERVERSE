<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (is_logged_in()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $bool_true = $GLOBALS['_sql_true'];
        $user = db_row("SELECT * FROM users WHERE email = ? AND is_active = $bool_true", [$email]);
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            db_exec("UPDATE users SET last_seen_at = NOW() WHERE id = ?", [$user['id']]);
            $redir = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redir);
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Login — PANTHERVERSE</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#08041a;--bg2:#0d0724;--surface:#130930;--surface2:#1c1045;--border:rgba(124,58,237,0.25);--purple:#7c3aed;--purple-l:#a855f7;--purple-d:#5b21b6;--gold:#f4a623;--gold-l:#fbbf24;--text:#ede9f8;--text-m:#b89fd8;--text-d:#6b5a8a;--red:#ef4444;--radius:14px;--shadow:0 20px 70px rgba(0,0,0,0.45);}
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
  height:auto;
  border-radius:20px;
  overflow:hidden;
  box-shadow:var(--shadow);
  border:1px solid rgba(124,58,237,0.22);
  background: rgba(19,9,48,0.72);
  display:grid;
  grid-template-columns: 1fr 440px;
}
@media(max-width: 900px){
  .auth-shell{grid-template-columns:1fr;}
}
.left-hero{
  position:relative;
  min-height: 560px;
  background:
    radial-gradient(ellipse 80% 80% at 30% 10%, rgba(124,58,237,0.35) 0%, transparent 60%),
    radial-gradient(ellipse 60% 50% at 85% 40%, rgba(244,166,35,0.12) 0%, transparent 60%),
    linear-gradient(160deg, rgba(90,33,182,0.35) 0%, rgba(13,7,36,0.2) 45%, rgba(8,4,26,0.5) 100%);
}
.left-hero::before{
  content:'';
  position:absolute; inset:0;
  background-image: url('<?= BASE_PATH ?>/assets/logo.png');
  background-repeat:no-repeat;
  background-position: center 70%;
  background-size: 180px 180px;
  opacity:0.08;
  pointer-events:none;
}
.left-photo{
  position:absolute;
  inset: 0;
  background-image: url('<?= BASE_PATH ?>/assets/hero_bg.png');
  background-size: cover;
  background-position: center;
  filter: saturate(1.1) contrast(1.05);
}
.left-photo::after{
  content:'';
  position:absolute; inset:0;
  background:
    linear-gradient(90deg, rgba(8,4,26,0.95) 0%, rgba(8,4,26,0.55) 40%, rgba(8,4,26,0.85) 100%),
    linear-gradient(135deg, rgba(124,58,237,0.40) 0%, rgba(244,166,35,0.18) 55%, rgba(13,7,36,0.25) 100%);
}
.left-content{
  position:relative;
  z-index:2;
  padding:34px 34px;
  height:100%;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
}
.left-top{
  display:flex;
  align-items:center;
  gap:14px;
}
.brand-ring{
  width:72px; height:72px;
  border-radius:50%;
  border:2px solid rgba(244,166,35,0.6);
  background: rgba(124,58,237,0.15);
  box-shadow: 0 0 30px rgba(244,166,35,0.22);
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0;
  animation: glow-pulse 3s ease-in-out infinite;
}
.brand-ring img{width:56px; height:56px; border-radius:50%; object-fit:cover;}
.brand-title{font-family:'Rajdhani',sans-serif; font-size:1.7rem; font-weight:700; line-height:1.1;}
.brand-title span{color: var(--gold); text-shadow: 0 0 24px rgba(244,166,35,0.35);}
.left-copy h1{
  font-family:'Rajdhani',sans-serif;
  font-size: 2.4rem;
  line-height:1.05;
  margin-bottom:10px;
}
.left-copy h1 span{color:var(--gold);}
.left-copy p{color:rgba(237,233,248,0.85); font-size:0.95rem; line-height:1.65; max-width:420px;}
.left-badges{display:flex; flex-wrap:wrap; gap:10px; margin-top:18px;}
.pill{
  background: rgba(124,58,237,0.14);
  border: 1px solid rgba(124,58,237,0.25);
  color: var(--purple-l);
  padding: 8px 12px;
  border-radius: 999px;
  font-weight:700;
  font-size:0.8rem;
}
.pill.gold{background: rgba(244,166,35,0.10); border-color: rgba(244,166,35,0.25); color: var(--gold-l);}
.left-foot{
  color: rgba(237,233,248,0.75);
  font-size:0.85rem;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
}
.left-foot .stamp{display:flex; gap:10px; align-items:center;}
.stamp-dot{width:10px; height:10px; border-radius:50%; background: var(--gold); box-shadow:0 0 14px rgba(244,166,35,0.4);}
@keyframes glow-pulse{0%,100%{box-shadow:0 0 8px rgba(124,58,237,0.5),0 0 16px rgba(124,58,237,0.1);}50%{box-shadow:0 0 16px rgba(124,58,237,0.5),0 0 32px rgba(124,58,237,0.2);}}

.right-panel{
  background: rgba(19,9,48,0.92);
  padding:34px 28px;
  display:flex;
  flex-direction:column;
  justify-content:center;
}
.right-panel .panel-logo{display:flex; align-items:center; justify-content:center; margin-bottom:18px;}
.right-panel .panel-logo img{width:64px; height:64px; border-radius:50%; box-shadow:0 0 26px rgba(124,58,237,0.35);}
.right-panel h2{font-family:'Rajdhani',sans-serif; font-size:1.55rem; color:#fff; margin-bottom:6px; text-align:center;}
.right-panel p{color:var(--text-d); font-size:0.9rem; text-align:center; margin-bottom:20px;}
.form-card{
  border:1px solid rgba(124,58,237,0.22);
  border-radius: var(--radius);
  background: rgba(13,7,36,0.55);
  padding:22px;
}
.err{background:rgba(239,68,68,0.10); border-left:3px solid var(--red); color:#fca5a5; padding:10px 12px; border-radius:8px; font-size:0.9rem; margin-bottom:14px;}
.demo-box{background:rgba(244,166,35,0.08); border:1px solid rgba(244,166,35,0.18); border-radius:12px; padding:12px; margin-bottom:16px; font-size:0.82rem; color:var(--text-m);}
.demo-box strong{color:var(--gold); display:block; margin-bottom:6px;}
.demo-box span{display:block; margin-bottom:2px;}
.fg{margin-bottom:14px;}
.fg label{display:block; font-size:0.85rem; font-weight:700; color:var(--text-m); margin-bottom:6px;}
.fg input{width:100%; background: rgba(124,58,237,0.08); border:1px solid rgba(124,58,237,0.32); border-radius:10px; color:var(--text); padding:10px 12px; font-size:0.92rem; font-family:'Nunito',sans-serif; outline:none; transition:border-color 0.2s, box-shadow 0.2s;}
.fg input:focus{border-color: var(--gold-l); box-shadow:0 0 0 3px rgba(244,166,35,0.12);} 
.fg input::placeholder{color:var(--text-d);}
.btn-gold{background:linear-gradient(135deg,var(--gold-l),var(--gold)); color:#1a0e38; font-weight:900; font-size:0.95rem; border:none; border-radius:10px; padding:11px 14px; width:100%; cursor:pointer; transition:all 0.15s; font-family:'Nunito',sans-serif; box-shadow:0 10px 26px rgba(244,166,35,0.18);}
.btn-gold:hover{transform:translateY(-1px); box-shadow:0 18px 44px rgba(244,166,35,0.28);} 
.link-row{text-align:center; font-size:0.9rem; color:var(--text-d); margin-top:16px;}
.link-row a{color:var(--gold); font-weight:800;}
@media(max-width: 900px){
  .left-hero{min-height: 340px;}
  .right-panel{padding:26px 18px;}
  .auth-shell{border-radius:18px;}
}
</style>
</head>
<body>
  <div class="auth-shell">
    <section class="left-hero" aria-hidden="true">
      <div class="left-photo"></div>
      <div class="left-content">
        <div class="left-top">
          <div class="brand-ring">
            <img src="<?= BASE_PATH ?>/assets/logo.png" alt="PANTHERVERSE">
          </div>
          <div class="brand-title">PANTHER<span>VERSE</span></div>
        </div>

        <div class="left-copy">
          <h1>Welcome to <span>Computing Studies</span></h1>
          <p>Login to access student resources, Q&amp;A, announcements, and community learning spaces.</p>
          <div class="left-badges">
            <span class="pill">Purple UI</span>
            <span class="pill gold">Gold Accents</span>
            <span class="pill">JRMSU Community</span>
          </div>
        </div>

        <div class="left-foot">
          <div class="stamp"><span class="stamp-dot"></span><span>Panther Minds Connect</span></div>
          <div style="font-weight:800;color:rgba(251,191,36,0.9)">● Secure</div>
        </div>
      </div>
    </section>

    <section class="right-panel">
      <div class="panel-logo">
        <img src="<?= BASE_PATH ?>/assets/logo.png" alt="logo">
      </div>
      <h2>Welcome back 👋</h2>
      <p>Enter your account details to continue</p>

      <div class="form-card">
        <?php if ($error): ?>
        <div class="err"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="demo-box">
          <strong>🔑 Demo Accounts</strong>
          <span>Admin: admin@pantherverse.jrmsu.edu.ph / Admin@12345</span>
          <span>Instructor: msantos@pantherverse.jrmsu.edu.ph / Instructor@12345</span>
          <span>Student: juan.delacruz@pantherverse.jrmsu.edu.ph / Student@12345</span>
        </div>

        <form method="POST" action="login.php">
          <?= csrf_field() ?>
          <div class="fg">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="yourname@jrmsu.edu.ph" required autofocus>
          </div>
          <div class="fg">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn-gold">Login to PANTHERVERSE</button>
        </form>

        <div class="link-row">Don't have an account? <a href="register.php">Join PANTHERVERSE</a></div>
      </div>
    </section>
  </div>
</body>
</html>

