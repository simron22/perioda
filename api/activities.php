<?php
// activities.php
header('Content-Type: application/json');
session_start();

require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch activities for the user
    $stmt = $pdo->prepare("SELECT type, value, DATE_FORMAT(time_logged, '%H:%i') as time FROM activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $activities = $stmt->fetchAll();
    
    echo json_encode(["success" => true, "activities" => $activities]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new activity
    $data = json_decode(file_get_contents('php://input'), true);
    
    $type = $data['type'] ?? '';
    $value = $data['value'] ?? '';
    $time = $data['time'] ?? date('H:i:s');
    
    if (empty($type) || empty($value)) {
        echo json_encode(["error" => "Type and value are required."]);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO activities (user_id, type, value, time_logged) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $type, $value, $time])) {
        echo json_encode(["success" => true, "message" => "Activity logged."]);
    } else {
        echo json_encode(["error" => "Failed to log activity."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
