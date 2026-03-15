<?php
// my-consultations.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$is_instructor = in_array(current_user_role(), ['instructor','admin']);
$tab = $_GET['tab'] ?? ($is_instructor ? 'incoming' : 'mine');

// Handle instructor response
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['respond'])) {
    csrf_check(); require_role('instructor','admin');
    $cid    = (int)$_POST['consultation_id'];
    $status = in_array($_POST['status'],['approved','declined']) ? $_POST['status'] : 'declined';
    $note   = trim($_POST['instructor_note'] ?? '');
    $consult= db_row("SELECT * FROM consultations WHERE id=? AND instructor_id=?", [$cid, current_user_id()]);
    if ($consult) {
        db_exec("UPDATE consultations SET status=?, instructor_note=?, updated_at=NOW() WHERE id=?", [$status,$note,$cid]);
        send_notification($consult['student_id'], 'consultation_'.$status, [
            'instructor' => current_user()['username'],
            'subject'    => $consult['subject'],
            'note'       => $note,
        ]);
        flash('success', 'Request ' . $status . '.');
    }
    redirect('my-consultations.php?tab=incoming');
}

// Handle student cancel
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cancel'])) {
    csrf_check();
    $cid = (int)$_POST['consultation_id'];
    db_exec("UPDATE consultations SET status='cancelled',updated_at=NOW() WHERE id=? AND student_id=? AND status='pending'", [$cid,current_user_id()]);
    flash('success','Consultation cancelled.');
    redirect('my-consultations.php');
}

// Handle mark complete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['complete'])) {
    csrf_check();
    $cid = (int)$_POST['consultation_id'];
    db_exec("UPDATE consultations SET status='completed',updated_at=NOW() WHERE id=? AND (student_id=? OR instructor_id=?)", [$cid,current_user_id(),current_user_id()]);
    redirect('my-consultations.php?tab='.($is_instructor?'incoming':'mine'));
}

// Fetch data
if ($is_instructor) {
    $incoming = db_rows("SELECT c.*,u.name as student_name,u.username as student_username,u.reputation as student_rep,q.title as q_title FROM consultations c JOIN users u ON c.student_id=u.id LEFT JOIN questions q ON c.question_id=q.id WHERE c.instructor_id=? ORDER BY FIELD(c.status,'pending','approved','declined','completed','cancelled'),c.created_at DESC", [current_user_id()]);
} else {
    $mine = db_rows("SELECT c.*,u.name as inst_name,u.username as inst_username,q.title as q_title FROM consultations c JOIN users u ON c.instructor_id=u.id LEFT JOIN questions q ON c.question_id=q.id WHERE c.student_id=? ORDER BY c.created_at DESC", [current_user_id()]);
}

$status_colors = ['pending'=>'var(--gold)','approved'=>'var(--green)','declined'=>'var(--red)','completed'=>'var(--purple-l)','cancelled'=>'var(--text-d)'];
$status_icons  = ['pending'=>'⏳','approved'=>'✅','declined'=>'❌','completed'=>'🎓','cancelled'=>'🚫'];
$days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

