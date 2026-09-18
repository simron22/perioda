<?php
require_once __DIR__ . '/../includes/user_auth.php';
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

$SYMPTOM_OPTIONS = ['Cramps','Headache','Back Pain','Bloating','Fatigue','Breast Tenderness','Nausea','Acne','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symptom = trim($_POST['symptom'] ?? '');
    $date = $_POST['symptom_date'] ?? '';
    $symptom_id = $_POST['symptom_id'] ?? null;

    if ($symptom === '' || $date === '') {
        $errors[] = "Please choose a symptom and a date.";
    }

    if (empty($errors)) {
        if ($symptom_id) {
            $check = $conn->prepare("SELECT user_id FROM symptoms WHERE symptom_id = ?");
            $check->bind_param("i", $symptom_id);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            $check->close();
            if (!$row || $row['user_id'] != $user_id) {
                $errors[] = "You are not authorized to edit this record.";
            } else {
                $stmt = $conn->prepare("UPDATE symptoms SET symptom=?, symptom_date=? WHERE symptom_id=? AND user_id=?");
                $stmt->bind_param("ssii", $symptom, $date, $symptom_id, $user_id);
                $stmt->execute();
                $stmt->close();
                $success = "Symptom updated.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO symptoms (user_id, symptom, symptom_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $symptom, $date);
            $stmt->execute();
            $stmt->close();
            $success = "Symptom recorded.";
        }
    }
}

if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM symptoms WHERE symptom_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    redirect('symptoms.php?deleted=1');
}

$editing = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM symptoms WHERE symptom_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM symptoms WHERE user_id = ? ORDER BY symptom_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$symptoms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Symptoms';
$active = 'symptoms';
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div><h1>Symptoms</h1><p>Record how your body feels day to day.</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo h($success); ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Symptom deleted.</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo h($e) . "<br>"; ?></div><?php endif; ?>

<div class="card">
    <h3><?php echo $editing ? 'Edit Symptom' : 'Add a Symptom'; ?></h3>
    <form method="POST">
        <?php if ($editing): ?><input type="hidden" name="symptom_id" value="<?php echo (int) $editing['symptom_id']; ?>"><?php endif; ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label for="symptom">Symptom</label>
                <select id="symptom" name="symptom" required>
                    <option value="">Select a symptom</option>
                    <?php foreach ($SYMPTOM_OPTIONS as $opt): ?>
                        <option value="<?php echo h($opt); ?>" <?php echo (($editing['symptom'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo h($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="symptom_date">Date</label>
                <input type="date" id="symptom_date" name="symptom_date" required value="<?php echo h($editing['symptom_date'] ?? date('Y-m-d')); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update' : 'Save'; ?></button>
        <?php if ($editing): ?><a href="symptoms.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Symptom History</h3>
    <?php if (empty($symptoms)): ?>
        <div class="empty-state">No symptoms recorded yet.</div>
    <?php else: ?>
        <table>
            <tr><th>Date</th><th>Symptom</th><th>Actions</th></tr>
            <?php foreach ($symptoms as $s): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($s['symptom_date'])); ?></td>
                    <td><?php echo h($s['symptom']); ?></td>
                    <td class="actions-cell">
                        <a href="symptoms.php?edit=<?php echo $s['symptom_id']; ?>" class="btn btn-outline btn-small">Edit</a>
                        <a href="symptoms.php?delete=<?php echo $s['symptom_id']; ?>" class="btn btn-danger btn-small confirm-delete" data-confirm="Delete this symptom?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
