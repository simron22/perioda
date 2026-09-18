<?php
require_once __DIR__ . '/../includes/user_auth.php';
require_once __DIR__ . '/../includes/cycle_functions.php';

$user_id = $_SESSION['user_id'];

$periods = get_user_periods($conn, $user_id);
$stats = calculate_cycle_stats($periods);

// recent symptoms (last 5)
$stmt = $conn->prepare("SELECT symptom, symptom_date FROM symptoms WHERE user_id = ? ORDER BY symptom_date DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_symptoms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// recent mood (last 1)
$stmt = $conn->prepare("SELECT mood, mood_date FROM moods WHERE user_id = ? ORDER BY mood_date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_mood = $stmt->get_result()->fetch_assoc();
$stmt->close();

$page_title = 'Dashboard';
$active = 'dashboard';
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div>
        <h1>Welcome back, <?php echo h(explode(' ', $_SESSION['full_name'])[0]); ?> 👋</h1>
        <p>Here's an overview of your cycle.</p>
    </div>
    <a href="period.php" class="btn btn-primary">+ Log Period</a>
</div>

<?php if ($stats['total_records'] === 0): ?>
    <div class="card empty-state">
        <h3>No period records yet</h3>
        <p>Start by logging your most recent period to see your stats and estimates here.</p>
        <a href="period.php" class="btn btn-primary">Log Your First Period</a>
    </div>
<?php else: ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Last Period</div>
            <div class="value"><?php echo date('M j, Y', strtotime($stats['last_period_start'])); ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Estimated Next Period</div>
            <div class="value"><?php echo date('M j, Y', strtotime($stats['estimated_next'])); ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Current Cycle Day</div>
            <div class="value"><?php echo $stats['current_cycle_day'] ?? '-'; ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Average Cycle</div>
            <div class="value"><?php echo $stats['avg_cycle_length']; ?> days</div>
        </div>
        <div class="stat-card">
            <div class="label">Average Period</div>
            <div class="value"><?php echo $stats['avg_period_length']; ?> days</div>
        </div>
        <div class="stat-card">
            <div class="label">Tracked Cycles</div>
            <div class="value"><?php echo $stats['total_records']; ?></div>
        </div>
    </div>

    <div class="disclaimer">
        ⓘ These numbers are simple <strong>estimates</strong> based on your previous records, not a medical prediction.
    </div>
<?php endif; ?>

<div class="card">
    <h3>Recent Symptoms & Mood</h3>
    <?php if (empty($recent_symptoms) && !$recent_mood): ?>
        <p style="color:var(--muted);">No symptoms or mood recorded yet. <a href="symptoms.php">Add one</a>.</p>
    <?php else: ?>
        <?php if ($recent_mood): ?>
            <p><strong>Latest mood:</strong> <?php echo h($recent_mood['mood']); ?> on <?php echo date('M j, Y', strtotime($recent_mood['mood_date'])); ?></p>
        <?php endif; ?>
        <?php if (!empty($recent_symptoms)): ?>
            <p><strong>Recent symptoms:</strong>
            <?php echo h(implode(', ', array_map(fn($s) => $s['symptom'], $recent_symptoms))); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card tip-card" style="border-top: 4px solid var(--primary-color);">
    <span class="tag">New Feature</span>
    <h3>Perioda Health Advisor</h3>
    <p>Search your symptoms to learn about possible period-related conditions and what you can do to feel better.</p>
    <a href="health-tips.php" class="btn btn-outline btn-small">Try the Symptom Advisor</a>
</div>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
