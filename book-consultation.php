<?php
// book-consultation.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$instructor_id = (int)($_GET['instructor'] ?? $_POST['instructor_id'] ?? 0);
$question_id   = (int)($_GET['question'] ?? 0); // pre-fill from a Q&A page

$instructor = db_row("SELECT u.*, c.name AS campus_name FROM users u LEFT JOIN campuses c ON u.campus_id=c.id WHERE u.id=? AND u.role IN ('instructor','admin') AND u.is_active=1", [$instructor_id]);
if (!$instructor) { flash('error','Instructor not found.'); redirect('consultations.php'); }

$availability = db_rows("SELECT * FROM instructor_availability WHERE user_id=? AND is_active=1 ORDER BY day_of_week, start_time", [$instructor_id]);
$question     = $question_id ? db_row("SELECT id, title FROM questions WHERE id=?", [$question_id]) : null;

$days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $subject  = trim($_POST['subject'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $pdate    = $_POST['preferred_date'] ?? '';
    $ptime    = $_POST['preferred_time'] ?? '';
    $qid      = (int)($_POST['question_id'] ?? 0);

    if (!$subject)              $error = 'Subject is required.';
    elseif (strlen($message)<20)$error = 'Please describe your question in at least 20 characters.';
    elseif (!$pdate || !$ptime) $error = 'Please provide a preferred date and time.';
    elseif (strtotime($pdate) < strtotime('today')) $error = 'Preferred date must be today or in the future.';
    else {
        db_insert("INSERT INTO consultations (student_id,instructor_id,subject,message,preferred_date,preferred_time,status,question_id,created_at,updated_at) VALUES (?,?,?,?,?,?,'pending',?,NOW(),NOW())",
            [current_user_id(), $instructor_id, $subject, $message, $pdate, $ptime, $qid ?: null]);
        send_notification($instructor_id, 'consultation_request', [
            'student'   => current_user()['username'],
            'subject'   => $subject,
            'date'      => $pdate,
        ]);
        flash('success','Consultation request sent! The instructor will review and respond soon.');
        redirect('my-consultations.php');
    }
}

$page_title = 'Book Consultation';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:720px;">
  <nav style="font-size:0.8rem;color:var(--text-d);margin-bottom:14px;">
    <a href="consultations.php" style="color:var(--text-d);">Instructors</a> › Book Consultation
  </nav>

  <!-- Instructor summary card -->
  <div class="card" style="margin-bottom:20px;padding:18px;display:flex;gap:14px;align-items:center;">
    <img src="<?= avatar_url($instructor['username']) ?>" style="width:56px;height:56px;border-radius:50%;border:2px solid var(--gold);" alt="">
    <div style="flex:1;">
      <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.05rem;"><?= e($instructor['name']) ?></div>
      <div style="font-size:0.8rem;color:var(--text-d);">
        @<?= e($instructor['username']) ?>
        <?php if($instructor['campus_name']): ?> · 📍 <?= e($instructor['campus_name']) ?><?php endif; ?>
      </div>
      <?php if($availability): ?>
      <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:5px;">
        <?php foreach($availability as $sl): ?>
        <span style="font-size:0.72rem;background:rgba(124,58,237,0.12);color:var(--purple-l);border-radius:4px;padding:2px 7px;">
          <?= $days[$sl['day_of_week']] ?> <?= date('g A', strtotime($sl['start_time'])) ?>–<?= date('g A', strtotime($sl['end_time'])) ?>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-head"><span class="card-title">📅 Request Consultation</span></div>
    <div class="card-body">
      <?php if($question): ?>
      <div class="alert alert-info" style="margin-bottom:16px;">
        📎 Linked to question: <strong><?= e($question['title']) ?></strong>
      </div>
      <?php endif; ?>

      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="instructor_id" value="<?= $instructor_id ?>">
        <input type="hidden" name="question_id"   value="<?= $question_id ?>">

        <div class="form-group">
          <label>Subject / Topic *</label>
          <input type="text" name="subject" value="<?= e($_POST['subject'] ?? ($availability[0]['subject'] ?? '')) ?>" placeholder="e.g. Data Structures — Binary Trees" required>
        </div>

        <div class="form-group">
          <label>Your Question / Context * <span class="form-hint" style="display:inline;">(be specific so the instructor can prepare)</span></label>
          <textarea name="message" rows="6" placeholder="Describe what you need help with. Include specific topics, what you've already tried, and what's still unclear." required><?= e($_POST['message'] ?? '') ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="form-group">
            <label>Preferred Date *</label>
            <input type="date" name="preferred_date" value="<?= e($_POST['preferred_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>" required>
            <div class="form-hint">Check the instructor's schedule above</div>
          </div>
          <div class="form-group">
            <label>Preferred Time *</label>
            <input type="time" name="preferred_time" value="<?= e($_POST['preferred_time'] ?? '') ?>" required>
          </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn-gold">Send Request 📅</button>
          <a href="consultations.php" class="btn-ghost">Cancel</a>
        </div>
        <p style="font-size:0.78rem;color:var(--text-d);margin-top:10px;">
          The instructor will review your request and either approve it or suggest a different time.
        </p>
      </form>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
