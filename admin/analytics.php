<?php
// admin/analytics.php - Admin Analytics Dashboard
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';


require_login();
require_role('admin');


$user = current_user();

// Get analytics data
$stats = get_analytics_summary();

// Get popular content
$popular_questions = get_popular_content('questions', 30, 10);
$popular_resources = get_popular_content('resources', 30, 10);

// Get recent activity
$recent_users = db_rows("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$recent_questions = db_rows("SELECT q.*, u.username FROM questions q JOIN users u ON q.user_id = u.id WHERE q.deleted_at IS NULL ORDER BY q.created_at DESC LIMIT 10");
$recent_activity = db_rows("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 20");

// Get user stats by role
$users_by_role = db_rows("SELECT role, COUNT(*) as count FROM users WHERE is_active = 1 GROUP BY role");

// Get content stats
$content_stats = [
    'questions' => db_count("SELECT COUNT(*) FROM questions WHERE deleted_at IS NULL"),
    'answers' => db_count("SELECT COUNT(*) FROM answers WHERE deleted_at IS NULL"),
    'resources' => db_count("SELECT COUNT(*) FROM resources WHERE deleted_at IS NULL"),
    'forum_posts' => db_count("SELECT COUNT(*) FROM forum_posts WHERE deleted_at IS NULL"),
    'projects' => db_count("SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL"),
];

// Get pending items
$pending_flags = db_count("SELECT COUNT(*) FROM content_flags WHERE status = 'pending'");
$pending_reports = db_count("SELECT COUNT(*) FROM reports WHERE status = 'pending'");

$page_title = 'Analytics Dashboard';
require_once '../includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">📊 Analytics Dashboard</h1>
            <p style="font-size:0.82rem;color:var(--text-d);">Platform statistics and insights</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="index.php" class="btn-ghost">← Back to Admin</a>
        </div>
    </div>

    <!-- Overview Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
        <div class="card" style="padding:20px;text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:var(--gold);"><?= number_format($stats['total_users']) ?></div>
            <div style="font-size:0.85rem;color:var(--text-d);">Total Users</div>
            <div style="font-size:0.75rem;color:var(--green);margin-top:4px;">+<?= $stats['new_users_week'] ?> this week</div>
        </div>
        <div class="card" style="padding:20px;text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:var(--purple-l);"><?= number_format($stats['total_questions']) ?></div>
            <div style="font-size:0.85rem;color:var(--text-d);">Questions</div>
            <div style="font-size:0.75rem;color:var(--gold);margin-top:4px;">+<?= $stats['questions_today'] ?> today</div>
        </div>
        <div class="card" style="padding:20px;text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:var(--green);"><?= number_format($stats['total_answers']) ?></div>
            <div style="font-size:0.85rem;color:var(--text-d);">Answers</div>
            <div style="font-size:0.75rem;color:var(--gold);margin-top:4px;">+<?= $stats['answers_today'] ?> today</div>
        </div>
        <div class="card" style="padding:20px;text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:var(--text-m);"><?= number_format($stats['total_resources']) ?></div>
            <div style="font-size:0.85rem;color:var(--text-d);">Resources</div>
            <div style="font-size:0.75rem;color:var(--text-d);margin-top:4px;"><?= $content_stats['forum_posts'] ?> forum posts</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <!-- Popular Questions -->
        <div class="card">
            <div class="card-head"><span class="card-title">🔥 Hot Questions (30 days)</span></div>
            <div class="card-body" style="padding:0;">
                <?php if ($popular_questions): foreach ($popular_questions as $q): ?>
                <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <a href="../question.php?id=<?= $q['id'] ?>" style="font-size:0.85rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:250px;"><?= e($q['title']) ?></a>
                    <div style="display:flex;gap:12px;font-size:0.75rem;color:var(--text-d);">
                        <span>👁️ <?= $q['view_count'] ?></span>
                        <span>⬆️ <?= $q['vote_count'] ?></span>
                        <span>💬 <?= $q['answer_count'] ?></span>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div style="padding:24px;text-align:center;color:var(--text-d);">No questions yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Popular Resources -->
        <div class="card">
            <div class="card-head"><span class="card-title">📁 Popular Resources</span></div>
            <div class="card-body" style="padding:0;">
                <?php if ($popular_resources): foreach ($popular_resources as $r): ?>
                <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <a href="../download-resource.php?id=<?= $r['id'] ?>" style="font-size:0.85rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:250px;"><?= e($r['title']) ?></a>
                    <div style="font-size:0.75rem;color:var(--text-d);">⬇️ <?= $r['download_count'] ?></div>
                </div>
                <?php endforeach; else: ?>
                <div style=";text-align:center;colorpadding:24px:var(--text-d);">No resources yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="card">
            <div class="card-head"><span class="card-title">👥 Recent Members</span></div>
            <div class="card-body" style="padding:0;">
                <?php if ($recent_users): foreach ($recent_users as $u): ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
                    <img src="<?= avatar_url($u['username']) ?>" style="width:32px;height:32px;border-radius:50%;">
                    <div style="flex:1;min-width:0;">
                        <a href="../profile.php?u=<?= urlencode($u['username']) ?>" style="font-size:0.85rem;color:var(--text);"><?= e($u['name']) ?></a>
                        <div style="font-size:0.75rem;color:var(--text-d);">@<?= e($u['username']) ?> · <?= $u['role'] ?></div>
                    </div>
                    <span style="font-size:0.75rem;color:var(--text-d);"><?= time_ago($u['created_at']) ?></span>
                </div>
                <?php endforeach; else: ?>
                <div style="padding:24px;text-align:center;color:var(--text-d);">No users yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-head"><span class="card-title">⚡ Recent Activity</span></div>
            <div class="card-body" style="padding:0;max-height:400px;overflow-y:auto;">
                <?php if ($recent_activity): foreach ($recent_activity as $a): ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);font-size:0.8rem;">
                    <span style="color:var(--purple-l);"><?= $a['action'] ?></span>
                    <?php if ($a['entity_type']): ?>
                    <span style="color:var(--text-d);"> on <?= $a['entity_type'] ?></span>
                    <?php endif; ?>
                    <span style="color:var(--text-d);float:right;"><?= time_ago($a['created_at']) ?></span>
                </div>
                <?php endforeach; else: ?>
                <div style="padding:24px;text-align:center;color:var(--text-d);">No recent activity</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin-top:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
        <?php if ($pending_flags > 0): ?>
        <a href="flags.php" class="card" style="padding:16px;text-decoration:none;border-left:3px solid var(--red);">
            <div style="font-size:1.5rem;margin-bottom:8px;">🚩</div>
            <div style="font-weight:600;color:var(--text);"><?= $pending_flags ?> Pending Flags</div>
            <div style="font-size:0.8rem;color:var(--text-d);">Review content flags</div>
        </a>
        <?php endif; ?>
        <?php if ($pending_reports > 0): ?>
        <a href="reports.php" class="card" style="padding:16px;text-decoration:none;border-left:3px solid var(--gold);">
            <div style="font-size:1.5rem;margin-bottom:8px;">📢</div>
            <div style="font-weight:600;color:var(--text);"><?= $pending_reports ?> Pending Reports</div>
            <div style="font-size:0.8rem;color:var(--text-d);">Review user reports</div>
        </a>
        <?php endif; ?>
        <a href="users.php" class="card" style="padding:16px;text-decoration:none;border-left:3px solid var(--purple);">
            <div style="font-size:1.5rem;margin-bottom:8px;">👥</div>
            <div style="font-weight:600;color:var(--text);">Manage Users</div>
            <div style="font-size:0.8rem;color:var(--text-d);">View all users</div>
        </a>
        <a href="../directory.php" class="card" style="padding:16px;text-decoration:none;border-left:3px solid var(--green);">
            <div style="font-size:1.5rem;margin-bottom:8px;">🔍</div>
            <div style="font-weight:600;color:var(--text);">User Directory</div>
            <div style="font-size:0.8rem;color:var(--text-d);">Browse community</div>
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

