<?php
// admin/scans.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// DB Connection
$host = 'localhost'; $db = 'agrosafe_db'; $u = 'root'; $p = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
} catch (PDOException $e) { die("DB Error"); }

// --- SET PAGE NAME FOR ACTIVE SIDEBAR LINK ---
$page_name = 'scans';

// FETCH GLOBAL HISTORY (JOIN with Users table to see WHO scanned it)
$sql = "SELECT h.*, u.username FROM history h JOIN users u ON h.user_id = u.id ORDER BY h.date DESC";
$stmt = $pdo->query($sql);
$scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Scan Database</title>
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
        .main-content { margin-left: var(--sidebar-width); padding: 30px; }
        .card-custom { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 20px; }
        .table-custom th { color: #7f8c8d; font-size: 0.85rem; text-transform: uppercase; }
        .badge-role { background: #e8f5e9; color: #2ecc71; padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><i class="fas fa-leaf text-success me-2"></i> AGRO<span class="text-white">SAFE</span></div>
        <small class="fw-bold text-uppercase text-light mb-4 d-block opacity-75" style="font-size:0.7rem;">Admin Panel</small>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Manage Farmer</a>
            <a href="scans.php" class="nav-link active"><i class="fas fa-database"></i> Scan History</a>
            <a href="subsidies.php" class="nav-link"><i class="fas fa-hand-holding-dollar"></i> Manage Subsidies</a>
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
        <h3 class="fw-bold mb-4">Global Disease Intelligence</h3>

        <div class="card-custom">
            <table class="table table-custom table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Diagnosis</th>
                        <th>Severity</th>
                        <th>Affected Area</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($scans as $scan): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo date('M d, Y', strtotime($scan['date'])); ?></div>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($scan['date'])); ?></small>
                        </td>
                        <td><i class="fas fa-user-circle text-muted me-1"></i> <?php echo htmlspecialchars($scan['username']); ?></td>
                        <td class="text-danger fw-bold"><?php echo htmlspecialchars($scan['diagnosis']); ?></td>
                        <td>
                            <?php if(($scan['severity'] ?? 1) == 2): ?>
                                <span class="badge bg-danger">Severe</span>
                            <?php else: ?>
                                <span class="badge bg-success">Mild</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $scan['affected_sqm'] ?? 0; ?> sqm</td>
                        <td>
                            <?php if($scan['status'] == 'Resolved'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success">Resolved</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning">Active</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
