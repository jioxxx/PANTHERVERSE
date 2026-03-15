<?php
// messages.php — inbox
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$uid = current_user_id();

// All conversations (grouped by other person)
$conversations = db_rows("
    SELECT
        u.id, u.name, u.username, u.reputation,
        m.body AS last_message,
        m.created_at AS last_at,
        m.sender_id AS last_sender,
        SUM(CASE WHEN m2.is_read=0 AND m2.receiver_id=? THEN 1 ELSE 0 END) AS unread
    FROM users u
    JOIN messages m ON (m.sender_id=u.id AND m.receiver_id=?)
                    OR (m.receiver_id=u.id AND m.sender_id=?)
    LEFT JOIN messages m2 ON ((m2.sender_id=u.id AND m2.receiver_id=?)
                           OR (m2.receiver_id=u.id AND m2.sender_id=?))
                          AND m2.is_read=0 AND m2.receiver_id=?
    WHERE u.id != ?
    GROUP BY u.id
    ORDER BY m.created_at DESC
", [$uid,$uid,$uid,$uid,$uid,$uid,$uid]);

// Deduplicate (keep latest per user)
$seen = []; $convs = [];
foreach($conversations as $c) { if(!isset($seen[$c['id']])) { $seen[$c['id']]=true; $convs[]=$c; } }

$total_unread = db_count("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0", [$uid]);

$page_title = 'Messages';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:800px;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
      <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;">💬 Messages</h1>
      <?php if($total_unread): ?><p style="font-size:0.82rem;color:var(--gold);"><?= $total_unread ?> unread message<?= $total_unread>1?'s':'' ?></p><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <form method="GET" action="message.php" style="display:flex;gap:6px;">
        <input type="text" name="to" placeholder="Username to message..." style="background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:7px 12px;font-size:0.85rem;font-family:'Nunito',sans-serif;outline:none;">
        <button type="submit" class="btn-purple">New</button>
      </form>
    </div>
  </div>

  <?php if(empty($convs)): ?>
  <div class="empty-state"><div class="empty-icon">💬</div><p>No messages yet. Start a conversation!</p></div>
  <?php else: ?>
  <div class="card">
    <?php foreach($convs as $c): ?>
    <a href="message.php?with=<?= urlencode($c['username']) ?>" style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border);text-decoration:none;transition:background 0.12s;<?= $c['unread']>0?'background:rgba(124,58,237,0.05);':'' ?>"
       onmouseover="this.style.background='rgba(124,58,237,0.06)'" onmouseout="this.style.background='<?= $c['unread']>0?'rgba(124,58,237,0.05)':'transparent' ?>'">
      <div style="position:relative;">
        <img src="<?= avatar_url($c['username']) ?>" style="width:44px;height:44px;border-radius:50%;flex-shrink:0;" alt="">
        <?php if($c['unread']>0): ?><span style="position:absolute;top:-2px;right:-2px;background:var(--red);color:#fff;border-radius:50%;width:16px;height:16px;font-size:0.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?= $c['unread'] ?></span><?php endif; ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
          <span style="font-weight:700;font-size:0.9rem;color:var(--text);"><?= e($c['name']) ?></span>
          <span style="font-size:0.75rem;color:var(--text-d);">@<?= e($c['username']) ?></span>
        </div>
        <div style="font-size:0.83rem;color:var(--text-d);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= $c['last_sender']==$uid?'<span style="color:var(--text-d);">You: </span>':'' ?><?= e(mb_substr($c['last_message'],0,60)) ?>
        </div>
      </div>
      <div style="font-size:0.75rem;color:var(--text-d);white-space:nowrap;flex-shrink:0;"><?= time_ago($c['last_at']) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
