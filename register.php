<?php require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$errors = [];
$full_name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ---------- Full name ----------
    if ($full_name === '') {
        $errors[] = "Full name is required.";
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $full_name)) {
        $errors[] = "Full name may only contain letters and spaces.";
    }

    // ---------- Email ----------
    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // ---------- Phone ----------
    if ($phone === '') {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $errors[] = "Phone number must contain exactly 10 digits.";
    } elseif (!preg_match('/^(97|98)/', $phone)) {
        $errors[] = "Phone number must start with 97 or 98.";
    }

    // ---------- Password ----------
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must be at least 8 characters and contain letters and numbers.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // ---------- Duplicate email check ----------
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        }
        $stmt->close();
    }

    // ---------- Save new user ----------
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'user', 'active')");
        $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed);
        if ($stmt->execute()) {
            $stmt->close();
            redirect('login.php?registered=1');
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up - Perioda</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="index.php" class="brand"><span class="dot"></span> Perioda</a>
        <div class="nav-links"><a href="login.php">Login</a></div>
    </div>
</nav>

<div class="auth-wrap">
    <div class="auth-card">
        <h2>Create your account</h2>
        <p class="auth-sub">Track your cycle privately and securely.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err) echo h($err) . "<br>"; ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" novalidate>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo h($full_name); ?>" placeholder="e.g. Saisha Sharma" required>
                <div class="field-msg"></div>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo h($email); ?>" placeholder="you@example.com" required>
                <div class="field-msg"></div>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo h($phone); ?>" placeholder="98XXXXXXXX" maxlength="10" required>
                <div class="field-msg"></div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
                <div class="field-msg"></div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                <div class="field-msg"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <div class="auth-footer">Already have an account? <a href="login.php">Login here</a></div>
    </div>
</div>

<script src="assets/js/validation.js"></script>
</body>
</html>
