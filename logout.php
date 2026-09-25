<?php require_once __DIR__ . '/includes/auth.php';

// Destroy the session completely
$_SESSION = [];
session_destroy();

redirect('login.php');
