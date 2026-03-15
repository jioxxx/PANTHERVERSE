<?php
// bookmarks.php - User's saved/bookmarked content
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

require_login();
$user = current_user();
$uid = current_user_id();

// Get filter
$type = $_GET['type'] ?? '';
$valid_types = ['question', 'resource', 'forum_post', 'project'];
if (!in_array($type, $valid_types)) $type = '';

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get bookmarks
$bookmarks = get_user_bookmarks($uid, $type, $per_page, $offset);
$total = db_count("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? " . ($type ? "AND bookmarkable_type = '$type'" : ""), [$uid]);
$total_pages = ceil($total / $per_page);

// Group by type for counts
$counts = [
    'all' => db_count("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?", [$uid]),
    'question' => db_count("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND bookmarkable_type = 'question'", [$uid]),
    'resource' => db_count("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND bookmarkable_type = 'resource'", [$uid]),
    'forum_post' => db_count("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND bookmarkable_type = 'forum_post'", [$uid]),
    'project' => db_count("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND bookmarkable_type = 'project'", [$uid]),
];

$page_title = 'My Bookmarks';
require_once 'includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;"><i class="bi bi-bookmark-star"></i> My Bookmarks</h1>
            <p style="font-size:0.82rem;color:var(--text-d);">Questions, resources, and posts you've saved for later</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="tabs">
        <a href="bookmarks.php" class="tab-link <?= $type === '' ? 'active' : '' ?>"><i class="bi bi-grid"></i> All (<?= $counts['all'] ?>)</a>
        <a href="bookmarks.php?type=question" class="tab-link <?= $type === 'question' ? 'active' : '' ?>"><i class="bi bi-question-circle"></i> Questions (<?= $counts['question'] ?>)</a>
        <a href="bookmarks.php?type=resource" class="tab-link <?= $type === 'resource' ? 'active' : '' ?>"><i class="bi bi-folder2"></i> Resources (<?= $counts['resource'] ?>)</a>
        <a href="bookmarks.php?type=forum_post" class="tab-link <?= $type === 'forum_post' ? 'active' : '' ?>"><i class="bi bi-chat-square-text"></i> Forum Posts (<?= $counts['forum_post'] ?>)</a>
        <a href="bookmarks.php?type=project" class="tab-link <?= $type === 'project' ? 'active' : '' ?>"><i class="bi bi-lightning"></i> Projects (<?= $counts['project'] ?>)</a>
    </div>

    <!-- Bookmarks List -->
    <?php if ($bookmarks): ?>
    <div style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($bookmarks as $b): ?>
        <div class="card" style="padding:16px;display:flex;align-items:flex-start;gap:14px;">
            <div style="font-size:1.4rem;flex-shrink:0;">
                <?php 
                $icon = match($b['bookmarkable_type']) {
                    'question'   => '<i class="bi bi-question-circle"></i>',
                    'resource'   => '<i class="bi bi-folder2"></i>',
                    'forum_post' => '<i class="bi bi-chat-square-text"></i>',
                    'project'    => '<i class="bi bi-lightning"></i>',
                    default      => '<i class="bi bi-bookmark"></i>'
                };
                echo $icon;
                ?>
            </div>
            <div style="flex:1;min-width:0;">
                <?php if ($b['bookmarkable_type'] === 'question'): ?>
                    <a href="question.php?id=<?= $b['bookmarkable_id'] ?>" style="font-weight:600;font-size:1rem;color:var(--text);">
                        <?= e($b['item_title']) ?>
                    </a>
                    <div style="font-size:0.78rem;color:var(--text-d);margin-top:4px;">
                        Bookmarked <?= time_ago($b['created_at']) ?>
                    </div>
                <?php elseif ($b['bookmarkable_type'] === 'resource'): ?>
                    <a href="download-resource.php?id=<?= $b['bookmarkable_id'] ?>" style="font-weight:600;font-size:1rem;color:var(--text);">
                        <?= e($b['item_title']) ?>
                    </a>
                    <div style="font-size:0.78rem;color:var(--text-d);margin-top:4px;">
                        Bookmarked <?= time_ago($b['created_at']) ?>
                    </div>
                <?php elseif ($b['bookmarkable_type'] === 'forum_post'): ?>
                    <a href="forum-post.php?id=<?= $b['bookmarkable_id'] ?>" style="font-weight:600;font-size:1rem;color:var(--text);">
                        <?= e($b['item_title']) ?>
                    </a>
                    <div style="font-size:0.78rem;color:var(--text-d);margin-top:4px;">
                        Bookmarked <?= time_ago($b['created_at']) ?>
                    </div>
                <?php elseif ($b['bookmarkable_type'] === 'project'): ?>
                    <a href="showcase.php?id=<?= $b['bookmarkable_id'] ?>" style="font-weight:600;font-size:1rem;color:var(--text);">
                        <?= e($b['item_title']) ?>
                    </a>
                    <div style="font-size:0.78rem;color:var(--text-d);margin-top:4px;">
                        Bookmarked <?= time_ago($b['created_at']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <form method="POST" action="api/bookmark.php" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="type" value="<?= $b['bookmarkable_type'] ?>">
                <input type="hidden" name="id" value="<?= $b['bookmarkable_id'] ?>">
                <button type="submit" class="btn-ghost btn-sm" title="Remove bookmark">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?><?= $type ? "&type=$type" : '' ?>" class="<?= $i === $page ? 'current' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-bookmark" style="font-size:3rem;color:var(--purple-l);"></i></div>
        <p>No bookmarks yet.</p>
        <p style="font-size:0.85rem;color:var(--text-d);">Bookmark questions, resources, and forum posts to access them quickly later.</p>
        <a href="questions.php" class="btn-gold">Browse Questions</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

