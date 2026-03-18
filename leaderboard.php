<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$period   = $_GET['period'] ?? 'alltime';
$campus   = (int)($_GET['campus'] ?? 0);
$tab      = $_GET['tab'] ?? 'students';

// Date filter
$date_cond = match($period) {
    'week'  => "AND rl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    'month' => "AND rl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    default => ""
};

$campus_cond = $campus ? "AND u.campus_id = $campus" : "";
$role_cond   = $tab === 'instructors' ? "AND u.role IN ('instructor','admin')" : "AND u.role = 'student'";

// For all-time we just use reputation directly
if ($period === 'alltime') {
    $leaders = db_rows("
        SELECT u.id, u.name, u.username, u.reputation, u.role, u.campus_id,
               c.code AS campus_code,
               u.reputation AS period_rep,
               (SELECT COUNT(*) FROM questions q WHERE q.user_id=u.id AND q.deleted_at IS NULL) AS q_count,
               (SELECT COUNT(*) FROM answers a WHERE a.user_id=u.id AND a.is_accepted=1 AND a.deleted_at IS NULL) AS accepted_count
        FROM users u
        LEFT JOIN campuses c ON u.campus_id=c.id
        WHERE u.is_active=1 $role_cond $campus_cond
        ORDER BY u.reputation DESC
        LIMIT 20
    ");
} else {
    $leaders = db_rows("
        SELECT u.id, u.name, u.username, u.reputation, u.role, u.campus_id,
               c.code AS campus_code,
               COALESCE(SUM(rl.amount),0) AS period_rep,
               (SELECT COUNT(*) FROM questions q WHERE q.user_id=u.id AND q.deleted_at IS NULL) AS q_count,
               (SELECT COUNT(*) FROM answers a WHERE a.user_id=u.id AND a.is_accepted=1 AND a.deleted_at IS NULL) AS accepted_count
        FROM users u
        LEFT JOIN campuses c ON u.campus_id=c.id
        LEFT JOIN reputation_logs rl ON u.id=rl.user_id $date_cond
        WHERE u.is_active=1 $role_cond $campus_cond
        GROUP BY u.id
        ORDER BY period_rep DESC, u.reputation DESC
        LIMIT 20
    ");
}

$bool_true = $GLOBALS['_sql_true'];
$campuses   = db_rows("SELECT id, name, code FROM campuses WHERE is_active=$bool_true ORDER BY name");
$my_rank    = null;
if (is_logged_in()) {
    $my_rank = db_count("SELECT COUNT(*) FROM users WHERE reputation > ? AND is_active=1 AND role=?", [current_user()['reputation'], current_user_role()]) + 1;
}

$page_title = 'Leaderboard';
require_once 'includes/header.php';

$medal = ['🥇','🥈','🥉'];
?>
<div class="page-wrap" style="max-width:860px;">
  <div style="text-align:center;margin-bottom:28px;">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;">🏆 Leaderboard</h1>
    <p style="color:var(--text-d);margin-top:4px;">Top contributors of PANTHERVERSE</p>
    <?php if($my_rank): ?>
    <p style="margin-top:8px;font-size:0.875rem;color:var(--gold);">
      You are ranked <strong>#<?= $my_rank ?></strong> among <?= $tab === 'students' ? 'students' : 'instructors' ?> 🎯
    </p>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;justify-content:center;">
    <div class="tabs" style="border:none;gap:4px;">
      <?php foreach(['students'=>'🎓 Students','instructors'=>'👨‍🏫 Instructors'] as $k=>$v): ?>
      <a href="?tab=<?=$k?>&period=<?=$period?>&campus=<?=$campus?>" class="tab-link <?=$tab===$k?'active':''?>"><?=$v?></a>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:6px;margin-left:auto;">
      <?php foreach(['alltime'=>'All Time','month'=>'This Month','week'=>'This Week'] as $k=>$v): ?>
      <a href="?tab=<?=$tab?>&period=<?=$k?>&campus=<?=$campus?>" class="<?=$period===$k?'btn-purple':'btn-ghost'?> btn-sm"><?=$v?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="?tab=<?=$tab?>&period=<?=$period?>&campus=0" class="<?=$campus===0?'btn-purple':'btn-ghost'?> btn-sm">All Campuses</a>
    <?php foreach($campuses as $c): ?>
    <a href="?tab=<?=$tab?>&period=<?=$period?>&campus=<?=$c['id']?>" class="<?=$campus==$c['id']?'btn-purple':'btn-ghost'?> btn-sm"><?= e($c['code']) ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Top 3 podium -->
  <?php if(count($leaders) >= 3): ?>
  <div style="display:flex;align-items:flex-end;justify-content:center;gap:14px;margin-bottom:28px;">
    <?php $podium = [1=>$leaders[1], 0=>$leaders[0], 2=>$leaders[2]]; // 2nd, 1st, 3rd order
    foreach($podium as $pos=>$p):
      $height = $pos===0 ? '120px' : ($pos===1 ? '90px' : '70px');
      $sz = $pos===0 ? '64px' : '50px';
    ?>
    <div style="text-align:center;flex:1;max-width:200px;">
      <img src="<?= avatar_url($p['username']) ?>" style="width:<?=$sz?>;height:<?=$sz?>;border-radius:50%;border:3px solid var(--gold);box-shadow:0 0 20px rgba(244,166,35,0.3);margin-bottom:8px;" alt="">
      <div style="font-size:<?=$pos===0?'1rem':'0.875rem'?>;font-weight:700;color:var(--text);"><?= e(explode(' ',$p['name'])[0]) ?></div>
      <div style="font-size:0.75rem;color:var(--text-d);">@<?= e($p['username']) ?></div>
      <div style="font-size:0.82rem;color:var(--gold);margin:4px 0;">⭐ <?= number_format($p['period_rep']) ?></div>
      <div style="background:linear-gradient(180deg,var(--purple-d),var(--purple));height:<?=$height?>;border-radius:8px 8px 0 0;display:flex;align-items:flex-start;justify-content:center;padding-top:10px;font-size:1.5rem;margin-top:8px;">
        <?= $medal[$pos] ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Full table -->
  <div class="card">
    <div style="overflow-x:auto;">
      <table class="pv-table">
        <thead>
          <tr>
            <th style="width:50px;">Rank</th>
            <th>User</th>
            <th>Campus</th>
            <th style="text-align:center;">Rep Points</th>
            <th style="text-align:center;">Questions</th>
            <th style="text-align:center;">Accepted Ans.</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($leaders as $i=>$l): $rank_num = $i+1; ?>
          <tr style="<?= is_logged_in() && current_user_id()==$l['id'] ? 'background:rgba(244,166,35,0.05);' : '' ?>">
            <td style="text-align:center;">
              <?php if($rank_num <= 3): ?>
              <span style="font-size:1.2rem;"><?= $medal[$rank_num-1] ?></span>
              <?php else: ?>
              <span style="font-family:'Rajdhani',sans-serif;font-weight:700;color:var(--text-d);">#<?= $rank_num ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <img src="<?= avatar_url($l['username']) ?>" style="width:32px;height:32px;border-radius:50%;" alt="">
                <div>
                  <a href="profile.php?u=<?= urlencode($l['username']) ?>" style="font-weight:700;font-size:0.875rem;color:var(--text);"><?= e($l['name']) ?></a>
                  <?php if(is_logged_in() && current_user_id()==$l['id']): ?><span style="font-size:0.7rem;color:var(--gold);margin-left:4px;">(You)</span><?php endif; ?>
                  <div style="font-size:0.75rem;color:var(--text-d);">@<?= e($l['username']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:0.82rem;color:var(--text-d);"><?= e($l['campus_code'] ?? '—') ?></td>
            <td style="text-align:center;">
              <span style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;color:var(--gold);">⭐ <?= number_format($l['period_rep']) ?></span>
            </td>
            <td style="text-align:center;color:var(--purple-l);"><?= $l['q_count'] ?></td>
            <td style="text-align:center;color:var(--green);"><?= $l['accepted_count'] ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($leaders)): ?>
          <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-d);">No data yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
