<?php
// settings/theme.php - Theme and appearance settings
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
        'theme' => $_POST['theme'] ?? 'dark',
        'language' => $_POST['language'] ?? 'en',
        'email_frequency' => $_POST['email_frequency'] ?? 'daily',
    ];
    
    // Validate theme
    if (!in_array($data['theme'], ['dark', 'light', 'system'])) {
        $data['theme'] = 'dark';
    }
    
    update_user_preferences($uid, $data);
    $success = 'Appearance settings saved!';
}

// Get current preferences
$prefs = get_user_preferences($uid);

$page_title = 'Appearance Settings';
require_once '../includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <a href="settings.php" class="btn-ghost" style="padding:8px 12px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">🎨 Appearance Settings</h1>
            <p style="font-size:0.82rem;color:var(--text-d);">Customize how PANTHERVERSE looks</p>
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
        
        <!-- Theme Selection -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head">
                <span class="card-title">🌙 Theme</span>
            </div>
            <div class="card-body">
                <p style="font-size:0.85rem;color:var(--text-d);margin-bottom:16px;">
                    Choose your preferred color theme.
                </p>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
                    <label style="cursor:pointer;">
                        <input type="radio" name="theme" value="dark" <?= $prefs['theme'] === 'dark' ? 'checked' : '' ?> style="display:none;">
                        <div class="card" style="padding:20px;text-align:center;<?= $prefs['theme'] === 'dark' ? 'border-color:var(--gold);' : '' ?>">
                            <div style="font-size:2rem;margin-bottom:8px;">🌙</div>
                            <div style="font-weight:600;">Dark</div>
                            <div style="font-size:0.75rem;color:var(--text-d);">Default dark theme</div>
                        </div>
                    </label>
                    <label style="cursor:pointer;">
                        <input type="radio" name="theme" value="light" <?= $prefs['theme'] === 'light' ? 'checked' : '' ?> style="display:none;">
                        <div class="card" style="padding:20px;text-align:center;<?= $prefs['theme'] === 'light' ? 'border-color:var(--gold);' : '' ?>">
                            <div style="font-size:2rem;margin-bottom:8px;">☀️</div>
                            <div style="font-weight:600;">Light</div>
                            <div style="font-size:0.75rem;color:var(--text-d);">Light theme</div>
                        </div>
                    </label>
                    <label style="cursor:pointer;">
                        <input type="radio" name="theme" value="system" <?= $prefs['theme'] === 'system' ? 'checked' : '' ?> style="display:none;">
                        <div class="card" style="padding:20px;text-align:center;<?= $prefs['theme'] === 'system' ? 'border-color:var(--gold);' : '' ?>">
                            <div style="font-size:2rem;margin-bottom:8px;">💻</div>
                            <div style="font-weight:600;">System</div>
                            <div style="font-size:0.75rem;color:var(--text-d);">Follow system preference</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Language -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head">
                <span class="card-title">🌐 Language</span>
            </div>
            <div class="card-body">
                <div class="form-group" style="max-width:300px;margin-bottom:0;">
                    <select name="language">
                        <option value="en" <?= $prefs['language'] === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="fil" <?= $prefs['language'] === 'fil' ? 'selected' : '' ?>>Filipino</option>
                        <option value="es" <?= $prefs['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Email Frequency -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head">
                <span class="card-title">📧 Email Digest</span>
            </div>
            <div class="card-body">
                <p style="font-size:0.85rem;color:var(--text-d);margin-bottom:16px;">
                    How often would you like to receive email digests?
                </p>
                <div class="form-group" style="max-width:300px;margin-bottom:0;">
                    <select name="email_frequency">
                        <option value="instant" <?= $prefs['email_frequency'] === 'instant' ? 'selected' : '' ?>>Instant (for important updates only)</option>
                        <option value="daily" <?= $prefs['email_frequency'] === 'daily' ? 'selected' : '' ?>>Daily digest</option>
                        <option value="weekly" <?= $prefs['email_frequency'] === 'weekly' ? 'selected' : '' ?>>Weekly digest</option>
                        <option value="never" <?= $prefs['email_frequency'] === 'never' ? 'selected' : '' ?>>Never</option>
                    </select>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-gold">Save Settings</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>

