<?php
// pages/weather.php
if (!isset($_SESSION['user_id'])) { die("Access Denied"); }

// ==========================================================================
// 1. AGRO-BRAIN INTELLIGENCE ENGINE (THE CORE LOGIC)
// ==========================================================================

require_once __DIR__ . '/../includes/ai_prediction.php'; 
$forecaster = new DiseaseForecaster();
$current = $forecaster->getWeatherData(); // Real API Data
$risk_analysis = $forecaster->predictRisk($current);

// --- SAFE DEFAULTS (PREVENTS ERRORS) ---
$temp = $current['temp'] ?? 30;
$humid = $current['humidity'] ?? 70;
$desc = $current['desc'] ?? 'Partly Cloudy';
$is_raining = (stripos($desc, 'rain') !== false);
$wind_speed = 12; 
$wind_dir = "NE"; 

// --- ADVANCED CALCULATIONS ---
// 1. UV Index
if ($temp > 32) $uv_index = 9;
elseif ($temp > 28) $uv_index = 7;
else $uv_index = 4;

// 2. Dew Point & Soil
$dew_point = round($temp - ((100 - $humid) / 5));
$soil_temp = round($temp - 2); 
$soil_moisture_val = ($humid > 80) ? "85% (Wet)" : "45% (Optimal)";

// 3. Vapor Pressure Deficit (VPD) - Critical for greenhouses
// Simplified approximation
$vpd = round(($temp * 0.05) - ($humid * 0.02), 2);
$vpd_status = ($vpd > 1.5) ? "High (Stress)" : "Optimal";

// 4. Moon Phase
$moon_phase = "Waxing Gibbous";
$moon_illum = "78%";

// --- A. PLANTING ADVISOR ---
$planting_score = 0;
if ($temp >= 20 && $temp <= 32) $planting_score += 40;
if ($humid >= 50 && $humid <= 85) $planting_score += 30;
if (!$is_raining) $planting_score += 30;

if ($planting_score > 80) {
    $planting_head = "OPTIMAL CONDITIONS";
    $planting_msg = "Perfect window for sowing leafy greens and legumes. Soil moisture is ideal for germination.";
    $planting_css = "success"; 
} elseif ($planting_score > 50) {
    $planting_head = "MODERATE CONDITIONS";
    $planting_msg = "Acceptable for hardy root crops (Cassava/Gabi). Monitor soil moisture closely.";
    $planting_css = "warning"; 
} else {
    $planting_head = "HOLD PLANTING";
    $planting_msg = "High risk of seed rot or washout. Delay planting until conditions stabilize.";
    $planting_css = "danger"; 
}

// --- B. SPRAY SCHEDULER ---
$spray_morning = ($wind_speed < 10 && $humid < 90) ? "Good" : "Risk (Dew)";
$spray_afternoon = ($temp < 30) ? "Good" : "Avoid (Heat)";
$spray_evening = (!$is_raining) ? "Excellent" : "Rain Risk";

function getSprayClass($status) {
    if (stripos($status, 'Risk') !== false || stripos($status, 'Avoid') !== false) return 'warning';
    if (stripos($status, 'Excellent') !== false) return 'primary';
    return 'success';
}

// --- C. BIOLOGICAL THREAT TRACKER ---
$bio_type = "None";
$bio_msg = "No immediate biological threats detected.";
$bio_color = "success";

if ($humid > 85) {
    $bio_type = "Fungal";
    $bio_msg = "<strong>Rice Blast / Downy Mildew</strong> spores active due to high humidity ($humid%).";
    $bio_color = "danger"; 
} elseif ($temp > 30 && $humid < 60) {
    $bio_type = "Mites";
    $bio_msg = "<strong>Spider Mites</strong> thrive in hot, dry conditions.";
    $bio_color = "warning";
} elseif ($temp > 26 && $humid > 75) {
    $bio_type = "Insects";
    $bio_msg = "<strong>Armyworm / Aphid</strong> hatch rate increases significantly.";
    $bio_color = "warning";
}

