<?php require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = "Invalid email or password.";
        } elseif ($user['status'] !== 'active') {
            $error = "Your account has been disabled. Please contact the administrator.";
        } else {
            // Regenerate session id on login to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Perioda</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="index.php" class="brand"><span class="dot"></span> Perioda</a>
        <div class="nav-links"><a href="register.php">Sign Up</a></div>
    </div>
</nav>

<div class="auth-wrap">
    <div class="auth-card">
        <h2>Welcome back</h2>
        <p class="auth-sub">Login to continue tracking your cycle.</p>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Account created successfully. Please login.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo h($email); ?>" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <div class="auth-footer">Don't have an account? <a href="register.php">Sign up here</a></div>
    </div>
</div>

</body>
</html>
