<?php
// login.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $email = trim($data['username'] ?? ''); // Using username field as email
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'user';

    if (empty($email) || empty($password)) {
        echo json_encode(["error" => "Please enter both email and password."]);
        exit;
    }

    // Find the user by email
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, profile_photo, created_at, role, status, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            echo json_encode(["error" => "Your account has been disabled."]);
            exit;
        }

        // Password is correct, start session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Don't send password back
        unset($user['password']);
        
        if ($user['role'] === 'admin') {
            echo json_encode(["success" => true, "message" => "Login successful", "role" => "admin", "admin" => $user]);
        } else {
            echo json_encode(["success" => true, "message" => "Login successful", "role" => "user", "user" => $user]);
        }
    } else {
        echo json_encode(["error" => "Incorrect email or password."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
