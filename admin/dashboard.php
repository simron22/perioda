<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$totalUsers = $conn->query("SELECT COUNT(*) c FROM users WHERE role = 'user'")->fetch_assoc()['c'];
$activeUsers = $conn->query("SELECT COUNT(*) c FROM users WHERE role = 'user' AND status = 'active'")->fetch_assoc()['c'];
$totalPeriods = $conn->query("SELECT COUNT(*) c FROM period_records")->fetch_assoc()['c'];
$totalTips = $conn->query("SELECT COUNT(*) c FROM health_tips")->fetch_assoc()['c'];

$recentUsers = $conn->query("SELECT full_name, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Admin Dashboard';
$active = 'dashboard';
require_once __DIR__ . '/../includes/admin_head.php';
?>

<div class="page-title">
    <div><h1>Admin Dashboard</h1><p>System overview.</p></div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="label">Registered Users</div><div class="value"><?php echo $totalUsers; ?></div></div>
    <div class="stat-card"><div class="label">Active Users</div><div class="value"><?php echo $activeUsers; ?></div></div>
    <div class="stat-card"><div class="label">Period Records</div><div class="value"><?php echo $totalPeriods; ?></div></div>
    <div class="stat-card"><div class="label">Health Tips</div><div class="value"><?php echo $totalTips; ?></div></div>
</div>

<div class="card">
    <h3>Recently Registered Users</h3>
    <?php if (empty($recentUsers)): ?>
        <div class="empty-state">No users yet.</div>
    <?php else: ?>
        <table>
            <tr><th>Name</th><th>Email</th><th>Joined</th></tr>
            <?php foreach ($recentUsers as $u): ?>
                <tr>
                    <td><?php echo h($u['full_name']); ?></td>
                    <td><?php echo h($u['email']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_foot.php'; ?>
