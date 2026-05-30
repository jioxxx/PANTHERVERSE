<?php
// notifications.php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

// Mark all read
db_exec("UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL", [current_user_id()]);

$notifs    = db_rows("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50", [current_user_id()]);
$page_title = 'Notifications';
require_once 'includes/header.php';
?>
<style>
.notif-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 10px;
    transition: all 0.2s;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}
.notif-card::before {
    content: '';
    position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
    background: var(--purple);
    opacity: 0;
    transition: opacity 0.2s;
}
.notif-card:hover { border-color: rgba(124,58,237,0.4); transform: translateX(3px); box-shadow: 0 4px 20px rgba(124,58,237,0.1); }
.notif-card:hover::before { opacity: 1; }
.notif-card.unread { border-color: rgba(124,58,237,0.3); background: rgba(124,58,237,0.05); }
.notif-card.unread::before { opacity: 1; }

.notif-icon {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.notif-icon.answer   { background: rgba(124,58,237,0.15); color: var(--purple-l); }
.notif-icon.accepted { background: rgba(16,185,129,0.15); color: var(--green); }
.notif-icon.comment  { background: rgba(244,166,35,0.15); color: var(--gold); }
.notif-icon.badge    { background: rgba(244,166,35,0.2);  color: var(--gold); }
.notif-icon.follow   { background: rgba(168,85,247,0.15); color: var(--purple-l); }
.notif-icon.default  { background: rgba(107,90,138,0.15); color: var(--text-d); }

.notif-body { flex: 1; min-width: 0; }
.notif-msg { font-size: 0.88rem; color: var(--text); line-height: 1.5; }
.notif-time { font-size: 0.75rem; color: var(--text-d); margin-top: 4px; }
</style>

<div class="page-wrap" style="max-width:720px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
        <div>
            <h1 style="font-size:1.8rem;font-weight:700;background:linear-gradient(135deg,#fff,var(--purple-l));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                Notifications
            </h1>
            <p style="color:var(--text-d);font-size:0.85rem;margin-top:4px;">
                <?= count($notifs) ?> notification<?= count($notifs)!=1?'s':'' ?> · All marked as read
            </p>
        </div>
        <a href="settings/notifications.php" class="btn-ghost btn-sm">
            <i class="bi bi-gear"></i> Preferences
        </a>
    </div>

    <?php if($notifs): foreach($notifs as $n):
        $d    = json_decode($n['data'], true) ?: [];
        $read = !empty($n['read_at']);

        $iconClass = match($n['type']) {
            'new_answer'      => 'answer',
            'answer_accepted' => 'accepted',
            'new_comment'     => 'comment',
            'badge_earned'    => 'badge',
            'new_follower'    => 'follow',
            'content_liked'   => 'badge',
            default           => 'default'
        };
        $icon = match($n['type']) {
            'new_answer'      => '<i class="bi bi-chat-left-text"></i>',
            'answer_accepted' => '<i class="bi bi-check-circle-fill"></i>',
            'new_comment'     => '<i class="bi bi-chat-dots"></i>',
            'badge_earned'    => '<i class="bi bi-award-fill"></i>',
            'new_follower'    => '<i class="bi bi-person-plus-fill"></i>',
            'content_liked'   => '<i class="bi bi-heart-fill"></i>',
            default           => '<i class="bi bi-bell-fill"></i>'
        };
        $msg = match($n['type']) {
            'new_answer'      => '<strong>'.e($d['answerer']??'Someone').'</strong> answered your question: <em>'.e(mb_substr($d['question_title']??'',0,60)).'</em>',
            'answer_accepted' => 'Your answer was accepted on: <em>'.e(mb_substr($d['question_title']??'',0,60)).'</em>',
            'new_comment'     => '<strong>'.e($d['commenter_name']??'Someone').'</strong> commented: <em>'.e(mb_substr($d['comment_body']??'',0,80)).'</em>',
            'badge_earned'    => '🏅 You earned the badge: <strong>'.e($d['badge_name']??'').'</strong>',
            'new_follower'    => '<strong>'.e($d['follower_name']??'Someone').'</strong> started following you',
            'content_liked'   => '<strong>'.e($d['liker_name']??'Someone').'</strong> liked your '.e($d['content_type']??'post'),
            default           => 'New notification',
        };
        $link = match($n['type']) {
            'new_answer', 'answer_accepted' => 'question.php?id='.($d['question_id']??''),
            'new_follower' => 'profile.php?u='.urlencode($d['follower_name']??''),
            default => ''
        };
    ?>
    <div class="notif-card <?= $read ? '' : 'unread' ?>" <?= $link ? 'onclick="window.location=\''.e($link).'\'" style="cursor:pointer;"' : '' ?>>
        <div class="notif-icon <?= $iconClass ?>"><?= $icon ?></div>
        <div class="notif-body">
            <div class="notif-msg"><?= $msg ?></div>
            <div class="notif-time"><i class="bi bi-clock" style="font-size:0.7rem;"></i> <?= time_ago($n['created_at']) ?></div>
        </div>
        <?php if($link): ?>
        <a href="<?= e($link) ?>" class="btn-ghost btn-sm" onclick="event.stopPropagation()">View →</a>
        <?php endif; ?>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state">
        <div class="empty-icon">🔔</div>
        <h3>All caught up!</h3>
        <p>You don't have any notifications yet. When someone answers your questions or follows you, it will show up here.</p>
        <a href="questions.php" class="btn-gold">Browse Questions</a>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
