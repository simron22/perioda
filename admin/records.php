<?php
require_once __DIR__ . '/../includes/admin_auth.php';

// Privacy note: the admin can see aggregate counts per user, but NOT the
// private details of period dates, symptoms, moods, or notes themselves.
$rows = $conn->query("
    SELECT u.user_id, u.full_name, u.email,
           (SELECT COUNT(*) FROM period_records p WHERE p.user_id = u.user_id) AS period_count,
           (SELECT COUNT(*) FROM symptoms s WHERE s.user_id = u.user_id) AS symptom_count,
           (SELECT COUNT(*) FROM moods m WHERE m.user_id = u.user_id) AS mood_count,
           (SELECT COUNT(*) FROM notes n WHERE n.user_id = u.user_id) AS note_count
    FROM users u
    WHERE u.role = 'user'
    ORDER BY u.full_name ASC
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Records';
$active = 'records';
require_once __DIR__ . '/../includes/admin_head.php';
?>

<div class="page-title">
    <div><h1>Tracking Records Overview</h1><p>Aggregate record counts per user.</p></div>
</div>

<div class="disclaimer">
    ⓘ For privacy, the admin can only see how many records each user has &mdash; not the actual period dates, symptoms, moods, or notes. Those remain visible only to the account owner.
</div>

<div class="card">
    <?php if (empty($rows)): ?>
        <div class="empty-state">No users found.</div>
    <?php else: ?>
        <table>
            <tr><th>User</th><th>Email</th><th>Periods</th><th>Symptoms</th><th>Moods</th><th>Notes</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo h($r['full_name']); ?></td>
                    <td><?php echo h($r['email']); ?></td>
                    <td><?php echo $r['period_count']; ?></td>
                    <td><?php echo $r['symptom_count']; ?></td>
                    <td><?php echo $r['mood_count']; ?></td>
                    <td><?php echo $r['note_count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_foot.php'; ?>
