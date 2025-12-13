<?php
// pages/dashboard.php

// 1. AUTH & CONNECTION
if (!isset($_SESSION['user_id'])) { die("Access Denied"); }
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'Farmer';

$host = 'localhost'; $db = 'agrosafe_db'; $u = 'root'; $p = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Connection Error."); }

// --- FIX: LOAD COMPOSER AUTOLOADER HERE ---
require_once __DIR__ . '/../vendor/autoload.php'; 
// ------------------------------------------

require_once __DIR__ . '/../includes/ai_prediction.php'; 
use Phpml\ModelManager;

// --- DEFINING MARKET DATA (Fix for Undefined Variable) ---
if (!isset($market_data)) {
    $market_data = [
        'crop_value' => 150.00,      
        'fungicide_cost' => 45.00,   
        'labor_cost' => 500.00       
    ];
}
// ---------------------------------------------------------
// 2. DIAGNOSTIC ENGINE (RUNS FIRST FOR INSTANT FEEDBACK)
// ---------------------------------------------------------
$result = null;
$scroll_to_result = false; // UX Trigger

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symptom'])) {
    $modelManager = new ModelManager();
    try {
        // Load AI Models
        $classifier = $modelManager->restoreFromFile(__DIR__ . '/../models/disease_classifier.phpml');
        $waterModel = $modelManager->restoreFromFile(__DIR__ . '/../models/water_predictor.phpml');
        $fungicideModel = $modelManager->restoreFromFile(__DIR__ . '/../models/fungicide_predictor.phpml');

        // Sanitize Inputs
        $symptomsInput = $_POST['symptom']; 
        $affectedSize  = (int)$_POST['affected_size']; 
        $totalLandSize = (int)$_POST['total_land_size']; 
        $severity      = (int)$_POST['severity'];

        // Logic Validation
        if ($affectedSize > $totalLandSize) { $affectedSize = $totalLandSize; }
        if ($totalLandSize <= 0) { $totalLandSize = 1000; } // Default prevention
        if (!is_array($symptomsInput)) { $symptomsInput = [$symptomsInput]; }

        // AI Predictions
        $disease = $classifier->predict([$symptomsInput]); 
        $disease = $disease[0]; 
        $water = $waterModel->predict([$affectedSize, $severity]);
        $fungicide_amount = $fungicideModel->predict([$affectedSize, $severity]);

        // Smart Treatment Dictionary
        $treatment_map = [
            'Nitrogen deficiency' => 'Nitrogen-Rich Fertilizer (Urea)',
            'Phosphorus Deficiency' => 'Phosphorus Fertilizer (Bone Meal)',
            'Potassium Deficiency' => 'Potash / Potassium Fertilizer',
            'Iron Deficiency' => 'Chelated Iron Spray',
            'Calcium Deficiency' => 'Calcium Nitrate Spray',
            'Magnesium Deficiency' => 'Epsom Salts (Magnesium)',
            'Boron Deficiency' => 'Boron Spray',
            'Blossom end rot' => 'Calcium Spray',
            'Powdery mildew' => 'Sulfur Fungicide or Neem Oil',
            'Fungal infection' => 'Copper-Based Fungicide',
            'Leaf Spot' => 'Chlorothalonil Fungicide',
            'Rust Fungus' => 'Sulfur Dust',
            'Botrytis (Gray Mold)' => 'Bio-Fungicide (Bacillus subtilis)',
            'Early Blight' => 'Copper Fungicide',
            'Root Rot' => 'Hydrogen Peroxide Drench',
            'Scab Disease' => 'Captan Fungicide',
            'Blackleg' => 'Copper Fungicide Drench',
            'Spider Mites' => 'Miticide or Neem Oil',
            'Aphids' => 'Insecticidal Soap',
            'Whiteflies' => 'Yellow Sticky Traps + Neem Oil',
            'Thrips Damage' => 'Spinosad Spray',
            'Leaf Miners' => 'Spinosad or Neem Oil',
            'Nematodes' => 'Nematicide Drench',
            'Root Knot Nematode' => 'Nematicide',
            'Scale Insects' => 'Horticultural Oil',
            'Mealybugs' => 'Alcohol Spray or Insecticidal Soap',
            'Japanese Beetle' => 'Pyrethrin Spray',
            'Slugs or Snails' => 'Iron Phosphate Pellets',
            'Fruit Fly Larvae' => 'Spinosad Bait',
            'Bacterial Wilt' => 'Copper Bactericide',
            'Bacterial Spot' => 'Copper-Based Bactericide',
            'Bacterial Canker' => 'Copper Spray',
            'Mosaic Virus' => 'Zinc Booster (Immune Support)',
            'Leaf Curl Virus' => 'Copper Spray (Preventative)',
            'Sunscald' => 'Use Shade Cloth (No Chemical)',
        ];
        $chemical_name = $treatment_map[$disease] ?? 'Broad-Spectrum Fungicide';

        // Recovery Timeline Logic
        if ($severity == 2) {
            $recovery_days = "14 - 21 Days";
            $frequency = "Apply Daily";
            $target_date = date('M d', strtotime("+21 days"));
        } else {
            $recovery_days = "5 - 7 Days";
            $frequency = "Apply Every 2 Days";
            $target_date = date('M d', strtotime("+7 days"));
        }

        // Financial Calculation
        $loss_rate = ($severity == 2) ? 0.60 : 0.20;
        $potential_loss = ($affectedSize * $market_data['crop_value']) * $loss_rate; 
        $treatment_cost = ($fungicide_amount * $market_data['fungicide_cost']) + $market_data['labor_cost'];
        $roi = $potential_loss - $treatment_cost;

        $result = [
            'disease' => $disease,
            'treatment_name' => $chemical_name,
            'water' => round($water, 1),
            'fungicide' => round($fungicide_amount, 1),
            'loss' => number_format($potential_loss, 2),
            'cost' => number_format($treatment_cost, 2),
            'roi' => number_format($roi, 2),
            'recovery_days' => $recovery_days,
            'frequency' => $frequency,
            'target_date' => $target_date
        ];

        // Save Record
        $symptomString = implode(', ', $symptomsInput);
        try {
            $stmt = $pdo->prepare("INSERT INTO history (user_id, date, symptom, diagnosis, roi, status, notes, severity, affected_sqm, total_sqm) VALUES (?, NOW(), ?, ?, ?, 'Pending', NULL, ?, ?, ?)");
            $stmt->execute([$user_id, $symptomString, $disease, $result['roi'], $severity, $affectedSize, $totalLandSize]);
        } catch (Exception $e) {
            // Fallback for older DB versions
            $stmt = $pdo->prepare("INSERT INTO history (user_id, date, symptom, diagnosis, roi, status) VALUES (?, NOW(), ?, ?, ?, 'Pending')");
            $stmt->execute([$user_id, $symptomString, $disease, $result['roi']]);
        }
        
        $scroll_to_result = true; // UX: Auto-scroll to result

    } catch (Exception $e) { $error_msg = $e->getMessage(); }
}

