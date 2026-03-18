<?php
// study-groups.php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Create group
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_group'])) {
    require_login(); csrf_check();
    $name   = trim($_POST['name'] ?? '');
    $subj   = trim($_POST['subject'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $max    = (int)($_POST['max_members'] ?? 20);
    $prog   = (int)($_POST['program_id'] ?? 0);
    $camp   = (int)($_POST['campus_id'] ?? 0);
    if (!$name || !$subj) $error = 'Name and subject are required.';
    else {
        $gid = db_insert("INSERT INTO study_groups (owner_id,name,subject,description,is_private,max_members,program_id,campus_id,created_at,updated_at) VALUES (?,?,?,?,0,?,?,?,NOW(),NOW())",
            [current_user_id(),$name,$subj,$desc,$max,$prog?:null,$camp?:null]);
        db_insert("INSERT INTO study_group_members (user_id,group_id,role,joined_at) VALUES (?,?,'moderator',NOW())", [current_user_id(),$gid]);
        flash('success','Study group created!');
        redirect("study-group.php?id=$gid");
    }
}

// Join / leave
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['join'])) {
    require_login(); csrf_check();
    $gid = (int)$_POST['group_id'];
    $g   = db_row("SELECT * FROM study_groups WHERE id=?", [$gid]);
    $mc  = db_count("SELECT COUNT(*) FROM study_group_members WHERE group_id=?", [$gid]);
    if ($g && $mc < $g['max_members'] && !db_row("SELECT 1 FROM study_group_members WHERE user_id=? AND group_id=?", [current_user_id(),$gid])) {
        db_insert("INSERT INTO study_group_members (user_id,group_id,role,joined_at) VALUES (?,?,'member',NOW())", [current_user_id(),$gid]);
    }
    redirect("study-group.php?id=$gid");
}

