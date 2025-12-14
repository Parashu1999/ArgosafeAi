<?php
// admin/dashboard.php
session_start();

// 1. SECURITY: ENSURE ADMIN LOGIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database Connection
$host = 'localhost'; $db = 'agrosafe_db'; $u = 'root'; $p = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Error: Could not connect or set attributes."); }

// --- SET PAGE NAME FOR ACTIVE SIDEBAR LINK ---
$page_name = 'dashboard';

// ==========================================================================
// 2. LOGIC: FETCH ANALYTICS & WEATHER DATA
// ==========================================================================

// --- A. WEATHER & AI INTELLIGENCE (FOR THE VISUAL WIDGETS) ---
require_once __DIR__ . '/../includes/ai_prediction.php'; 
$forecaster = new DiseaseForecaster();
$current = $forecaster->getWeatherData(); 
$risk_analysis = $forecaster->predictRisk($current);

// Fallbacks
$temp = $current['temp'] ?? 30;
$humid = $current['humidity'] ?? 70;
$desc = $current['desc'] ?? 'Partly Cloudy';
$wind_speed = 12;
$is_raining = (stripos($desc, 'rain') !== false);

// --- AGRO-INTELLIGENCE CALCULATIONS ---
$planting_score = 0;
if ($temp >= 20 && $temp <= 32) $planting_score += 40;
if ($humid >= 50 && $humid <= 85) $planting_score += 30;
if (!$is_raining) $planting_score += 30;

if ($planting_score > 80) {
    $planting_status = "Optimal";
    $planting_msg = "Conditions optimal for leafy greens.";
} elseif ($planting_score > 50) {
    $planting_status = "Moderate";
    $planting_msg = "Monitor conditions; suitable for root crops.";
} else {
    $planting_status = "Hold Planting";
    $planting_msg = "Conditions may cause seed rot or poor germination.";
}

// Spray Logic
$spray_morning = ($wind_speed < 10 && $humid < 90) ? "Risk (Dew/Wind)" : "Good";
$spray_afternoon = ($temp < 30) ? "Good" : "Avoid";
$spray_evening = "Excellent";


// --- B. USER & SYSTEM ANALYTICS (FIXED UNDEFINED VARIABLES) ---
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// FIX: Initialize variables if query fails or returns NULL
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$new_users_today = $stmt->fetchColumn() ?? 0; // <-- FIX 1: Use null coalescing to default to 0

$active_users = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM history WHERE date >= NOW() - INTERVAL 1 DAY")->fetchColumn() ?? 0;

$total_scans = $pdo->query("SELECT COUNT(*) FROM history")->fetchColumn() ?? 0;

$scans_today = $pdo->query("SELECT COUNT(*) FROM history WHERE DATE(date) = CURDATE()")->fetchColumn() ?? 0;

// --- FIX: Ensure $top_diseases is always an array ---
$stmt = $pdo->query("SELECT diagnosis, COUNT(*) as count FROM history GROUP BY diagnosis ORDER BY count DESC LIMIT 3");
$top_diseases = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? []; // <-- FIX 2 & 3: Ensure $top_diseases is an array

$top_disease = $pdo->query("SELECT diagnosis FROM history GROUP BY diagnosis ORDER BY COUNT(*) DESC LIMIT 1")->fetchColumn();
$top_disease_name = $top_disease ?? 'None';

