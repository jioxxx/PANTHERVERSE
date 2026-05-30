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
<style>
.settings-hub-card {
    display: flex;
    align-items: center;
    gap: 18px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px 22px;
    text-decoration: none;
    transition: all 0.22s;
    position: relative;
    overflow: hidden;
}
.settings-hub-card::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(124,58,237,0.06) 0%, transparent 70%);
    opacity: 0; transition: opacity 0.22s;
}
.settings-hub-card:hover { border-color: var(--purple-l); transform: translateY(-2px); box-shadow: 0 8px 32px rgba(124,58,237,0.12); }
.settings-hub-card:hover::before { opacity: 1; }
.settings-hub-card:hover .hub-arrow { transform: translateX(4px); color: var(--gold); }

.hub-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.hub-icon.purple { background: rgba(124,58,237,0.15); border: 1px solid rgba(124,58,237,0.25); color: var(--purple-l); }
.hub-icon.gold   { background: rgba(244,166,35,0.12); border: 1px solid rgba(244,166,35,0.2); color: var(--gold); }
.hub-icon.green  { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2); color: var(--green); }
.hub-icon.blue   { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.2); color: #60a5fa; }

.hub-info { flex: 1; }
.hub-title { font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 1.1rem; color: var(--text); margin-bottom: 3px; }
.hub-desc  { font-size: 0.82rem; color: var(--text-d); line-height: 1.4; }
.hub-arrow { font-size: 1rem; color: var(--text-d); transition: all 0.2s; flex-shrink: 0; }
</style>

<div class="page-wrap" style="max-width: 760px;">
    <!-- Header -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-size:1.9rem;font-weight:700;background:linear-gradient(135deg,#fff,var(--purple-l));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px;">
            Settings
        </h1>
        <p style="color:var(--text-d);font-size:0.88rem;">Manage your account, preferences and security</p>
    </div>

    <!-- User Summary Card -->
    <div style="background:linear-gradient(135deg,rgba(124,58,237,0.12),rgba(244,166,35,0.06));border:1px solid rgba(124,58,237,0.25);border-radius:16px;padding:22px 24px;margin-bottom:28px;display:flex;align-items:center;gap:18px;">
        <img src="<?= avatar_url($user['username']) ?>" style="width:64px;height:64px;border-radius:50%;border:2px solid var(--purple);box-shadow:0 0 20px rgba(124,58,237,0.35);object-fit:cover;" alt="avatar">
        <div style="flex:1;">
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.3rem;color:var(--text);"><?= e($user['name']) ?></div>
            <div style="color:var(--text-d);font-size:0.85rem;">@<?= e($user['username']) ?> &nbsp;·&nbsp; <span style="color:var(--purple-l);"><?= ucfirst($user['role']) ?></span></div>
            <div style="color:var(--gold);font-size:0.8rem;margin-top:4px;"><i class="bi bi-star-fill" style="font-size:0.75rem;"></i> <?= number_format($user['reputation']) ?> reputation</div>
        </div>
        <a href="../profile.php?u=<?= urlencode($user['username']) ?>" class="btn-ghost btn-sm">
            <i class="bi bi-eye"></i> View Profile
        </a>
    </div>

    <!-- Settings Links Grid -->
    <div style="display:flex;flex-direction:column;gap:12px;">

        <a href="../settings.php" class="settings-hub-card">
            <div class="hub-icon purple"><i class="bi bi-person-gear"></i></div>
            <div class="hub-info">
                <div class="hub-title">Profile & Security</div>
                <div class="hub-desc">Update your name, bio, profile photo, and change your password</div>
            </div>
            <i class="bi bi-chevron-right hub-arrow"></i>
        </a>

        <a href="notifications.php" class="settings-hub-card">
            <div class="hub-icon gold"><i class="bi bi-bell-fill"></i></div>
            <div class="hub-info">
                <div class="hub-title">Notifications</div>
                <div class="hub-desc">Choose which in-app and email notifications you receive</div>
            </div>
            <i class="bi bi-chevron-right hub-arrow"></i>
        </a>

        <a href="theme.php" class="settings-hub-card">
            <div class="hub-icon purple"><i class="bi bi-palette-fill"></i></div>
            <div class="hub-info">
                <div class="hub-title">Appearance</div>
                <div class="hub-desc">Dark mode, language, and email digest frequency</div>
            </div>
            <i class="bi bi-chevron-right hub-arrow"></i>
        </a>

        <a href="../bookmarks.php" class="settings-hub-card">
            <div class="hub-icon green"><i class="bi bi-bookmark-fill"></i></div>
            <div class="hub-info">
                <div class="hub-title">Bookmarks</div>
                <div class="hub-desc">View your saved questions, resources and forum posts</div>
            </div>
            <i class="bi bi-chevron-right hub-arrow"></i>
        </a>

        <a href="api.php" class="settings-hub-card">
            <div class="hub-icon blue"><i class="bi bi-code-slash"></i></div>
            <div class="hub-info">
                <div class="hub-title">API Access</div>
                <div class="hub-desc">Generate and manage tokens for external applications</div>
            </div>
            <i class="bi bi-chevron-right hub-arrow"></i>
        </a>

    </div>

    <!-- Danger Zone -->
    <div style="margin-top:32px;padding:18px 20px;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.2);border-radius:12px;">
        <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.95rem;color:var(--red);margin-bottom:6px;">
            <i class="bi bi-exclamation-triangle"></i> Danger Zone
        </div>
        <p style="font-size:0.82rem;color:var(--text-d);margin-bottom:12px;">These actions are irreversible. Please be certain.</p>
        <form method="POST" action="../logout.php" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn-danger" style="font-size:0.82rem;">
                <i class="bi bi-box-arrow-right"></i> Sign Out of All Devices
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
