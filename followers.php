
<?php
// followers.php - User's followers
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$username = trim($_GET['u'] ?? '');
if (!$username) redirect('index.php');

$profile_user = db_row("SELECT * FROM users WHERE username=? AND is_active=1", [$username]);
if (!$profile_user) die('<div style="padding:40px;text-align:center;color:#f4a623;background:#0e0720;font-family:monospace;">User not found.</div>');

// Get followers
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$followers = db_rows("
    SELECT u.*, uf.created_at as followed_at
    FROM user_follows uf
    JOIN users u ON uf.follower_id = u.id
    WHERE uf.following_id = ?
    ORDER BY uf.created_at DESC
    LIMIT ? OFFSET ?
", [$profile_user['id'], $per_page, $offset]);

$total = db_count("SELECT COUNT(*) FROM user_follows WHERE following_id = ?", [$profile_user['id']]);
$total_pages = ceil($total / $per_page);

$page_title = 'Followers - @' . $username;
require_once 'includes/header.php';
?>

<div class="page-wrap">
    <div style="margin-bottom:24px;">
        <a href="profile.php?u=<?= urlencode($username) ?>" class="btn-ghost btn-sm" style="margin-bottom:12px;display:inline-block;">
            ← Back to <?= e($username) ?>
        </a>
        <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">
            👥 Followers of <?= e($username) ?>
        </h1>
        <p style="font-size:0.82rem;color:var(--text-d);"><?= number_format($total) ?> followers</p>
    </div>

    <?php if ($followers): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;">
        <?php foreach ($followers as $f): ?>
        <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px;">
            <img src="<?= avatar_url($f['username'], 48) ?>" style="width:48px;height:48px;border-radius:50%;">
            <div style="flex:1;min-width:0;">
                <a href="profile.php?u=<?= urlencode($f['username']) ?>" style="font-weight:600;color:var(--text);display:block;">
                    <?= e($f['name']) ?>
                </a>
                <div style="font-size:0.78rem;color:var(--text-d);">@<?= e($f['username']) ?></div>
                <div style="font-size:0.75rem;color:var(--purple-l);">⭐ <?= number_format($f['reputation']) ?></div>
            </div>
            <?php if (is_logged_in() && current_user_id() != $f['id']): ?>
                <?php $am_following = db_count("SELECT 1 FROM user_follows WHERE follower_id=? AND following_id=?", [current_user_id(), $f['id']]) > 0; ?>
                <form method="POST" action="api/follow.php" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= $f['id'] ?>">
                    <input type="hidden" name="action" value="<?= $am_following ? 'unfollow' : 'follow' ?>">
                    <button type="submit" class="btn-<?= $am_following ? 'ghost' : 'purple' ?> btn-sm">
                        <?= $am_following ? '✓ Following' : '+ Follow' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?u=<?= urlencode($username) ?>&page=<?= $i ?>" class="<?= $i === $page ? 'current' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <p>No followers yet.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

