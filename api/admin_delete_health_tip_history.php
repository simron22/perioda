<?php
header('Content-Type: application/json');
require 'db.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$history_id = $data['history_id'] ?? null;

if (!$history_id) {
    echo json_encode(["success" => false, "error" => "History ID is required."]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM user_health_tip_history WHERE history_id = ?");
    $stmt->execute([$history_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Record not found or already deleted."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
