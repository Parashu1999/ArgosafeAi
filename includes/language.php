<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function app_languages(): array
{
    return [
        'en' => 'English',
        'kn' => 'ಕನ್ನಡ',
    ];
}

function app_set_language(string $lang): void
{
    if (array_key_exists($lang, app_languages())) {
        $_SESSION['lang'] = $lang;
    }
}

function app_get_language(): string
{
    $lang = $_SESSION['lang'] ?? 'en';
    return array_key_exists($lang, app_languages()) ? $lang : 'en';
}

function app_handle_language_request(): void
{
    if (isset($_GET['lang'])) {
        app_set_language((string) $_GET['lang']);
    } elseif (isset($_POST['lang'])) {
        app_set_language((string) $_POST['lang']);
    }
}

function app_translations(): array
{
    return [
        'en' => [
            'app_name' => 'AgroSafeAI',
            'label_language' => 'Language',
            'page_dashboard' => 'Dashboard',
            'page_market' => 'Market Data',
            'page_history' => 'History Log',
            'page_weather' => 'Weather',
            'nav_dashboard' => 'Dashboard',
            'nav_market' => 'Market Data',
            'nav_history' => 'History Log',
            'nav_weather' => 'Weather',
            'nav_sign_out' => 'Sign Out',
            'status_system_online' => 'System Online',
            'status_enterprise_version' => 'v2.4.0 Enterprise',
            'footer_text' => '© {year} AgroSafeAI | Developed by PARASHURAMA | All Rights Reserved for Sustainable Agriculture',
            'err_page_not_found' => 'Page not found: {page}',
            'err_access_denied' => 'Access Denied',

            'login_welcome_back' => 'Welcome Back',
            'login_enter_credentials' => 'Enter your credentials to access the AI.',
            'login_username' => 'USERNAME',
            'login_password' => 'PASSWORD',
            'login_remember_me' => 'Remember me',
            'login_forgot_password' => 'Forgot Password?',
            'login_button' => 'Login to Dashboard',
            'login_no_account' => 'No account?',
            'login_register_here' => 'Register here',
            'register_join' => 'Join AgroSafeAI',
            'register_create_profile' => 'Create your secure farm profile.',
            'register_email' => 'EMAIL ADDRESS',
            'register_create_account' => 'Create Account',
            'register_have_account' => 'Already have an account?',
            'register_login_here' => 'Login here',
            'forgot_reset_password' => 'Reset Password',
            'forgot_send_help' => "We'll send a recovery link to your email.",
            'forgot_send_link' => 'Send Link',
            'forgot_back_login' => 'Back to Login',
            'forgot_demo_contact_admin' => 'Demo Mode: Contact admin reset.',
            'placeholder_username' => 'Farmer123',
            'placeholder_email' => 'name@farm.com',

            'err_connection_failed' => 'Connection failed',
            'err_username_taken' => 'Username already taken.',
            'success_account_created_verify' => 'Account created! Please check your email to verify.',
            'err_registration_failed' => 'Registration failed.',
            'err_smtp_not_configured' => 'Registration failed: set SMTP username/password in includes/mail_config.php.',
            'err_smtp_auth_failed' => 'Registration failed: Gmail SMTP authentication failed. Update username/app-password in includes/mail_config.php.',
            'err_registration_email_send' => 'Registration failed because verification email could not be sent. Please check SMTP settings.',
            'err_verify_email_before_login' => 'Please verify your email before login.',
            'err_invalid_credentials' => 'Invalid credentials.',

            'mail_subject_verify' => 'Verify your AgroSafeAI account',
            'mail_body_html' => 'Hi,<br><br>Please verify your AgroSafeAI account by clicking this link:<br><a href="{url}">Verify Email</a><br><br>If you did not register, please ignore this email.',
            'mail_body_text' => 'Please verify your AgroSafeAI account: {url}',

            'verify_success' => 'Email verified successfully.',
            'verify_login_now' => 'Login Now',
            'verify_invalid_or_expired' => 'Invalid or expired verification link.',
            'verify_invalid' => 'Invalid verification link.',
            'verify_db_failed' => 'Database connection failed.',

            'dashboard_hello_farmer' => 'Hello, Farmer {name}.',
            'dashboard_hub_subtitle' => 'Your farm intelligence hub is active. Review your health metrics below or run a new diagnosis.',
            'dashboard_new_diagnosis' => 'New Diagnosis',
            'dashboard_configure_params' => 'Configure your observation parameters below.',
            'dashboard_visual_observations' => 'Visual Observations',
            'dashboard_select_observations' => 'Select observations...',
            'dashboard_select_symptoms' => 'Select symptoms...',
            'dashboard_total_farm_size' => 'Total Farm Size',
            'dashboard_affected_area' => 'Affected Area',
            'dashboard_observed_severity' => 'Observed Severity',
            'dashboard_mild_early' => 'Mild / Early',
            'dashboard_severe_late' => 'Severe / Late',
            'dashboard_run_ai_analysis' => 'Run AI Analysis',
            'dashboard_live_market' => 'Live Market',
            'dashboard_data_synced' => 'Data synced:',
        ],
        'kn' => [
            'app_name' => 'AgroSafeAI',
            'label_language' => 'ಭಾಷೆ',
            'page_dashboard' => 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್',
            'page_market' => 'ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ',
            'page_history' => 'ಇತಿಹಾಸ ದಾಖಲೆ',
            'page_weather' => 'ಹವಾಮಾನ',
            'nav_dashboard' => 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್',
            'nav_market' => 'ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ',
            'nav_history' => 'ಇತಿಹಾಸ ದಾಖಲೆ',
            'nav_weather' => 'ಹವಾಮಾನ',
            'nav_sign_out' => 'ಲಾಗ್ ಔಟ್',
            'status_system_online' => 'ಸಿಸ್ಟಮ್ ಆನ್‌ಲೈನ್',
            'status_enterprise_version' => 'v2.4.0 ಎಂಟರ್‌ಪ್ರೈಸ್',
            'footer_text' => '© {year} AgroSafeAI | ಅಭಿವೃದ್ಧಿಪಡಿಸಿದವರು PARASHURAMA | ಸ್ಥಿರ ಕೃಷಿಗಾಗಿ ಎಲ್ಲಾ ಹಕ್ಕುಗಳು ಕಾಯ್ದಿರಿಸಲಾಗಿದೆ',
            'err_page_not_found' => 'ಪುಟ ಕಂಡುಬಂದಿಲ್ಲ: {page}',
            'err_access_denied' => 'ಪ್ರವೇಶ ನಿರಾಕರಿಸಲಾಗಿದೆ',

            'login_welcome_back' => 'ಮತ್ತೆ ಸ್ವಾಗತ',
            'login_enter_credentials' => 'AI ಬಳಸಲು ನಿಮ್ಮ ವಿವರಗಳನ್ನು ನಮೂದಿಸಿ.',
            'login_username' => 'ಬಳಕೆದಾರ ಹೆಸರು',
            'login_password' => 'ಗುಪ್ತಪದ',
            'login_remember_me' => 'ನನ್ನನ್ನು ನೆನಪಿರಲಿ',
            'login_forgot_password' => 'ಗುಪ್ತಪದ ಮರೆತಿರಾ?',
            'login_button' => 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ಗೆ ಲಾಗಿನ್',
            'login_no_account' => 'ಖಾತೆ ಇಲ್ಲವೇ?',
            'login_register_here' => 'ಇಲ್ಲಿ ನೋಂದಣಿ ಮಾಡಿ',
            'register_join' => 'AgroSafeAI ಸೇರಿ',
            'register_create_profile' => 'ನಿಮ್ಮ ಸುರಕ್ಷಿತ ಕೃಷಿ ಪ್ರೊಫೈಲ್ ರಚಿಸಿ.',
            'register_email' => 'ಇಮೇಲ್ ವಿಳಾಸ',
            'register_create_account' => 'ಖಾತೆ ರಚಿಸಿ',
            'register_have_account' => 'ಈಗಾಗಲೇ ಖಾತೆ ಇದೆಯೇ?',
            'register_login_here' => 'ಇಲ್ಲಿ ಲಾಗಿನ್ ಮಾಡಿ',
            'forgot_reset_password' => 'ಗುಪ್ತಪದ ಮರುಹೊಂದಿಸಿ',
            'forgot_send_help' => 'ನಿಮ್ಮ ಇಮೇಲ್‌ಗೆ ಮರುಪಡೆಯುವ ಲಿಂಕ್ ಕಳುಹಿಸಲಾಗುತ್ತದೆ.',
            'forgot_send_link' => 'ಲಿಂಕ್ ಕಳುಹಿಸಿ',
            'forgot_back_login' => 'ಲಾಗಿನ್‌ಗೆ ಹಿಂತಿರುಗಿ',
            'forgot_demo_contact_admin' => 'ಡೆಮೋ ಮೋಡ್: ಆಡ್ಮಿನ್ ಅನ್ನು ಸಂಪರ್ಕಿಸಿ.',
            'placeholder_username' => 'Farmer123',
            'placeholder_email' => 'name@farm.com',

            'err_connection_failed' => 'ಡೇಟಾಬೇಸ್ ಸಂಪರ್ಕ ವಿಫಲವಾಗಿದೆ',
            'err_username_taken' => 'ಈ ಬಳಕೆದಾರ ಹೆಸರು ಈಗಾಗಲೇ ಬಳಸಲಾಗಿದೆ.',
            'success_account_created_verify' => 'ಖಾತೆ ರಚಿಸಲಾಗಿದೆ! ಇಮೇಲ್ ಪರಿಶೀಲಿಸಿ ದೃಢೀಕರಿಸಿ.',
            'err_registration_failed' => 'ನೋಂದಣಿ ವಿಫಲವಾಗಿದೆ.',
            'err_smtp_not_configured' => 'ನೋಂದಣಿ ವಿಫಲ: includes/mail_config.php ನಲ್ಲಿ SMTP ಬಳಕೆದಾರ ಹೆಸರು/ಗುಪ್ತಪದವನ್ನು ಹೊಂದಿಸಿ.',
            'err_smtp_auth_failed' => 'ನೋಂದಣಿ ವಿಫಲ: Gmail SMTP ದೃಢೀಕರಣ ವಿಫಲವಾಗಿದೆ. includes/mail_config.php ನಲ್ಲಿ username/app-password ನವೀಕರಿಸಿ.',
            'err_registration_email_send' => 'ನೋಂದಣಿ ವಿಫಲ: ದೃಢೀಕರಣ ಇಮೇಲ್ ಕಳುಹಿಸಲು ಆಗಲಿಲ್ಲ. SMTP ಸೆಟ್ಟಿಂಗ್‌ಗಳನ್ನು ಪರಿಶೀಲಿಸಿ.',
            'err_verify_email_before_login' => 'ಲಾಗಿನ್ ಮಾಡುವ ಮೊದಲು ನಿಮ್ಮ ಇಮೇಲ್ ದೃಢೀಕರಿಸಿ.',
            'err_invalid_credentials' => 'ತಪ್ಪಾದ ವಿವರಗಳು.',

            'mail_subject_verify' => 'ನಿಮ್ಮ AgroSafeAI ಖಾತೆಯನ್ನು ದೃಢೀಕರಿಸಿ',
            'mail_body_html' => 'ನಮಸ್ಕಾರ,<br><br>ದಯವಿಟ್ಟು ಈ ಲಿಂಕ್ ಕ್ಲಿಕ್ ಮಾಡಿ ನಿಮ್ಮ AgroSafeAI ಖಾತೆಯನ್ನು ದೃಢೀಕರಿಸಿ:<br><a href="{url}">ಇಮೇಲ್ ದೃಢೀಕರಿಸಿ</a><br><br>ನೀವು ನೋಂದಣಿ ಮಾಡದಿದ್ದರೆ ಈ ಮೇಲ್ ಅನ್ನು ನಿರ್ಲಕ್ಷಿಸಿ.',
            'mail_body_text' => 'ನಿಮ್ಮ AgroSafeAI ಖಾತೆ ದೃಢೀಕರಣಕ್ಕಾಗಿ: {url}',

            'verify_success' => 'ಇಮೇಲ್ ಯಶಸ್ವಿಯಾಗಿ ದೃಢೀಕರಿಸಲಾಗಿದೆ.',
            'verify_login_now' => 'ಈಗ ಲಾಗಿನ್ ಮಾಡಿ',
            'verify_invalid_or_expired' => 'ತಪ್ಪಾದ ಅಥವಾ ಅವಧಿ ಮೀರಿದ ದೃಢೀಕರಣ ಲಿಂಕ್.',
            'verify_invalid' => 'ತಪ್ಪಾದ ದೃಢೀಕರಣ ಲಿಂಕ್.',
            'verify_db_failed' => 'ಡೇಟಾಬೇಸ್ ಸಂಪರ್ಕ ವಿಫಲವಾಗಿದೆ.',

            'dashboard_hello_farmer' => 'ನಮಸ್ಕಾರ ರೈತ {name}.',
            'dashboard_hub_subtitle' => 'ನಿಮ್ಮ ಕೃಷಿ ಬುದ್ಧಿವಂತಿಕೆ ವ್ಯವಸ್ಥೆ ಸಕ್ರಿಯವಾಗಿದೆ. ಕೆಳಗಿನ ಸೂಚಕಗಳನ್ನು ನೋಡಿ ಅಥವಾ ಹೊಸ ವಿಶ್ಲೇಷಣೆ ನಡೆಸಿ.',
            'dashboard_new_diagnosis' => 'ಹೊಸ ವಿಶ್ಲೇಷಣೆ',
            'dashboard_configure_params' => 'ಕೆಳಗಿನ ವೀಕ್ಷಣೆ ಮಾಹಿತಿಯನ್ನು ನಮೂದಿಸಿ.',
            'dashboard_visual_observations' => 'ದೃಶ್ಯ ವೀಕ್ಷಣೆಗಳು',
            'dashboard_select_observations' => 'ವೀಕ್ಷಣೆಗಳನ್ನು ಆಯ್ಕೆಮಾಡಿ...',
            'dashboard_select_symptoms' => 'ಲಕ್ಷಣಗಳನ್ನು ಆಯ್ಕೆಮಾಡಿ...',
            'dashboard_total_farm_size' => 'ಒಟ್ಟು ಕೃಷಿ ಪ್ರದೇಶ',
            'dashboard_affected_area' => 'ಬಾಧಿತ ಪ್ರದೇಶ',
            'dashboard_observed_severity' => 'ತೀವ್ರತೆ',
            'dashboard_mild_early' => 'ಸಾಧಾರಣ / ಆರಂಭಿಕ',
            'dashboard_severe_late' => 'ತೀವ್ರ / ಕೊನೆಯ ಹಂತ',
            'dashboard_run_ai_analysis' => 'AI ವಿಶ್ಲೇಷಣೆ ಚಾಲನೆ ಮಾಡಿ',
            'dashboard_live_market' => 'ಲೈವ್ ಮಾರುಕಟ್ಟೆ',
            'dashboard_data_synced' => 'ಮಾಹಿತಿ ಸಮನ್ವಯ:',
        ],
    ];
}

function t(string $key, array $replace = []): string
{
    $lang = app_get_language();
    $translations = app_translations();
    $text = $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }

    return $text;
}
