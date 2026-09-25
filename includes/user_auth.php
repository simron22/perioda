<?php
// =====================================================
// Include this at the top of every page under /user/
// Blocks the page unless a normal user is logged in
// =====================================================

require_once __DIR__ . '/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

if ($_SESSION['role'] !== 'user') {
    // An admin account should not browse user-only pages
    redirect('../admin/dashboard.php');
}
