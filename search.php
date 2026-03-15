<?php
// search.php - Enhanced Search Page
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? ''; // questions, users, resources, forum_posts, tags
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$results = [];
$total = 0;

if ($query) {
    $results = search_all($query, $type, $per_page);
    
    // Calculate total
    if ($type) {
        $total = count($results[$type] ?? []);
    } else {
        $total = 0;
        foreach ($results as $r) {
            $total += count($r);
        }
    }
}

// Get popular searches
$popular_tags = get_popular_tags(12);

$page_title = 'Search';
require_once 'includes/header.php';
?>

<div class="page-wrap">
    <div style="margin-bottom:24px;">
        <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;margin-bottom:8px;">🔍 Search</h1>
        <p style="font-size:0.82rem;color:var(--text-d);">Find questions, users, resources, and more</p>
    </div>

    <!-- Search Form -->
    <form method="GET" style="margin-bottom:24px;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:280px;">
                <input type="text" name="q" value="<?= e($query) ?>" placeholder="Search anything..." style="font-size:1rem;padding:12px 16px;">
            </div>
            <button type="submit" class="btn-purple" style="padding:12px 24px;font-size:1rem;">Search</button>
        </div>
        
        <!-- Type Filter -->
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
            <a href="?q=<?= urlencode($query) ?>" class="tag <?= $type === '' ? '' : '' ?>" style="<?= $type === '' ? 'background:var(--purple);color:#fff;' : '' ?>">All</a>
            <a href="?q=<?= urlencode($query) ?>&type=questions" class="tag" style="<?= $type === 'questions' ? 'background:var(--purple);color:#fff;' : '' ?>">❓ Questions</a>
            <a href="?q=<?= urlencode($query) ?>&type=users" class="tag" style="<?= $type === 'users' ? 'background:var(--purple);color:#fff;' : '' ?>">👥 Users</a>
            <a href="?q=<?= urlencode($query) ?>&type=resources" class="tag" style="<?= $type === 'resources' ? 'background:var(--purple);color:#fff;' : '' ?>">📁 Resources</a>
            <a href="?q=<?= urlencode($query) ?>&type=forum_posts" class="tag" style="<?= $type === 'forum_posts' ? 'background:var(--purple);color:#fff;' : '' ?>">💬 Forums</a>
            <a href="?q=<?= urlencode($query) ?>&type=tags" class="tag" style="<?= $type === 'tags' ? 'background:var(--purple);color:#fff;' : '' ?>">🏷️ Tags</a>
        </div>
    </form>

    <?php if ($query): ?>
        <p style="font-size:0.9rem;color:var(--text-d);margin-bottom:20px;">
            <?php if ($total > 0): ?>
                Found <?= number_format($total) ?> results for "<strong><?= e($query) ?></strong>"
            <?php else: ?>
                No results found for "<strong><?= e($query) ?></strong>"
            <?php endif; ?>
        </p>

        <!-- Results -->
        <?php if ($total > 0): ?>
        
        <?php if (!$type || $type === 'questions'): ?>
        <?php if (!empty($results['questions'])): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head"><span class="card-title">❓ Questions (<?= count($results['questions']) ?>)</span></div>
            <div class="card-body" style="padding:0;">
                <?php foreach ($results['questions'] as $q): ?>
                <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                    <a href="question.php?id=<?= $q['id'] ?>" style="font-weight:600;color:var(--text);display:block;margin-bottom:4px;">
                        <?= e($q['title']) ?>
                    </a>
                    <div style="display:flex;gap:16px;font-size:0.78rem;color:var(--text-d);">
                        <span>⬆️ <?= $q['vote_count'] ?></span>
                        <span>👁️ <?= $q['view_count'] ?></span>
                        <span>💬 <?= $q['answer_count'] ?></span>
                        <span>by <a href="profile.php?u=<?= urlencode($q['username']) ?>" style="color:var(--purple-l);"><?= e($q['username']) ?></a></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!$type || $type === 'users'): ?>
        <?php if (!empty($results['users'])): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head"><span class="card-title">👥 Users (<?= count($results['users']) ?>)</span></div>
            <div class="card-body" style="padding:0;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;padding:16px;">
                <?php foreach ($results['users'] as $u): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--bg3);border-radius:8px;">
                    <img src="<?= avatar_url($u['username'], 48) ?>" style="width:48px;height:48px;border-radius:50%;">
                    <div>
                        <a href="profile.php?u=<?= urlencode($u['username']) ?>" style="font-weight:600;color:var(--text);"><?= e($u['name']) ?></a>
                        <div style="font-size:0.78rem;color:var(--text-d);">@<?= e($u['username']) ?> · ⭐ <?= number_format($u['reputation']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!$type || $type === 'resources'): ?>
        <?php if (!empty($results['resources'])): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head"><span class="card-title">📁 Resources (<?= count($results['resources']) ?>)</span></div>
            <div class="card-body" style="padding:0;">
                <?php foreach ($results['resources'] as $r): ?>
                <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <a href="download-resource.php?id=<?= $r['id'] ?>" style="font-weight:600;color:var(--text);"><?= e($r['title']) ?></a>
                        <div style="font-size:0.78rem;color:var(--text-d);">by <?= e($r['username']) ?> · <?= e($r['file_type']) ?></div>
                    </div>
                    <span style="font-size:0.8rem;color:var(--text-d);">⬇️ <?= $r['download_count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!$type || $type === 'forum_posts'): ?>
        <?php if (!empty($results['forum_posts'])): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head"><span class="card-title">💬 Forum Posts (<?= count($results['forum_posts']) ?>)</span></div>
            <div class="card-body" style="padding:0;">
                <?php foreach ($results['forum_posts'] as $fp): ?>
                <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                    <a href="forum-post.php?id=<?= $fp['id'] ?>" style="font-weight:600;color:var(--text);display:block;margin-bottom:4px;">
                        <?= e($fp['title']) ?>
                    </a>
                    <div style="font-size:0.78rem;color:var(--text-d);">
                        in <?= e($fp['category_name']) ?> · by <?= e($fp['username']) ?> · 👁️ <?= $fp['view_count'] ?> · 💬 <?= $fp['reply_count'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!$type || $type === 'tags'): ?>
        <?php if (!empty($results['tags'])): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-head"><span class="card-title">🏷️ Tags (<?= count($results['tags']) ?>)</span></div>
            <div class="card-body" style="padding:16px;">
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($results['tags'] as $t): ?>
                <a href="questions.php?tag=<?= e($t['slug']) ?>" class="tag"><?= e($t['name']) ?> (<?= $t['usage_count'] ?>)</a>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php endif; ?>

    <?php else: ?>
        <!-- Popular Tags when no search -->
        <div class="card">
            <div class="card-head"><span class="card-title">🏷️ Popular Tags</span></div>
            <div class="card-body" style="padding:16px;">
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($popular_tags as $tag): ?>
                <a href="questions.php?tag=<?= e($tag['slug']) ?>" class="tag"><?= e($tag['name']) ?> (<?= $tag['usage_count'] ?>)</a>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

