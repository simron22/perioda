<?php
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($email) || empty($phone) || empty($new_password)) {
    echo json_encode(["success" => false, "error" => "All fields are required."]);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(["success" => false, "error" => "Password must be at least 6 characters."]);
    exit;
}

try {
    // Verify identity one more time just to be secure
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND phone = ?");
    $stmt->execute([$email, $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the password in the database
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        
        if ($updateStmt->execute([$hashed_password, $user['user_id']])) {
            echo json_encode(["success" => true, "message" => "Password updated successfully."]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to update password."]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Invalid verification details."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error occurred."]);
}
?>
