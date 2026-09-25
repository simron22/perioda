<?php
// =====================================================
// Simple cycle calculation helpers used on the dashboard
// and calendar page. Kept intentionally simple - these
// are ESTIMATES, not medical predictions.
// =====================================================

// Fetch all period records for a user, oldest first
function get_user_periods($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM period_records WHERE user_id = ? ORDER BY start_date ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Calculate basic cycle statistics from a list of period records
function calculate_cycle_stats($periods) {
    $stats = [
        'total_records'      => count($periods),
        'avg_cycle_length'   => 28,   // sensible default until enough data exists
        'avg_period_length'  => 5,
        'last_period_start'  => null,
        'estimated_next'     => null,
        'current_cycle_day'  => null,
    ];

    if (empty($periods)) {
        return $stats;
    }

    // ---- average period duration (end - start + 1 day) ----
    $durations = [];
    foreach ($periods as $p) {
        if (!empty($p['end_date'])) {
            $start = new DateTime($p['start_date']);
            $end = new DateTime($p['end_date']);
            $days = $end->diff($start)->days + 1;
            if ($days > 0 && $days < 15) { // ignore obviously bad data
                $durations[] = $days;
            }
        }
    }
    if (!empty($durations)) {
        $stats['avg_period_length'] = round(array_sum($durations) / count($durations));
    }

    // ---- average cycle length (gap between consecutive start dates) ----
    $cycleLengths = [];
    for ($i = 1; $i < count($periods); $i++) {
        $prevStart = new DateTime($periods[$i - 1]['start_date']);
        $currStart = new DateTime($periods[$i]['start_date']);
        $diff = $prevStart->diff($currStart)->days;
        if ($diff > 10 && $diff < 60) { // ignore unrealistic gaps
            $cycleLengths[] = $diff;
        }
    }
    if (!empty($cycleLengths)) {
        $stats['avg_cycle_length'] = round(array_sum($cycleLengths) / count($cycleLengths));
    }

    // ---- last period + estimated next period ----
    $last = end($periods);
    $lastStart = new DateTime($last['start_date']);
    $stats['last_period_start'] = $lastStart->format('Y-m-d');

    $estimatedNext = clone $lastStart;
    $estimatedNext->modify('+' . $stats['avg_cycle_length'] . ' days');
    $stats['estimated_next'] = $estimatedNext->format('Y-m-d');

    // ---- current cycle day ----
    $today = new DateTime('today');
    if ($today >= $lastStart) {
        $stats['current_cycle_day'] = $today->diff($lastStart)->days + 1;
    }

    return $stats;
}
