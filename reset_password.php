<?php
require_once __DIR__ . '/includes/language.php';
app_handle_language_request();

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$success = '';
$tokenValid = false;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=agrosafe_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(t('err_connection_failed'));
}

// Ensure compatibility for older schemas.
try {
    $pdo->exec("ALTER TABLE users ADD reset_token VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE users ADD reset_token_expires_at DATETIME DEFAULT NULL");
} catch (Exception $e) {
}

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at >= NOW() LIMIT 1");
    $stmt->execute([$token]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $tokenValid = (bool) $targetUser;
} else {
    $targetUser = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!$tokenValid) {
        $error = t('reset_invalid_or_expired');
    } elseif (strlen($newPassword) < 6) {
        $error = t('err_password_too_short');
    } elseif ($newPassword !== $confirmPassword) {
        $error = t('err_password_mismatch');
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt->execute([$hashedPassword, $targetUser['id']]);
        $success = t('reset_success');
        $tokenValid = false;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(app_get_language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('reset_title')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f4f7f6 0%, #e8f5e9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .reset-card {
            width: 100%;
            max-width: 520px;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="brand-login text-center mb-4"><i class="fas fa-leaf"></i> AgroSafeAI</div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="custom-card">
            <?php if ($tokenValid): ?>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars(t('reset_title')); ?></h4>
                <p class="text-muted small mb-4"><?php echo htmlspecialchars(t('reset_subtitle')); ?></p>

                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('reset_new_password')); ?></label>
                        <input type="password" name="new_password" class="form-control bg-light border-0 p-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('reset_confirm_password')); ?></label>
                        <input type="password" name="confirm_password" class="form-control bg-light border-0 p-3" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn-primary-custom shadow w-100 py-3 mb-3"><?php echo htmlspecialchars(t('reset_button')); ?></button>
                </form>
            <?php elseif ($success === ''): ?>
                <div class="alert alert-warning mb-3"><?php echo htmlspecialchars(t('reset_invalid_or_expired')); ?></div>
            <?php endif; ?>

            <div class="text-center">
                <a href="login.php" class="small text-muted text-decoration-none"><i class="fas fa-arrow-left me-1"></i> <?php echo htmlspecialchars(t('reset_back_login')); ?></a>
            </div>
        </div>
    </div>
</body>
</html>
