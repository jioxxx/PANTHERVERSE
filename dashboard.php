<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$user = current_user();
$uid  = current_user_id();

// Personal stats
$bool_true = $GLOBALS['_sql_true'];
$stats = [
    'questions'   => db_count("SELECT COUNT(*) FROM questions WHERE user_id=? AND deleted_at IS NULL", [$uid]),
    'answers'     => db_count("SELECT COUNT(*) FROM answers WHERE user_id=? AND deleted_at IS NULL", [$uid]),
    'accepted'    => db_count("SELECT COUNT(*) FROM answers WHERE user_id=? AND is_accepted=$bool_true AND deleted_at IS NULL", [$uid]),
    'resources'   => db_count("SELECT COUNT(*) FROM resources WHERE user_id=? AND deleted_at IS NULL", [$uid]),
    'groups'      => db_count("SELECT COUNT(*) FROM study_group_members WHERE user_id=?", [$uid]),
    'consultations'=> db_count("SELECT COUNT(*) FROM consultations WHERE student_id=? AND status='approved'", [$uid]),
];

// Rank by reputation
$rank = db_count("SELECT COUNT(*) FROM users WHERE reputation > ? AND is_active=$bool_true", [$user['reputation']]) + 1;

// Recent activity (questions + answers)
$recent_activity = db_rows("
    (SELECT 'question' as type, q.id, q.title as content, q.created_at FROM questions q WHERE q.user_id=? AND q.deleted_at IS NULL ORDER BY q.created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'answer' as type, a.question_id as id, CONCAT('Answered: ', LEFT(q.title,60)) as content, a.created_at FROM answers a JOIN questions q ON a.question_id=q.id WHERE a.user_id=? AND a.deleted_at IS NULL ORDER BY a.created_at DESC LIMIT 5)
    ORDER BY created_at DESC LIMIT 8
", [$uid, $uid]);

// Unread notifications
$notifications = db_rows("SELECT * FROM notifications WHERE user_id=? AND read_at IS NULL ORDER BY created_at DESC LIMIT 5", [$uid]);

// My study groups
$my_groups = db_rows("SELECT sg.*, sgm.role as my_role, COUNT(sgm2.user_id) as member_count FROM study_groups sg JOIN study_group_members sgm ON sg.id=sgm.group_id AND sgm.user_id=? LEFT JOIN study_group_members sgm2 ON sg.id=sgm2.group_id GROUP BY sg.id ORDER BY sg.created_at DESC LIMIT 4", [$uid]);

// Pending consultations (as student)
$pending_consults = db_rows("SELECT c.*, u.name as inst_name FROM consultations c JOIN users u ON c.instructor_id=u.id WHERE c.student_id=? AND c.status IN ('pending','approved') ORDER BY c.preferred_date LIMIT 3", [$uid]);

// Instructor: pending requests count
$incoming_count = 0;
if (in_array($user['role'], ['instructor','admin'])) {
    $incoming_count = db_count("SELECT COUNT(*) FROM consultations WHERE instructor_id=? AND status='pending'", [$uid]);
}

// Upcoming calendar events
$curdate_expr = $GLOBALS['_is_pgsql'] ? "CURRENT_DATE" : "CURDATE()";
$upcoming_events = db_rows("SELECT * FROM calendar_events WHERE event_date >= $curdate_expr AND (campus_id IS NULL OR campus_id=?) ORDER BY event_date ASC LIMIT 5", [$user['campus_id'] ?? 0]);

$type_colors = ['exam'=>'var(--red)','deadline'=>'var(--gold)','holiday'=>'var(--green)','event'=>'var(--purple-l)','class'=>'var(--text-m)','other'=>'var(--text-d)'];
$type_icons  = ['exam'=>'📝','deadline'=>'⏰','holiday'=>'🌴','event'=>'🎉','class'=>'📚','other'=>'📌'];

$page_title = 'Dashboard';
require_once 'includes/header.php';
?>

<div class="page-wrap">

  <!-- Welcome banner -->
  <div style="background:linear-gradient(135deg,#1e1040,#2d1470);border:1px solid var(--border2);border-radius:14px;padding:24px 28px;margin-bottom:24px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:radial-gradient(circle,rgba(244,166,35,0.1),transparent 70%);pointer-events:none;"></div>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
      <img src="<?= avatar_url($user['username']) ?>" style="width:64px;height:64px;border-radius:50%;border:2px solid var(--gold);box-shadow:0 0 20px rgba(244,166,35,0.3);" alt="">
      <div style="flex:1;">
        <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;color:#fff;margin-bottom:2px;">
          Welcome back, <?= e(explode(' ',$user['name'])[0]) ?>! 👋
        </h1>
        <div style="font-size:0.85rem;color:var(--text-m);">
          @<?= e($user['username']) ?>
          <span style="color:var(--text-d);margin:0 6px;">·</span>
          <?= ucfirst($user['role']) ?>
          <?php if($incoming_count > 0): ?>
          <span style="margin-left:10px;background:var(--red);color:#fff;border-radius:20px;padding:1px 8px;font-size:0.75rem;font-weight:700;">
            <?= $incoming_count ?> pending request<?= $incoming_count>1?'s':'' ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-family:'Rajdhani',sans-serif;font-size:1.6rem;font-weight:700;color:var(--gold);">⭐ <?= number_format($user['reputation']) ?></div>
        <div style="font-size:0.75rem;color:var(--text-d);">Rank #<?= $rank ?> on platform</div>
      </div>
    </div>
  </div>

  <!-- Stats grid -->
  <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:24px;">
    <?php foreach([
      ['Questions',  $stats['questions'],    '❓', 'my-questions.php'],
      ['Answers',    $stats['answers'],      '💬', 'questions.php'],
      ['Accepted',   $stats['accepted'],     '✅', 'profile.php?u='.urlencode($user['username'])],
      ['Resources',  $stats['resources'],    '📁', 'resources.php'],
      ['Groups',     $stats['groups'],       '👥', 'study-groups.php'],
      ['Consults',   $stats['consultations'],'📅', 'my-consultations.php'],
    ] as [$lbl,$val,$ic,$href]): ?>
    <a href="<?= $href ?>" class="card" style="padding:14px 10px;text-align:center;text-decoration:none;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor=''">
      <div style="font-size:1.1rem;margin-bottom:4px;"><?= $ic ?></div>
      <div style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;color:var(--gold);"><?= $val ?></div>
      <div style="font-size:0.7rem;color:var(--text-d);"><?= $lbl ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
  <div>

    <!-- Quick Actions -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
      <a href="ask.php"            class="btn-gold"><i class="bi bi-plus-lg"></i> Ask Question</a>
      <a href="consultations.php"  class="btn-purple">📅 Book Consultation</a>
      <a href="study-groups.php"   class="btn-ghost">👥 Study Groups</a>
      <a href="upload-resource.php"class="btn-ghost">📤 Share Resource</a>
      <?php if($incoming_count > 0): ?>
      <a href="my-consultations.php" class="btn-ghost" style="border-color:var(--red);color:var(--red);">📥 <?= $incoming_count ?> Request<?= $incoming_count>1?'s':'' ?></a>
      <?php endif; ?>
    </div>

    <!-- Pending Consultations -->
    <?php if($pending_consults): ?>
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head"><span class="card-title">📅 Upcoming Consultations</span><a href="my-consultations.php" style="font-size:0.8rem;color:var(--gold);">View All →</a></div>
      <div class="card-body" style="padding:0;">
        <?php foreach($pending_consults as $c): ?>
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;">
          <div style="font-size:1.2rem;"><?= $c['status']==='approved'?'✅':'⏳' ?></div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:0.875rem;"><?= e($c['subject']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-d);">with <?= e($c['inst_name']) ?> · <?= date('M j',strtotime($c['preferred_date'])) ?> at <?= date('g:i A',strtotime($c['preferred_time'])) ?></div>
          </div>
          <span style="font-size:0.75rem;font-weight:700;color:<?= $c['status']==='approved'?'var(--green)':'var(--gold)' ?>;"><?= ucfirst($c['status']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <div class="card">
      <div class="card-head"><span class="card-title">⚡ Recent Activity</span></div>
      <div class="card-body" style="padding:0;">
        <?php if($recent_activity): foreach($recent_activity as $act): ?>
        <div style="padding:11px 18px;border-bottom:1px solid rgba(124,58,237,0.1);display:flex;align-items:center;gap:10px;">
          <span style="font-size:1rem;"><?= $act['type']==='question'?'❓':'💬' ?></span>
          <div style="flex:1;min-width:0;">
            <a href="question.php?id=<?= $act['id'] ?>" style="font-size:0.855rem;color:var(--text-m);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;"><?= e($act['content']) ?></a>
          </div>
          <span style="font-size:0.75rem;color:var(--text-d);white-space:nowrap;"><?= time_ago($act['created_at']) ?></span>
        </div>
        <?php endforeach; else: ?>
        <div style="padding:32px;text-align:center;color:var(--text-d);">No activity yet — start asking questions!</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
  <div>

    <!-- Notifications -->
    <?php if($notifications): ?>
    <div class="widget" style="margin-bottom:16px;">
      <div class="widget-head">🔔 Unread Notifications <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 6px;font-size:0.7rem;margin-left:4px;"><?= count($notifications) ?></span></div>
      <div class="widget-body" style="padding:0;">
        <?php $notif_labels=['new_answer'=>'New answer','answer_accepted'=>'Answer accepted','consultation_approved'=>'Consultation ✅','consultation_declined'=>'Consultation ❌','consultation_request'=>'Booking request'];
        foreach($notifications as $n):
          $d = json_decode($n['data'],true);
        ?>
        <div style="padding:10px 14px;border-bottom:1px solid rgba(124,58,237,0.1);font-size:0.8rem;">
          <div style="color:var(--gold);font-weight:600;margin-bottom:2px;"><?= $notif_labels[$n['type']] ?? $n['type'] ?></div>
          <div style="color:var(--text-d);"><?= time_ago($n['created_at']) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="padding:10px 14px;"><a href="notifications.php" style="font-size:0.8rem;color:var(--gold);">Mark all read →</a></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- My Study Groups -->
    <?php if($my_groups): ?>
    <div class="widget" style="margin-bottom:16px;">
      <div class="widget-head">👥 My Study Groups</div>
      <div class="widget-body" style="padding:0;">
        <?php foreach($my_groups as $g): ?>
        <a href="study-group.php?id=<?= $g['id'] ?>" style="display:block;padding:10px 14px;border-bottom:1px solid rgba(124,58,237,0.1);text-decoration:none;">
          <div style="font-weight:600;font-size:0.85rem;color:var(--text);"><?= e($g['name']) ?></div>
          <div style="font-size:0.75rem;color:var(--text-d);"><?= e($g['subject']) ?> · <?= $g['member_count'] ?> members</div>
        </a>
        <?php endforeach; ?>
        <div style="padding:10px 14px;"><a href="study-groups.php" style="font-size:0.8rem;color:var(--gold);">Browse all groups →</a></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Events -->
    <?php if($upcoming_events): ?>
    <div class="widget">
      <div class="widget-head">🗓️ Upcoming Events</div>
      <div class="widget-body" style="padding:0;">
        <?php foreach($upcoming_events as $ev): ?>
        <div style="padding:10px 14px;border-bottom:1px solid rgba(124,58,237,0.1);display:flex;gap:10px;align-items:flex-start;">
          <div style="font-size:1.1rem;margin-top:1px;"><?= $type_icons[$ev['event_type']] ?></div>
          <div>
            <div style="font-size:0.83rem;font-weight:600;color:var(--text);"><?= e($ev['title']) ?></div>
            <div style="font-size:0.75rem;color:<?= $type_colors[$ev['event_type']] ?>;margin-top:2px;"><?= date('M j', strtotime($ev['event_date'])) ?> · <?= ucfirst($ev['event_type']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <div style="padding:10px 14px;"><a href="calendar.php" style="font-size:0.8rem;color:var(--gold);">Full calendar →</a></div>
      </div>
    </div>
    <?php endif; ?>

  </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