$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE sg.name LIKE ? OR sg.subject LIKE ?" : "";
$params = $search ? ["%$search%","%$search%"] : [];
$groups = db_rows("
    SELECT sg.*, u.username AS owner_username, c.code AS campus_code, p.code AS program_code,
           COUNT(DISTINCT sgm.user_id) AS member_count,
           COUNT(DISTINCT sgp.id) AS post_count
    FROM study_groups sg
    JOIN users u ON sg.owner_id=u.id
    LEFT JOIN campuses c ON sg.campus_id=c.id
    LEFT JOIN programs p ON sg.program_id=p.id
    LEFT JOIN study_group_members sgm ON sg.id=sgm.group_id
    LEFT JOIN study_group_posts sgp ON sg.id=sgp.group_id AND sgp.deleted_at IS NULL
    $where
    GROUP BY sg.id
    ORDER BY member_count DESC, sg.created_at DESC
", $params);

$my_group_ids = [];
if(is_logged_in()) {
    $rows = db_rows("SELECT group_id FROM study_group_members WHERE user_id=?", [current_user_id()]);
    $my_group_ids = array_column($rows,'group_id');
}

$bool_true = $GLOBALS['_sql_true'];
$campuses = db_rows("SELECT id,name FROM campuses WHERE is_active=$bool_true ORDER BY name");
$programs = db_rows("SELECT id,name,code FROM programs ORDER BY name");
$page_title = 'Study Groups';
require_once 'includes/header.php';
?>
<div class="page-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">📚 Study Groups</h1>
      <p style="font-size:0.82rem;color:var(--text-d);">Join or create subject-based study groups</p>
    </div>
    <?php if(is_logged_in()): ?>
    <button onclick="document.getElementById('create-form').style.display=document.getElementById('create-form').style.display==='none'?'block':'none'" class="btn-gold">+ Create Group</button>
    <?php endif; ?>
  </div>

  <!-- Create form -->
  <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <div id="create-form" style="display:none;margin-bottom:20px;">
    <div class="card"><div class="card-head"><span class="card-title">📚 Create Study Group</span></div>
    <div class="card-body">
    <form method="POST">
      <?= csrf_field() ?><input type="hidden" name="create_group" value="1">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group"><label>Group Name *</label><input type="text" name="name" placeholder="e.g. BSCS Data Structures Study Group" required></div>
        <div class="form-group"><label>Subject / Topic *</label><input type="text" name="subject" placeholder="e.g. Data Structures & Algorithms" required></div>
        <div class="form-group"><label>Campus</label>
          <select name="campus_id"><option value="">All Campuses</option>
          <?php foreach($campuses as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Program</label>
          <select name="program_id"><option value="">All Programs</option>
          <?php foreach($programs as $p): ?><option value="<?=$p['id']?>"><?= e($p['code']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group"><label>Description</label><textarea name="description" rows="3" placeholder="What will this group focus on?"></textarea></div>
      <div class="form-group" style="max-width:150px;"><label>Max Members</label><input type="number" name="max_members" value="20" min="2" max="100"></div>
      <button type="submit" class="btn-gold">Create Group</button>
    </form></div></div>
  </div>

  <!-- Search -->
  <form method="GET" style="margin-bottom:18px;display:flex;gap:10px;">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search groups by name or subject..." style="flex:1;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:8px 12px;font-size:0.875rem;font-family:'Nunito',sans-serif;outline:none;">
    <button type="submit" class="btn-purple">Search</button>
    <?php if($search): ?><a href="study-groups.php" class="btn-ghost">Clear</a><?php endif; ?>
  </form>

  <!-- Groups grid -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
    <?php if($groups): foreach($groups as $g):
      $is_member = in_array($g['id'], $my_group_ids);
      $is_full   = $g['member_count'] >= $g['max_members'];
    ?>
    <div class="card" style="transition:border-color 0.15s;" onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor=''">
      <div style="padding:16px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;">
          <div style="background:rgba(124,58,237,0.15);width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">📚</div>
          <div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;">
            <?php if($g['campus_code']): ?><span class="tag" style="font-size:0.68rem;"><?= e($g['campus_code']) ?></span><?php endif; ?>
            <?php if($g['program_code']): ?><span class="tag" style="font-size:0.68rem;"><?= e($g['program_code']) ?></span><?php endif; ?>
          </div>
        </div>
        <h3 style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.975rem;margin:8px 0 4px;color:var(--text);"><?= e($g['name']) ?></h3>
        <div style="font-size:0.78rem;color:var(--purple-l);margin-bottom:6px;">📖 <?= e($g['subject']) ?></div>
        <?php if($g['description']): ?>
        <p style="font-size:0.82rem;color:var(--text-d);margin-bottom:10px;line-height:1.5;"><?= e(mb_substr($g['description'],0,90)).(strlen($g['description'])>90?'...':'') ?></p>
        <?php endif; ?>
        <div style="display:flex;gap:12px;font-size:0.78rem;color:var(--text-d);margin-bottom:12px;">
          <span>👥 <?= $g['member_count'] ?>/<?= $g['max_members'] ?></span>
          <span>💬 <?= $g['post_count'] ?> posts</span>
          <span>by <a href="profile.php?u=<?= urlencode($g['owner_username']) ?>" style="color:var(--purple-l);">@<?= e($g['owner_username']) ?></a></span>
        </div>
        <div style="display:flex;gap:8px;">
          <a href="study-group.php?id=<?= $g['id'] ?>" class="btn-ghost btn-sm" style="flex:1;justify-content:center;">View Group</a>
          <?php if($is_member): ?>
          <span class="btn-gold btn-sm" style="cursor:default;opacity:0.85;">✓ Joined</span>
          <?php elseif($is_full): ?>
          <span class="btn-ghost btn-sm" style="opacity:0.5;cursor:not-allowed;">Full</span>
          <?php elseif(is_logged_in()): ?>
          <form method="POST" style="display:inline;">
            <?= csrf_field() ?><input type="hidden" name="join" value="1"><input type="hidden" name="group_id" value="<?= $g['id'] ?>">
            <button type="submit" class="btn-purple btn-sm">+ Join</button>
          </form>
          <?php else: ?>
          <a href="login.php" class="btn-ghost btn-sm">Login</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state" style="grid-column:1/-1;"><div class="empty-icon">📚</div><p>No study groups yet.</p><?php if(is_logged_in()): ?><button onclick="document.getElementById('create-form').style.display='block'" class="btn-gold">Create the First One</button><?php endif; ?></div>
    <?php endif; ?>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
