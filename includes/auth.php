<?php
// =====================================================
// Core session + helper functions
// Included by every page that needs sessions
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Escape output to prevent XSS when printing user data
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Is someone logged in at all?
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Is the logged-in person an admin?
function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

// Redirect helper
function redirect($path) {
    header("Location: $path");
    exit;
}
