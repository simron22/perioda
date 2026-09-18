<?php
// cycles.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all cycle logs for user, newest first
    $stmt = $pdo->prepare("SELECT period_id AS id, start_date, end_date, estimated_ovulation_date, notes, created_at FROM period_records WHERE user_id = ? ORDER BY start_date DESC");
    $stmt->execute([$user_id]);
    $cycles = $stmt->fetchAll();

    echo json_encode(["success" => true, "cycles" => $cycles]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $start_date = $data['start_date'] ?? '';
    $end_date = $data['end_date'] ?? null;
    $notes = $data['notes'] ?? '';

    if (empty($start_date)) {
        echo json_encode(["error" => "Start date is required."]);
        exit;
    }

    // Fetch user cycle length
    $stmt_set = $pdo->prepare("SELECT cycle_length FROM user_settings WHERE user_id = ?");
    $stmt_set->execute([$user_id]);
    $settings = $stmt_set->fetch();
    $cycle_length = $settings ? (int)$settings['cycle_length'] : 28;
    
    // Calculate estimated ovulation date
    $start_date_obj = new DateTime($start_date);
    $ovulation_days = $cycle_length - 14;
    $start_date_obj->modify("+$ovulation_days days");
    $estimated_ovulation_date = $start_date_obj->format('Y-m-d');

    $stmt = $pdo->prepare("INSERT INTO period_records (user_id, start_date, end_date, estimated_ovulation_date, notes) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $start_date, $end_date, $estimated_ovulation_date, $notes])) {
        echo json_encode(["success" => true, "message" => "Cycle logged.", "id" => $pdo->lastInsertId()]);
    } else {
        echo json_encode(["error" => "Failed to log cycle."]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $start_date = $data['start_date'] ?? '';
    $end_date = $data['end_date'] ?? null;
    $notes = $data['notes'] ?? '';

    if (!$id || empty($start_date)) {
        echo json_encode(["error" => "Cycle ID and Start date are required."]);
        exit;
    }

    // Fetch user cycle length
    $stmt_set = $pdo->prepare("SELECT cycle_length FROM user_settings WHERE user_id = ?");
    $stmt_set->execute([$user_id]);
    $settings = $stmt_set->fetch();
    $cycle_length = $settings ? (int)$settings['cycle_length'] : 28;
    
    // Calculate estimated ovulation date
    $start_date_obj = new DateTime($start_date);
    $ovulation_days = $cycle_length - 14;
    $start_date_obj->modify("+$ovulation_days days");
    $estimated_ovulation_date = $start_date_obj->format('Y-m-d');

    $stmt = $pdo->prepare("UPDATE period_records SET start_date = ?, end_date = ?, estimated_ovulation_date = ?, notes = ? WHERE period_id = ? AND user_id = ?");
    if ($stmt->execute([$start_date, $end_date, $estimated_ovulation_date, $notes, $id, $user_id])) {
        echo json_encode(["success" => true, "message" => "Cycle updated."]);
    } else {
        echo json_encode(["error" => "Failed to update cycle."]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;

    if (!$id) {
        echo json_encode(["error" => "Cycle ID is required."]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM period_records WHERE period_id = ? AND user_id = ?");
    if ($stmt->execute([$id, $user_id])) {
        echo json_encode(["success" => true, "message" => "Cycle deleted."]);
    } else {
        echo json_encode(["error" => "Failed to delete cycle."]);
    }

} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
