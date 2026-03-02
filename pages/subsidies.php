<?php
// pages/subsidies.php
if (!function_exists('t')) {
    require_once __DIR__ . '/../includes/language.php';
    app_handle_language_request();
}

if (!isset($_SESSION['user_id'])) {
    die(t('err_access_denied'));
}

$host = 'localhost';
$db = 'agrosafe_db';
$u = 'root';
$p = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB Connection Error.');
}

// Keep page compatible even if migration was not run yet.
$pdo->exec("
    CREATE TABLE IF NOT EXISTS subsidies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scheme_name VARCHAR(150) NOT NULL,
        department VARCHAR(150) DEFAULT NULL,
        category VARCHAR(100) DEFAULT NULL,
        subsidy_amount DECIMAL(12,2) DEFAULT NULL,
        eligibility TEXT NOT NULL,
        application_url VARCHAR(255) DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$stmt = $pdo->query("
    SELECT *
    FROM subsidies
    WHERE status = 'active'
      AND (start_date IS NULL OR start_date <= CURDATE())
      AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY created_at DESC
");
$subsidies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .subsidy-hero {
        background: linear-gradient(135deg, #0f9b0f 0%, #3cb371 100%);
        color: #fff;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 12px 30px rgba(15, 155, 15, 0.25);
    }
    .subsidy-card {
        background: #fff;
        border: 1px solid #edf0f5;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 1.2rem;
        height: 100%;
    }
    .subsidy-label {
        color: #6c757d;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .subsidy-value {
        font-size: 0.92rem;
        color: #1f2937;
        margin-bottom: 0.7rem;
    }
</style>

<div class="container-fluid px-0">
    <div class="subsidy-hero">
        <h2 class="fw-bold mb-1"><i class="fas fa-hand-holding-dollar me-2"></i><?php echo htmlspecialchars(t('subsidies_title')); ?></h2>
        <p class="mb-0 opacity-90"><?php echo htmlspecialchars(t('subsidies_subtitle')); ?></p>
    </div>

    <?php if (empty($subsidies)): ?>
        <div class="alert alert-info border-0 shadow-sm">
            <i class="fas fa-circle-info me-2"></i><?php echo htmlspecialchars(t('subsidies_none')); ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($subsidies as $subsidy): ?>
                <div class="col-lg-6">
                    <div class="subsidy-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($subsidy['scheme_name']); ?></h5>
                            <?php if (!empty($subsidy['category'])): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <?php echo htmlspecialchars($subsidy['category']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="subsidy-label"><?php echo htmlspecialchars(t('subsidies_department')); ?></div>
                        <div class="subsidy-value"><?php echo htmlspecialchars($subsidy['department'] ?: '-'); ?></div>

                        <div class="subsidy-label"><?php echo htmlspecialchars(t('subsidies_amount')); ?></div>
                        <div class="subsidy-value">
                            <?php
                                if ($subsidy['subsidy_amount'] !== null) {
                                    echo '₹' . number_format((float) $subsidy['subsidy_amount'], 2);
                                } else {
                                    echo '-';
                                }
                            ?>
                        </div>

                        <div class="subsidy-label"><?php echo htmlspecialchars(t('subsidies_eligibility')); ?></div>
                        <div class="subsidy-value"><?php echo nl2br(htmlspecialchars($subsidy['eligibility'])); ?></div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo htmlspecialchars(t('subsidies_last_date')); ?>:
                                <?php echo !empty($subsidy['end_date']) ? htmlspecialchars(date('d M Y', strtotime($subsidy['end_date']))) : '-'; ?>
                            </small>

                            <?php if (!empty($subsidy['application_url'])): ?>
                                <a href="<?php echo htmlspecialchars($subsidy['application_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm">
                                    <i class="fas fa-up-right-from-square me-1"></i><?php echo htmlspecialchars(t('subsidies_apply_now')); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
