<?php
// admin.php
header('Content-Type: application/json');

require 'db.php';

// Get total users (excluding admin)
try {
    $stmtUsers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $totalUsers = $stmtUsers->fetch()['count'];

    // Get active users
    $stmtActive = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND status = 'active'");
    $activeUsers = $stmtActive->fetch()['count'];

    // Get total activities (from period_records)
    $stmtActivities = $pdo->query("SELECT COUNT(*) as count FROM period_records");
    $totalActivities = $stmtActivities->fetch()['count'];

    // Get total health tips
    $stmtTips = $pdo->query("SELECT COUNT(*) as count FROM health_tips");
    $totalTips = $stmtTips->fetch()['count'];

    // Get all users for the dashboard
    $stmtAllUsers = $pdo->query("SELECT user_id, full_name, email, phone, status, created_at, dob FROM users WHERE role = 'user' ORDER BY created_at DESC");
    $usersRaw = $stmtAllUsers->fetchAll(PDO::FETCH_ASSOC);

    $users = [];
    foreach ($usersRaw as $u) {
        $age = 'N/A';
        if (!empty($u['dob'])) {
            $dob = new DateTime($u['dob']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
        }
        $u['age'] = $age;
        $users[] = $u;
    }

    $lastUser = null;
    if (count($users) > 0) {
        $lastUser = $users[0];
    }

    // Get sexual activity aggregated data
    $stmtActivity = $pdo->query("SELECT sexual_activity_dates FROM period_records WHERE sexual_activity_dates IS NOT NULL AND sexual_activity_dates != ''");
    $allDatesStr = $stmtActivity->fetchAll(PDO::FETCH_COLUMN);
    
    $sexualActivityCounts = [];
    foreach ($allDatesStr as $datesStr) {
        $dates = explode(',', $datesStr);
        foreach ($dates as $date) {
            $date = trim($date);
            if (!empty($date)) {
                if (!isset($sexualActivityCounts[$date])) {
                    $sexualActivityCounts[$date] = 0;
                }
                $sexualActivityCounts[$date]++;
            }
        }
    }

    echo json_encode([
        "success" => true,
        "totalUsers" => $totalUsers,
        "activeUsers" => $activeUsers,
        "totalActivities" => $totalActivities,
        "totalTips" => $totalTips,
        "users" => $users,
        "lastUser" => $lastUser,
        "sexualActivityCounts" => $sexualActivityCounts
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