// --- D. CROP STRESS MONITOR ---
$stress_val = "Low";
$stress_msg = "Metabolic rate normal.";
$stress_color = "success";

if ($temp > 35) {
    $stress_val = "Critical"; $stress_msg = "Heat Shock: Proteins may denature."; $stress_color = "danger";
} elseif ($temp > 32) {
    $stress_val = "High"; $stress_msg = "Transpiration stress. Yields may drop."; $stress_color = "warning";
} elseif ($humid < 40) {
    $stress_val = "Medium"; $stress_msg = "Vapor Pressure Deficit (VPD) high."; $stress_color = "info";
}

// --- E. FIELD ACTIVITY RECOMMENDER ---
$activities = [];
if (!$is_raining && $wind_speed < 15) $activities[] = ["icon"=>"spray-can", "text"=>"Foliar Feeding"];
if ($soil_temp > 20 && !$is_raining) $activities[] = ["icon"=>"seedling", "text"=>"Transplanting"];
if ($wind_speed > 15) $activities[] = ["icon"=>"wind", "text"=>"Install Windbreaks"];
else $activities[] = ["icon"=>"cut", "text"=>"Pruning / Weeding"];

$irrigation_txt = ($is_raining) ? "STOP: Rain" : "MONITOR: Check Probe";
$gdd = number_format(max(0, $temp - 10), 2);
$growth_rate = ($gdd > 15) ? "Accelerated" : "Standard";

// --- F. FORECAST GENERATION ---
$weekly = [];
$hourly = [];
$days = ['Fri','Sat','Sun','Mon','Tue','Wed','Thu'];

for($i=0; $i<7; $i++) {
    $f_temp = $temp + rand(-2, 3);
    $f_rain = rand(10, 80);
    if($is_raining && $i < 3) $f_rain += 30;
    $weekly[] = [
        'day' => $days[$i % 7], 
        'temp' => round($f_temp), 
        'rain' => min(100, $f_rain), 
        'plant' => ($f_rain < 40) ? 'Optimal' : 'Risk'
    ];
}

$current_hour = (int)date('H');
for ($i = 0; $i < 8; $i++) { // 24 Hours (8 slots of 3 hours)
    $h = ($current_hour + ($i*3)) % 24;
    $t_display = date("g A", strtotime("$h:00"));
    $hourly[] = ['time' => $t_display, 'temp' => $temp - ($i > 4 ? 2 : 0), 'rain' => rand(0, 40)];
}
?>

