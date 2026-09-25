<?php
require_once __DIR__ . '/../includes/user_auth.php';
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// ---------- Handle CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?: null;
    $flow = $_POST['flow'] ?? 'medium';
    $notes = trim($_POST['notes'] ?? '');
    $period_id = $_POST['period_id'] ?? null;

    if ($start_date === '') {
        $errors[] = "Start date is required.";
    }
    if ($end_date && strtotime($end_date) < strtotime($start_date)) {
        $errors[] = "End date cannot be earlier than start date.";
    }
    if (!in_array($flow, ['light', 'medium', 'heavy'])) {
        $flow = 'medium';
    }

    if (empty($errors)) {
        if ($period_id) {
            // UPDATE - verify ownership first
            $check = $conn->prepare("SELECT user_id FROM period_records WHERE period_id = ?");
            $check->bind_param("i", $period_id);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$row || $row['user_id'] != $user_id) {
                $errors[] = "You are not authorized to edit this record.";
            } else {
                $stmt = $conn->prepare("UPDATE period_records SET start_date=?, end_date=?, flow=?, notes=? WHERE period_id=? AND user_id=?");
                $stmt->bind_param("ssssii", $start_date, $end_date, $flow, $notes, $period_id, $user_id);
                $stmt->execute();
                $stmt->close();
                $success = "Period record updated.";
            }
        } else {
            // CREATE
            $stmt = $conn->prepare("INSERT INTO period_records (user_id, start_date, end_date, flow, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $start_date, $end_date, $flow, $notes);
            $stmt->execute();
            $stmt->close();
            $success = "Period record added.";
        }
    }
}

// ---------- Handle DELETE ----------
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM period_records WHERE period_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    redirect('period.php?deleted=1');
}

// ---------- Load record for editing ----------
$editing = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM period_records WHERE period_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---------- Fetch all records ----------
$stmt = $conn->prepare("SELECT * FROM period_records WHERE user_id = ? ORDER BY start_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$periods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Period Tracker';
$active = 'period';
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div>
        <h1>Period Tracker</h1>
        <p>Record and manage your menstrual periods.</p>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo h($success); ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Period record deleted.</div><?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e) echo h($e) . "<br>"; ?></div>
<?php endif; ?>

<div class="card">
    <h3><?php echo $editing ? 'Edit Period Record' : 'Log a New Period'; ?></h3>
    <form method="POST">
        <?php if ($editing): ?>
            <input type="hidden" name="period_id" value="<?php echo (int) $editing['period_id']; ?>">
        <?php endif; ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required
                       value="<?php echo h($editing['start_date'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date (optional)</label>
                <input type="date" id="end_date" name="end_date"
                       value="<?php echo h($editing['end_date'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="flow">Flow Level</label>
            <select id="flow" name="flow">
                <?php foreach (['light', 'medium', 'heavy'] as $f): ?>
                    <option value="<?php echo $f; ?>" <?php echo (($editing['flow'] ?? 'medium') === $f) ? 'selected' : ''; ?>>
                        <?php echo ucfirst($f); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="notes">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="2" placeholder="Anything you'd like to remember about this period..."><?php echo h($editing['notes'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update Record' : 'Save Record'; ?></button>
        <?php if ($editing): ?>
            <a href="period.php" class="btn btn-outline">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Your Period History</h3>
    <?php if (empty($periods)): ?>
        <div class="empty-state">No period records yet.</div>
    <?php else: ?>
        <table>
            <tr><th>Start</th><th>End</th><th>Flow</th><th>Notes</th><th>Actions</th></tr>
            <?php foreach ($periods as $p): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($p['start_date'])); ?></td>
                    <td><?php echo $p['end_date'] ? date('M j, Y', strtotime($p['end_date'])) : '-'; ?></td>
                    <td><?php echo ucfirst(h($p['flow'])); ?></td>
                    <td><?php echo h($p['notes']) ?: '-'; ?></td>
                    <td class="actions-cell">
                        <a href="period.php?edit=<?php echo $p['period_id']; ?>" class="btn btn-outline btn-small">Edit</a>
                        <a href="period.php?delete=<?php echo $p['period_id']; ?>" class="btn btn-danger btn-small confirm-delete" data-confirm="Delete this period record?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
