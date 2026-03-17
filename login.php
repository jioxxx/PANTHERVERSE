<?php
session_start();
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
:root{--bg:#0e0720;--surface:#1a0e38;--surface2:#231550;--border:rgba(124,58,237,0.3);--purple:#7c3aed;--purple-l:#9d5cf6;--gold:#f4a623;--text:#e8dff8;--text-m:#a78bca;--text-d:#6b4fa0;--red:#ef4444;--green:#10b981;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Nunito',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
background-image:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(124,58,237,0.2) 0%,transparent 60%);}
.auth-wrap{width:100%;max-width:420px;}
.auth-logo{text-align:center;margin-bottom:28px;}
.auth-logo .ring{width:80px;height:80px;border-radius:50%;border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;background:rgba(124,58,237,0.15);box-shadow:0 0 24px rgba(244,166,35,0.25);}
.auth-logo .ring img{width:68px;height:68px;border-radius:50%;object-fit:cover;}
.auth-logo .ring .lf{font-size:2.5rem;display:none;}
.auth-logo h1{font-family:'Rajdhani',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;}
.auth-logo h1 span{color:var(--gold);}
.auth-logo p{font-size:0.85rem;color:var(--text-d);margin-top:4px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px;}
.card h2{font-family:'Rajdhani',sans-serif;font-size:1.3rem;font-weight:700;margin-bottom:20px;color:var(--text);}
.fg{margin-bottom:16px;}
.fg label{display:block;font-size:0.85rem;font-weight:600;color:var(--text-m);margin-bottom:5px;}
.fg input{width:100%;background:rgba(124,58,237,0.08);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:9px 12px;font-size:0.9rem;font-family:'Nunito',sans-serif;outline:none;transition:border-color 0.2s;}
.fg input:focus{border-color:var(--purple);}
.fg input::placeholder{color:var(--text-d);}
.btn-gold{background:linear-gradient(135deg,var(--gold),#d97706);color:#1a0e38;font-weight:700;font-size:0.95rem;border:none;border-radius:8px;padding:10px;width:100%;cursor:pointer;transition:all 0.15s;font-family:'Nunito',sans-serif;}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(244,166,35,0.4);}
.alert-error{background:rgba(239,68,68,0.1);border-left:3px solid var(--red);color:#fca5a5;padding:9px 12px;border-radius:7px;font-size:0.875rem;margin-bottom:14px;}
.divider{text-align:center;color:var(--text-d);font-size:0.82rem;margin:16px 0;}
.link-row{text-align:center;font-size:0.875rem;color:var(--text-d);margin-top:16px;}
.link-row a{color:var(--gold);font-weight:600;}
.demo-box{background:rgba(244,166,35,0.06);border:1px solid rgba(244,166,35,0.2);border-radius:8px;padding:12px;margin-bottom:16px;font-size:0.8rem;color:var(--text-m);}
.demo-box strong{color:var(--gold);display:block;margin-bottom:6px;}
.demo-box span{display:block;margin-bottom:2px;}
@media(max-width: 480px) {
  .card{padding:20px;}
  .auth-logo h1{font-size:1.6rem;}
  .auth-wrap{padding:0 10px;}
}
</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-logo">
    <div class="ring">
      <img src="/assets/logo.png" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span class="lf">🐆</span>
    </div>
    <h1>PANTHER<span>VERSE</span></h1>
    <p>JRMSU Academic Community Platform</p>
  </div>

  <div class="card">
    <h2>Welcome back 👋</h2>

    <?php if ($error): ?>
    <div class="alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- Demo credentials hint -->
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
</div>
</body>
</html>
