<?php
require_once __DIR__ . '/vendor/autoload.php';
use Phpml\ModelManager;

// --- CONFIGURATION ---
// --- CONFIGURATION & LIVE API CONNECTION ---

// 1. Define Base Prices in USD (Global Standard)
$base_prices_usd = [
    'crop_value'     => 0.25,  // $0.25 per sqm yield
    'fungicide_cost' => 0.05,  // $0.05 per ml
    'labor_cost'     => 15.00  // $15.00 flat rate
];

// 2. CONNECT TO LIVE API (ExchangeRate-API - Free & No Key Needed)
function getLiveExchangeRate() {
    $api_url = "https://api.exchangerate-api.com/v4/latest/USD";
    
    // Use cURL to fetch data (Professional Method)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        // Return the USD -> PHP rate (or default to 56 if API fails)
        return $data['rates']['PHP'] ?? 56.00;
    }
    return 56.00; // Fallback if internet is down
}

// 3. CALCULATE REAL-TIME PRICES
$current_rate = getLiveExchangeRate();
$last_updated = date("h:i:s A"); // Timestamp

$market_data = [
    'crop_value'     => $base_prices_usd['crop_value'] * $current_rate,
    'fungicide_cost' => $base_prices_usd['fungicide_cost'] * $current_rate,
    'labor_cost'     => $base_prices_usd['labor_cost'] * $current_rate
];

// --- 1. HANDLE DIAGNOSIS LOGIC ---
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symptom'])) {
    $modelManager = new ModelManager();
    
    // Load Models
    $classifier = $modelManager->restoreFromFile(__DIR__ . '/models/disease_classifier.phpml');
    $waterModel = $modelManager->restoreFromFile(__DIR__ . '/models/water_predictor.phpml');
    $fungicideModel = $modelManager->restoreFromFile(__DIR__ . '/models/fungicide_predictor.phpml');

    // Inputs
    $symptom = $_POST['symptom'];
    $farmSize = (int)$_POST['farm_size'];
    $severity = (int)$_POST['severity'];

    // Predictions
    $disease = $classifier->predict([$symptom]);
    $water = $waterModel->predict([$farmSize, $severity]);
    $fungicide = $fungicideModel->predict([$farmSize, $severity]);

    // Financials
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

    // --- SAVE TO HISTORY LOG ---
    $historyFile = fopen(__DIR__ . '/data/history.csv', 'a');
    // Format: Date, Symptom, Disease, ROI
    fputcsv($historyFile, [date('Y-m-d H:i'), $symptom, $disease, $result['roi']]);
    fclose($historyFile);
}

// --- 2. LOAD HISTORY & INTELLIGENCE LOGIC ---
$history_log = [];
$health_score = 100; // Start perfect
$total_scans = 0;
$recent_severity_sum = 0;
$disease_counts = [];
$total_potential_loss = 0;
$total_projected_revenue = 10000; // Example: Start with a mock "Goal Revenue" of $10k (or ₱500k)

if (file_exists(__DIR__ . '/data/history.csv')) {
    if (($h = fopen(__DIR__ . '/data/history.csv', "r")) !== FALSE) {
        while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {
            $history_log[] = $data;
            
            // Intelligence Calculations
            if(count($data) >= 4) {
                $total_scans++;
                // Analyze Disease Name for Frequency
                $d_name = $data[2];
                if(!isset($disease_counts[$d_name])) $disease_counts[$d_name] = 0;
                $disease_counts[$d_name]++;

                // Estimate Severity Impact (Mock logic: if ROI is high, it was severe)
                // In a real app, we would save severity directly to CSV. 
                // Here we assume every disease hit reduces health slightly.
                $recent_severity_sum += 1; 
            }
        }
        fclose($h);
    }
    $history_log = array_reverse($history_log);
}

// A. CALCULATE HEALTH SCORE
// Logic: Lose 5 points for every disease detected, but never go below 0.
$health_reduction = $total_scans * 5; 
$health_score = max(0, 100 - $health_reduction);

// B. SMART PATTERN INSIGHT (The "Critical Study")
$smart_insight = "Farm looks healthy! Keep monitoring.";
$insight_color = "success"; // green

