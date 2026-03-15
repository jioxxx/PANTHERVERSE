<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$subject_filter = trim($_GET['subject'] ?? '');
$campus_filter  = (int)($_GET['campus'] ?? 0);

// Fetch instructors with their availability
$where = ["u.role IN ('instructor','admin')", "u.is_active = 1"];
$params = [];
if ($campus_filter) { $where[] = "u.campus_id = ?"; $params[] = $campus_filter; }

$instructors = db_rows("
    SELECT u.id, u.name, u.username, u.bio, u.reputation, u.campus_id,
           c.name AS campus_name, c.code AS campus_code,
           COUNT(DISTINCT ia.id) AS slot_count,
           COUNT(DISTINCT q.id) AS answered_count,
           GROUP_CONCAT(DISTINCT ia.subject ORDER BY ia.subject SEPARATOR '||') AS subjects
    FROM users u
    LEFT JOIN campuses c ON u.campus_id = c.id
    LEFT JOIN instructor_availability ia ON u.id = ia.user_id AND ia.is_active = 1
    LEFT JOIN answers a ON u.id = a.user_id AND a.is_instructor_verified = 1
    LEFT JOIN questions q ON a.question_id = q.id
    " . "WHERE " . implode(' AND ', $where) . "
    GROUP BY u.id
    ORDER BY u.reputation DESC
", $params);

// Filter by subject client-side (already in subj string)
if ($subject_filter) {
    $instructors = array_filter($instructors, fn($i) => stripos($i['subjects'] ?? '', $subject_filter) !== false);
}

$campuses = db_rows("SELECT id, name, code FROM campuses WHERE is_active=1 ORDER BY name");
$all_subjects = db_rows("SELECT DISTINCT subject FROM instructor_availability WHERE is_active=1 AND subject IS NOT NULL ORDER BY subject");

$page_title = 'Consultation';
require_once 'includes/header.php';

$days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$day_full = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
?>

<div class="page-wrap">
  <div style="margin-bottom:22px;">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.6rem;font-weight:700;">📅 Instructor Consultation</h1>
    <p style="color:var(--text-d);font-size:0.875rem;margin-top:4px;">
      Browse available instructors, view their schedules, and request a one-on-one consultation session.
    </p>
  </div>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:22px;">
    <select name="subject" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:8px 12px;font-family:'Nunito',sans-serif;font-size:0.875rem;outline:none;">
      <option value="">All Subjects</option>
      <?php foreach($all_subjects as $s): ?>
      <option value="<?= e($s['subject']) ?>" <?= $subject_filter===$s['subject']?'selected':'' ?>><?= e($s['subject']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="campus" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:8px 12px;font-family:'Nunito',sans-serif;font-size:0.875rem;outline:none;">
      <option value="">All Campuses</option>
      <?php foreach($campuses as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $campus_filter==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-purple">Filter</button>
    <a href="consultations.php" class="btn-ghost">Clear</a>
    <?php if(is_logged_in()): ?>
    <a href="my-consultations.php" class="btn-gold" style="margin-left:auto;">My Bookings →</a>
    <?php endif; ?>
  </form>

  <?php if(empty($instructors)): ?>
  <div class="empty-state"><div class="empty-icon">👨‍🏫</div><p>No instructors found for this filter.</p></div>
  <?php else: ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;">
    <?php foreach($instructors as $inst):
      $availability = db_rows("SELECT * FROM instructor_availability WHERE user_id=? AND is_active=1 ORDER BY day_of_week, start_time", [$inst['id']]);
      $subjects_arr = array_filter(array_unique(explode('||', $inst['subjects'] ?? '')));
    ?>
    <div class="card" style="overflow:hidden;">
      <!-- Card header -->
      <div style="background:linear-gradient(135deg,#1e1040,#2d1470);padding:20px;position:relative;">
        <div style="position:absolute;top:12px;right:12px;">
          <span style="font-size:0.72rem;background:rgba(244,166,35,0.2);color:var(--gold);border:1px solid rgba(244,166,35,0.3);border-radius:20px;padding:3px 10px;font-weight:700;">
            🎓 Instructor
          </span>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
          <img src="<?= avatar_url($inst['username']) ?>" style="width:56px;height:56px;border-radius:50%;border:2px solid var(--gold);box-shadow:0 0 16px rgba(244,166,35,0.25);" alt="">
          <div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.05rem;color:#fff;"><?= e($inst['name']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-d);">@<?= e($inst['username']) ?></div>
            <?php if($inst['campus_name']): ?>
            <div style="font-size:0.75rem;color:var(--purple-l);margin-top:2px;">📍 <?= e($inst['campus_name']) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php if($inst['bio']): ?>
        <p style="font-size:0.8rem;color:var(--text-m);margin-top:12px;line-height:1.5;"><?= e(mb_substr($inst['bio'],0,100)).(strlen($inst['bio'])>100?'...':'') ?></p>
        <?php endif; ?>

        <!-- Subjects -->
        <?php if($subjects_arr): ?>
        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:10px;">
          <?php foreach(array_slice($subjects_arr,0,3) as $sub): ?>
          <span class="tag" style="font-size:0.7rem;"><?= e(trim($sub)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Stats row -->
      <div style="display:flex;border-bottom:1px solid var(--border);">
        <div style="flex:1;padding:10px;text-align:center;border-right:1px solid var(--border);">
          <div style="font-family:'Rajdhani',sans-serif;font-size:1.2rem;font-weight:700;color:var(--gold);">⭐ <?= number_format($inst['reputation']) ?></div>
          <div style="font-size:0.7rem;color:var(--text-d);">Reputation</div>
        </div>
        <div style="flex:1;padding:10px;text-align:center;border-right:1px solid var(--border);">
          <div style="font-family:'Rajdhani',sans-serif;font-size:1.2rem;font-weight:700;color:var(--green);"><?= $inst['slot_count'] ?></div>
          <div style="font-size:0.7rem;color:var(--text-d);">Time Slots</div>
        </div>
        <div style="flex:1;padding:10px;text-align:center;">
          <div style="font-family:'Rajdhani',sans-serif;font-size:1.2rem;font-weight:700;color:var(--purple-l);"><?= $inst['answered_count'] ?></div>
          <div style="font-size:0.7rem;color:var(--text-d);">Verified Ans.</div>
        </div>
      </div>

      <!-- Availability -->
      <div style="padding:14px;">
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-d);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Available Schedule</div>
        <?php if($availability): ?>
        <div style="display:flex;flex-direction:column;gap:5px;">
          <?php foreach($availability as $slot): ?>
          <div style="display:flex;align-items:center;gap:8px;font-size:0.8rem;">
            <span style="background:rgba(124,58,237,0.15);color:var(--purple-l);border-radius:4px;padding:2px 7px;font-weight:700;min-width:32px;text-align:center;"><?= $days[$slot['day_of_week']] ?></span>
            <span style="color:var(--text-m);"><?= date('g:i A', strtotime($slot['start_time'])) ?> – <?= date('g:i A', strtotime($slot['end_time'])) ?></span>
            <?php if($slot['subject']): ?><span style="color:var(--text-d);">· <?= e($slot['subject']) ?></span><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="font-size:0.8rem;color:var(--text-d);">No scheduled slots — send a request anyway.</p>
        <?php endif; ?>

        <div style="margin-top:14px;display:flex;gap:8px;">
          <a href="book-consultation.php?instructor=<?= $inst['id'] ?>" class="btn-gold" style="flex:1;justify-content:center;<?= !is_logged_in()?'pointer-events:none;opacity:0.5':'' ?>">
            📅 Book Consultation
          </a>
          <a href="profile.php?u=<?= urlencode($inst['username']) ?>" class="btn-ghost btn-sm">Profile</a>
        </div>
        <?php if(!is_logged_in()): ?>
        <p style="font-size:0.75rem;color:var(--text-d);margin-top:6px;text-align:center;"><a href="login.php">Login</a> to book a consultation.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
