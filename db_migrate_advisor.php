<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'perioda';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->select_db($db_name);

$sql = "
CREATE TABLE IF NOT EXISTS health_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condition_name VARCHAR(150) NOT NULL,
    keywords VARCHAR(500) NOT NULL,
    description TEXT NOT NULL,
    common_symptoms TEXT NOT NULL,
    self_care TEXT NOT NULL,
    warning_signs TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";

if ($conn->query($sql) === TRUE) {
    echo "Table health_conditions created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Clear table before inserting (in case of re-run)
$conn->query("TRUNCATE TABLE health_conditions");

$conditions = [
    [
        'condition_name' => 'Menstrual Cramps (Dysmenorrhea)',
        'keywords' => 'cramp,cramps,pain,stomach,belly,abdominal,lower back',
        'description' => 'These symptoms can commonly occur during menstruation and may be associated with menstrual cramps, which are throbbing or cramping pains in the lower abdomen.',
        'common_symptoms' => json_encode(['Lower abdominal cramps', 'Lower back pain', 'Pain radiating down the legs', 'Nausea', 'Loose stools']),
        'self_care' => json_encode(['Rest', 'Stay hydrated', 'Use a warm compress or heating pad on your lower back or abdomen', 'Gentle physical activity like stretching or walking if comfortable', 'Take a warm bath']),
        'warning_signs' => 'Seek medical advice if the pain is severe, suddenly different from your usual period pain, prevents you from doing normal daily activities, or if you also have a fever.'
    ],
    [
        'condition_name' => 'Premenstrual Syndrome (PMS)',
        'keywords' => 'mood,mood swings,sad,irritable,anxious,angry,tender breasts,crying,pms',
        'description' => 'Your symptoms may be associated with PMS, a very common condition that affects a woman\'s emotions, physical health, and behavior during certain days of the menstrual cycle.',
        'common_symptoms' => json_encode(['Mood swings', 'Irritability or anger', 'Tender or swollen breasts', 'Appetite changes and food cravings', 'Trouble falling asleep (insomnia)']),
        'self_care' => json_encode(['Eat smaller, more frequent meals to reduce bloating', 'Limit salt and salty foods to reduce fluid retention', 'Exercise regularly', 'Practice stress management like yoga or deep breathing']),
        'warning_signs' => 'If PMS symptoms are so severe they affect your daily life, work, or relationships, consult a doctor as you may have PMDD (Premenstrual Dysphoric Disorder).'
    ],
    [
        'condition_name' => 'Menstrual-Related Headache / Migraine',
        'keywords' => 'headache,migraine,head,throbbing,light sensitivity',
        'description' => 'Changing hormone levels right before or during your period can trigger headaches or migraines in many women.',
        'common_symptoms' => json_encode(['Throbbing head pain', 'Sensitivity to light or sound', 'Nausea', 'Fatigue']),
        'self_care' => json_encode(['Rest in a dark, quiet room', 'Apply a cold cloth or ice pack to the painful area', 'Stay hydrated', 'Maintain a regular sleep schedule', 'Limit caffeine and alcohol']),
        'warning_signs' => 'Seek immediate medical attention if you have a sudden, severe headache like a thunderclap, or a headache accompanied by fever, stiff neck, confusion, or vision changes.'
    ],
    [
        'condition_name' => 'Menstrual Nausea',
        'keywords' => 'nausea,sick,vomit,vomiting,stomach upset,throw up',
        'description' => 'Hormonal fluctuations (especially prostaglandins) during menstruation can enter the bloodstream and affect the digestive tract, causing nausea.',
        'common_symptoms' => json_encode(['Feeling sick to your stomach', 'Vomiting', 'Loss of appetite', 'Accompanying cramps or headaches']),
        'self_care' => json_encode(['Drink clear or ice-cold drinks', 'Eat light, bland foods (like crackers or plain bread)', 'Avoid fried, greasy, or sweet foods', 'Sip ginger tea or peppermint tea', 'Eat smaller meals more slowly']),
        'warning_signs' => 'Seek medical help if you are unable to keep any fluids down for more than 24 hours, or if you show signs of severe dehydration.'
    ],
    [
        'condition_name' => 'Menstrual Bloating',
        'keywords' => 'bloat,bloated,swollen,gas,full,heavy,water retention',
        'description' => 'Bloating is a common symptom of PMS caused by changes in hormone levels (estrogen and progesterone), causing your body to retain more water and salt.',
        'common_symptoms' => json_encode(['Feeling full, tight, or swollen in the abdomen', 'Weight gain (due to water)', 'Gassiness']),
        'self_care' => json_encode(['Drink plenty of water', 'Limit sodium (salt) intake', 'Eat potassium-rich foods (like bananas and sweet potatoes)', 'Avoid carbonated drinks', 'Exercise regularly to improve digestion']),
        'warning_signs' => 'Consult a doctor if the bloating is severe, persists after your period ends, or is accompanied by severe abdominal pain.'
    ],
    [
        'condition_name' => 'Menstrual Fatigue',
        'keywords' => 'fatigue,tired,exhausted,sleepy,weak,energy,lethargic',
        'description' => 'Fatigue is common before and during menstruation due to fluctuating hormones and possibly a drop in iron levels from bleeding.',
        'common_symptoms' => json_encode(['Lack of energy', 'Feeling excessively tired or sleepy', 'Difficulty concentrating', 'Weakness']),
        'self_care' => json_encode(['Prioritize getting 7-9 hours of sleep', 'Eat iron-rich foods (spinach, beans, red meat)', 'Maintain steady blood sugar with balanced meals', 'Do light exercises like walking or yoga to boost energy']),
        'warning_signs' => 'If fatigue is extreme, constant, or you feel short of breath, you should see a doctor to check for anemia or other conditions.'
    ],
    [
        'condition_name' => 'Dizziness during Menstruation',
        'keywords' => 'dizzy,lightheaded,faint,spinning',
        'description' => 'Dizziness can occur due to hormonal changes, blood loss causing a drop in iron (anemia), or a drop in blood pressure caused by prostaglandins.',
        'common_symptoms' => json_encode(['Feeling lightheaded or faint', 'Feeling unsteady or weak', 'Spinning sensation (vertigo)']),
        'self_care' => json_encode(['Sit or lie down immediately when you feel dizzy', 'Drink plenty of water and stay hydrated', 'Avoid sudden changes in posture (like standing up too quickly)', 'Eat regular, balanced meals']),
        'warning_signs' => 'Seek prompt medical attention if you faint, or if dizziness is accompanied by chest pain, shortness of breath, or a severe headache.'
    ],
    [
        'condition_name' => 'Heavy Menstrual Bleeding (Menorrhagia)',
        'keywords' => 'heavy bleeding,heavy flow,clots,lots of blood,soaking,changing pad',
        'description' => 'Heavy periods can interfere with your daily life and may lead to anemia if left untreated. It is important to monitor your flow.',
        'common_symptoms' => json_encode(['Soaking through one or more pads/tampons every hour', 'Needing to wake up to change sanitary protection during the night', 'Passing large blood clots', 'Symptoms of anemia (tiredness, fatigue, shortness of breath)']),
        'self_care' => json_encode(['Use both a pad and a tampon for extra protection if necessary', 'Rest when you feel tired', 'Eat a diet rich in iron to prevent anemia', 'Stay hydrated']),
        'warning_signs' => 'You should consult a healthcare professional if you consistently soak through a pad/tampon every hour for consecutive hours, pass very large clots, or feel extremely weak and dizzy.'
    ]
];

$stmt = $conn->prepare("INSERT INTO health_conditions (condition_name, keywords, description, common_symptoms, self_care, warning_signs) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($conditions as $cond) {
    $stmt->bind_param("ssssss", $cond['condition_name'], $cond['keywords'], $cond['description'], $cond['common_symptoms'], $cond['self_care'], $cond['warning_signs']);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo "Inserted predefined conditions successfully.\n";
?>
