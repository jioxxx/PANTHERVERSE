<?php
// showcase.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$projects = db_rows("SELECT p.*,u.username FROM projects p JOIN users u ON p.user_id=u.id WHERE p.deleted_at IS NULL ORDER BY p.is_endorsed DESC, p.like_count DESC, p.created_at DESC");
$page_title = 'Project Showcase';
require_once 'includes/header.php';
?>
<div class="page-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div>
      <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;">🚀 Project Showcase</h1>
      <p style="font-size:0.82rem;color:var(--text-d);">Student & capstone projects from across JRMSU</p>
    </div>
    <?php if(is_logged_in()): ?>
    <a href="submit-project.php" class="btn-gold">+ Submit Project</a>
    <?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
    <?php if($projects): foreach($projects as $p): ?>
    <div class="card" style="padding:18px;transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor=''">
      <?php if($p['is_endorsed']): ?><div style="font-size:0.72rem;background:rgba(244,166,35,0.15);color:var(--gold);border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:8px;">🏅 Instructor Endorsed</div><?php endif; ?>
      <h3 style="font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:700;margin-bottom:8px;"><?= e($p['title']) ?></h3>
      <p style="font-size:0.83rem;color:var(--text-d);margin-bottom:12px;line-height:1.5;"><?= e(mb_substr($p['description'],0,120)).(strlen($p['description'])>120?'...':'') ?></p>
      <?php if($p['tech_stack']): ?>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:12px;">
        <?php foreach(array_slice(explode(',',$p['tech_stack']),0,4) as $tech): ?>
        <span class="tag" style="font-size:0.7rem;"><?= e(trim($tech)) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;">
        <div style="font-size:0.78rem;color:var(--text-d);">
          by <a href="profile.php?u=<?= urlencode($p['username']) ?>" style="color:var(--purple-l);">@<?= e($p['username']) ?></a>
        </div>
        <div style="display:flex;gap:8px;">
          <?php if($p['repo_url']): ?><a href="<?= e($p['repo_url']) ?>" target="_blank" class="btn-ghost btn-sm">GitHub</a><?php endif; ?>
          <?php if($p['demo_url']): ?><a href="<?= e($p['demo_url']) ?>" target="_blank" class="btn-gold btn-sm">Demo</a><?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">🚀</div><p>No projects yet. Be the first!</p></div>
    <?php endif; ?>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
