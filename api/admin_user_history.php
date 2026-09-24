<?php
// admin_user_history.php
header('Content-Type: application/json');
require 'db.php';

// Very basic admin check - assuming admin is logged in if this is called, 
// but you should implement proper session role checks.
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // echo json_encode(["success" => false, "error" => "Unauthorized"]);
    // exit;
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "error" => "User ID is required."]);
    exit;
}

try {
    // Fetch user details
    $stmtUser = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "error" => "User not found."]);
        exit;
    }

    // Fetch Cycles
    $stmtCycles = $pdo->prepare("SELECT period_id, start_date, end_date, estimated_ovulation_date, sexual_activity_dates FROM period_records WHERE user_id = ? ORDER BY start_date DESC");
    $stmtCycles->execute([$user_id]);
    $cycles = $stmtCycles->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Moods
    $stmtMoods = $pdo->prepare("SELECT mood_id, mood, intensity, mood_date AS log_date FROM moods WHERE user_id = ? ORDER BY mood_date DESC");
    $stmtMoods->execute([$user_id]);
    $moods = $stmtMoods->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Symptoms
    $stmtSymptoms = $pdo->prepare("SELECT symptom_id, symptom, severity, symptom_date AS log_date FROM symptoms WHERE user_id = ? ORDER BY symptom_date DESC");
    $stmtSymptoms->execute([$user_id]);
    $symptoms = $stmtSymptoms->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Notes
    $stmtNotes = $pdo->prepare("SELECT note_id, title, note AS content, note_date AS log_date FROM notes WHERE user_id = ? ORDER BY created_at DESC");
    $stmtNotes->execute([$user_id]);
    $notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Health Tip History
    $stmtHistory = $pdo->prepare("SELECT history_id AS id, condition_name, symptoms_searched AS query, requested_at AS created_at FROM user_health_tip_history WHERE user_id = ? ORDER BY requested_at DESC");
    $stmtHistory->execute([$user_id]);
    $health_tip_history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "full_name" => $user['full_name'],
        "cycles" => $cycles,
        "moods" => $moods,
        "symptoms" => $symptoms,
        "health_tips_history" => $health_tip_history,
        "notes" => $notes
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
