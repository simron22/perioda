<?php
require_once __DIR__ . '/../includes/user_auth.php';
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

$MOOD_OPTIONS = ['Happy','Normal','Sad','Irritated','Anxious','Tired','Stressed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mood = trim($_POST['mood'] ?? '');
    $date = $_POST['mood_date'] ?? '';
    $mood_id = $_POST['mood_id'] ?? null;

    if ($mood === '' || $date === '') {
        $errors[] = "Please choose a mood and a date.";
    }

    if (empty($errors)) {
        if ($mood_id) {
            $check = $conn->prepare("SELECT user_id FROM moods WHERE mood_id = ?");
            $check->bind_param("i", $mood_id);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            $check->close();
            if (!$row || $row['user_id'] != $user_id) {
                $errors[] = "You are not authorized to edit this record.";
            } else {
                $stmt = $conn->prepare("UPDATE moods SET mood=?, mood_date=? WHERE mood_id=? AND user_id=?");
                $stmt->bind_param("ssii", $mood, $date, $mood_id, $user_id);
                $stmt->execute();
                $stmt->close();
                $success = "Mood updated.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO moods (user_id, mood, mood_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $mood, $date);
            $stmt->execute();
            $stmt->close();
            $success = "Mood recorded.";
        }
    }
}

if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM moods WHERE mood_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    redirect('moods.php?deleted=1');
}

$editing = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM moods WHERE mood_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM moods WHERE user_id = ? ORDER BY mood_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$moods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Mood';
$active = 'moods';
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div><h1>Mood Tracker</h1><p>Track your mood patterns over time.</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo h($success); ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Mood entry deleted.</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo h($e) . "<br>"; ?></div><?php endif; ?>

<div class="card">
    <h3><?php echo $editing ? 'Edit Mood' : 'Add Mood'; ?></h3>
    <form method="POST">
        <?php if ($editing): ?><input type="hidden" name="mood_id" value="<?php echo (int) $editing['mood_id']; ?>"><?php endif; ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label for="mood">Mood</label>
                <select id="mood" name="mood" required>
                    <option value="">Select a mood</option>
                    <?php foreach ($MOOD_OPTIONS as $opt): ?>
                        <option value="<?php echo h($opt); ?>" <?php echo (($editing['mood'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo h($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="mood_date">Date</label>
                <input type="date" id="mood_date" name="mood_date" required value="<?php echo h($editing['mood_date'] ?? date('Y-m-d')); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update' : 'Save'; ?></button>
        <?php if ($editing): ?><a href="moods.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Mood History</h3>
    <?php if (empty($moods)): ?>
        <div class="empty-state">No mood entries yet.</div>
    <?php else: ?>
        <table>
            <tr><th>Date</th><th>Mood</th><th>Actions</th></tr>
            <?php foreach ($moods as $m): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($m['mood_date'])); ?></td>
                    <td><?php echo h($m['mood']); ?></td>
                    <td class="actions-cell">
                        <a href="moods.php?edit=<?php echo $m['mood_id']; ?>" class="btn btn-outline btn-small">Edit</a>
                        <a href="moods.php?delete=<?php echo $m['mood_id']; ?>" class="btn btn-danger btn-small confirm-delete" data-confirm="Delete this mood entry?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
