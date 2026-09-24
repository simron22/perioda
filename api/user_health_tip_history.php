<?php
// user_health_tip_history.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmtHistory = $pdo->prepare("SELECT history_id AS id, condition_name, symptoms_searched AS query, requested_at AS created_at FROM user_health_tip_history WHERE user_id = ? ORDER BY requested_at DESC");
        $stmtHistory->execute([$user_id]);
        $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(["success" => true, "history" => $history]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to fetch history."]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;

    if (!$id) {
        echo json_encode(["error" => "History ID is required."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM user_health_tip_history WHERE history_id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            echo json_encode(["success" => true, "message" => "History item deleted."]);
        } else {
            echo json_encode(["error" => "Failed to delete history item."]);
        }
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to delete history item."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
