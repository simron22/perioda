<?php
// symptoms.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch symptom history, newest first
        $stmt = $pdo->prepare("SELECT symptom_id AS id, symptom, severity, symptom_date AS log_date, created_at FROM symptoms WHERE user_id = ? ORDER BY symptom_date DESC, created_at DESC LIMIT 30");
        $stmt->execute([$user_id]);
        $symptoms = $stmt->fetchAll();

        echo json_encode(["success" => true, "symptoms" => $symptoms]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $symptom = trim($data['symptom'] ?? '');
        $severity = $data['severity'] ?? 'mild';
        $log_date = $data['log_date'] ?? date('Y-m-d');

        if (empty($symptom)) {
            echo json_encode(["error" => "Symptom is required."]);
            exit;
        }

        // Validate severity
        $validSeverities = ['mild', 'moderate', 'severe'];
        if (!in_array($severity, $validSeverities)) {
            $severity = 'mild';
        }

        $stmt = $pdo->prepare("INSERT INTO symptoms (user_id, symptom, severity, symptom_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $symptom, $severity, $log_date])) {
            echo json_encode(["success" => true, "message" => "Symptom logged.", "id" => $pdo->lastInsertId()]);
        } else {
            echo json_encode(["error" => "Failed to log symptom."]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $symptom = trim($data['symptom'] ?? '');
        $severity = $data['severity'] ?? 'mild';
        $log_date = $data['log_date'] ?? date('Y-m-d');

        if (!$id || empty($symptom)) {
            echo json_encode(["error" => "Symptom ID and symptom are required."]);
            exit;
        }

        $validSeverities = ['mild', 'moderate', 'severe'];
        if (!in_array($severity, $validSeverities)) {
            $severity = 'mild';
        }

        $stmt = $pdo->prepare("UPDATE symptoms SET symptom = ?, severity = ?, symptom_date = ? WHERE symptom_id = ? AND user_id = ?");
        if ($stmt->execute([$symptom, $severity, $log_date, $id, $user_id])) {
            echo json_encode(["success" => true, "message" => "Symptom updated."]);
        } else {
            echo json_encode(["error" => "Failed to update symptom."]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;

        if (!$id) {
            echo json_encode(["error" => "Symptom ID is required."]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM symptoms WHERE symptom_id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            echo json_encode(["success" => true, "message" => "Symptom deleted."]);
        } else {
            echo json_encode(["error" => "Failed to delete symptom."]);
        }

} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
