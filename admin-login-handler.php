<?php
// =====================================================
// Admin Login Handler
// Uses includes/db.php (mysqli) — NOT the api/ folder
// Returns JSON for fetch() calls from admin-login.html
// =====================================================

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method."]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["error" => "Please enter both email and password."]);
    exit;
}

// Find the user by email
$stmt = $conn->prepare("SELECT user_id, full_name, email, role, status, password FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(["error" => "Invalid email or password."]);
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(["error" => "Invalid email or password."]);
    exit;
}

// Check if the account is active
if ($user['status'] !== 'active') {
    echo json_encode(["error" => "Your account has been disabled."]);
    exit;
}

// Check if the user is an admin
if ($user['role'] !== 'admin') {
    echo json_encode(["error" => "Access denied. Admin credentials required."]);
    exit;
}

// Start admin session
session_regenerate_id(true);
$_SESSION['user_id']   = $user['user_id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role']      = $user['role'];

echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "admin"   => [
        "user_id"   => $user['user_id'],
        "full_name" => $user['full_name'],
        "email"     => $user['email'],
        "role"      => $user['role']
    ]
]);
?>
