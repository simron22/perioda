<?php
// user.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in.", "loggedIn" => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id AS id, full_name, email, phone, profile_photo, created_at FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($user) {
        echo json_encode(["success" => true, "loggedIn" => true, "user" => $user]);
    } else {
        session_destroy();
        echo json_encode(["error" => "User not found.", "loggedIn" => false]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) {
        echo json_encode(["error" => "User not found."]);
        exit;
    }

    $full_name = trim($_POST['full_name'] ?? $user['full_name']);
    $email = trim($_POST['email'] ?? $user['email']);
    $phone = trim($_POST['phone'] ?? $user['phone']);
    
    $photo_path = $user['profile_photo'];

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $file_info = pathinfo($_FILES['profile_photo']['name']);
        $ext = strtolower($file_info['extension']);
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed_exts)) {
            $new_filename = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
                $photo_path = $destination;
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_photo = ? WHERE user_id = ?");
    if ($stmt->execute([$full_name, $email, $phone, $photo_path, $user['id']])) {
        // Fetch updated user
        $stmt = $pdo->prepare("SELECT user_id AS id, full_name, email, phone, profile_photo, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $updated_user = $stmt->fetch();
        
        echo json_encode(["success" => true, "message" => "Profile updated.", "user" => $updated_user]);
    } else {
        echo json_encode(["error" => "Failed to update profile."]);
    }
}
