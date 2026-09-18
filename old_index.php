<?php require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perioda - Period Tracker & Women's Health Management</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="brand"><span class="dot"></span> Perioda</div>
        <div class="nav-links">
            <a href="login.php">Login</a>
            <a href="register.php" class="btn btn-primary btn-small">Sign Up</a>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <h1>Understand your cycle, <span class="highlight">one day at a time</span></h1>
        <p class="sub">Perioda helps you record your periods, track symptoms and mood, and get simple estimates for your next cycle — all in one private, secure place.</p>
        <div class="hero-actions">
            <a href="register.php" class="btn btn-primary">Create Free Account</a>
            <a href="login.php" class="btn btn-outline">I already have an account</a>
        </div>
    </div>
</section>

<section class="container">
    <div class="features">
        <div class="feature-card">
            <div class="icon">🩸</div>
            <h3>Period Tracking</h3>
            <p>Log start and end dates, flow level, and notes for every cycle.</p>
        </div>
        <div class="feature-card">
            <div class="icon">📅</div>
            <h3>Cycle Calendar</h3>
            <p>See past periods and estimated upcoming periods on a simple calendar.</p>
        </div>
        <div class="feature-card">
            <div class="icon">🙂</div>
            <h3>Symptoms & Mood</h3>
            <p>Record how you feel each day to spot your personal patterns over time.</p>
        </div>
        <div class="feature-card">
            <div class="icon">💡</div>
            <h3>Health Tips</h3>
            <p>Browse general wellness tips on hygiene, nutrition, comfort, and more.</p>
        </div>
        <div class="feature-card">
            <div class="icon">🔒</div>
            <h3>Private & Secure</h3>
            <p>Your health data is only ever visible to you, protected with secure login.</p>
        </div>
        <div class="feature-card">
            <div class="icon">📝</div>
            <h3>Personal Notes</h3>
            <p>Keep private notes alongside your records to remember how each cycle went.</p>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container">
        &copy; <?php echo date('Y'); ?> Perioda &mdash; A BCA Software Engineering Mini Project.
        Perioda is a personal tracking tool and is not a substitute for professional medical advice.
    </div>
</footer>

</body>
</html>
