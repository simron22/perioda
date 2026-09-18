<?php
require_once __DIR__ . '/../includes/user_auth.php';
require_once __DIR__ . '/../includes/cycle_functions.php';

$user_id = $_SESSION['user_id'];
$periods = get_user_periods($conn, $user_id);
$stats = calculate_cycle_stats($periods);

// which month to show (default current month)
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDay = new DateTime("$year-$month-01");
$daysInMonth = (int) $firstDay->format('t');
$startWeekday = (int) $firstDay->format('w'); // 0 = Sunday

// Build a set of "period days" (Y-m-d strings) from all records
$periodDays = [];
foreach ($periods as $p) {
    $start = new DateTime($p['start_date']);
    $end = $p['end_date'] ? new DateTime($p['end_date']) : (clone $start);
    while ($start <= $end) {
        $periodDays[$start->format('Y-m-d')] = true;
        $start->modify('+1 day');
    }
}

// Build estimated upcoming period days
$estimatedDays = [];
if ($stats['estimated_next']) {
    $est = new DateTime($stats['estimated_next']);
    $len = max(1, (int) $stats['avg_period_length']);
    for ($i = 0; $i < $len; $i++) {
        $estimatedDays[$est->format('Y-m-d')] = true;
        $est->modify('+1 day');
    }
}

$page_title = 'Calendar';
$active = 'calendar';
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div>
        <h1>Calendar</h1>
        <p>Previous periods and estimated upcoming period.</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="btn btn-outline btn-small" href="?month=<?php echo $month - 1; ?>&year=<?php echo $year; ?>">&larr; Prev</a>
        <a class="btn btn-outline btn-small" href="?month=<?php echo $month + 1; ?>&year=<?php echo $year; ?>">Next &rarr;</a>
    </div>
</div>

<div class="card">
    <h3><?php echo $firstDay->format('F Y'); ?></h3>
    <div class="calendar-grid">
        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
            <div class="dow"><?php echo $d; ?></div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $startWeekday; $i++): ?>
            <div class="day-cell blank"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMonth; $day++):
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $class = 'day-cell';
            if (isset($periodDays[$dateStr])) $class .= ' period';
            elseif (isset($estimatedDays[$dateStr])) $class .= ' estimated';
        ?>
            <div class="<?php echo $class; ?>"><?php echo $day; ?></div>
        <?php endfor; ?>
    </div>

    <div class="legend">
        <span><span class="dot" style="background:var(--rose);"></span> Period days</span>
        <span><span class="dot" style="background:var(--rose-light); border:1.5px dashed var(--rose);"></span> Estimated upcoming period</span>
    </div>
</div>

<?php if (!empty($periods)): ?>
<div class="disclaimer">
    ⓘ Estimated dates are based on your average cycle length (<?php echo $stats['avg_cycle_length']; ?> days) and are not guaranteed.
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