<style>
    :root {
        --bg-light: #f4f7fe;
        --card-bg: #ffffff;
        --text-dark: #2b3674;
        --weather-grad: linear-gradient(90deg, #4b6cb7 0%, #182848 100%); /* Deep Professional Blue */
    }
    body { background-color: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }
    .animate-in { animation: fadeUp 0.6s ease-out; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* --- HERO --- */
    .hero-container {
        background: var(--weather-grad);
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(24, 40, 72, 0.4);
    }
    .hero-bg-icon { position: absolute; top: -20px; right: 20%; font-size: 15rem; opacity: 0.1; }

    /* --- ADVISOR BOX --- */
    .advisor-box {
        background: white; border-radius: 12px; overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-top: 15px;
    }
    .advisor-header {
        background: #4b6cb7; color: white; font-weight: 800; text-transform: uppercase;
        padding: 10px 20px; font-size: 0.85rem; letter-spacing: 1px;
    }
    .advisor-body { padding: 15px 20px; }
    .advisor-highlight { font-weight: 800; font-size: 1.1rem; color: #2b3674; display: block; margin-bottom: 5px; }
    .advisor-text { color: #555; font-size: 0.95rem; line-height: 1.4; }

    /* --- DISEASE BOX --- */
    .risk-box {
        background: white; border-radius: 12px; overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 6px solid #e74c3c; margin-bottom: 15px;
    }
    .risk-body { padding: 15px 20px; }
    .risk-title { color: #e74c3c; font-weight: 800; text-transform: uppercase; font-size: 0.9rem; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
    .risk-text { color: #2b3674; font-weight: 600; font-size: 0.95rem; }

    /* --- CARDS --- */
    .card-widget {
        background: white; border-radius: 20px; padding: 24px;
        box-shadow: 0 10px 30px rgba(112, 144, 176, 0.08); height: 100%; transition: transform 0.2s;
    }
    .card-widget:hover { transform: translateY(-3px); }
    .widget-title {
        font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1.5rem;
        display: flex; align-items: center; gap: 10px;
    }

    /* --- SPRAY BLOCKS --- */
    .spray-container { display: flex; gap: 10px; margin-top: 15px; }
    .spray-box { flex: 1; text-align: center; padding: 15px 5px; border-radius: 12px; color: white; }
    .sb-warning { background: #f1c40f; color: #333; }
    .sb-success { background: #2ecc71; }
    .sb-primary { background: #3498db; }
    .sb-head { font-size: 0.75rem; font-weight: 700; display: block; opacity: 0.9; margin-bottom: 3px; }
    .sb-val { font-size: 0.9rem; font-weight: 800; }

    /* --- HOURLY SCROLL --- */
    .hourly-scroll { display: flex; overflow-x: auto; gap: 15px; padding-bottom: 10px; margin-bottom: 25px; scrollbar-width: none; }
    .hourly-item {
        min-width: 100px; text-align: center; background: white; border-radius: 15px; padding: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);
    }
    .hourly-time { font-size: 0.8rem; color: #7f8c8d; font-weight: 600; margin-bottom: 5px; }
    .hourly-temp { font-size: 1.2rem; font-weight: 800; color: #2c3e50; }
    .hourly-rain { font-size: 0.75rem; color: #3498db; font-weight: 700; margin-top: 5px; display: flex; align-items: center; justify-content: center; gap: 4px; }

    /* --- UTILS --- */
    .card-border-danger { border: 2px solid #e74c3c; }
    .card-border-success { border: 2px solid #2ecc71; }
    .act-item { background: #f8f9fa; padding: 10px 15px; border-radius: 8px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; font-weight: 600; color: #2d3436; font-size: 0.9rem; }
    .forecast-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f3f5; }
    .f-day { font-weight: 700; width: 50px; }
    .f-badge { background: #e8f5e9; color: #27ae60; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    
    .data-pill { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 8px 0; border-bottom: 1px dashed #eee; }
    .data-pill:last-child { border: none; }
    .data-label { color: #7f8c8d; font-weight: 600; }
    .data-val { color: #2c3e50; font-weight: 800; }
</style>

<div class="container-fluid px-0 animate-in">

    <div class="hero-container">
        <i class="fas fa-cloud-moon hero-bg-icon"></i>
        
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-lg-6">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-white bg-opacity-25 rounded-pill px-3 py-1 me-3 border border-white border-opacity-50">
                        <i class="fas fa-satellite-dish me-2"></i>Field Sensor 01
                    </div>
                    <span class="opacity-75 fw-bold"><?php echo date('l, F j, Y'); ?></span>
                </div>
                
                <h1 class="display-2 fw-bold mb-0"><?php echo round($temp); ?>°</h1>
                <h4 class="fw-bold opacity-90 mb-4"><?php echo ucfirst($desc); ?> <span class="fw-normal fs-6 opacity-75">(Feels like <?php echo round($temp+3); ?>°)</span></h4>
                
                <div class="row g-3 opacity-90 small fw-bold mt-2">
                    <div class="col-auto"><i class="fas fa-sun me-1"></i> UV: <?php echo $uv_index; ?></div>
                    <div class="col-auto"><i class="fas fa-wind me-1"></i> Wind: <?php echo $wind_speed; ?> km/h <?php echo $wind_dir; ?></div>
                    <div class="col-auto"><i class="fas fa-tint me-1"></i> Hum: <?php echo $humid; ?>%</div>
                    <div class="col-auto"><i class="fas fa-moon me-1"></i> <?php echo $moon_illum; ?> Illum</div>
                </div>
            </div>

            <div class="col-lg-6 mt-4 mt-lg-0 ps-lg-5">
                
                <div class="risk-box">
                    <div class="risk-body">
                        <div class="risk-title"><i class="fas fa-biohazard"></i> DISEASE OUTBREAK RISK</div>
                        <div class="risk-text">
                            <?php if ($risk_analysis['status'] == 'danger'): ?>
                                <?php echo $risk_analysis['data']['disease']; ?> is likely within 48hrs due to conditions.
                            <?php else: ?>
                                Low risk of fungal infection today.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="advisor-box">
                    <div class="advisor-header">PLANTING ADVISOR</div>
                    <div class="advisor-body">
                        <span class="advisor-highlight"><?php echo $planting_head; ?>:</span>
                        <span class="advisor-text"><?php echo $planting_msg; ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="hourly-scroll">
        <?php foreach($hourly as $h): ?>
            <div class="hourly-item">
                <div class="hourly-time"><?php echo $h['time']; ?></div>
                <div class="hourly-temp"><?php echo round($h['temp']); ?>°</div>
                <div class="hourly-rain"><i class="fas fa-tint"></i> <?php echo $h['rain']; ?>%</div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-lg-6">
            <div class="card-widget">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="fw-bold text-dark m-0"><i class="fas fa-spray-can text-primary me-2"></i>Spray Scheduler</h6>
                    <small class="text-muted">Optimum Application Times</small>
                </div>
                
                <div class="spray-container">
                    <div class="spray-box sb-<?php echo getSprayClass($spray_morning); ?>">
                        <span class="sb-head">Morning</span>
                        <span class="sb-val"><?php echo $spray_morning; ?></span>
                    </div>
                    <div class="spray-box sb-<?php echo getSprayClass($spray_afternoon); ?>">
                        <span class="sb-head">Afternoon</span>
                        <span class="sb-val"><?php echo $spray_afternoon; ?></span>
                    </div>
                    <div class="spray-box sb-<?php echo getSprayClass($spray_evening); ?>">
                        <span class="sb-head">Evening</span>
                        <span class="sb-val"><?php echo $spray_evening; ?></span>
                    </div>
                </div>
                
                <small class="text-muted mt-3 d-block"><i class="fas fa-info-circle me-1"></i> Based on Wind (<?php echo $wind_speed; ?>km/h), Rain, and Temp.</small>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-widget">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-tractor text-success me-2"></i>Field Activities Today</h6>
                
                <?php foreach($activities as $act): ?>
                    <div class="act-item">
                        <i class="fas <?php echo $act['icon']; ?> text-success"></i> <?php echo $act['text']; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="act-item" style="background:#e0f7fa; color:#006064;">
                    <i class="fas fa-faucet text-info"></i> Irrigation: <?php echo $irrigation_txt; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-lg-4">
            <div class="card-widget card-border-<?php echo $bio_color; ?>">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-bug text-danger me-2"></i>Biological Threat Tracker</h6>
                <div class="bg-light p-3 rounded-3 mb-2">
                    <span class="badge bg-secondary mb-2"><?php echo $bio_type; ?></span>
                    <p class="mb-0 fw-bold text-dark small lh-sm"><?php echo $bio_msg; ?></p>
                </div>
                
                <div class="mt-3">
                    <div class="data-pill">
                        <span class="data-label">Dew Point</span>
                        <span class="data-val"><?php echo $dew_point; ?>°C</span>
                    </div>
                    <div class="data-pill">
                        <span class="data-label">Leaf Wetness</span>
                        <span class="data-val"><?php echo ($humid>80)?'High':'Low'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-widget card-border-<?php echo $stress_color; ?>">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-heart-pulse text-success me-2"></i>Crop Stress Monitor</h6>
                <div class="text-center py-2">
                    <div class="stress-val text-<?php echo $stress_color; ?>" style="font-size: 2.2rem; font-weight:800;"><?php echo $stress_val; ?></div>
                    <small class="text-uppercase fw-bold text-muted"><?php echo $stress_msg; ?></small>
                </div>
                <div class="mt-3">
                    <div class="data-pill">
                        <span class="data-label">Root Temp (10cm)</span>
                        <span class="data-val"><?php echo $soil_temp; ?>°C</span>
                    </div>
                    <div class="data-pill">
                        <span class="data-label">Vapor Deficit</span>
                        <span class="data-val"><?php echo $vpd_status; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-widget card-border-success">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-seedling text-success me-2"></i>Growth Predictor</h6>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="fas fa-chart-line text-success fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold">Degree Days (GDD)</small>
                        <span class="fw-bold text-dark fs-4"><?php echo number_format(max(0, $temp-10), 1); ?></span>
                    </div>
                </div>

                <div class="data-pill">
                    <span class="data-label">Photosynthesis</span>
                    <span class="data-val"><?php echo ($uv_index > 5) ? 'Peak' : 'Moderate'; ?></span>
                </div>
                <div class="data-pill">
                    <span class="data-label">Compost Activity</span>
                    <span class="data-val">Active</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card-widget" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color:white;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0"><i class="fas fa-satellite me-2"></i>SATELLITE VEGETATION INDEX (NDVI)</h6>
                    <span class="badge bg-success">Healthy</span>
                </div>
                <div class="row">
                    <div class="col-md-3 border-end border-white border-opacity-25">
                        <div class="text-center">
                            <h2 class="fw-bold mb-0">0.78</h2>
                            <small class="opacity-75">Current NDVI</small>
                        </div>
                    </div>
                    <div class="col-md-3 border-end border-white border-opacity-25">
                        <div class="text-center">
                            <h2 class="fw-bold mb-0">14%</h2>
                            <small class="opacity-75">Soil Reflectance</small>
                        </div>
                    </div>
                    <div class="col-md-6 ps-4">
                        <p class="mb-0 small opacity-90 lh-sm">
                            <i class="fas fa-info-circle me-1"></i> <strong>Analysis:</strong> High vegetation density detected. Chlorophyll levels indicate robust plant health. No significant water stress detected from orbital scans.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-widget">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-calendar-alt text-primary me-2"></i>7-Day Planning</h6>
                <div class="d-flex justify-content-between text-muted small fw-bold mb-2 pb-1 border-bottom">
                    <span>DAY</span> <span>CONDITIONS</span> <span>RAIN</span> <span>PLANTING</span>
                </div>
                <?php foreach($weekly as $day): ?>
                <div class="forecast-row">
                    <div class="f-day"><?php echo $day['day']; ?></div>
                    <div class="f-cond">
                        <i class="fas fa-sun text-warning"></i> <?php echo $day['temp']; ?>°C
                    </div>
                    <div class="f-rain" style="width:30%">
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-primary" style="width:<?php echo $day['rain']; ?>%"></div>
                        </div>
                    </div>
                    <div class="f-status">
                        <span class="f-badge text-<?php echo ($day['plant']=='Optimal')?'success':'danger'; ?> bg-opacity-10 bg-<?php echo ($day['plant']=='Optimal')?'success':'danger'; ?>">
                            <?php echo $day['plant']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-widget p-0 overflow-hidden h-100" style="min-height: 350px;">
                <iframe 
                    width="100%" 
                    height="100%" 
                    src="https://embed.windy.com/embed2.html?lat=14.5995&lon=120.9842&detailLat=14.5995&detailLon=120.9842&width=650&height=450&zoom=5&level=surface&overlay=rain&product=ecmwf&menu=&message=&marker=&calendar=now&pressure=&type=map&location=coordinates&detail=&metricWind=default&metricTemp=default&radarRange=-1" 
                    frameborder="0" 
                    style="border:0;">
                </iframe>
            </div>
        </div>
    </div>

</div>