// ---------------------------------------------------------
// 3. INTELLIGENCE GATHERING (WEATHER & HEALTH)
// ---------------------------------------------------------

// Weather AI
$forecaster = new DiseaseForecaster();
$weather = $forecaster->getWeatherData();
$prediction = $forecaster->predictRisk($weather);

// Farm Health Calculation
$stmt = $pdo->prepare("SELECT * FROM history WHERE user_id = ? ORDER BY date DESC LIMIT 50");
$stmt->execute([$user_id]);
$history_log = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_damage_pct = 0;
$active_issues_count = 0;

foreach ($history_log as $log) {
    $status = isset($log['status']) ? $log['status'] : 'Pending';
    
    // Only Active issues hurt the score
    if ($status !== 'Resolved') {
        $active_issues_count++;
        
        // Factor 1: Percentage of Land Affected
        if (isset($log['affected_sqm']) && isset($log['total_sqm']) && $log['total_sqm'] > 0) {
            $damage_ratio = ($log['affected_sqm'] / $log['total_sqm']) * 100;
        } else {
            $damage_ratio = 5; // Default fallback
        }
        
        // Factor 2: Severity Multiplier (Severe = 2x damage)
        $sev_multiplier = (isset($log['severity']) && $log['severity'] == 2) ? 2.0 : 1.0;
        
        $total_damage_pct += ($damage_ratio * $sev_multiplier);
    }
}

// Cap Health Score: 100 - Total Damage %
$health_score = max(0, round(100 - $total_damage_pct));

