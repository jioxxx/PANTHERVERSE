<?php
// settings/notifications.php - Notification preferences
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
$user = current_user();
$uid = current_user_id();

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $data = [
        'notify_new_answer' => isset($_POST['notify_new_answer']) ? 1 : 0,
        'notify_answer_accepted' => isset($_POST['notify_answer_accepted']) ? 1 : 0,
        'notify_new_comment' => isset($_POST['notify_new_comment']) ? 1 : 0,
        'notify_new_follower' => isset($_POST['notify_new_follower']) ? 1 : 0,
        'notify_mention' => isset($_POST['notify_mention']) ? 1 : 0,
        'notify_consultation' => isset($_POST['notify_consultation']) ? 1 : 0,
        'notify_study_group' => isset($_POST['notify_study_group']) ? 1 : 0,
        'notify_message' => isset($_POST['notify_message']) ? 1 : 0,
        'notify_announcement' => isset($_POST['notify_announcement']) ? 1 : 0,
        'email_new_answer' => isset($_POST['email_new_answer']) ? 1 : 0,
        'email_answer_accepted' => isset($_POST['email_answer_accepted']) ? 1 : 0,
        'email_new_follower' => isset($_POST['email_new_follower']) ? 1 : 0,
        'email_mention' => isset($_POST['email_mention']) ? 1 : 0,
        'email_consultation' => isset($_POST['email_consultation']) ? 1 : 0,
        'email_announcement' => isset($_POST['email_announcement']) ? 1 : 0,
    ];
    
    update_notification_preferences($uid, $data);
    $success = 'Notification preferences saved!';
}

// Get current preferences
$prefs = get_notification_preferences($uid);

$page_title = 'Notification Settings';
require_once '../includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <a href="settings.php" class="btn-ghost" style="padding:8px 12px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">🔔 Notification Settings</h1>
            <p style="font-size:0.82rem;color:var(--text-d);">Choose what notifications you receive</p>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        
        <!-- In-App Notifications -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head">
                <span class="card-title">📱 In-App Notifications</span>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_new_answer" value="1" <?= $prefs['notify_new_answer'] ? 'checked' : '' ?>>
                        <span>New answers to my questions</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_answer_accepted" value="1" <?= $prefs['notify_answer_accepted'] ? 'checked' : '' ?>>
                        <span>My answer is accepted</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_new_comment" value="1" <?= $prefs['notify_new_comment'] ? 'checked' : '' ?>>
                        <span>New comments on my posts</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_new_follower" value="1" <?= $prefs['notify_new_follower'] ? 'checked' : '' ?>>
                        <span>New followers</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_mention" value="1" <?= $prefs['notify_mention'] ? 'checked' : '' ?>>
                        <span>When someone mentions me</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_consultation" value="1" <?= $prefs['notify_consultation'] ? 'checked' : '' ?>>
                        <span>Consultation updates</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_study_group" value="1" <?= $prefs['notify_study_group'] ? 'checked' : '' ?>>
                        <span>Study group activity</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_message" value="1" <?= $prefs['notify_message'] ? 'checked' : '' ?>>
                        <span>Direct messages</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="notify_announcement" value="1" <?= $prefs['notify_announcement'] ? 'checked' : '' ?>>
                        <span>New announcements</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Email Notifications -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head">
                <span class="card-title">📧 Email Notifications</span>
            </div>
            <div class="card-body">
                <p style="font-size:0.85rem;color:var(--text-d);margin-bottom:16px;">
                    Receive important updates via email. Note: Email notifications are disabled by default.
                </p>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="email_new_answer" value="1" <?= $prefs['email_new_answer'] ? 'checked' : '' ?>>
                        <span>New answers to my questions</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="email_answer_accepted" value="1" <?= $prefs['email_answer_accepted'] ? 'checked' : '' ?>>
                        <span>My answer is accepted</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="email_new_follower" value="1" <?= $prefs['email_new_follower'] ? 'checked' : '' ?>>
                        <span>New followers</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="email_mention" value="1" <?= $prefs['email_mention'] ? 'checked' : '' ?>>
                        <span>When someone mentions me</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="email_consultation" value="1" <?= $prefs['email_consultation'] ? 'checked' : '' ?>>
                        <span>Consultation updates</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="email_announcement" value="1" <?= $prefs['email_announcement'] ? 'checked' : '' ?>>
                        <span>New announcements</span>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-gold">Save Preferences</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>

