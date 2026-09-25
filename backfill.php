<?php
require 'api/db.php';

$stmt = $pdo->query("SELECT * FROM period_records WHERE estimated_ovulation_date IS NULL");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($records as $record) {
    $stmt_set = $pdo->prepare("SELECT cycle_length FROM user_settings WHERE user_id = ?");
    $stmt_set->execute([$record['user_id']]);
    $settings = $stmt_set->fetch();
    $cycle_length = $settings ? (int)$settings['cycle_length'] : 28;

    $start_date_obj = new DateTime($record['start_date']);
    $ovulation_days = $cycle_length - 14;
    $start_date_obj->modify("+$ovulation_days days");
    $estimated_ovulation_date = $start_date_obj->format('Y-m-d');

    $update = $pdo->prepare("UPDATE period_records SET estimated_ovulation_date = ? WHERE period_id = ?");
    $update->execute([$estimated_ovulation_date, $record['period_id']]);
}
echo "Done";