// Identify Top Threat
$active_diagnoses = [];
foreach ($history_log as $log) {
    if ((isset($log['status']) ? $log['status'] : 'Pending') !== 'Resolved') { $active_diagnoses[] = $log['diagnosis']; }
}
$counts = array_count_values($active_diagnoses);
arsort($counts);
$top_threat = !empty($counts) ? key($counts) : 'None';

// Set UI Colors & Messages
if ($health_score >= 90) {
    $insight_msg = "Farm condition is <strong>Optimal</strong>. Minimal active threats.";
    $insight_color = "success";
    $health_label = "Optimal";
} elseif ($health_score >= 70) {
    $insight_msg = "Farm is <strong>Stable</strong>. Routine maintenance recommended.";
    $insight_color = "success"; 
    $health_label = "Good";
} elseif ($health_score >= 40) {
    $insight_msg = "<strong>Alert:</strong> Significant area affected by $top_threat.";
    $insight_color = "warning";
    $health_label = "At Risk";
} else {
    $insight_msg = "<strong>CRITICAL:</strong> Widespread infection. Immediate intervention required.";
    $insight_color = "danger";
    $health_label = "Critical";
}
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        --glass-surface: rgba(255, 255, 255, 0.95);
        --radius-lg: 24px;
        --radius-md: 16px;
        --input-bg: #f3f6f8;
        --accent-glow: 0 8px 30px rgba(46, 204, 113, 0.3);
    }

    /* --- LAYOUT ANIMATIONS --- */
    .animate-fade-in { animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* --- COMPONENT: MODERN CARD --- */
    .card-modern {
        background: var(--glass-surface);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: var(--radius-lg);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.3s ease;
    }
    .card-modern:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01); }

    /* --- COMPONENT: HERO HEADER --- */
    .hero-welcome {
        background: var(--primary-gradient);
        border-radius: var(--radius-lg); color: white; padding: 3rem 2.5rem;
        position: relative; overflow: hidden; margin-bottom: 2.5rem;
        box-shadow: var(--accent-glow);
    }
    .hero-pattern { position: absolute; top: -10%; right: -5%; opacity: 0.1; font-size: 18rem; transform: rotate(-10deg); }

    /* --- COMPONENT: HEALTH GAUGE --- */
    .health-gauge-container { position: relative; width: 100px; height: 100px; }
    .health-gauge {
        width: 100%; height: 100%; border-radius: 50%;
        background: conic-gradient(var(--gauge-color) var(--gauge-value), #e9ecef 0deg);
        display: flex; align-items: center; justify-content: center;
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.05);
        transition: background 1.5s ease-out;
    }
    .health-gauge::before { content: ""; position: absolute; width: 82%; height: 82%; background: white; border-radius: 50%; }
    .health-gauge-text { position: absolute; z-index: 2; font-weight: 800; font-size: 1.5rem; color: #2d3436; }

    /* --- COMPONENT: FORM INPUTS (SOFT UI) --- */
    .form-label { font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 700; color: #636e72; margin-bottom: 0.5rem; }
    .form-control, .form-select {
        background-color: var(--input-bg); border: 2px solid transparent;
        border-radius: 12px; padding: 14px 16px; font-weight: 600; color: #2d3436;
        transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus {
        background-color: white; border-color: #2ecc71;
        box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.1);
    }
    .input-group-text { background: var(--input-bg); border: none; border-radius: 12px 0 0 12px; color: #b2bec3; }

    /* --- COMPONENT: ANALYZE BUTTON (GLOW) --- */
    .btn-action {
        background: linear-gradient(135deg, #0f9b0f 0%, #00d2ff 100%);
        border: none; color: white; padding: 18px 32px;
        border-radius: 50px; font-weight: 800; font-size: 1.1rem;
        letter-spacing: 1px; text-transform: uppercase;
        box-shadow: 0 10px 30px rgba(15, 155, 15, 0.4);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        width: 100%; display: flex; align-items: center; justify-content: center;
        position: relative; overflow: hidden;
    }
    .btn-action:hover { transform: translateY(-4px) scale(1.02); box-shadow: 0 20px 40px rgba(15, 155, 15, 0.6); filter: brightness(1.1); }
    .btn-action:active { transform: translateY(2px); }
    .btn-action::after {
        content: ""; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); transition: 0.6s;
    }
    .btn-action:hover::after { left: 100%; }

    /* --- COMPONENT: TICKER --- */
    .ticker-item { transition: background 0.2s; cursor: default; }
    .ticker-item:hover { background-color: #f8f9fa; }
    
    /* UTILS */
    .animate-pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
</style>

<div class="container-fluid px-0 animate-fade-in">

    <div class="hero-welcome">
        <i class="fas fa-leaf hero-pattern"></i>
        <div class="position-relative" style="z-index: 2;">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-white bg-opacity-25 rounded-pill px-3 py-1 d-inline-flex align-items-center me-3 backdrop-blur">
                    <span class="badge bg-white text-success rounded-circle p-1 me-2 animate-pulse"><i class="fas fa-circle" style="font-size:0.5rem"></i></span>
                    <span class="text-white small fw-bold">System Online</span>
                </div>
                <span class="text-white opacity-75 small"><?php echo date('l, F j, Y'); ?></span>
            </div>
            <h1 class="display-5 fw-bold mb-2">Hello, Farmer <?php echo htmlspecialchars($user_name); ?>.</h1>
            <p class="fs-5 opacity-90 mb-0" style="max-width: 600px;">Your farm intelligence hub is active. Review your health metrics below or run a new diagnosis.</p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        
        <div class="col-lg-6">
            <div class="card-modern h-100 p-0 overflow-hidden position-relative border-start border-4 border-<?php echo $prediction['status'] == 'danger' ? 'danger' : 'success'; ?>">
                <?php if ($prediction['status'] == 'danger'): ?>
                    <div class="position-absolute top-0 end-0 p-4 opacity-10 animate-pulse"><i class="fas fa-radar fa-5x text-danger"></i></div>
                <?php endif; ?>

                <div class="p-4 d-flex align-items-center h-100">
                    <div class="me-4 text-center ps-2">
                        <h2 class="display-4 fw-bold text-dark m-0"><?php echo round($weather['temp']); ?>°</h2>
                        <span class="badge bg-light text-dark border mt-2">HUM: <?php echo $weather['humidity']; ?>%</span>
                    </div>
                    <div class="ps-4 border-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-<?php echo $prediction['status'] == 'danger' ? 'exclamation-triangle text-danger' : 'check-circle text-success'; ?> fa-lg me-2"></i>
                            <h5 class="fw-bold m-0 text-dark">AI Forecast</h5>
                        </div>
                        <p class="text-muted mb-0 lh-sm">
                            <?php if ($prediction['status'] == 'danger'): ?>
                                <span class="text-danger fw-bold">Risk Detected:</span> <?php echo $prediction['data']['disease']; ?> due to current humidity levels.
                            <?php else: ?>
                                Environmental conditions are currently <span class="text-success fw-bold">Optimal</span>. No immediate disease vectors.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-modern h-100 p-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="health-gauge-container me-4">
                        <div class="health-gauge" style="--gauge-value: <?php echo $health_score; ?>%; --gauge-color: var(--bs-<?php echo $insight_color; ?>);"></div>
                        <div class="health-gauge-text position-absolute top-50 start-50 translate-middle"><?php echo $health_score; ?></div>
                    </div>
                    <div>
                        <div class="text-uppercase text-muted fw-bold small mb-1">Overall Health Index</div>
                        <h3 class="fw-bold text-dark m-0"><?php echo $health_label; ?></h3>
                        <small class="text-<?php echo $insight_color; ?> fw-bold">
                            <?php echo ($health_score > 50) ? '<i class="fas fa-arrow-trend-up me-1"></i> Conditions Improving' : '<i class="fas fa-arrow-trend-down me-1"></i> Attention Needed'; ?>
                        </small>
                    </div>
                </div>
                <div class="ps-4 border-start w-50">
                    <small class="text-uppercase text-primary fw-bold mb-2 d-block"><i class="fas fa-robot me-1"></i> Smart Insight</small>
                    <p class="text-muted small mb-0 lh-sm"><?php echo $insight_msg; ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($result): ?>
    <div id="results-panel" class="mb-5 animate-fade-in">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-dark text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div>
                <h4 class="fw-bold m-0">Diagnostic Report</h4>
                <small class="text-muted">Generated <?php echo date('h:i A'); ?></small>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-modern h-100 p-4 position-relative overflow-hidden border-top border-4 border-danger">
                    <div class="position-absolute top-0 end-0 p-3 opacity-10"><i class="fas fa-biohazard fa-5x text-danger"></i></div>
                    <small class="text-uppercase text-muted fw-bold">Pathogen Identified</small>
                    <h2 class="fw-bold text-danger mt-2 mb-3"><?php echo $result['disease']; ?></h2>
                    
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2 mb-4">
                        <i class="fas fa-circle me-1 small"></i>
                        <?php echo ($severity == 2) ? 'Severe Infection' : 'Early Stage'; ?>
                    </span>

                    <div class="bg-light rounded-3 p-3">
                        <small class="text-muted d-block mb-2 fw-bold" style="font-size:0.7rem">OBSERVED SYMPTOMS</small>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($symptomsInput as $sym): ?>
                                <span class="badge bg-white text-dark border fw-normal px-2 py-1">
                                    <i class="fas fa-check text-success me-1"></i><?php echo htmlspecialchars($sym); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-modern h-100 p-4 position-relative overflow-hidden border-top border-4 border-info">
                    <div class="position-absolute top-0 end-0 p-3 opacity-10"><i class="fas fa-file-medical fa-5x text-info"></i></div>
                    <small class="text-uppercase text-muted fw-bold">Medical Protocol</small>
                    
                    <div class="bg-info bg-opacity-10 border border-info border-opacity-25 rounded-3 p-3 mt-3 mb-3">
                        <small class="text-info fw-bold d-block mb-1" style="font-size:0.7rem">RX / CHEMICAL</small>
                        <div class="fw-bold text-dark fs-5"><?php echo $result['treatment_name']; ?></div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="border rounded-3 p-2 text-center">
                                <small class="text-muted fw-bold" style="font-size:0.65rem">DOSAGE</small>
                                <div class="fw-bold text-dark"><?php echo $result['fungicide']; ?> ml</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded-3 p-2 text-center">
                                <small class="text-muted fw-bold" style="font-size:0.65rem">WATER MIX</small>
                                <div class="fw-bold text-dark"><?php echo $result['water']; ?> L</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <div>
                            <small class="text-muted d-block fw-bold" style="font-size:0.65rem">FREQUENCY</small>
                            <span class="text-dark fw-bold small"><?php echo $result['frequency']; ?></span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block fw-bold" style="font-size:0.65rem">TARGET RECOVERY</small>
                            <span class="text-success fw-bold small"><?php echo $result['target_date']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-modern h-100 p-4 position-relative overflow-hidden border-top border-4 border-success">
                    <div class="position-absolute top-0 end-0 p-3 opacity-10"><i class="fas fa-chart-pie fa-5x text-success"></i></div>
                    <small class="text-uppercase text-muted fw-bold">Financial Forecast</small>
                    
                    <div class="text-center py-4">
                        <small class="text-success fw-bold text-uppercase ls-1">Projected ROI</small>
                        <h1 class="fw-bold text-success m-0 display-5">+₱<?php echo $result['roi']; ?></h1>
                        <small class="text-muted">Net Value Preserved</small>
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Crop Value Saved</span>
                            <span class="fw-bold text-dark small">₱<?php echo $result['loss']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Treatment Cost</span>
                            <span class="fw-bold text-danger small">-₱<?php echo $result['cost']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if($scroll_to_result): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById('results-panel').scrollIntoView({ behavior: 'smooth' });
        });
    </script>
    <?php endif; ?>
    
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card-modern h-100">
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle p-2 me-3"><i class="fas fa-wand-magic-sparkles"></i></div>
                        <div>
                            <h5 class="fw-bold text-dark m-0">New Diagnosis</h5>
                            <small class="text-muted">Configure your observation parameters below.</small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <form method="POST" id="diagnosisForm">
                        
                        <div class="mb-4">
                            <label class="form-label text-uppercase">Visual Observations</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-eye"></i></span>
                                <select name="symptom[]" id="symptom-select" class="form-select border-start-0 ps-3" multiple="multiple" required>
                                    <option value="" disabled>Select symptoms...</option>
                                    <optgroup label="Common Issues">
                                        <option value="yellow leaves">Yellow Leaves (General)</option>
                                        <option value="wilting">Wilting (Drooping)</option>
                                        <option value="stunted growth">Stunted Growth</option>
                                        <option value="dry leaf tips">Dry/Burnt Leaf Tips</option>
                                    </optgroup>
                                    <optgroup label="Spots & Marks">
                                        <option value="black spots">Black Spots</option>
                                        <option value="brown spots">Brown Spots</option>
                                        <option value="rust spots">Rust Colored Spots</option>
                                        <option value="white powder">White Powder (Mildew)</option>
                                        <option value="concentric rings">Target Spots (Blight)</option>
                                        <option value="water soaked spots">Water-Soaked Spots</option>
                                    </optgroup>
                                    <optgroup label="Pests & Damage">
                                        <option value="holes in leaves">Holes in Leaves</option>
                                        <option value="webbing on leaves">Webbing (Mites)</option>
                                        <option value="sticky honey dew">Sticky Residue</option>
                                        <option value="white cottony mass">White Cottony Mass</option>
                                        <option value="leaf miner tracks">Winding Tracks</option>
                                    </optgroup>
                                    <optgroup label="Roots & Fruit">
                                        <option value="rotting roots">Rotting Roots</option>
                                        <option value="galls on roots">Root Galls/Knots</option>
                                        <option value="deformed fruit">Deformed Fruit</option>
                                        <option value="blossom end rot">Rotten Fruit Bottom</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-uppercase">Total Farm Size</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-expand"></i></span>
                                    <input type="number" name="total_land_size" class="form-control border-start-0" placeholder="e.g. 1000" required>
                                    <span class="input-group-text">sqm</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-uppercase">Affected Area</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-ruler-combined text-danger"></i></span>
                                    <input type="number" name="affected_size" class="form-control border-start-0" placeholder="e.g. 50" required>
                                    <span class="input-group-text">sqm</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label text-uppercase">Observed Severity</label>
                            <div class="d-flex gap-3">
                                <input type="radio" class="btn-check" name="severity" id="sev1" value="1" checked>
                                <label class="btn btn-outline-success w-50 py-3 fw-bold rounded-4 border-2" for="sev1">
                                    <i class="fas fa-shield-alt me-2"></i> Mild / Early
                                </label>
                                <input type="radio" class="btn-check" name="severity" id="sev2" value="2">
                                <label class="btn btn-outline-danger w-50 py-3 fw-bold rounded-4 border-2" for="sev2">
                                    <i class="fas fa-radiation me-2"></i> Severe / Late
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn-action">
                            <i class="fas fa-microchip me-2 fa-lg"></i> Run AI Analysis
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-modern h-100">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark m-0"><i class="fas fa-chart-line text-success me-2"></i>Live Market</h5>
                    <div class="spinner-grow text-success spinner-grow-sm" role="status"></div>
                </div>
                <div class="card-body p-0">
                    <div class="ticker-item p-4 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3"><i class="fas fa-wheat fa-lg"></i></div>
                            <div>
                                <small class="text-muted fw-bold" style="font-size:0.7rem">CROP VALUE</small>
                                <div class="fw-bold text-dark">Yield / sqm</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <h5 class="fw-bold text-dark m-0">₱<?php echo number_format($market_data['crop_value'], 2); ?></h5>
                            <small class="text-success fw-bold"><i class="fas fa-caret-up"></i> 2.4%</small>
                        </div>
                    </div>

                    <div class="ticker-item p-4 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3"><i class="fas fa-flask fa-lg"></i></div>
                            <div>
                                <small class="text-muted fw-bold" style="font-size:0.7rem">CHEMICAL</small>
                                <div class="fw-bold text-dark">Cost / ml</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <h5 class="fw-bold text-dark m-0">₱<?php echo number_format($market_data['fungicide_cost'], 2); ?></h5>
                            <small class="text-muted fw-bold">- 0.0%</small>
                        </div>
                    </div>

                    <div class="ticker-item p-4 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded p-3 me-3"><i class="fas fa-users fa-lg"></i></div>
                            <div>
                                <small class="text-muted fw-bold" style="font-size:0.7rem">LABOR</small>
                                <div class="fw-bold text-dark">Avg Rate</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <h5 class="fw-bold text-dark m-0">₱<?php echo number_format($market_data['labor_cost'], 2); ?></h5>
                            <small class="text-danger fw-bold"><i class="fas fa-caret-up"></i> 1.2%</small>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-light text-center border-top">
                        <small class="text-muted fst-italic" style="font-size: 0.7rem;">
                            <i class="fas fa-sync-alt me-1"></i> Data synced: <?php echo date("h:i A"); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>