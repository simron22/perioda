<?php
// Include after setting $page_title and $active (menu key)
// Expects admin_auth.php to already be included by the calling page
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($page_title ?? 'Admin'); ?> - Perioda Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="dashboard.php" class="brand"><span class="dot"></span> Perioda <span class="badge badge-admin" style="margin-left:6px;">Admin</span></a>
        <div class="nav-links">
            <span style="color:var(--muted); font-size:.9rem;">Hi, <?php echo h($_SESSION['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-outline btn-small">Logout</a>
        </div>
    </div>
</nav>

<div class="app-shell">
    <aside class="sidebar">
        <a href="dashboard.php" class="<?php echo ($active ?? '') === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="users.php" class="<?php echo ($active ?? '') === 'users' ? 'active' : ''; ?>">Users</a>
        <a href="records.php" class="<?php echo ($active ?? '') === 'records' ? 'active' : ''; ?>">Records</a>
        <a href="health-tips.php" class="<?php echo ($active ?? '') === 'tips' ? 'active' : ''; ?>">Health Tips</a>
    </aside>
    <main class="main-content">
