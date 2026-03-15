<?php
// message.php — single conversation
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$uid = current_user_id();

// Get the other user
$with_username = trim($_GET['with'] ?? $_POST['with'] ?? '');
$to_username   = trim($_GET['to'] ?? '');
if ($to_username && !$with_username) $with_username = $to_username;

$other = $with_username ? db_row("SELECT * FROM users WHERE username=? AND id!=?", [$with_username, $uid]) : null;

// Send message
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send'])) {
    csrf_check();
    $body = trim($_POST['body'] ?? '');
    $rid  = (int)$_POST['receiver_id'];
    if (!$body) $error = 'Message cannot be empty.';
    elseif (!db_row("SELECT 1 FROM users WHERE id=? AND is_active=1", [$rid])) $error = 'User not found.';
    else {
        db_insert("INSERT INTO messages (sender_id,receiver_id,body,is_read,created_at,updated_at) VALUES (?,?,?,0,NOW(),NOW())", [$uid,$rid,$body]);
        // notification
        send_notification($rid,'new_message',['from'=>current_user()['username'],'preview'=>mb_substr($body,0,60)]);
        redirect("message.php?with=".urlencode($with_username));
    }
}

// Mark messages as read
if ($other) {
    db_exec("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?", [$other['id'],$uid]);
}

// Get thread
$messages = $other ? db_rows("
    SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id=u.id
    WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)
    ORDER BY m.created_at ASC
", [$uid,$other['id'],$other['id'],$uid]) : [];

$page_title = $other ? 'Chat with @'.e($other['username']) : 'New Message';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:740px;">
  <nav style="font-size:0.8rem;color:var(--text-d);margin-bottom:14px;">
    <a href="messages.php" style="color:var(--text-d);">← Messages</a>
  </nav>

  <?php if(!$other && !$with_username): ?>
  <!-- New message - user search -->
  <div class="card"><div class="card-head"><span class="card-title">💬 New Message</span></div>
  <div class="card-body">
    <form method="GET">
      <div class="form-group"><label>Send to (username)</label>
        <input type="text" name="to" placeholder="Enter username..." autofocus required>
      </div>
      <button type="submit" class="btn-gold">Start Conversation</button>
    </form>
  </div></div>

  <?php elseif(!$other): ?>
  <div class="alert alert-error">User "@<?= e($with_username) ?>" not found.</div>
  <a href="messages.php" class="btn-ghost">← Back</a>

  <?php else: ?>
  <!-- Conversation header -->
  <div class="card" style="margin-bottom:16px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
    <img src="<?= avatar_url($other['username']) ?>" style="width:42px;height:42px;border-radius:50%;border:2px solid var(--border);" alt="">
    <div style="flex:1;">
      <div style="font-weight:700;font-family:'Rajdhani',sans-serif;font-size:1.05rem;"><?= e($other['name']) ?></div>
      <div style="font-size:0.78rem;color:var(--text-d);">@<?= e($other['username']) ?> · <?= ucfirst($other['role']) ?></div>
    </div>
    <a href="profile.php?u=<?= urlencode($other['username']) ?>" class="btn-ghost btn-sm">View Profile</a>
  </div>

  <!-- Messages -->
  <div id="msg-thread" style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;max-height:500px;overflow-y:auto;padding:4px 2px;">
    <?php if(empty($messages)): ?>
    <div style="text-align:center;color:var(--text-d);padding:32px;font-size:0.875rem;">No messages yet. Say hello! 👋</div>
    <?php else: foreach($messages as $m):
      $is_mine = $m['sender_id'] == $uid;
    ?>
    <div style="display:flex;<?= $is_mine?'justify-content:flex-end':'justify-content:flex-start' ?>;gap:8px;align-items:flex-end;">
      <?php if(!$is_mine): ?>
      <img src="<?= avatar_url($other['username']) ?>" style="width:28px;height:28px;border-radius:50%;flex-shrink:0;" alt="">
      <?php endif; ?>
      <div style="max-width:70%;">
        <div style="background:<?= $is_mine?'var(--purple)':'var(--surface2)' ?>;border-radius:<?= $is_mine?'14px 14px 4px 14px':'14px 14px 14px 4px' ?>;padding:10px 14px;font-size:0.875rem;line-height:1.55;color:<?= $is_mine?'#fff':'var(--text)' ?>;word-break:break-word;">
          <?= nl2br(e($m['body'])) ?>
        </div>
        <div style="font-size:0.7rem;color:var(--text-d);margin-top:3px;text-align:<?= $is_mine?'right':'left' ?>;">
          <?= time_ago($m['created_at']) ?>
          <?php if($is_mine): ?><?= $m['is_read']?'· ✓ Read':'· Sent' ?><?php endif; ?>
        </div>
      </div>
      <?php if($is_mine): ?>
      <img src="<?= avatar_url(current_user()['username']) ?>" style="width:28px;height:28px;border-radius:50%;flex-shrink:0;" alt="">
      <?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Reply box -->
  <?php if($error): ?><div class="alert alert-error" style="margin-bottom:10px;"><?= e($error) ?></div><?php endif; ?>
  <div class="card" style="padding:14px;">
    <form method="POST" style="display:flex;gap:10px;align-items:flex-end;">
      <?= csrf_field() ?>
      <input type="hidden" name="send" value="1">
      <input type="hidden" name="with" value="<?= e($other['username']) ?>">
      <input type="hidden" name="receiver_id" value="<?= $other['id'] ?>">
      <textarea name="body" rows="2" placeholder="Type a message..." style="flex:1;background:var(--bg3);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:10px;font-size:0.875rem;font-family:'Nunito',sans-serif;outline:none;resize:none;" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.closest('form').submit();}"></textarea>
      <button type="submit" class="btn-gold" style="height:42px;padding:0 16px;">Send ↑</button>
    </form>
    <div style="font-size:0.72rem;color:var(--text-d);margin-top:5px;">Enter to send · Shift+Enter for new line</div>
  </div>
  <?php endif; ?>
</div>
<script>
// Auto-scroll to bottom of thread
const thread = document.getElementById('msg-thread');
if (thread) thread.scrollTop = thread.scrollHeight;
</script>
<?php require_once 'includes/footer.php'; ?>
