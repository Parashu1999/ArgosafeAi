<?php
// Load shared configuration
require_once __DIR__ . '/includes/config.php';

// Determine which page to load (Default to dashboard)
$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'market', 'history'];

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <i class="fas fa-leaf"></i> AgroSafeAI
        </div>
        
        <div class="nav-links">
            <a href="index.php?page=dashboard" class="nav-link <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="index.php?page=market" class="nav-link <?php echo ($page == 'market') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Market Data
            </a>
            <a href="index.php?page=history" class="nav-link <?php echo ($page == 'history') ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> History Log
            </a>
        </div>

        <div class="mt-auto pt-5">
            <div class="alert alert-success border-0" style="background: #e8f5e9; font-size: 0.85rem;">
                <i class="fas fa-check-circle me-1"></i> System Online
            </div>
        </div>
    </nav>

    <main class="main-content">
        <?php 
            // Dynamically include the requested page
            include __DIR__ . "/pages/{$page}.php"; 
        ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>