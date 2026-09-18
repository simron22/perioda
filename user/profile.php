<?php
require_once __DIR__ . '/../includes/user_auth.php';
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// current data
$stmt = $conn->prepare("SELECT full_name, email, phone, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!preg_match('/^[A-Za-z\s]+$/', $full_name)) {
        $errors[] = "Full name may only contain letters and spaces.";
    }
    if (!preg_match('/^\d{10}$/', $phone) || !preg_match('/^(97|98)/', $phone)) {
        $errors[] = "Phone number must be exactly 10 digits and start with 97 or 98.";
    }

    // optional password change
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';
    $changePassword = ($new_password !== '' || $confirm_password !== '');
    if ($changePassword) {
        if (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $errors[] = "New password must be at least 8 characters with letters and numbers.";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        }
    }

    if (empty($errors)) {
        if ($changePassword) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, password=? WHERE user_id=?");
            $stmt->bind_param("sssi", $full_name, $phone, $hashed, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=? WHERE user_id=?");
            $stmt->bind_param("ssi", $full_name, $phone, $user_id);
        }
        $stmt->execute();
        $stmt->close();
        $_SESSION['full_name'] = $full_name;
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
        $success = "Profile updated successfully.";
    }
}

$page_title = 'Profile';
$active = 'profile';
require_once __DIR__ . '/../includes/user_head.php';
?>

<div class="page-title">
    <div><h1>My Profile</h1><p>View and update your account information.</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo h($success); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo h($e) . "<br>"; ?></div><?php endif; ?>

<div class="card">
    <h3>Account Details</h3>
    <form method="POST">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo h($user['full_name']); ?>" required>
        </div>
        <div class="form-group">
            <label>University Email</label>
            <input type="email" value="<?php echo h($user['email']); ?>" disabled>
        </div>
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" maxlength="10" value="<?php echo h($user['phone']); ?>" required>
        </div>
        <p style="color:var(--muted); font-size:.85rem;">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>

        <h3 style="margin-top:26px;">Change Password <span style="color:var(--muted); font-weight:400; font-size:.85rem;">(optional)</span></h3>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
        </div>
        <div class="form-group">
            <label for="confirm_new_password">Confirm New Password</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/user_foot.php'; ?>
