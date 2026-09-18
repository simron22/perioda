<?php
// register.php
header('Content-Type: application/json');

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $name = trim($data['name'] ?? '');
    $age = intval($data['age'] ?? 0);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // Validation
    if (empty($name) || $age <= 0 || empty($email) || empty($password)) {
        echo json_encode(["error" => "All fields are required and must be valid."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["error" => "Invalid email format."]);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["error" => "An account with this email already exists."]);
        exit;
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user (new schema: full_name, email, phone, password, role, status)
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'user', 'active')");
    // We use a default phone number since the old frontend doesn't provide one
    if ($stmt->execute([$name, $email, '0000000000', $password_hash])) {
        // Automatically log them in after registration
        session_start();
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['full_name'] = $name;
        $_SESSION['role'] = 'user';
        
        echo json_encode(["success" => true, "message" => "Account created successfully!"]);
    } else {
        echo json_encode(["error" => "Failed to create account."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
