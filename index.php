<?php
// START OF INDEX.PHP
session_start();

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Personalize
$user_name = $_SESSION['username'] ?? 'Farmer';

// 3. Routing
$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'market', 'history', 'weather']; // Added 'weather'

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroSafeAI - <?php echo ucfirst($page); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #2ecc71;
            --dark-bg: #1e272e;
            --sidebar-gradient: linear-gradient(180deg, #0f2027 0%, #203a43 100%);
            --glass-white: rgba(255, 255, 255, 0.05);
            --border-color: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8f9fa; /* Light gray background for content */
            overflow-x: hidden;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--sidebar-gradient);
            color: white;
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 10px 0 30px rgba(0,0,0,0.1);
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            color: var(--primary-color);
            letter-spacing: -0.5px;
        }
        
        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 1.1rem;
            text-align: center;
        }

        /* Hover State */
        .nav-link:hover {
            background: var(--glass-white);
            color: white;
            transform: translateX(5px);
        }

        /* Active State */
        .nav-link.active {
            background: linear-gradient(90deg, rgba(46, 204, 113, 0.2) 0%, rgba(46, 204, 113, 0) 100%);
            color: var(--primary-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        /* MAIN CONTENT AREA */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2.5rem;
            min-height: 100vh;
        }

        /* UTILS */
        .logout-btn {
            margin-top: auto;
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }
        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.1);
            color: #ff8787;
        }

        /* SYSTEM STATUS WIDGET */
        .system-status {
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid var(--border-color);
        }
        
        .pulse-dot {
            display: inline-block; width: 8px; height: 8px;
            background-color: #2ecc71; border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 0 rgba(46, 204, 113, 0.4);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }

        .developer-footer {
            text-align: center;
            padding: 15px;
            color: #6c757d;
            font-size: 14px;
            margin-left: var(--sidebar-width);
            background: #f8f9fa;
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <i class="fas fa-leaf me-2"></i> AgroSafe<span class="text-white">AI</span>
        </div>
        
        <div class="nav-links">
            <a href="index.php?page=dashboard" class="nav-link <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fa-solid fa-table-cells-large"></i> Dashboard
            </a>
            
            <a href="index.php?page=market" class="nav-link <?php echo ($page == 'market') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Market Data
            </a>
            
            <a href="index.php?page=history" class="nav-link <?php echo ($page == 'history') ? 'active' : ''; ?>">
                <i class="fas fa-clock-rotate-left"></i> History Log
            </a>

            <a href="index.php?page=weather" class="nav-link <?php echo ($page == 'weather') ? 'active' : ''; ?>">
                <i class="fas fa-cloud-sun"></i> Weather
            </a>
        </div>

        <a href="logout.php" class="nav-link logout-btn">
            <i class="fas fa-power-off"></i> Sign Out
        </a>

        <div class="system-status">
            <div class="d-flex align-items-center mb-1">
                <span class="pulse-dot"></span>
                <span class="text-white small fw-bold">System Online</span>
            </div>
            <div class="text-muted" style="font-size: 0.75rem;">v2.4.0 Enterprise</div>
        </div>
    </nav>

    <main class="main-content">
        <?php 
            // File existence check for safety
            $file = __DIR__ . "/pages/{$page}.php";
            if (file_exists($file)) {
                include $file;
            } else {
                echo "<div class='alert alert-danger'>Page not found: {$page}</div>";
            }
        ?>
    </main>

    <!-- Developer Footer -->

    <!-- Developer Footer Added -->
<div style="
    text-align:center;
    padding:15px;
    color:#6c757d;
    font-size:14px;
    background:#f4f7fe;
    margin-left:260px;
">
    © 2026 AgroSafeAI | Developed by PARASHURAMA | All Rights Reserved <i class="fas fa-heart text-danger"></i> for Sustainable Agriculture
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize Select2 Globally if element exists
        if($('#symptom-select').length > 0) {
            $('#symptom-select').select2({
                theme: "bootstrap-5",
                placeholder: "Select observations...",
                allowClear: true,
                width: '100%',
                closeOnSelect: false,
                selectionCssClass: 'select2--large', // Custom class if needed
            });
        }
    });
    </script>
</body>
</html>