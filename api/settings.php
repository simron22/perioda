<?php
// settings.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch user settings
    $stmt = $pdo->prepare("SELECT cycle_length, period_duration FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();

    if (!$settings) {
        // Return defaults if no settings exist yet
        $settings = [
            "cycle_length" => 28,
            "period_duration" => 5
        ];
    }

    echo json_encode(["success" => true, "settings" => $settings]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $cycle_length = intval($data['cycle_length'] ?? 28);
    $period_duration = intval($data['period_duration'] ?? 5);

    // Validate ranges
    if ($cycle_length < 20 || $cycle_length > 45) {
        echo json_encode(["error" => "Cycle length must be between 20 and 45 days."]);
        exit;
    }

    if ($period_duration < 2 || $period_duration > 10) {
        echo json_encode(["error" => "Period duration must be between 2 and 10 days."]);
        exit;
    }

    // Upsert: insert or update
    $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, cycle_length, period_duration) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cycle_length = VALUES(cycle_length), period_duration = VALUES(period_duration)");
    if ($stmt->execute([$user_id, $cycle_length, $period_duration])) {
        echo json_encode(["success" => true, "message" => "Settings saved."]);
    } else {
        echo json_encode(["error" => "Failed to save settings."]);
    }

} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
