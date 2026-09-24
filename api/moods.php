<?php
// moods.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch mood history, newest first
        $stmt = $pdo->prepare("SELECT mood_id AS id, mood, intensity, mood_date AS log_date, created_at FROM moods WHERE user_id = ? ORDER BY mood_date DESC, created_at DESC LIMIT 30");
        $stmt->execute([$user_id]);
        $moods = $stmt->fetchAll();

        echo json_encode(["success" => true, "moods" => $moods]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $mood = trim($data['mood'] ?? '');
        $intensity = intval($data['intensity'] ?? 3);
        $log_date = $data['log_date'] ?? date('Y-m-d');

        if (empty($mood)) {
            echo json_encode(["error" => "Mood is required."]);
            exit;
        }

        if ($intensity < 1 || $intensity > 5) {
            $intensity = 3;
        }

        $stmt = $pdo->prepare("INSERT INTO moods (user_id, mood, intensity, mood_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $mood, $intensity, $log_date])) {
            echo json_encode(["success" => true, "message" => "Mood logged.", "id" => $pdo->lastInsertId()]);
        } else {
            echo json_encode(["error" => "Failed to log mood."]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $mood = trim($data['mood'] ?? '');
        $intensity = intval($data['intensity'] ?? 3);
        $log_date = $data['log_date'] ?? date('Y-m-d');

        if (!$id || empty($mood)) {
            echo json_encode(["error" => "Mood ID and mood are required."]);
            exit;
        }

        if ($intensity < 1 || $intensity > 5) {
            $intensity = 3;
        }

        $stmt = $pdo->prepare("UPDATE moods SET mood = ?, intensity = ?, mood_date = ? WHERE mood_id = ? AND user_id = ?");
        if ($stmt->execute([$mood, $intensity, $log_date, $id, $user_id])) {
            echo json_encode(["success" => true, "message" => "Mood updated."]);
        } else {
            echo json_encode(["error" => "Failed to update mood."]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;

        if (!$id) {
            echo json_encode(["error" => "Mood ID is required."]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM moods WHERE mood_id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            echo json_encode(["success" => true, "message" => "Mood deleted."]);
        } else {
            echo json_encode(["error" => "Failed to delete mood."]);
        }

} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