// System Health
$api_usage = 45; 
$alerts = [];
if ($api_usage > 80) $alerts[] = ["type"=>"warning", "msg"=>"Weather API quota near limit"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgroSafe Admin - Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --admin-bg: #f4f7fe;
            --sidebar-width: 260px;
            --sidebar-bg: #111c44;
            --primary: #4318FF;
            --card-white: #ffffff;
            --text-dark: #2b3674;
            --text-gray: #a3aed0;
        }
        body { background-color: var(--admin-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-dark); }
        
        /* SIDEBAR (Unified Styling) */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0;
            background: var(--sidebar-bg); color: white; padding: 24px;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        .brand { font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; letter-spacing: 1px; }
        .nav-link { 
            color: #a3aed0; padding: 14px 10px; margin-bottom: 5px; border-radius: 10px; 
            font-weight: 500; display: flex; align-items: center; gap: 12px; transition: 0.2s;
        }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { border-right: 4px solid var(--primary); border-radius: 10px 0 0 10px; }
        .nav-link i { width: 20px; font-size: 1.1rem; }

        /* CONTENT */
        .main-content { margin-left: var(--sidebar-width); padding: 30px; }
        
        /* HERO WEATHER BANNER */
        .weather-hero {
            background: linear-gradient(90deg, #36D1DC 0%, #5B86E5 100%);
            border-radius: 20px; padding: 30px; color: white; position: relative; overflow: hidden;
            box-shadow: 0 10px 30px rgba(58, 96, 115, 0.3);
            margin-bottom: 30px;
        }
        .field-badge {
            background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4);
            border-radius: 30px; padding: 5px 15px; font-weight: 700; font-size: 0.8rem;
            display: inline-flex; align-items: center; gap: 8px; margin-right: 15px;
        }
        .temp-display { font-size: 4rem; font-weight: 800; line-height: 1; }
        .weather-meta { font-size: 0.9rem; opacity: 0.9; font-weight: 600; display: flex; gap: 20px; margin-top: 15px; }

        /* RISK & ADVISOR CARDS */
        .alert-card {
            background: white; border-radius: 12px; overflow: hidden; margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .alert-card.risk { border-left: 6px solid #e74c3c; padding: 15px 20px; }
        .risk-title { color: #e74c3c; font-weight: 800; text-transform: uppercase; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
        .risk-desc { color: #2b3674; font-weight: 600; font-size: 0.9rem; line-height: 1.3; }

        .advisor-header { background: #5B86E5; padding: 8px 15px; color: white; font-weight: 800; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
        .advisor-body { padding: 12px 15px; }
        .advisor-highlight { color: #2b3674; font-weight: 800; display: inline; font-size: 1rem; }
        .advisor-text { color: #555; font-size: 0.9rem; }

        /* WIDGET GRID */
        .widget-card {
            background: white; border-radius: 20px; padding: 24px;
            box-shadow: 0 5px 20px rgba(112, 144, 176, 0.08); height: 100%; transition: transform 0.2s;
        }
        .widget-card:hover { transform: translateY(-3px); }
        .widget-title { font-size: 1rem; font-weight: 700; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        /* SPRAY SCHEDULER */
        .spray-grid { display: flex; gap: 10px; }
        .spray-item { flex: 1; text-align: center; padding: 15px 10px; border-radius: 12px; color: white; }
        .si-morning { background: #f1c40f; color: #333; }
        .si-afternoon { background: #2ecc71; }
        .si-evening { background: #3498db; }
        .si-label { font-size: 0.75rem; font-weight: 700; display: block; opacity: 0.9; }
        .si-val { font-size: 0.9rem; font-weight: 800; }

        /* STATS OVERVIEW */
        .stat-icon {
            width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-right: 15px;
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><i class="fas fa-leaf text-success me-2"></i> AGRO<span class="text-white">SAFE</span></div>
        <small class="fw-bold text-uppercase text-light mb-4 d-block opacity-75" style="font-size:0.7rem;">Admin Panel</small>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Manage Farmer</a>
            <a href="scans.php" class="nav-link"><i class="fas fa-database"></i> Scan History</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-sliders-h"></i> System Settings</a>
            <a href="security.php" class="nav-link"><i class="fas fa-lock"></i> Security & Privacy</a>
              <li class="nav-item">
                    <a class="nav-link" href="dataset.php">
                        <i class="fa-solid fa-circle-nodes"></i>
                        Dataset Manager
                    </a>
            </li> 
            <div style="margin-top: auto; padding-top: 100px;">
                <a href="../logout.php?redirect=admin" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>

         
        </div>
    </nav>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <small class="text-muted fw-bold text-uppercase ls-1">Admin Command Center</small>
                <h3 class="fw-bold text-dark m-0">System Overview</h3>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-server text-success me-2"></i>Online</span>
                <div class="bg-white p-2 rounded-circle shadow-sm border"><i class="fas fa-bell text-gray"></i></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="weather-hero">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <div class="d-flex align-items-center mb-3">
                                <div class="field-badge"><i class="fas fa-satellite-dish"></i> Live Field Sensor</div>
                                <span class="opacity-75 fw-bold"><?php echo date('l, F j, Y'); ?></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="temp-display me-4"><?php echo round($temp); ?>°</div>
                                <div>
                                    <h3 class="fw-bold m-0"><?php echo $desc; ?></h3>
                                    <span class="opacity-75 fw-bold">Feels like <?php echo round($temp + 2); ?>°</span>
                                </div>
                            </div>
                            <div class="weather-meta">
                                <span><i class="fas fa-sun"></i> UV Index: 4 (Moderate)</span>
                                <span><i class="fas fa-wind"></i> Wind: <?php echo $wind_speed; ?> km/h</span>
                                <span><i class="fas fa-tint"></i> Humidity: <?php echo $humid; ?>%</span>
                            </div>
                        </div>

                        <div class="col-lg-5 mt-4 mt-lg-0">
                            
                            <div class="alert-card risk">
                                <div class="risk-title"><i class="fas fa-biohazard"></i> DISEASE OUTBREAK RISK</div>
                                <div class="risk-desc">
                                    <?php if ($risk_analysis['status'] == 'danger'): ?>
                                        <?php echo $risk_analysis['data']['disease']; ?> is likely within 48hrs.
                                    <?php else: ?>
                                        No immediate disease threats detected.
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="alert-card">
                                <div class="advisor-header">PLANTING ADVISOR</div>
                                <div class="advisor-body">
                                    <span class="advisor-highlight"><?php echo $planting_status; ?>:</span>
                                    <span class="advisor-text"><?php echo $planting_msg; ?></span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-6">
                <div class="widget-card">
                    <div class="widget-title"><i class="fas fa-spray-can text-primary"></i> Spray Scheduler (Today)</div>
                    <div class="spray-grid">
                        <div class="spray-item si-morning">
                            <span class="si-label">Morning</span>
                            <span class="si-val"><?php echo $spray_morning; ?></span>
                        </div>
                        <div class="spray-item si-afternoon">
                            <span class="si-label">Afternoon</span>
                            <span class="si-val"><?php echo $spray_afternoon; ?></span>
                        </div>
                        <div class="spray-item si-evening">
                            <span class="si-label">Evening</span>
                            <span class="si-val"><?php echo $spray_evening; ?></span>
                        </div>
                    </div>
                    <p class="small text-muted mt-3 mb-0"><i class="fas fa-info-circle me-1"></i> Based on wind speed & evaporation rates.</p>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="widget-card d-flex align-items-center p-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users"></i></div>
                            <div>
                                <small class="text-gray fw-bold">Total Users</small>
                                <h3 class="fw-bold text-dark m-0"><?php echo $total_users; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="widget-card d-flex align-items-center p-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-plus"></i></div>
                            <div>
                                <small class="text-gray fw-bold">New Today</small>
                                <h3 class="fw-bold text-dark m-0">+<?php echo $new_users_today; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="widget-card d-flex align-items-center p-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-qrcode"></i></div>
                            <div>
                                <small class="text-gray fw-bold">Daily Scans</small>
                                <h3 class="fw-bold text-dark m-0"><?php echo $scans_today; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="widget-card d-flex align-items-center p-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-bug"></i></div>
                            <div>
                                <small class="text-gray fw-bold">Top Threat</small>
                                <h6 class="fw-bold text-dark m-0 text-truncate" style="max-width:100px;"><?php echo $top_disease_name; ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row g-4 mt-4">
            
            <div class="col-lg-8">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0"><i class="fas fa-microscope text-danger me-2"></i>Detection Summary</h5>
                        <button class="btn btn-sm btn-outline-secondary">View Full Report</button>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-4 border-end text-center">
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($total_scans); ?></h3>
                            <small class="text-muted">Total Scans All-Time</small>
                        </div>
                        <div class="col-4 border-end text-center">
                            <h3 class="fw-bold text-success mb-0"><?php echo $scans_today; ?></h3>
                            <small class="text-muted">Scans Processed Today</small>
                        </div>
                        <div class="col-4 text-center">
                            <h5 class="fw-bold text-primary mb-0 text-truncate">Tomato (Sim.)</h5>
                            <small class="text-muted">Most Scanned Crop</small>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Top Detected Pathogens</h6>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Disease Name</th>
                                    <th>Frequency</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($top_diseases) > 0): ?>
                                    <?php foreach($top_diseases as $d): ?>
                                    <tr>
                                        <td class="fw-bold text-danger"><?php echo htmlspecialchars($d['diagnosis']); ?></td>
                                        <td><?php echo $d['count']; ?> detections</td>
                                        <td><span class="badge bg-danger bg-opacity-10 text-danger"><i class="fas fa-arrow-up"></i> Rising</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted">No scan data available yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="stat-card mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-cloud-sun text-info me-2"></i>Weather API Status</h6>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Daily Quota Usage</span>
                            <span class="fw-bold"><?php echo $api_usage; ?>%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: <?php echo $api_usage; ?>%"></div>
                        </div>
                    </div>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Top Loc: <strong>Calabarzon</strong></li>
                        <li><i class="fas fa-bell me-2"></i> 145 Rain Alerts Sent</li>
                    </ul>
                </div>

                <div class="stat-card bg-light border">
                    <h6 class="fw-bold mb-3">System Notifications</h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach($alerts as $alert): ?>
                            <div class="alert-item alert-<?php echo $alert['type']; ?>">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle mt-1 me-2 text-<?php echo $alert['type']; ?>"></i>
                                    <small class="text-dark fw-bold"><?php echo $alert['msg']; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($alerts)): ?>
                            <div class="text-center text-muted small py-3">No active system alerts.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>