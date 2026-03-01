<?php
require_once 'includes/auth.php';
$currentLang = app_get_language();
$languages = app_languages();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('app_name')); ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f4f7f6 0%, #e8f5e9 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container { width: 100%; max-width: 420px; padding: 20px; }
        .auth-card { animation: fadeInUp 0.5s ease-out; }
        .hidden { display: none; }
        .brand-login { font-size: 2rem; color: var(--primary-color); font-weight: 800; text-align: center; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<div class="auth-container">
    <form method="GET" class="text-end mb-3">
        <label for="lang" class="small text-muted me-2"><?php echo htmlspecialchars(t('label_language')); ?></label>
        <select id="lang" name="lang" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
            <?php foreach ($languages as $code => $label): ?>
                <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $currentLang === $code ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="brand-login"><i class="fas fa-leaf"></i> AgroSafeAI</div>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div id="login-form" class="custom-card auth-card">
        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars(t('login_welcome_back')); ?></h4>
        <p class="text-muted small mb-4"><?php echo htmlspecialchars(t('login_enter_credentials')); ?></p>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('login_username')); ?></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control bg-light border-0 p-3" placeholder="<?php echo htmlspecialchars(t('placeholder_username')); ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('login_password')); ?></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-0 p-3" placeholder="********" required>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted" for="remember"><?php echo htmlspecialchars(t('login_remember_me')); ?></label>
                </div>
                <a href="#" onclick="toggleForm('forgot')" class="small text-primary text-decoration-none"><?php echo htmlspecialchars(t('login_forgot_password')); ?></a>
            </div>
            <button type="submit" name="login" class="btn-primary-custom shadow w-100 py-3 mb-3"><?php echo htmlspecialchars(t('login_button')); ?></button>
            <div class="text-center">
                <small class="text-muted"><?php echo htmlspecialchars(t('login_no_account')); ?> <a href="#" onclick="toggleForm('register')" class="fw-bold text-primary text-decoration-none"><?php echo htmlspecialchars(t('login_register_here')); ?></a></small>
            </div>
        </form>
    </div>

    <div id="register-form" class="custom-card auth-card hidden">
        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars(t('register_join')); ?></h4>
        <p class="text-muted small mb-4"><?php echo htmlspecialchars(t('register_create_profile')); ?></p>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('login_username')); ?></label>
                <input type="text" name="username" class="form-control bg-light border-0 p-3" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_email')); ?></label>
                <input type="email" name="email" class="form-control bg-light border-0 p-3" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('login_password')); ?></label>
                <input type="password" name="password" class="form-control bg-light border-0 p-3" required>
            </div>
            <button type="submit" name="register" class="btn-primary-custom shadow w-100 py-3 mb-3"><?php echo htmlspecialchars(t('register_create_account')); ?></button>
            <div class="text-center">
                <small class="text-muted"><?php echo htmlspecialchars(t('register_have_account')); ?> <a href="#" onclick="toggleForm('login')" class="fw-bold text-primary text-decoration-none"><?php echo htmlspecialchars(t('register_login_here')); ?></a></small>
            </div>
        </form>
    </div>

    <div id="forgot-form" class="custom-card auth-card hidden">
        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars(t('forgot_reset_password')); ?></h4>
        <p class="text-muted small mb-4"><?php echo htmlspecialchars(t('forgot_send_help')); ?></p>

        <form>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_email')); ?></label>
                <input type="email" class="form-control bg-light border-0 p-3" placeholder="<?php echo htmlspecialchars(t('placeholder_email')); ?>">
            </div>
            <button type="button" class="btn-primary-custom shadow w-100 py-3 mb-3" onclick="alert(<?php echo json_encode(t('forgot_demo_contact_admin')); ?>)"><?php echo htmlspecialchars(t('forgot_send_link')); ?></button>
            <div class="text-center">
                <a href="#" onclick="toggleForm('login')" class="small text-muted text-decoration-none"><i class="fas fa-arrow-left me-1"></i> <?php echo htmlspecialchars(t('forgot_back_login')); ?></a>
            </div>
        </form>
    </div>

</div>

<script>
    function toggleForm(formType) {
        document.getElementById('login-form').classList.add('hidden');
        document.getElementById('register-form').classList.add('hidden');
        document.getElementById('forgot-form').classList.add('hidden');
        document.getElementById(formType + '-form').classList.remove('hidden');
    }
</script>
</body>
</html>
