<?php
require_once __DIR__ . '/../includes/user_auth.php';
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_text = trim($_POST['note_text'] ?? '');
    $date = $_POST['note_date'] ?? '';
    $note_id = $_POST['note_id'] ?? null;

    if ($note_text === '' || $date === '') {
        $errors[] = "Please write a note and choose a date.";
    }

    if (empty($errors)) {
        if ($note_id) {
            $check = $conn->prepare("SELECT user_id FROM notes WHERE note_id = ?");
            $check->bind_param("i", $note_id);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            $check->close();
            if (!$row || $row['user_id'] != $user_id) {
                $errors[] = "You are not authorized to edit this note.";
            } else {
                $stmt = $conn->prepare("UPDATE notes SET note_text=?, note_date=? WHERE note_id=? AND user_id=?");
                $stmt->bind_param("ssii", $note_text, $date, $note_id, $user_id);
                $stmt->execute();
                $stmt->close();
                $success = "Note updated.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO notes (user_id, note_text, note_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $note_text, $date);
            $stmt->execute();
            $stmt->close();
            $success = "Note saved.";
        }
    }
}

if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    redirect('notes.php?deleted=1');
}

$editing = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY note_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Notes';
$active = 'notes';
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div><h1>Personal Notes</h1><p>Private notes only visible to you.</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo h($success); ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Note deleted.</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo h($e) . "<br>"; ?></div><?php endif; ?>

<div class="card">
    <h3><?php echo $editing ? 'Edit Note' : 'Add a Note'; ?></h3>
    <form method="POST">
        <?php if ($editing): ?><input type="hidden" name="note_id" value="<?php echo (int) $editing['note_id']; ?>"><?php endif; ?>
        <div class="form-group">
            <label for="note_date">Date</label>
            <input type="date" id="note_date" name="note_date" required value="<?php echo h($editing['note_date'] ?? date('Y-m-d')); ?>">
        </div>
        <div class="form-group">
            <label for="note_text">Note</label>
            <textarea id="note_text" name="note_text" rows="3" placeholder="e.g. Felt tired today, cramps in the evening..." required><?php echo h($editing['note_text'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update' : 'Save'; ?></button>
        <?php if ($editing): ?><a href="notes.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Your Notes</h3>
    <?php if (empty($notes)): ?>
        <div class="empty-state">No notes yet.</div>
    <?php else: ?>
        <table>
            <tr><th>Date</th><th>Note</th><th>Actions</th></tr>
            <?php foreach ($notes as $n): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($n['note_date'])); ?></td>
                    <td><?php echo h($n['note_text']); ?></td>
                    <td class="actions-cell">
                        <a href="notes.php?edit=<?php echo $n['note_id']; ?>" class="btn btn-outline btn-small">Edit</a>
                        <a href="notes.php?delete=<?php echo $n['note_id']; ?>" class="btn btn-danger btn-small confirm-delete" data-confirm="Delete this note?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
