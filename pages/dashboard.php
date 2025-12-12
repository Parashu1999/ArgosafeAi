<?php
use Phpml\ModelManager;

// --- A. HANDLE DIAGNOSIS LOGIC (Form Submission) ---
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symptom'])) {
    $modelManager = new ModelManager();
    
    // Paths are relative to index.php because that's where execution starts
    $classifier = $modelManager->restoreFromFile(__DIR__ . '/../models/disease_classifier.phpml');
    $waterModel = $modelManager->restoreFromFile(__DIR__ . '/../models/water_predictor.phpml');
    $fungicideModel = $modelManager->restoreFromFile(__DIR__ . '/../models/fungicide_predictor.phpml');

    // Inputs
    $symptom = $_POST['symptom'];
    $farmSize = (int)$_POST['farm_size'];
    $severity = (int)$_POST['severity'];

    // Predictions
    $disease = $classifier->predict([$symptom]);
    $water = $waterModel->predict([$farmSize, $severity]);
    $fungicide = $fungicideModel->predict([$farmSize, $severity]);

    // Financials (Using $market_data from config.php)
    $loss_rate = ($severity == 2) ? 0.60 : 0.20;
    $potential_loss = ($farmSize * $market_data['crop_value']) * $loss_rate;
    $treatment_cost = ($fungicide * $market_data['fungicide_cost']) + $market_data['labor_cost'];
    $roi = $potential_loss - $treatment_cost;

    $result = [
        'disease' => $disease,
        'water' => round($water, 1),
        'fungicide' => round($fungicide, 1),
        'loss' => number_format($potential_loss, 2),
        'cost' => number_format($treatment_cost, 2),
        'roi' => number_format($roi, 2)
    ];

    // Save to History
    $historyFile = fopen(__DIR__ . '/../data/history.csv', 'a');
    fputcsv($historyFile, [date('Y-m-d H:i'), $symptom, $disease, $result['roi']]);
    fclose($historyFile);
}

// --- B. LOAD INTELLIGENCE & HEALTH SCORE ---
$history_log = [];
$total_scans = 0;
$disease_counts = [];

if (file_exists(__DIR__ . '/../data/history.csv')) {
    if (($h = fopen(__DIR__ . '/../data/history.csv', "r")) !== FALSE) {
        while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {
            if(count($data) >= 3) { // Ensure data integrity
                $history_log[] = $data;
                $total_scans++;
                $d_name = $data[2];
                if(!isset($disease_counts[$d_name])) $disease_counts[$d_name] = 0;
                $disease_counts[$d_name]++;
            }
        }
        fclose($h);
    }
}

// Calculate Health Score
$health_reduction = $total_scans * 5; 
$health_score = max(0, 100 - $health_reduction);

// Generate Smart Insights
$smart_insight = "Farm looks healthy! Keep monitoring.";
$insight_color = "success"; 

if (!empty($disease_counts)) {
    arsort($disease_counts);
    $top_disease = array_key_first($disease_counts);
    $count = $disease_counts[$top_disease];
    
    if (strpos($top_disease, 'Rot') !== false || strpos($top_disease, 'Fungal') !== false) {
        $smart_insight = "⚠️ <strong>Critical Pattern:</strong> $count cases of <strong>$top_disease</strong>. Reduce irrigation.";
        $insight_color = "warning";
    } elseif (strpos($top_disease, 'Nitrogen') !== false) {
        $smart_insight = "📉 <strong>Nutrient Alert:</strong> Signs of <strong>$top_disease</strong>. Check Soil N-P-K.";
        $insight_color = "info";
    } elseif (strpos($top_disease, 'Pest') !== false) {
        $smart_insight = "🦟 <strong>Outbreak:</strong> High pest activity ($count cases). Check fencing.";
        $insight_color = "danger";
    }
}
?>

<header class="d-flex justify-content-between align-items-center mb-5">
    <div>
        <h2 class="fw-bold m-0">Welcome back, Farmer <?php echo htmlspecialchars($user_name); ?>! 🌾</h2>
        <p class="text-muted">Here is your farm's health overview.</p>
    </div>
</header>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="custom-card bg-white h-100">
            <h6 class="text-muted fw-bold">FARM HEALTH SCORE</h6>
            <div class="d-flex align-items-center mt-3">
                <div class="display-4 fw-bold <?php echo ($health_score > 70) ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $health_score; ?>%
                </div>
            </div>
            <div class="progress mt-3" style="height: 6px;">
                <div class="progress-bar <?php echo ($health_score > 70) ? 'bg-success' : 'bg-danger'; ?>" style="width: <?php echo $health_score; ?>%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="custom-card h-100 border-start border-4 border-<?php echo $insight_color; ?>">
            <div class="d-flex align-items-start">
                <div class="me-3 mt-1"><i class="fas fa-brain fa-2x text-<?php echo $insight_color; ?>"></i></div>
                <div>
                    <h6 class="text-<?php echo $insight_color; ?> fw-bold">AI Strategic Insight</h6>
                    <p class="mb-0 fs-5"><?php echo $smart_insight; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="custom-card">
            <h5 class="mb-4 fw-bold text-primary"><i class="fas fa-stethoscope me-2"></i>New Diagnosis</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SYMPTOM</label>
                    <select name="symptom" class="form-select p-3" required>
                        <option value="" disabled selected>Select observation...</option>
                        <option value="yellow leaves">Yellow Leaves (Chlorosis)</option>
                        <option value="stunted growth">Stunted Growth</option>
                        <option value="white powder">White Powder (Mildew)</option>
                        <option value="black spots">Black Spots</option>
                        <option value="holes in leaves">Holes in Leaves</option>
                        <option value="wilting">Wilting</option>
                    </select>
                </div>
                <div class="row mb-4">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">AREA (sqm)</label>
                        <input type="number" name="farm_size" class="form-control p-3" placeholder="50" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">SEVERITY</label>
                        <select name="severity" class="form-select p-3">
                            <option value="1">Mild</option>
                            <option value="2">Severe</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary-custom shadow py-3 w-100">Run Diagnostics</button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <?php if ($result): ?>
        <div class="custom-card bg-white border-0 h-100">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold text-success"><i class="fas fa-check-circle me-2"></i>Analysis Complete</h5>
            </div>
            
            <div class="p-4 bg-light rounded-3 text-center mb-4">
                <p class="text-muted fw-bold mb-1 text-uppercase small">Pathogen Identified</p>
                <h2 class="fw-bold text-dark display-6 mb-0"><?php echo $result['disease']; ?></h2>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <small class="text-primary fw-bold">TREATMENT</small>
                        <ul class="list-unstyled mt-2 mb-0">
                            <li>Fungicide: <strong><?php echo $result['fungicide']; ?> ml</strong></li>
                            <li>Water Mix: <strong><?php echo $result['water']; ?> L</strong></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <small class="text-success fw-bold">FINANCIALS</small>
                        <ul class="list-unstyled mt-2 mb-0">
                            <li class="text-danger">Risk: <strong>-$<?php echo $result['loss']; ?></strong></li>
                            <li class="text-success">ROI: <strong>+$<?php echo $result['roi']; ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="custom-card h-100 d-flex flex-column align-items-center justify-content-center text-center p-5">
            <div class="bg-light rounded-circle p-4 mb-3"><i class="fas fa-leaf fa-3x text-success opacity-50"></i></div>
            <h5 class="fw-bold text-secondary">Ready to Analyze</h5>
            <p class="text-muted">Awaiting input data...</p>
        </div>
        <?php endif; ?>
    </div>
</div>