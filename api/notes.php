<?php
// notes.php
header('Content-Type: application/json');
session_start();

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch all notes, newest first
        $stmt = $pdo->prepare("SELECT note_id AS id, title, content, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $notes = $stmt->fetchAll();

        echo json_encode(["success" => true, "notes" => $notes]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');

        if (empty($title) || empty($content)) {
            echo json_encode(["error" => "Title and content are required."]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, note_date) VALUES (?, ?, ?, CURDATE())");
        if ($stmt->execute([$user_id, $title, $content])) {
            echo json_encode(["success" => true, "message" => "Note saved.", "id" => $pdo->lastInsertId()]);
        } else {
            echo json_encode(["error" => "Failed to save note."]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;

        if (!$id) {
            echo json_encode(["error" => "Note ID is required."]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM notes WHERE note_id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            echo json_encode(["success" => true, "message" => "Note deleted."]);
        } else {
            echo json_encode(["error" => "Failed to delete note."]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');

        if (!$id || empty($title) || empty($content)) {
            echo json_encode(["error" => "Note ID, title, and content are required."]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ? WHERE note_id = ? AND user_id = ?");
        if ($stmt->execute([$title, $content, $id, $user_id])) {
            echo json_encode(["success" => true, "message" => "Note updated."]);
        } else {
            echo json_encode(["error" => "Failed to update note."]);
        }

} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
