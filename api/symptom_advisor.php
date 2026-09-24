<?php
@session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not authenticated"]);
    exit;
}

$query = $_GET['q'] ?? '';

if (empty(trim($query))) {
    echo json_encode(["success" => false, "error" => "Please enter your symptoms to search."]);
    exit;
}

// Convert to lowercase and sanitize
$query = strtolower(trim($query));

// Basic stop words to remove
$stop_words = ['i', 'have', 'feel', 'feeling', 'my', 'is', 'and', 'with', 'a', 'the', 'during', 'period', 'am', 'so', 'very', 'severe', 'bad'];
$words = preg_split('/[\s,\.]+/', $query);
$search_terms = [];
foreach ($words as $word) {
    if (!empty($word) && !in_array($word, $stop_words)) {
        $search_terms[] = $word;
    }
}

if (empty($search_terms)) {
    // If only stop words were used, fallback to the full query as a term just in case
    $search_terms[] = $query;
}

try {
    // Fetch all conditions to score them
    $stmt = $pdo->query("SELECT * FROM health_conditions");
    $conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $best_match = null;
    $highest_score = 0;

    foreach ($conditions as $cond) {
        $score = 0;
        // Decode JSON arrays for frontend compatibility
        $cond['common_symptoms'] = json_decode($cond['common_symptoms'], true);
        $cond['self_care'] = json_decode($cond['self_care'], true);
        $keywords = explode(',', strtolower($cond['keywords']));
        
        foreach ($search_terms as $term) {
            foreach ($keywords as $kw) {
                // If search term matches a keyword exactly or is a substring (or vice versa)
                if (strpos($kw, $term) !== false || strpos($term, $kw) !== false) {
                    $score += 1;
                    // Boost exact matches
                    if ($kw === $term) {
                        $score += 2;
                    }
                }
            }
        }

        if ($score > $highest_score) {
            $highest_score = $score;
            $best_match = $cond;
        }
    }

    if ($best_match && $highest_score > 0) {
        // Log to history
        $stmtHistory = $pdo->prepare("INSERT INTO user_health_tip_history (user_id, condition_name, symptoms_searched) VALUES (?, ?, ?)");
        $stmtHistory->execute([$_SESSION['user_id'], $best_match['condition_name'], $query]);

        echo json_encode([
            "success" => true, 
            "condition" => $best_match
        ]);
    } else {
        // Generic fallback for ANY problem
        $fallback_condition = [
            "condition_name" => "General Advice for: " . htmlspecialchars(ucwords($query)),
            "description" => "We couldn't pinpoint a specific condition in our database for these exact terms, but we always recommend basic wellness steps for any discomfort.",
            "self_care" => [
                "Stay hydrated and drink plenty of water throughout the day.",
                "Ensure you are getting enough rest and adequate sleep.",
                "Try gentle stretching, yoga, or light exercise if you feel up to it.",
                "Use a warm compress or heating pad to soothe muscle aches.",
                "Maintain a balanced diet and avoid excessive caffeine or sugar."
            ],
            "warning_signs" => "If your symptoms are severe, persistent, or worsening, please consult a healthcare professional immediately."
        ];

        // Log the fallback search too
        $stmtHistory = $pdo->prepare("INSERT INTO user_health_tip_history (user_id, condition_name, symptoms_searched) VALUES (?, ?, ?)");
        $stmtHistory->execute([$_SESSION['user_id'], 'General Inquiry', $query]);

        echo json_encode([
            "success" => true, 
            "condition" => $fallback_condition
        ]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "An error occurred while searching."]);
}
?>
