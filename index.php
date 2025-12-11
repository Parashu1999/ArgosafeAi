<?php
require_once __DIR__ . '/vendor/autoload.php';

use Phpml\ModelManager;

// Initialize variables
$diagnosis = null;
$treatment = null;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modelManager = new ModelManager();

    // --- STEP 1: DIAGNOSIS ---
    $symptom = $_POST['symptom'];
    
    // Load the Disease Model
    $classifier = $modelManager->restoreFromFile('models/disease_classifier.phpml');
    $diagnosis = $classifier->predict([$symptom]);

    // --- STEP 2: TREATMENT PREDICTION ---
    $farmSize = (int)$_POST['farm_size'];
    $severity = (int)$_POST['severity']; // 1 = Mild, 2 = Severe

    // Load Resource Models
    $waterModel = $modelManager->restoreFromFile('models/water_predictor.phpml');
    $fungicideModel = $modelManager->restoreFromFile('models/fungicide_predictor.phpml');

    // Predict
    $waterNeeded = $waterModel->predict([$farmSize, $severity]);
    $fungicideNeeded = $fungicideModel->predict([$farmSize, $severity]);

    // Prepare display data
    $treatment = [
        'water' => round($waterNeeded, 1),
        'fungicide' => round($fungicideNeeded, 1)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgroSafeAI Diagnosis</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .result-box { background: #e0f7fa; padding: 1.5rem; border-radius: 8px; margin-top: 2rem; border: 1px solid #006064; }
        label { display: block; margin-top: 1rem; font-weight: bold; }
        input, select, button { width: 100%; padding: 0.5rem; margin-top: 0.5rem; }
        button { background: #00796b; color: white; border: none; cursor: pointer; font-size: 1rem; margin-top: 1.5rem; }
        button:hover { background: #004d40; }
    </style>
</head>
<body>

    <h1>🌱 AgroSafeAI</h1>
    <p>Smart Crop Disease Diagnosis & Resource Estimator</p>

    <form method="POST">
        <h3>1. Disease Diagnosis</h3>
        <label>Select Observed Symptom:</label>
        <select name="symptom" required>
            <option value="yellow leaves">Yellow Leaves</option>
            <option value="white powder">White Powder</option>
            <option value="black spots">Black Spots</option>
            <option value="holes in leaves">Holes in Leaves</option>
            <option value="wilting">Wilting</option>
        </select>

        <h3>2. Farm Details</h3>
        <label>Farm Size (Square Meters):</label>
        <input type="number" name="farm_size" placeholder="e.g. 100" required>

        <label>Severity Level:</label>
        <select name="severity">
            <option value="1">Mild (Early Stage)</option>
            <option value="2">Severe (Advanced Stage)</option>
        </select>

        <button type="submit">Diagnose & Prescribe</button>
    </form>

    <?php if ($diagnosis): ?>
    <div class="result-box">
        <h2>📋 Results</h2>
        <p><strong>Detected Disease:</strong> <span style="color: red;"><?php echo $diagnosis; ?></span></p>
        
        <hr>
        
        <h3>💊 Recommended Treatment</h3>
        <ul>
            <li><strong>Water Needed:</strong> <?php echo $treatment['water']; ?> Liters</li>
            <li><strong>Fungicide/Nutrient Needed:</strong> <?php echo $treatment['fungicide']; ?> ml</li>
        </ul>
        <p><em>Apply mixture every 7 days until symptoms subside.</em></p>
    </div>
    <?php endif; ?>

</body>
</html>