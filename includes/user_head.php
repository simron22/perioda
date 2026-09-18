<?php
// Include after setting $page_title and $active (menu key)
// Expects user_auth.php to already be included by the calling page
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($page_title ?? 'Dashboard'); ?> - Perioda</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="dashboard.php" class="brand"><span class="dot"></span> Perioda</a>
        <div class="nav-links">
            <span style="color:var(--muted); font-size:.9rem;">Hi, <?php echo h($_SESSION['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-outline btn-small">Logout</a>
        </div>
    </div>
</nav>

<div class="app-shell">
    <aside class="sidebar">
        <a href="dashboard.php" class="<?php echo ($active ?? '') === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="period.php" class="<?php echo ($active ?? '') === 'period' ? 'active' : ''; ?>">Period Tracker</a>
        <a href="calendar.php" class="<?php echo ($active ?? '') === 'calendar' ? 'active' : ''; ?>">Calendar</a>
        <a href="symptoms.php" class="<?php echo ($active ?? '') === 'symptoms' ? 'active' : ''; ?>">Symptoms</a>
        <a href="moods.php" class="<?php echo ($active ?? '') === 'moods' ? 'active' : ''; ?>">Mood</a>
        <a href="notes.php" class="<?php echo ($active ?? '') === 'notes' ? 'active' : ''; ?>">Notes</a>
        <div class="side-label">Info</div>
        <a href="health-tips.php" class="<?php echo ($active ?? '') === 'tips' ? 'active' : ''; ?>">Health Tips</a>
        <a href="profile.php" class="<?php echo ($active ?? '') === 'profile' ? 'active' : ''; ?>">Profile</a>
    </aside>
    <main class="main-content">
