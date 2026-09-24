<?php
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';

if (empty($email) || empty($phone)) {
    echo json_encode(["success" => false, "error" => "Email and phone number are required."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ? AND phone = ?");
    $stmt->execute([$email, $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["success" => true, "message" => "Identity verified."]);
    } else {
        echo json_encode(["success" => false, "error" => "No account found matching this email and phone number."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error occurred."]);
}
?>
