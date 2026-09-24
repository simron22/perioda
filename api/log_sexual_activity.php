<?php
// log_sexual_activity.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $date = $data['date'] ?? null;
    $action = $data['action'] ?? 'toggle'; // 'toggle', 'add', 'remove'

    if (!$date) {
        echo json_encode(["error" => "Date is required."]);
        exit;
    }

    try {
        // Find the most appropriate period record
        $stmt = $pdo->prepare("SELECT period_id, sexual_activity_dates FROM period_records WHERE user_id = ? AND start_date <= ? ORDER BY start_date DESC LIMIT 1");
        $stmt->execute([$user_id, $date]);
        $record = $stmt->fetch();

        if (!$record) {
            $stmt = $pdo->prepare("SELECT period_id, sexual_activity_dates FROM period_records WHERE user_id = ? ORDER BY start_date ASC LIMIT 1");
            $stmt->execute([$user_id]);
            $record = $stmt->fetch();
        }

        if (!$record) {
            echo json_encode(["error" => "Please log at least one period cycle before tracking sexual activity."]);
            exit;
        }

        $dates_str = $record['sexual_activity_dates'] ?? '';
        $dates_arr = empty($dates_str) ? [] : explode(',', $dates_str);

        $index = array_search($date, $dates_arr);
        $is_active = false;

        if ($action === 'toggle') {
            if ($index !== false) {
                // Remove it
                unset($dates_arr[$index]);
                $is_active = false;
            } else {
                // Add it
                $dates_arr[] = $date;
                $is_active = true;
            }
        } elseif ($action === 'add') {
            if ($index === false) {
                $dates_arr[] = $date;
            }
            $is_active = true;
        } elseif ($action === 'remove') {
            if ($index !== false) {
                unset($dates_arr[$index]);
            }
            $is_active = false;
        }

        // Clean array
        $dates_arr = array_unique(array_filter(array_map('trim', $dates_arr)));
        $new_dates_str = implode(',', $dates_arr);

        $upd = $pdo->prepare("UPDATE period_records SET sexual_activity_dates = ? WHERE period_id = ?");
        $upd->execute([$new_dates_str, $record['period_id']]);

        echo json_encode(["success" => true, "is_active" => $is_active, "dates" => $dates_arr]);

    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
