<?php
// directory.php - User directory with filters
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get filters
$role = $_GET['role'] ?? '';
$campus_id = (int)($_GET['campus'] ?? 0);
$program_id = (int)($_GET['program'] ?? 0);
$search = trim($_GET['q'] ?? '');

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 24;
$offset = ($page - 1) * $per_page;

// Get users
$result = get_users_directory($role ?: null, $campus_id ?: null, $program_id ?: null, $search, $per_page, $offset);
$users = $result['users'];
$total = $result['total'];
$total_pages = ceil($total / $per_page);

// Get filters data
$campuses = db_rows("SELECT id, name, code FROM campuses WHERE is_active = 1 ORDER BY name");
$programs = db_rows("SELECT id, name, code FROM programs ORDER BY name");

$page_title = 'User Directory';
require_once 'includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">👥 User Directory</h1>
            <p style="font-size:0.82rem;color:var(--text-d);">Browse members of the community</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="card" style="padding:16px;margin-bottom:20px;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label style="font-size:0.8rem;color:var(--text-m);margin-bottom:4px;display:block;">Search</label>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name, username, or email...">
            </div>
            <div>
                <label style="font-size:0.8rem;color:var(--text-m);margin-bottom:4px;display:block;">Role</label>
                <select name="role" style="width:auto;min-width:140px;">
                    <option value="">All Roles</option>
                    <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="instructor" <?= $role === 'instructor' ? 'selected' : '' ?>>Instructors</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admins</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.8rem;color:var(--text-m);margin-bottom:4px;display:block;">Campus</label>
                <select name="campus" style="width:auto;min-width:140px;">
                    <option value="0">All Campuses</option>
                    <?php foreach ($campuses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $campus_id === $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:0.8rem;color:var(--text-m);margin-bottom:4px;display:block;">Program</label>
                <select name="program" style="width:auto;min-width:140px;">
                    <option value="0">All Programs</option>
                    <?php foreach ($programs as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $program_id === $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-purple">Filter</button>
            <?php if ($search || $role || $campus_id || $program_id): ?>
            <a href="directory.php" class="btn-ghost">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Results count -->
    <p style="font-size:0.85rem;color:var(--text-d);margin-bottom:16px;">
        Showing <?= count($users) ?> of <?= number_format($total) ?> members
    </p>

    <!-- Users Grid -->
    <?php if ($users): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
        <?php foreach ($users as $u): ?>
        <div class="card" style="padding:16px;text-align:center;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
            <img src="<?= avatar_url($u['username'], 80) ?>" style="width:80px;height:80px;border-radius:50%;margin-bottom:12px;border:2px solid var(--border);">
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">
                <?= e($u['name']) ?>
                <?php if ($u['role'] === 'instructor'): ?><span class="role-tag">Prof</span><?php endif; ?>
                <?php if ($u['role'] === 'admin'): ?><span class="role-tag admin">Admin</span><?php endif; ?>
            </div>
            <a href="profile.php?u=<?= urlencode($u['username']) ?>" style="font-size:0.85rem;color:var(--purple-l);">@<?= e($u['username']) ?></a>
            <div style="font-size:0.78rem;color:var(--text-d);margin:8px 0;">
                <?= e($u['program_code'] ?? 'N/A') ?> · <?= e($u['campus_name'] ?? 'N/A') ?>
            </div>
            <div style="display:flex;justify-content:center;gap:16px;font-size:0.75rem;color:var(--text-d);">
                <span>⭐ <?= number_format($u['reputation']) ?></span>
                <span>❓ <?= $u['question_count'] ?? 0 ?></span>
                <span>💬 <?= $u['answer_count'] ?? 0 ?></span>
            </div>
            <?php if (is_logged_in() && current_user_id() !== $u['id']): ?>
            <div style="margin-top:12px;">
                <?php if (is_following(current_user_id(), $u['id'])): ?>
                <form method="POST" action="api/follow.php" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="unfollow">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn-ghost btn-sm" style="width:100%;justify-content:center;">✓ Following</button>
                </form>
                <?php else: ?>
                <form method="POST" action="api/follow.php" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="follow">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn-purple btn-sm" style="width:100%;justify-content:center;">+ Follow</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php 
        $query_params = [];
        if ($search) $query_params['q'] = $search;
        if ($role) $query_params['role'] = $role;
        if ($campus_id) $query_params['campus'] = $campus_id;
        if ($program_id) $query_params['program'] = $program_id;
        $query_string = http_build_query($query_params);
        ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?><?= $query_string ? "&$query_string" : '' ?>" class="<?= $i === $page ? 'current' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <p>No members found.</p>
        <p style="font-size:0.85rem;color:var(--text-d);">Try adjusting your filters.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