$page_title = 'My Consultations';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:860px;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">📅 My Consultations</h1>
    <a href="consultations.php" class="btn-gold">Browse Instructors →</a>
  </div>

  <?php if($is_instructor): ?>
  <div class="tabs" style="margin-bottom:20px;">
    <a href="?tab=incoming"  class="tab-link <?=$tab==='incoming'?'active':''?>">📥 Incoming Requests</a>
    <a href="?tab=manage"    class="tab-link <?=$tab==='manage'?'active':''?>">🗓️ My Availability</a>
  </div>

  <?php if($tab==='manage'): ?>
    <!-- Manage Availability -->
    <?php
    $my_slots = db_rows("SELECT * FROM instructor_availability WHERE user_id=? ORDER BY day_of_week,start_time",[current_user_id()]);
    // Handle add slot
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_slot'])) {
        csrf_check();
        db_insert("INSERT INTO instructor_availability (user_id,day_of_week,start_time,end_time,location,subject,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,1,NOW(),NOW())",
            [current_user_id(),(int)$_POST['day'],$_POST['start'],$_POST['end'],trim($_POST['location']??''),trim($_POST['subject']??'')]);
        redirect('my-consultations.php?tab=manage');
    }
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_slot'])) {
        csrf_check();
        db_exec("DELETE FROM instructor_availability WHERE id=? AND user_id=?",[(int)$_POST['slot_id'],current_user_id()]);
        redirect('my-consultations.php?tab=manage');
    }
    $my_slots = db_rows("SELECT * FROM instructor_availability WHERE user_id=? ORDER BY day_of_week,start_time",[current_user_id()]);
    ?>
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head"><span class="card-title">➕ Add Availability Slot</span></div>
      <div class="card-body">
        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 2fr auto;gap:10px;align-items:end;">
          <?= csrf_field() ?><input type="hidden" name="add_slot" value="1">
          <div class="form-group" style="margin:0;"><label>Day</label>
            <select name="day" style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:8px 10px;font-family:'Nunito',sans-serif;width:100%;outline:none;">
              <?php foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i=>$d): ?>
              <option value="<?=$i?>"><?=$d?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0;"><label>Start</label><input type="time" name="start" required></div>
          <div class="form-group" style="margin:0;"><label>End</label><input type="time" name="end" required></div>
          <div class="form-group" style="margin:0;"><label>Subject</label><input type="text" name="subject" placeholder="e.g. OOP"></div>
          <div class="form-group" style="margin:0;"><label>Location</label><input type="text" name="location" placeholder="Room / Zoom link"></div>
          <button type="submit" class="btn-gold" style="height:38px;">Add</button>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><span class="card-title">🗓️ Your Schedule</span></div>
      <div style="overflow-x:auto;">
        <table class="pv-table">
          <thead><tr><th>Day</th><th>Time</th><th>Subject</th><th>Location</th><th></th></tr></thead>
          <tbody>
          <?php if($my_slots): foreach($my_slots as $sl): ?>
          <tr>
            <td><span style="font-weight:700;color:var(--purple-l);"><?= ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$sl['day_of_week']] ?></span></td>
            <td><?= date('g:i A',strtotime($sl['start_time'])) ?> – <?= date('g:i A',strtotime($sl['end_time'])) ?></td>
            <td><?= e($sl['subject'] ?? '—') ?></td>
            <td style="font-size:0.82rem;color:var(--text-d);"><?= e($sl['location'] ?? '—') ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Remove this slot?')">
                <?= csrf_field() ?><input type="hidden" name="delete_slot" value="1"><input type="hidden" name="slot_id" value="<?= $sl['id'] ?>">
                <button type="submit" class="btn-danger" style="font-size:0.75rem;padding:3px 10px;">Remove</button>
              </form>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="5" style="text-align:center;color:var(--text-d);padding:24px;">No slots added yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php else: // incoming requests ?>
    <?php if(empty($incoming)): ?>
    <div class="empty-state"><div class="empty-icon">📥</div><p>No consultation requests yet.</p></div>
    <?php else: foreach($incoming as $c): ?>
    <div class="card" style="margin-bottom:14px;border-left:3px solid <?= $status_colors[$c['status']] ?>;">
      <div style="padding:16px 18px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
              <img src="<?= avatar_url($c['student_username']) ?>" style="width:30px;height:30px;border-radius:50%;" alt="">
              <a href="profile.php?u=<?= urlencode($c['student_username']) ?>" style="font-weight:700;color:var(--purple-l);">@<?= e($c['student_username']) ?></a>
              <span style="font-size:0.78rem;color:var(--text-d);"><?= time_ago($c['created_at']) ?></span>
            </div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1rem;color:var(--text);margin-bottom:6px;"><?= e($c['subject']) ?></div>
            <p style="font-size:0.875rem;color:var(--text-m);line-height:1.6;margin-bottom:8px;"><?= e($c['message']) ?></p>
            <div style="font-size:0.8rem;color:var(--text-d);">
              📅 <?= date('D, M j Y', strtotime($c['preferred_date'])) ?> at <?= date('g:i A', strtotime($c['preferred_time'])) ?>
              <?php if($c['q_title']): ?> · 🔗 re: <a href="question.php?id=<?= $c['question_id'] ?>" style="color:var(--gold);font-size:0.78rem;"><?= e(mb_substr($c['q_title'],0,50)) ?>...</a><?php endif; ?>
            </div>
          </div>
          <div style="text-align:right;">
            <span style="font-size:0.78rem;font-weight:700;color:<?= $status_colors[$c['status']] ?>;"><?= $status_icons[$c['status']] ?> <?= ucfirst($c['status']) ?></span>
          </div>
        </div>

        <?php if($c['instructor_note']): ?>
        <div style="margin-top:12px;background:rgba(124,58,237,0.08);border-left:2px solid var(--purple);padding:10px 12px;border-radius:0 8px 8px 0;font-size:0.83rem;color:var(--text-m);">
          <strong style="color:var(--text);">Your note:</strong> <?= e($c['instructor_note']) ?>
        </div>
        <?php endif; ?>

        <?php if($c['status']==='pending'): ?>
        <form method="POST" style="margin-top:14px;background:rgba(124,58,237,0.06);border-radius:8px;padding:14px;">
          <?= csrf_field() ?><input type="hidden" name="respond" value="1"><input type="hidden" name="consultation_id" value="<?= $c['id'] ?>">
          <div class="form-group" style="margin-bottom:10px;">
            <label style="font-size:0.82rem;">Reply Message (optional — include meeting details if approving)</label>
            <textarea name="instructor_note" rows="3" placeholder="e.g. See you at CS Lab 2. Bring your notes!"></textarea>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" name="status" value="approved" class="btn-gold">✅ Approve</button>
            <button type="submit" name="status" value="declined" class="btn-danger">❌ Decline</button>
          </div>
        </form>
        <?php elseif($c['status']==='approved'): ?>
        <form method="POST" style="margin-top:10px;">
          <?= csrf_field() ?><input type="hidden" name="complete" value="1"><input type="hidden" name="consultation_id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn-ghost btn-sm">🎓 Mark as Completed</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; endif; ?>
  <?php endif; ?>

  <?php else: // Student view ?>
    <?php if(empty($mine)): ?>
    <div class="empty-state"><div class="empty-icon">📅</div><p>No consultation requests yet.</p><a href="consultations.php" class="btn-gold">Find an Instructor</a></div>
    <?php else: foreach($mine as $c): ?>
    <div class="card" style="margin-bottom:14px;border-left:3px solid <?= $status_colors[$c['status']] ?>;">
      <div style="padding:16px 18px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
              <img src="<?= avatar_url($c['inst_username']) ?>" style="width:28px;height:28px;border-radius:50%;" alt="">
              <span style="font-weight:700;color:var(--purple-l);"><?= e($c['inst_name']) ?></span>
              <span style="font-size:0.75rem;color:var(--text-d);"><?= time_ago($c['created_at']) ?></span>
            </div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1rem;margin-bottom:4px;"><?= e($c['subject']) ?></div>
            <div style="font-size:0.8rem;color:var(--text-d);">📅 <?= date('D, M j Y',strtotime($c['preferred_date'])) ?> at <?= date('g:i A',strtotime($c['preferred_time'])) ?></div>
          </div>
          <span style="font-size:0.82rem;font-weight:700;color:<?= $status_colors[$c['status']] ?>;"><?= $status_icons[$c['status']] ?> <?= ucfirst($c['status']) ?></span>
        </div>
        <?php if($c['instructor_note']): ?>
        <div style="margin-top:12px;background:rgba(16,185,129,0.08);border-left:2px solid var(--green);padding:10px 12px;border-radius:0 8px 8px 0;font-size:0.83rem;color:var(--text-m);">
          <strong style="color:var(--text);">Instructor's reply:</strong> <?= e($c['instructor_note']) ?>
        </div>
        <?php endif; ?>
        <?php if($c['status']==='pending'): ?>
        <form method="POST" style="margin-top:10px;">
          <?= csrf_field() ?><input type="hidden" name="cancel" value="1"><input type="hidden" name="consultation_id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Cancel this request?')">Cancel Request</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; endif; ?>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
