<?php
// admin.php
header('Content-Type: application/json');

require 'db.php';

// Get total users (excluding admin)
try {
    $stmtUsers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $totalUsers = $stmtUsers->fetch()['count'];

    // Get total activities (from period_records)
    $stmtActivities = $pdo->query("SELECT COUNT(*) as count FROM period_records");
    $totalActivities = $stmtActivities->fetch()['count'];

    // Get total health tips
    $stmtTips = $pdo->query("SELECT COUNT(*) as count FROM health_tips");
    $totalTips = $stmtTips->fetch()['count'];

    // Get all users for the dashboard
    $stmtAllUsers = $pdo->query("SELECT full_name, email, phone, status, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC");
    $users = $stmtAllUsers->fetchAll();

    $lastUser = null;
    if (count($users) > 0) {
        $lastUser = $users[0];
    }

    echo json_encode([
        "success" => true,
        "totalUsers" => $totalUsers,
        "totalActivities" => $totalActivities,
        "totalTips" => $totalTips,
        "users" => $users,
        "lastUser" => $lastUser
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
