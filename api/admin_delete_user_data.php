<?php
header('Content-Type: application/json');
require 'db.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$type = $data['type'] ?? null;
$item_id = $data['item_id'] ?? null;
$user_id = $data['user_id'] ?? null;

if (!$type || !$item_id || !$user_id) {
    echo json_encode(["success" => false, "error" => "Missing parameters."]);
    exit;
}

try {
    if ($type === 'cycle') {
        $stmt = $pdo->prepare("DELETE FROM period_records WHERE period_id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
    } elseif ($type === 'mood') {
        $stmt = $pdo->prepare("DELETE FROM moods WHERE mood_id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
    } elseif ($type === 'symptom') {
        $stmt = $pdo->prepare("DELETE FROM symptoms WHERE symptom_id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
    } elseif ($type === 'note') {
        $stmt = $pdo->prepare("DELETE FROM notes WHERE note_id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
    } elseif ($type === 'activity') {
        // For sexual activity, $item_id is actually the date
        $date = $item_id;
        // Get all cycles for this user
        $stmt = $pdo->prepare("SELECT period_id, sexual_activity_dates FROM period_records WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deleted = false;
        foreach ($cycles as $record) {
            $dates_str = $record['sexual_activity_dates'] ?? '';
            if (empty($dates_str)) continue;
            
            $dates_arr = explode(',', $dates_str);
            $index = array_search($date, array_map('trim', $dates_arr));
            
            if ($index !== false) {
                unset($dates_arr[$index]);
                $dates_arr = array_unique(array_filter(array_map('trim', $dates_arr)));
                $new_dates_str = implode(',', $dates_arr);
                
                $upd = $pdo->prepare("UPDATE period_records SET sexual_activity_dates = ? WHERE period_id = ?");
                $upd->execute([$new_dates_str, $record['period_id']]);
                $deleted = true;
            }
        }
        
        if ($deleted) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Record not found."]);
        }
        exit;
    } else {
        echo json_encode(["success" => false, "error" => "Invalid type."]);
        exit;
    }
    
    // For normal DELETE queries
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Record not found or already deleted."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
