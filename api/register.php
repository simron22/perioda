<?php
// register.php
header('Content-Type: application/json');

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $name = trim($data['name'] ?? '');
    $dob = trim($data['dob'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // Validation
    if (empty($name) || empty($dob) || empty($phone) || empty($email) || empty($password)) {
        echo json_encode(["error" => "All fields are required and must be valid."]);
        exit;
    }
    
    if (!preg_match('/^\+977(96|97|98)\d{8}$/', $phone)) {
        echo json_encode(["error" => "Please enter a valid Nepali mobile number (e.g., +9779812345678)."]);
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

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, dob, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'active')");
    if ($stmt->execute([$name, $email, $dob, $phone, $password_hash])) {
        // Automatically log them in after registration
        session_start();
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $name;
        $_SESSION['role'] = 'user';
        
        $user = [
            "user_id" => $user_id,
            "full_name" => $name,
            "email" => $email,
            "phone" => $phone,
            "profile_photo" => null,
            "role" => "user",
            "status" => "active"
        ];
        
        echo json_encode(["success" => true, "message" => "Account created successfully!", "user" => $user]);
    } else {
        echo json_encode(["error" => "Failed to create account."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
