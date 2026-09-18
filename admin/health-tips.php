<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$categories = ['Menstrual Hygiene','Nutrition','Exercise','Rest and Sleep','Period Comfort','General Wellness'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $tip_id = $_POST['tip_id'] ?? null;

    if ($title === '' || $content === '' || !in_array($category, $categories)) {
        $errors[] = "Please fill in all fields with a valid category.";
    }

    if (empty($errors)) {
        if ($tip_id) {
            $stmt = $conn->prepare("UPDATE health_tips SET title=?, category=?, content=? WHERE tip_id=?");
            $stmt->bind_param("sssi", $title, $category, $content, $tip_id);
            $stmt->execute();
            $stmt->close();
            $success = "Health tip updated.";
        } else {
            $stmt = $conn->prepare("INSERT INTO health_tips (title, category, content) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $category, $content);
            $stmt->execute();
            $stmt->close();
            $success = "Health tip added.";
        }
    }
}

if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM health_tips WHERE tip_id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();
    redirect('health-tips.php?deleted=1');
}

$editing = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM health_tips WHERE tip_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$tips = $conn->query("SELECT * FROM health_tips ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Health Tips';
$active = 'tips';
require_once __DIR__ . '/../includes/admin_head.php';
?>

<div class="page-title">
    <div><h1>Manage Health Tips</h1><p>Add, edit, or remove wellness tips shown to users.</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo h($success); ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Health tip deleted.</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo h($e) . "<br>"; ?></div><?php endif; ?>

<div class="card">
    <h3><?php echo $editing ? 'Edit Health Tip' : 'Add a Health Tip'; ?></h3>
    <form method="POST">
        <?php if ($editing): ?><input type="hidden" name="tip_id" value="<?php echo (int) $editing['tip_id']; ?>"><?php endif; ?>
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required value="<?php echo h($editing['title'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select a category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo h($cat); ?>" <?php echo (($editing['category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo h($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" rows="3" required><?php echo h($editing['content'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update Tip' : 'Add Tip'; ?></button>
        <?php if ($editing): ?><a href="health-tips.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>All Health Tips</h3>
    <?php if (empty($tips)): ?>
        <div class="empty-state">No health tips yet.</div>
    <?php else: ?>
        <table>
            <tr><th>Title</th><th>Category</th><th>Actions</th></tr>
            <?php foreach ($tips as $t): ?>
                <tr>
                    <td><?php echo h($t['title']); ?></td>
                    <td><span class="tag"><?php echo h($t['category']); ?></span></td>
                    <td class="actions-cell">
                        <a href="health-tips.php?edit=<?php echo $t['tip_id']; ?>" class="btn btn-outline btn-small">Edit</a>
                        <a href="health-tips.php?delete=<?php echo $t['tip_id']; ?>" class="btn btn-danger btn-small confirm-delete" data-confirm="Delete this health tip?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_foot.php'; ?>
