<?php
// START OF INDEX.PHP
session_start();
require_once __DIR__ . '/includes/language.php';
app_handle_language_request();

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Personalize
$user_name = $_SESSION['username'] ?? 'Farmer';

// 3. Routing
$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'market', 'history', 'weather'];

if (!in_array($page, $allowed_pages, true)) {
    $page = 'dashboard';
}

$pageTitles = [
    'dashboard' => t('page_dashboard'),
    'market' => t('page_market'),
    'history' => t('page_history'),
    'weather' => t('page_weather'),
];

$currentLang = app_get_language();
$languages = app_languages();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('app_name') . ' - ' . ($pageTitles[$page] ?? ucfirst($page))); ?></title>

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
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
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
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            color: var(--primary-color);
            letter-spacing: -0.5px;
        }

        .language-select {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 8px 10px;
        }

        .language-select option {
            color: #1f2933;
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

        .nav-link:hover {
            background: var(--glass-white);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(46, 204, 113, 0.2) 0%, rgba(46, 204, 113, 0) 100%);
            color: var(--primary-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2.5rem;
            min-height: 100vh;
        }

        .logout-btn {
            margin-top: auto;
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }

        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.1);
            color: #ff8787;
        }

        .system-status {
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid var(--border-color);
        }

        .pulse-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #2ecc71;
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 0 rgba(46, 204, 113, 0.4);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <i class="fas fa-leaf me-2"></i> AgroSafe<span class="text-white">AI</span>
        </div>

        <form method="GET" class="mb-4">
            <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
            <label for="lang" class="small text-white-50 d-block mb-2 fw-bold"><?php echo htmlspecialchars(t('label_language')); ?></label>
            <select id="lang" name="lang" class="form-select language-select" onchange="this.form.submit()">
                <?php foreach ($languages as $code => $label): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $currentLang === $code ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="nav-links">
            <a href="index.php?page=dashboard" class="nav-link <?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                <i class="fa-solid fa-table-cells-large"></i> <?php echo htmlspecialchars(t('nav_dashboard')); ?>
            </a>

            <a href="index.php?page=market" class="nav-link <?php echo ($page === 'market') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> <?php echo htmlspecialchars(t('nav_market')); ?>
            </a>

            <a href="index.php?page=history" class="nav-link <?php echo ($page === 'history') ? 'active' : ''; ?>">
                <i class="fas fa-clock-rotate-left"></i> <?php echo htmlspecialchars(t('nav_history')); ?>
            </a>

            <a href="index.php?page=weather" class="nav-link <?php echo ($page === 'weather') ? 'active' : ''; ?>">
                <i class="fas fa-cloud-sun"></i> <?php echo htmlspecialchars(t('nav_weather')); ?>
            </a>
        </div>

        <a href="logout.php" class="nav-link logout-btn">
            <i class="fas fa-power-off"></i> <?php echo htmlspecialchars(t('nav_sign_out')); ?>
        </a>

        <div class="system-status">
            <div class="d-flex align-items-center mb-1">
                <span class="pulse-dot"></span>
                <span class="text-white small fw-bold"><?php echo htmlspecialchars(t('status_system_online')); ?></span>
            </div>
            <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars(t('status_enterprise_version')); ?></div>
        </div>
    </nav>

    <main class="main-content">
        <?php
            $file = __DIR__ . "/pages/{$page}.php";
            if (file_exists($file)) {
                include $file;
            } else {
                echo "<div class='alert alert-danger'>" . htmlspecialchars(t('err_page_not_found', ['page' => $page])) . "</div>";
            }
        ?>
    </main>

    <div style="text-align:center;padding:15px;color:#6c757d;font-size:14px;background:#f4f7fe;margin-left:260px;">
        <?php echo htmlspecialchars(t('footer_text', ['year' => date('Y')])); ?> <i class="fas fa-heart text-danger"></i>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        if ($('#symptom-select').length > 0) {
            $('#symptom-select').select2({
                theme: 'bootstrap-5',
                placeholder: <?php echo json_encode(t('dashboard_select_observations')); ?>,
                allowClear: true,
                width: '100%',
                closeOnSelect: false,
                selectionCssClass: 'select2--large'
            });
        }
    });
    </script>
</body>
</html>
