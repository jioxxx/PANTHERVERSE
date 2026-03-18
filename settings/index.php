<?php
// settings/index.php - Settings Dashboard
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
$user = current_user();

$page_title = 'Settings';
require_once '../includes/header.php';
?>

<div class="page-wrap">
    <div style="margin-bottom:24px;">
        <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">⚙️ Settings</h1>
        <p style="font-size:0.82rem;color:var(--text-d);">Manage your account preferences</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
        <!-- Profile Settings -->
        <a href="profile.php" class="card" style="padding:20px;text-decoration:none;display:block;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
            <div style="font-size:2rem;margin-bottom:12px;">👤</div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">Profile</div>
            <div style="font-size:0.8rem;color:var(--text-d);margin-top:4px;">Update your name, bio, and profile photo</div>
        </a>

        <!-- Account Settings -->
        <a href="settings.php" class="card" style="padding:20px;text-decoration:none;display:block;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
            <div style="font-size:2rem;margin-bottom:12px;">🔐</div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">Account</div>
            <div style="font-size:0.8rem;color:var(--text-d);margin-top:4px;">Change password and email settings</div>
        </a>

        <!-- Notification Settings -->
        <a href="settings/notifications.php" class="card" style="padding:20px;text-decoration:none;display:block;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
            <div style="font-size:2rem;margin-bottom:12px;">🔔</div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">Notifications</div>
            <div style="font-size:0.8rem;color:var(--text-d);margin-top:4px;">Choose what notifications you receive</div>
        </a>

        <!-- Theme Settings -->
        <a href="settings/theme.php" class="card" style="padding:20px;text-decoration:none;display:block;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
            <div style="font-size:2rem;margin-bottom:12px;">🎨</div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">Appearance</div>
            <div style="font-size:0.8rem;color:var(--text-d);margin-top:4px;">Dark mode, language, and display options</div>
        </a>

        <!-- Bookmarks -->
        <a href="../bookmarks.php" class="card" style="padding:20px;text-decoration:none;display:block;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
            <div style="font-size:2rem;margin-bottom:12px;">🔖</div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">Bookmarks</div>
            <div style="font-size:0.8rem;color:var(--text-d);margin-top:4px;">View your saved questions and resources</div>
        </a>

        <!-- API Tokens -->
        <a href="settings/api.php" class="card" style="padding:20px;text-decoration:none;display:block;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
            <div style="font-size:2rem;margin-bottom:12px;">🔑</div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">API Access</div>
            <div style="font-size:0.8rem;color:var(--text-d);margin-top:4px;">Generate tokens for external apps</div>
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

