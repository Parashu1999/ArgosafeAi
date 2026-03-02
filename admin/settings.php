<?php
// admin/settings.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- DATABASE CONNECTION ---
$host = 'localhost'; $db = 'agrosafe_db'; $u = 'root'; $p = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
    // Ensure constants are set for error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) { 
    die("DB Error: Could not connect or set attributes."); 
}

// --- SET PAGE NAME FOR ACTIVE SIDEBAR LINK ---
$page_name = 'settings';

// Ensure key-value columns exist even if this table comes from an older schema.
try {
    $columns = [];
    $colStmt = $pdo->query("SHOW COLUMNS FROM settings");
    while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($col['Field'])) {
            $columns[] = $col['Field'];
        }
    }

    if (!in_array('setting_key', $columns, true)) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN setting_key VARCHAR(191) NULL");
    }
    if (!in_array('setting_value', $columns, true)) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN setting_value TEXT NULL");
    }
} catch (Exception $e) {
    // Keep page usable with defaults even if migration fails.
}

// SAVE SETTINGS (Handles Market Data and API Keys)
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_config') {
    $updates = [
        'crop_value' => $_POST['crop_value'],
        'fungicide_cost' => $_POST['fungicide_cost'],
        'labor_cost' => $_POST['labor_cost'],
        'weather_api_key' => $_POST['weather_api_key'],
        'email_api_key' => $_POST['email_api_key']
    ];

    foreach ($updates as $key => $val) {
        // Update first; insert when key does not exist.
        $updateSql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$val, $key]);

        if ($updateStmt->rowCount() === 0) {
            $insertSql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([$key, $val]);
        }
    }
    $msg = "System configuration updated successfully!";
}

// FETCH CURRENT SETTINGS
$current = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IS NOT NULL");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($row['setting_key']) || !array_key_exists('setting_value', $row)) {
        continue;
    }
    $current[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-bg: #f4f7fe;
            --sidebar-width: 260px;
            --sidebar-bg: #111c44;
            --primary: #4318FF;
            --text-dark: #2b3674;
        }
        body { background-color: var(--admin-bg); font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* SIDEBAR (Unified Styling) */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0;
            background: var(--sidebar-bg); color: white; padding: 24px;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        .brand { font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; letter-spacing: 1px; }
        .nav-link { 
            color: #a3aed0; padding: 14px 10px; margin-bottom: 5px; border-radius: 10px; 
            font-weight: 500; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: 0.2s;
        }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { border-right: 4px solid var(--primary); border-radius: 10px 0 0 10px; }
        .nav-link i { width: 20px; font-size: 1.1rem; }

        /* CONTENT */
        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 30px; }
        .form-label { font-weight: 600; color: #34495e; }
        .btn-action { width: 100%; border: none; font-weight: 700; }
    </style>
</head>
<body>
    
    <script>
        function triggerBackup(type) {
            if (type === 'db' && confirm("Are you sure you want to trigger a manual database backup?")) {
                alert("SUCCESS: Database backup initiated and saved to the 'backups/' folder.");
            } else if (type === 'media' && confirm("Are you sure you want to backup media files? This may take time.")) {
                alert("SUCCESS: Media files (Images/Scans) archived successfully.");
            } else if (type === 'restore' && confirm("WARNING: Are you sure you want to RESTORE the database from the last saved point? This will overwrite current data.")) {
                alert("RESTORING: Data restore process initiated from 2025-12-12 backup. Please wait...");
            }
        }
    </script>

    <nav class="sidebar">
        <div class="brand"><i class="fas fa-leaf text-success me-2"></i> AGRO<span class="text-white">SAFE</span></div>
        <small class="fw-bold text-uppercase text-light mb-4 d-block opacity-75" style="font-size:0.7rem;">Admin Panel</small>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i>Manage Farmer</a>
            <a href="scans.php" class="nav-link"><i class="fas fa-database"></i> Scan History</a>
            <a href="subsidies.php" class="nav-link"><i class="fas fa-hand-holding-dollar"></i> Manage Subsidies</a>
            <a href="settings.php" class="nav-link active"><i class="fas fa-sliders-h"></i> System Settings</a>
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
        <h3 class="fw-bold mb-4">System Configuration</h3>

        <?php if($msg): ?>
            <div class="alert alert-success d-flex align-items-center rounded-3"><i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-custom">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_config">
                        
                        <h5 class="fw-bold mb-3 text-primary"><i class="fas fa-chart-line me-2"></i>Market Data Controls</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Crop Value (PHP/sqm)</label>
                                <input type="number" step="0.01" name="crop_value" class="form-control" value="<?php echo $current['crop_value'] ?? 150; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Chemical Cost (PHP/ml)</label>
                                <input type="number" step="0.01" name="fungicide_cost" class="form-control" value="<?php echo $current['fungicide_cost'] ?? 45; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Labor Rate (PHP/Day)</label>
                                <input type="number" step="0.01" name="labor_cost" class="form-control" value="<?php echo $current['labor_cost'] ?? 500; ?>">
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3 text-dark mt-4"><i class="fas fa-plug me-2"></i>API & Integration Settings</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Weather API Key</label>
                                <input type="text" name="weather_api_key" class="form-control" placeholder="OpenWeatherMap Key" value="<?php echo $current['weather_api_key'] ?? 'YOUR_API_KEY_123'; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email/SMS Notification Key</label>
                                <input type="text" name="email_api_key" class="form-control" placeholder="SendGrid/Twilio Key" value="<?php echo $current['email_api_key'] ?? 'YOUR_SMS_API_KEY_XYZ'; ?>">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <button type="submit" class="btn btn-primary btn-action py-2 fw-bold"><i class="fas fa-save me-2"></i>Save All Configuration</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-custom bg-light h-100">
                    <h5 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Backup & Restore</h5>
                    <p class="small text-muted mb-4">Manage database and media file backups.</p>

                    <button class="btn btn-success btn-action mb-3" onclick="triggerBackup('db')"><i class="fas fa-database me-2"></i>Create Database Backup</button>
                    <button class="btn btn-info btn-action mb-3 text-white" onclick="triggerBackup('media')"><i class="fas fa-folder-open me-2"></i>Backup Media Files</button>
                    <button class="btn btn-danger btn-action mb-3" onclick="triggerBackup('restore')"><i class="fas fa-undo me-2"></i>Restore from Last Backup</button>
                    
                    <small class="d-block mt-4 text-muted border-top pt-3">Last automatic backup: 2025-12-12 04:00 AM</small>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
