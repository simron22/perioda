<?php
// =====================================================
// Include this at the top of every page under /admin/
// Blocks the page unless the logged-in account is admin
// =====================================================

require_once __DIR__ . '/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

if ($_SESSION['role'] !== 'admin') {
    // A normal user should not be able to reach admin pages
    redirect('../user/dashboard.php');
}
