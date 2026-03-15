<?php
// calendar.php - Enhanced Academic Calendar
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = is_logged_in() ? current_user() : null;
$uid = $user ? current_user_id() : 0;

// Get current month/year
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

// Get filter
$event_type = $_GET['type'] ?? '';

// Build calendar data
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day = mktime(0, 0, 0, $month, 1, $year);
$start_day = date('w', $first_day);
$month_name = date('F', $first_day);

// Get events for this month
$start_date = sprintf('%04d-%02d-01', $year, $month);
$end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);

$query = "SELECT * FROM calendar_events WHERE event_date >= ? AND event_date <= ?";
$params = [$start_date, $end_date];

if ($event_type) {
    $query .= " AND event_type = ?";
    $params[] = $event_type;
}

$query .= " ORDER BY event_date ASC";
$events = db_rows($query, $params);

// Group events by day
$events_by_day = [];
foreach ($events as $e) {
    $day = (int)date('j', strtotime($e['event_date']));
    if (!isset($events_by_day[$day])) $events_by_day[$day] = [];
    $events_by_day[$day][] = $e;
}

// Get upcoming events
$upcoming = db_rows("
    SELECT * FROM calendar_events 
    WHERE event_date >= CURDATE() 
    ORDER BY event_date ASC 
    LIMIT 10
");

// Event type colors
$type_colors = ['exam'=>'#ef4444','deadline'=>'#f4a623','holiday'=>'#10b981','event'=>'#7c3aed','class'=>'#3b82f6','other'=>'#6b7280'];
$type_icons = ['exam'=>'📝','deadline'=>'⏰','holiday'=>'🌴','event'=>'🎉','class'=>'📚','other'=>'📌'];

$page_title = 'Academic Calendar';
require_once 'includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">🗓️ Academic Calendar</h1>
            <p style="font-size:0.82rem;color:var(--text-d);">Stay updated with exams, deadlines, and events</p>
        </div>
    </div>

    <!-- Month Navigation -->
    <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
        <a href="?month=<?= $month-1 ?>&year=<?= $month < 1 ? $year-1 : $year ?>" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>
        <span style="font-family:'Rajdhani',sans-serif;font-size:1.3rem;font-weight:700;min-width:180px;text-align:center;"><?= $month_name ?> <?= $year ?></span>
        <a href="?month=<?= $month+1 ?>&year=<?= $month > 11 ? $year+1 : $year ?>" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
        <a href="calendar.php" class="btn-ghost btn-sm" style="margin-left:auto;">Today</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;">
        <!-- Calendar Grid -->
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="display:grid;grid-template-columns:repeat(7,1fr);background:var(--bg3);border-bottom:1px solid var(--border);">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div style="padding:10px;text-align:center;font-weight:700;font-size:0.8rem;color:var(--text-m);"><?= $d ?></div>
                <?php endforeach; ?>
            </div>
            
            <div style="display:grid;grid-template-columns:repeat(7,1fr);">
                <?php for ($i = 0; $i < $start_day; $i++): ?>
                <div style="min-height:100px;background:var(--surface);border-right:1px solid var(--border);border-bottom:1px solid var(--border);opacity:0.5;"></div>
                <?php endfor; ?>
                
                <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                <?php $is_today = (date('j') == $day && date('n') == $month && date('Y') == $year); ?>
                <div style="min-height:100px;background:var(--surface);border-right:1px solid var(--border);border-bottom:1px solid var(--border);padding:6px;<?= $is_today ? 'background:rgba(124,58,237,0.1);' : '' ?>">
                    <div style="font-weight:700;font-size:0.85rem;margin-bottom:4px;<?= $is_today ? 'color:var(--gold);' : 'color:var(--text-m);' ?>">
                        <?= $day ?>
                        <?php if ($is_today): ?><span style="font-size:0.65rem;background:var(--gold);color:var(--bg);padding:1px 5px;border-radius:3px;margin-left:4px;">TODAY</span><?php endif; ?>
                    </div>
                    <?php if (isset($events_by_day[$day])): ?>
                        <?php foreach (array_slice($events_by_day[$day], 0, 3) as $ev): ?>
                        <div style="font-size:0.65rem;padding:2px 4px;margin-bottom:2px;border-radius:3px;background:<?= $type_colors[$ev['event_type']] ?>20;border-left:2px solid <?= $type_colors[$ev['event_type']] ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= $type_icons[$ev['event_type']] ?> <?= e($ev['title']) ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div class="widget">
                <div class="widget-head">📅 Upcoming Events</div>
                <div class="widget-body" style="padding:0;">
                    <?php if ($upcoming): foreach ($upcoming as $ev): ?>
                    <div style="padding:10px 14px;border-bottom:1px solid rgba(124,58,237,0.1);display:flex;gap:10px;align-items:flex-start;">
                        <div style="font-size:1.1rem;"><?= $type_icons[$ev['event_type']] ?></div>
                        <div>
                            <div style="font-size:0.83rem;font-weight:600;color:var(--text);"><?= e($ev['title']) ?></div>
                            <div style="font-size:0.75rem;color:<?= $type_colors[$ev['event_type']] ?>;margin-top:2px;"><?= date('M j, Y', strtotime($ev['event_date'])) ?> · <?= ucfirst($ev['event_type']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div style="padding:24px;text-align:center;color:var(--text-d);font-size:0.85rem;">No upcoming events</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="widget">
                <div class="widget-head">🎨 Event Types</div>
                <div class="widget-body">
                    <?php foreach ($type_colors as $type => $color): ?>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:0.8rem;">
                        <span style="width:12px;height:12px;background:<?= $color ?>;border-radius:3px;"></span>
                        <span><?= $type_icons[$type] ?> <?= ucfirst($type) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

