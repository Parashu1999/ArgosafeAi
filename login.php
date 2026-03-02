<?php
require_once 'includes/auth.php';
$currentLang = app_get_language();
$languages = app_languages();
$activeForm = $activeForm ?? 'login';
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
            min-height: 100vh;
            padding: 24px 0;
        }
        .auth-container { width: 100%; max-width: 640px; padding: 20px; margin: 0 auto; }
        .auth-card { animation: fadeInUp 0.5s ease-out; }
        .hidden { display: none; }
        .brand-login { font-size: 2rem; color: var(--primary-color); font-weight: 800; text-align: center; margin-bottom: 1.5rem; }
        .form-section-title { font-size: 0.72rem; font-weight: 700; color: #6c757d; letter-spacing: 0.08em; text-transform: uppercase; }
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

    <div id="auth-feedback">
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
    </div>

    <div id="login-form" class="custom-card auth-card <?php echo $activeForm === 'login' ? '' : 'hidden'; ?>">
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
                <a href="#" onclick="toggleForm('forgot'); return false;" class="small text-primary text-decoration-none"><?php echo htmlspecialchars(t('login_forgot_password')); ?></a>
            </div>
            <button type="submit" name="login" class="btn-primary-custom shadow w-100 py-3 mb-3"><?php echo htmlspecialchars(t('login_button')); ?></button>
            <div class="text-center">
                <small class="text-muted"><?php echo htmlspecialchars(t('login_no_account')); ?> <a href="#" onclick="toggleForm('register'); return false;" class="fw-bold text-primary text-decoration-none"><?php echo htmlspecialchars(t('login_register_here')); ?></a></small>
            </div>
        </form>
    </div>

    <div id="register-form" class="custom-card auth-card <?php echo $activeForm === 'register' ? '' : 'hidden'; ?>">
        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars(t('register_join')); ?></h4>
        <p class="text-muted small mb-4"><?php echo htmlspecialchars(t('register_create_profile')); ?></p>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('login_username')); ?></label>
                    <input type="text" name="username" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_email')); ?></label>
                    <input type="email" name="email" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_mobile')); ?></label>
                    <input type="text" name="mobile_number" class="form-control bg-light border-0 p-3" maxlength="15" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_gender')); ?></label>
                    <select name="gender" class="form-select bg-light border-0 p-3" required>
                        <option value=""><?php echo htmlspecialchars(t('register_gender_select')); ?></option>
                        <option value="Male"><?php echo htmlspecialchars(t('register_gender_male')); ?></option>
                        <option value="Female"><?php echo htmlspecialchars(t('register_gender_female')); ?></option>
                        <option value="Other"><?php echo htmlspecialchars(t('register_gender_other')); ?></option>
                    </select>
                </div>
            </div>

            <div class="mt-4 mb-2 form-section-title"><?php echo htmlspecialchars(t('register_address_heading')); ?></div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_state')); ?></label>
                    <input type="text" name="state" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_country')); ?></label>
                    <input type="text" name="country" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_taluku')); ?></label>
                    <input type="text" name="taluku" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_district')); ?></label>
                    <input type="text" name="district" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_panchayath')); ?></label>
                    <input type="text" name="panchayath" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_pincode')); ?></label>
                    <input type="text" name="pincode" class="form-control bg-light border-0 p-3" maxlength="12" required>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('login_password')); ?></label>
                <input type="password" name="password" class="form-control bg-light border-0 p-3" required>
            </div>
            <button type="submit" name="register" class="btn-primary-custom shadow w-100 py-3 mt-4 mb-3"><?php echo htmlspecialchars(t('register_create_account')); ?></button>
            <div class="text-center">
                <small class="text-muted"><?php echo htmlspecialchars(t('register_have_account')); ?> <a href="#" onclick="toggleForm('login'); return false;" class="fw-bold text-primary text-decoration-none"><?php echo htmlspecialchars(t('register_login_here')); ?></a></small>
            </div>
        </form>
    </div>

    <div id="forgot-form" class="custom-card auth-card <?php echo $activeForm === 'forgot' ? '' : 'hidden'; ?>">
        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars(t('forgot_reset_password')); ?></h4>
        <p class="text-muted small mb-4"><?php echo htmlspecialchars(t('forgot_send_help')); ?></p>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted"><?php echo htmlspecialchars(t('register_email')); ?></label>
                <input type="email" name="forgot_email" class="form-control bg-light border-0 p-3" placeholder="<?php echo htmlspecialchars(t('placeholder_email')); ?>" value="<?php echo htmlspecialchars((string) ($_POST['forgot_email'] ?? '')); ?>" required>
            </div>
            <button type="submit" name="forgot_password" class="btn-primary-custom shadow w-100 py-3 mb-3"><?php echo htmlspecialchars(t('forgot_send_link')); ?></button>
            <div class="text-center">
                <a href="#" onclick="toggleForm('login'); return false;" class="small text-muted text-decoration-none"><i class="fas fa-arrow-left me-1"></i> <?php echo htmlspecialchars(t('forgot_back_login')); ?></a>
            </div>
        </form>
    </div>

</div>

<script>
    function toggleForm(formType) {
        const feedback = document.getElementById('auth-feedback');
        if (feedback) {
            feedback.classList.add('d-none');
        }

        document.getElementById('login-form').classList.add('hidden');
        document.getElementById('register-form').classList.add('hidden');
        document.getElementById('forgot-form').classList.add('hidden');
        document.getElementById(formType + '-form').classList.remove('hidden');
    }
</script>
</body>
</html>
