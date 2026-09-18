<?php
session_start();
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
    $stmt = $conn->query("SELECT * FROM health_conditions");
    $conditions = $stmt->fetch_all(MYSQLI_ASSOC);

    $best_match = null;
    $highest_score = 0;

    foreach ($conditions as $cond) {
        $score = 0;
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
        // Decode JSON arrays for frontend
        $best_match['common_symptoms'] = json_decode($best_match['common_symptoms'], true);
        $best_match['self_care'] = json_decode($best_match['self_care'], true);
        
        echo json_encode([
            "success" => true, 
            "condition" => $best_match
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "error" => "We couldn't find a specific condition matching those exact terms. If your symptoms are severe or concerning, please consult a healthcare professional."
        ]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "An error occurred while searching."]);
}
?>