if (!empty($disease_counts)) {
    // Find most common disease
    arsort($disease_counts);
    $top_disease = array_key_first($disease_counts);
    $count = $disease_counts[$top_disease];
    
    // Generate Advice based on the specific disease
    if (strpos($top_disease, 'Rot') !== false || strpos($top_disease, 'Fungal') !== false || strpos($top_disease, 'Mildew') !== false) {
        $smart_insight = "⚠️ <strong>Critical Pattern:</strong> $count recent cases of <strong>$top_disease</strong>. This is highly correlated with <strong>excess moisture</strong>. <br><u>Recommendation:</u> Reduce irrigation frequency by 15% immediately.";
        $insight_color = "warning";
    } elseif (strpos($top_disease, 'Nitrogen') !== false || strpos($top_disease, 'Yellow') !== false) {
        $smart_insight = "📉 <strong>Nutrient Alert:</strong> Repeated signs of <strong>$top_disease</strong>. Soil quality may be degrading. <br><u>Recommendation:</u> Schedule a soil N-P-K test.";
        $insight_color = "info";
    } elseif (strpos($top_disease, 'Pest') !== false || strpos($top_disease, 'Bugs') !== false) {
        $smart_insight = "🦟 <strong>Outbreak Warning:</strong> Pest activity is rising ($count cases). <br><u>Recommendation:</u> Check perimeter fencing and apply preventive organic neem oil.";
        $insight_color = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroSafeAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <i class="fas fa-leaf"></i> AgroSafeAI
        </div>
        
        <div class="nav-links">
            <a href="#" class="nav-link active" data-target="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="#" class="nav-link" data-target="market">
                <i class="fas fa-chart-line"></i> Market Data
            </a>
            <a href="#" class="nav-link" data-target="history">
                <i class="fas fa-history"></i> History Log
            </a>
        </div>

        <div class="mt-auto pt-5">
            <div class="alert alert-success border-0" style="background: #e8f5e9; font-size: 0.85rem;">
                <i class="fas fa-check-circle me-1"></i> System Online<br>
                <small class="text-muted">v2.4 Enterprise</small>
            </div>
        </div>
    </nav>

    <main class="main-content">
        
        <header class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold m-0">Welcome back, Farmer! 🌾</h2>
                <p class="text-muted">Here is your farm's health overview.</p>
            </div>
            <div class="d-flex gap-3">
                <div class="stat-box">
                    <div class="stat-icon bg-green-soft"><i class="fas fa-seedling"></i></div>
                    <div>
                        <h5 class="m-0 fw-bold"><?php echo count($history_log); ?></h5>
                        <small class="text-muted">Scans</small>
                    </div>
                </div>
            </div>
        </header>

        <section id="dashboard" class="view-section active">
    
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="custom-card bg-white h-100 position-relative overflow-hidden">
                            <h6 class="text-muted fw-bold">FARM HEALTH SCORE</h6>
                            <div class="d-flex align-items-center mt-3">
                                <div class="display-4 fw-bold <?php echo ($health_score > 70) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $health_score; ?>%
                                </div>
                                <div class="ms-3">
                                    <?php if($health_score > 80): ?>
                                        <span class="badge bg-success">Optimal</span>
                                    <?php elseif($health_score > 50): ?>
                                        <span class="badge bg-warning text-dark">Risk Warning</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">CRITICAL</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="progress mt-3" style="height: 6px;">
                                <div class="progress-bar <?php echo ($health_score > 70) ? 'bg-success' : 'bg-danger'; ?>" role="progressbar" style="width: <?php echo $health_score; ?>%"></div>
                            </div>
                            <small class="text-muted mt-2 d-block">Based on last <?php echo $total_scans; ?> diagnoses.</small>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="custom-card h-100 border-start border-4 border-<?php echo $insight_color; ?>">
                            <div class="d-flex align-items-start">
                                <div class="me-3 mt-1">
                                    <i class="fas fa-brain fa-2x text-<?php echo $insight_color; ?>"></i>
                                </div>
                                <div>
                                    <h6 class="text-<?php echo $insight_color; ?> fw-bold text-uppercase">AI Strategic Insight</h6>
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
                                    <label class="form-label text-muted small fw-bold">OBSERVED SYMPTOM</label>
                                    <select name="symptom" class="form-select bg-light border-0 p-3" required>
                                        <option value="" disabled selected>Select what you see...</option>
                                        <option value="yellow leaves">Yellow Leaves (Chlorosis)</option>
                                        <option value="stunted growth">Stunted Growth</option>
                                        <option value="white powder">White Powder (Mildew)</option>
                                        <option value="black spots">Black Spots</option>
                                        <option value="holes in leaves">Holes in Leaves</option>
                                        <option value="wilting">Wilting / Drooping</option>
                                    </select>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <label class="form-label text-muted small fw-bold">AFFECTED AREA (sqm)</label>
                                        <input type="number" name="farm_size" class="form-control bg-light border-0 p-3" placeholder="e.g. 50" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted small fw-bold">SEVERITY</label>
                                        <select name="severity" class="form-select bg-light border-0 p-3">
                                            <option value="1">Mild (Just Started)</option>
                                            <option value="2">Severe (Spreading)</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn-primary-custom shadow py-3">
                                    Run Diagnostics <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <?php if ($result): ?>
                        <div class="custom-card bg-white border-0 h-100 animate-fade-in">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="fw-bold text-success"><i class="fas fa-check-circle me-2"></i>Analysis Complete</h5>
                                <small class="text-muted"><?php echo date('h:i A'); ?></small>
                            </div>
                            
                            <div class="p-4 bg-light rounded-3 text-center mb-4">
                                <p class="text-muted fw-bold mb-1 text-uppercase small">Pathogen Identified</p>
                                <h2 class="fw-bold text-dark display-6 mb-0"><?php echo $result['disease']; ?></h2>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <small class="text-primary fw-bold"><i class="fas fa-prescription-bottle me-1"></i> TREATMENT</small>
                                        <ul class="list-unstyled mt-2 mb-0">
                                            <li class="mb-2 d-flex justify-content-between">
                                                <span>Fungicide:</span> <strong><?php echo $result['fungicide']; ?> ml</strong>
                                            </li>
                                            <li class="d-flex justify-content-between">
                                                <span>Water Mix:</span> <strong><?php echo $result['water']; ?> L</strong>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <small class="text-success fw-bold"><i class="fas fa-coins me-1"></i> FINANCIALS</small>
                                        <ul class="list-unstyled mt-2 mb-0">
                                            <li class="mb-2 d-flex justify-content-between text-danger">
                                                <span>Risk Loss:</span> <strong>-$<?php echo $result['loss']; ?></strong>
                                            </li>
                                            <li class="d-flex justify-content-between text-success">
                                                <span>ROI Saved:</span> <strong>+$<?php echo $result['roi']; ?></strong>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="custom-card h-100 d-flex flex-column align-items-center justify-content-center text-center p-5">
                            <div class="bg-light rounded-circle p-4 mb-3">
                                <i class="fas fa-leaf fa-3x text-success opacity-50"></i>
                            </div>
                            <h5 class="fw-bold text-secondary">Ready to Analyze</h5>
                            <p class="text-muted" style="max-width: 300px;">
                                Fill out the form on the left to receive a critical diagnosis, treatment plan, and financial forecast.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        <section id="market" class="view-section">
    
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Live Market Rates (PHP)</h4>
                    <div class="text-end">
                        <span class="badge bg-danger animate-pulse">● LIVE CONNECTION</span>
                        <small class="text-muted d-block mt-1" id="last-update">Connecting...</small>
                    </div>
                </div>

                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
                    <i class="fas fa-satellite-dish fa-2x me-3 opacity-50"></i>
                    <div>
                        <strong>Global Exchange Link Active</strong><br>
                        Current USD/PHP Rate: <strong id="live-rate">...</strong>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="custom-card text-center border-bottom border-4 border-warning">
                            <i class="fas fa-coins fa-2x text-warning mb-3"></i>
                            <h5>Crop Yield Value</h5>
                            <h3 class="fw-bold price-value" id="price-crop">...</h3>
                            <small class="text-muted">per sqm (Real-time)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-card text-center border-bottom border-4 border-info">
                            <i class="fas fa-flask fa-2x text-info mb-3"></i>
                            <h5>Chemical Cost</h5>
                            <h3 class="fw-bold price-value" id="price-chem">...</h3>
                            <small class="text-muted">per ml (Imported)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-card text-center border-bottom border-4 border-success">
                            <i class="fas fa-users fa-2x text-success mb-3"></i>
                            <h5>Labor Rate</h5>
                            <h3 class="fw-bold price-value" id="price-labor">...</h3>
                            <small class="text-muted">Local Standard</small>
                        </div>
                    </div>
                </div>
            </section>

        <section id="history" class="view-section">
            <div class="custom-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Scan History</h4>
                    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()"><i class="fas fa-sync"></i> Refresh</button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 rounded-start">Date</th>
                                <th class="border-0">Symptom</th>
                                <th class="border-0">Diagnosis</th>
                                <th class="border-0 rounded-end">Saved (ROI)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($history_log)): ?>
                                <?php foreach ($history_log as $log): ?>
                                    <?php if(count($log) >= 4): ?>
                                    <tr>
                                        <td><small class="text-muted"><?php echo $log[0]; ?></small></td>
                                        <td><?php echo htmlspecialchars($log[1]); ?></td>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($log[2]); ?></span></td>
                                        <td class="fw-bold text-success">+$<?php echo htmlspecialchars($log[3]); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No history yet. Start diagnosing!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>