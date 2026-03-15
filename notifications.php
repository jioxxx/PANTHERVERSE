<?php
// notifications.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

// Mark all read
db_exec("UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL", [current_user_id()]);

$notifs = db_rows("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50", [current_user_id()]);
$page_title = 'Notifications';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:700px;">
  <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:20px;"><i class="bi bi-bell"></i> Notifications</h1>
  <?php if($notifs): foreach($notifs as $n):
    $d = json_decode($n['data'], true);
    $icon = match($n['type']) { 
        'new_answer'      => '<i class="bi bi-chat-left-text"></i>',
        'answer_accepted' => '<i class="bi bi-check-circle-fill" style="color:var(--green);"></i>',
        'new_comment'     => '<i class="bi bi-chat-dots"></i>',
        'badge_earned'    => '<i class="bi bi-award" style="color:var(--gold);"></i>',
        default           => '<i class="bi bi-bell"></i>' 
    };
    $msg = match($n['type']) {
      'new_answer'     => ($d['answerer']??'Someone').' answered your question: '.($d['question_title']??''),
      'answer_accepted'=> 'Your answer was accepted on: '.($d['question_title']??''),
      'new_comment'    => ($d['commenter_name']??'Someone').' commented: '.($d['comment_body']??''),
      'badge_earned'   => 'You earned the badge: '.($d['badge_name']??''),
      default          => 'New notification',
    };
    $link = match($n['type']) {
      'new_answer','answer_accepted' => 'question.php?id='.($d['question_id']??''),
      default => '#'
    };
  ?>
  <div class="card" style="margin-bottom:8px;padding:12px 16px;display:flex;align-items:flex-start;gap:12px;<?= $n['read_at']?'opacity:0.7':'' ?>">
    <span style="font-size:1.1rem;margin-top:2px;color:var(--text-d);"><?= $icon ?></span>
    <div style="flex:1;">
      <div style="font-size:0.875rem;color:var(--text);"><?= e($msg) ?></div>
      <div style="font-size:0.75rem;color:var(--text-d);margin-top:4px;"><?= time_ago($n['created_at']) ?></div>
    </div>
    <?php if($link !== '#'): ?>
    <a href="<?= $link ?>" class="btn-ghost btn-sm">View →</a>
    <?php endif; ?>
  </div>
  <?php endforeach; else: ?>
  <div class="empty-state"><div class="empty-icon"><i class="bi bi-bell"></i></div><p>No notifications yet.</p></div>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
