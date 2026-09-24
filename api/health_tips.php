<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not authenticated"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Define category mappings
$symptom_map = [
    'cramps' => ['Period Comfort', 'Exercise'],
    'headache' => ['Rest and Sleep', 'General Wellness'],
    'bloating' => ['Nutrition'],
    'fatigue' => ['Rest and Sleep', 'Nutrition'],
    'acne' => ['General Wellness', 'Menstrual Hygiene'],
    'back pain' => ['Period Comfort', 'Exercise'],
    'nausea' => ['Nutrition', 'Period Comfort'],
    'breast tenderness' => ['Period Comfort']
];

$mood_map = [
    'sad' => ['Rest and Sleep', 'Exercise'],
    'anxious' => ['Rest and Sleep', 'Exercise'],
    'irritable' => ['Rest and Sleep', 'Period Comfort'],
    'tired' => ['Rest and Sleep', 'Nutrition']
];

$categories_to_fetch = [];

try {
    // 1. Fetch recent symptoms (last 14 days)
    $stmt = $pdo->prepare("SELECT DISTINCT symptom FROM symptoms WHERE user_id = ? AND symptom_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)");
    $stmt->execute([$user_id]);
    $recent_symptoms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Fetch recent moods (last 14 days)
    $stmt = $pdo->prepare("SELECT DISTINCT mood FROM moods WHERE user_id = ? AND mood_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)");
    $stmt->execute([$user_id]);
    $recent_moods = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Map symptoms to categories
    foreach ($recent_symptoms as $symptom) {
        if (isset($symptom_map[$symptom])) {
            foreach ($symptom_map[$symptom] as $cat) {
                $categories_to_fetch[] = $cat;
            }
        }
    }

    // 4. Map moods to categories
    foreach ($recent_moods as $mood) {
        if (isset($mood_map[$mood])) {
            foreach ($mood_map[$mood] as $cat) {
                $categories_to_fetch[] = $cat;
            }
        }
    }

    // Deduplicate categories
    $categories_to_fetch = array_unique($categories_to_fetch);

    // 5. Query tips
    if (empty($categories_to_fetch)) {
        // Fallback: Fetch a mix of general tips if no specific problems mapped
        $stmt = $pdo->query("SELECT * FROM health_tips ORDER BY RAND() LIMIT 6");
        $tips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch tips from the matched categories
        $in_clause = implode(',', array_fill(0, count($categories_to_fetch), '?'));
        // Get some tips from the matching categories
        $stmt = $pdo->prepare("SELECT * FROM health_tips WHERE category IN ($in_clause) ORDER BY RAND() LIMIT 6");
        $stmt->execute(array_values($categories_to_fetch));
        $tips = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we didn't find enough tips, backfill with general ones
        if (count($tips) < 3) {
            $existing_ids = array_column($tips, 'tip_id');
            if (empty($existing_ids)) {
                $stmt = $pdo->query("SELECT * FROM health_tips ORDER BY RAND() LIMIT 6");
            } else {
                $id_in_clause = implode(',', array_fill(0, count($existing_ids), '?'));
                $stmt = $pdo->prepare("SELECT * FROM health_tips WHERE tip_id NOT IN ($id_in_clause) ORDER BY RAND() LIMIT " . (6 - count($tips)));
                $stmt->execute($existing_ids);
            }
            $additional_tips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tips = array_merge($tips, $additional_tips);
        }
    }

    echo json_encode(["success" => true, "tips" => $tips]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
