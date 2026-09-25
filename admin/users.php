<?php
require_once __DIR__ . '/../includes/admin_auth.php';

// Toggle a user's active/disabled status (admin cannot disable admins)
if (isset($_GET['toggle'])) {
    $target_id = (int) $_GET['toggle'];
    $stmt = $conn->prepare("SELECT role, status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $target_id);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($target && $target['role'] === 'user') {
        $new_status = $target['status'] === 'active' ? 'disabled' : 'active';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_status, $target_id);
        $stmt->execute();
        $stmt->close();
    }
    redirect('users.php');
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'user' AND (full_name LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $users = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

$page_title = 'Users';
$active = 'users';
require_once __DIR__ . '/../includes/admin_head.php';
?>

<div class="page-title">
    <div><h1>Registered Users</h1><p>View and manage user accounts.</p></div>
</div>

<div class="card">
    <form method="GET" style="display:flex; gap:10px;">
        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo h($search); ?>" style="flex:1; padding:10px 14px; border-radius:10px; border:1.5px solid var(--border);">
        <button type="submit" class="btn btn-outline">Search</button>
    </form>
</div>

<div class="card">
    <?php if (empty($users)): ?>
        <div class="empty-state">No users found.</div>
    <?php else: ?>
        <table>
            <tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo h($u['full_name']); ?></td>
                    <td><?php echo h($u['email']); ?></td>
                    <td><?php echo h($u['phone']); ?></td>
                    <td><span class="badge <?php echo $u['status'] === 'active' ? 'badge-active' : 'badge-disabled'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <a href="users.php?toggle=<?php echo $u['user_id']; ?>" class="btn btn-small <?php echo $u['status'] === 'active' ? 'btn-danger' : 'btn-secondary'; ?> confirm-delete"
                           data-confirm="<?php echo $u['status'] === 'active' ? 'Disable this account?' : 'Re-activate this account?'; ?>">
                            <?php echo $u['status'] === 'active' ? 'Disable' : 'Activate'; ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_foot.php'; ?>
