<?php
// admin_delete_user.php
header('Content-Type: application/json');
require 'db.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$target_id = $data['id'] ?? ($_GET['id'] ?? null);

if (!$target_id) {
    echo json_encode(["success" => false, "error" => "User ID is required."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Delete dependent records manually in case foreign keys don't cascade
    $stmt1 = $pdo->prepare("DELETE FROM period_records WHERE user_id = ?");
    $stmt1->execute([$target_id]);

    $stmt2 = $pdo->prepare("DELETE FROM moods WHERE user_id = ?");
    $stmt2->execute([$target_id]);

    $stmt3 = $pdo->prepare("DELETE FROM symptoms WHERE user_id = ?");
    $stmt3->execute([$target_id]);

    $stmt4 = $pdo->prepare("DELETE FROM notes WHERE user_id = ?");
    $stmt4->execute([$target_id]);

    $stmt5 = $pdo->prepare("DELETE FROM user_settings WHERE user_id = ?");
    $stmt5->execute([$target_id]);

    $stmt6 = $pdo->prepare("DELETE FROM user_health_tip_history WHERE user_id = ?");
    $stmt6->execute([$target_id]);

    // Finally delete the user
    $stmtUser = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
    $stmtUser->execute([$target_id]);

    if ($stmtUser->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(["success" => true]);
    } else {
        $pdo->rollBack();
        echo json_encode(["success" => false, "error" => "Could not delete user. They may be an admin or not exist."]);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
