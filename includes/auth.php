<?php
// includes/auth.php
session_start();
require_once __DIR__ . '/language.php';
app_handle_language_request();

// PHPMailer import
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function mailConfig(): array
{
    $fileConfig = [];
    $configPath = __DIR__ . '/mail_config.php';

    if (is_file($configPath)) {
        $loaded = require $configPath;
        if (is_array($loaded)) {
            $fileConfig = $loaded;
        }
    }

    return [
        'host' => getenv('MAIL_HOST') ?: ($fileConfig['host'] ?? 'smtp.gmail.com'),
        'port' => (int) (getenv('MAIL_PORT') ?: ($fileConfig['port'] ?? 587)),
        'username' => trim((string) (getenv('MAIL_USERNAME') ?: ($fileConfig['username'] ?? ''))),
        'password' => trim((string) (getenv('MAIL_PASSWORD') ?: ($fileConfig['password'] ?? ''))),
        'from_email' => trim((string) (getenv('MAIL_FROM') ?: ($fileConfig['from_email'] ?? ''))),
        'from_name' => getenv('MAIL_FROM_NAME') ?: ($fileConfig['from_name'] ?? 'AgroSafeAI'),
        'encryption' => strtolower((string) (getenv('MAIL_ENCRYPTION') ?: ($fileConfig['encryption'] ?? 'tls'))),
        'app_url' => trim((string) (getenv('APP_URL') ?: ($fileConfig['app_url'] ?? ''))),
        'skip_tls_verify' => filter_var(
            getenv('MAIL_SKIP_TLS_VERIFY') ?: ($fileConfig['skip_tls_verify'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        ),
        'debug' => (int) (getenv('MAIL_DEBUG') ?: ($fileConfig['debug'] ?? 0)),
    ];
}

function appBaseUrl(): string
{
    $configuredUrl = mailConfig()['app_url'] ?: getenv('APP_URL');
    if (!empty($configuredUrl)) {
        return normalizeBaseUrlForMobile((string) $configuredUrl);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $hostOnly = $host;
    $hostPort = '';
    if (strpos($host, ':') !== false) {
        [$hostOnly, $portPart] = explode(':', $host, 2);
        $hostPort = ':' . $portPart;
    }

    if (isLocalHost($hostOnly)) {
        $lanIp = detectLanIp();
        if ($lanIp !== null) {
            $host = $lanIp . $hostPort;
        }
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/');

    return $scheme . '://' . $host . $scriptDir;
}

function isLocalHost(string $host): bool
{
    $normalized = strtolower(trim($host));
    return in_array($normalized, ['localhost', '127.0.0.1', '::1'], true);
}

function detectLanIp(): ?string
{
    $candidates = [];

    if (!empty($_SERVER['SERVER_ADDR'])) {
        $candidates[] = (string) $_SERVER['SERVER_ADDR'];
    }

    $hostName = gethostname();
    if (is_string($hostName) && $hostName !== '') {
        $resolved = gethostbyname($hostName);
        if ($resolved !== $hostName) {
            $candidates[] = $resolved;
        }
    }

    foreach ($candidates as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            continue;
        }

        if (strpos($ip, '127.') === 0) {
            continue;
        }

        return $ip;
    }

    return null;
}

function normalizeBaseUrlForMobile(string $baseUrl): string
{
    $baseUrl = rtrim($baseUrl, '/');
    $parts = parse_url($baseUrl);

    if ($parts === false || empty($parts['host'])) {
        return $baseUrl;
    }

    $host = (string) $parts['host'];
    if (!isLocalHost($host)) {
        return $baseUrl;
    }

    $lanIp = detectLanIp();
    if ($lanIp === null) {
        return $baseUrl;
    }

    $scheme = $parts['scheme'] ?? 'http';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = $parts['path'] ?? '';

    return rtrim($scheme . '://' . $lanIp . $port . $path, '/');
}

function sendVerificationEmail(string $recipientEmail, string $token, string $username): void
{
    $config = mailConfig();
    $smtpHost = $config['host'];
    $smtpPort = $config['port'];
    $smtpUsername = $config['username'];
    $smtpPassword = preg_replace('/\s+/', '', (string) $config['password']);
    $fromAddress = $config['from_email'] !== '' ? $config['from_email'] : $smtpUsername;
    $fromName = $config['from_name'];
    $smtpEncryption = $config['encryption'];
    $skipTlsVerify = (bool) $config['skip_tls_verify'];
    $smtpDebug = $config['debug'];

    if ($smtpUsername === '' || $smtpPassword === '' || $fromAddress === '') {
        throw new RuntimeException('SMTP credentials are not configured.');
    }

    $verifyUrl = appBaseUrl() . '/verify.php?token=' . urlencode($token) . '&lang=' . urlencode(app_get_language());

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug = $smtpDebug;
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = ($smtpEncryption === 'ssl')
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;

    if ($skipTlsVerify) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    $mail->setFrom($fromAddress, $fromName);
    $mail->addAddress($recipientEmail);

    $mail->isHTML(true);
    $safeName = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');

    $mail->Subject = t('mail_subject_verify');
    $mail->Body = "
<div style='font-family: Arial, sans-serif; background-color:#f4f6f9; padding:20px;'>
    <div style='max-width:600px; margin:auto; background:#ffffff; padding:30px; border-radius:8px;'>
        <h2 style='color:#2e7d32; text-align:center; margin-top:0;'>Welcome to AgroSafeAI</h2>

        <p>Dear <strong>{$safeName}</strong>,</p>

        <p>Thank you for registering with <strong>AgroSafeAI</strong> — an AI-powered crop disease detection platform designed to support smart farming.</p>

        <p>To activate your account, please verify your email address by clicking the button below:</p>

        <div style='text-align:center; margin:25px 0;'>
            <a href='{$safeUrl}'
               style='background:#2e7d32; color:#ffffff; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block;'>
                Verify My Account
            </a>
        </div>

        <p style='word-break:break-all; font-size:13px; color:#444;'>If button does not open on mobile, copy this link:<br>{$safeUrl}</p>

        <p>If you did not create this account, please ignore this email.</p>

        <hr style='margin-top:30px;'>

        <p style='font-size:13px; color:#555;'>
            Best Regards,<br>
            <strong>AgroSafeAI Team</strong><br>
            Smart Agriculture • AI Powered • Farmer First
        </p>
    </div>
</div>
";
    $mail->AltBody = "Dear {$username}, verify your account: {$verifyUrl}";

    $mail->send();
}

function sendPasswordResetEmail(string $recipientEmail, string $token): void
{
    $config = mailConfig();
    $smtpHost = $config['host'];
    $smtpPort = $config['port'];
    $smtpUsername = $config['username'];
    $smtpPassword = preg_replace('/\s+/', '', (string) $config['password']);
    $fromAddress = $config['from_email'] !== '' ? $config['from_email'] : $smtpUsername;
    $fromName = $config['from_name'];
    $smtpEncryption = $config['encryption'];
    $skipTlsVerify = (bool) $config['skip_tls_verify'];
    $smtpDebug = $config['debug'];

    if ($smtpUsername === '' || $smtpPassword === '' || $fromAddress === '') {
        throw new RuntimeException('SMTP credentials are not configured.');
    }

    $resetUrl = appBaseUrl() . '/reset_password.php?token=' . urlencode($token) . '&lang=' . urlencode(app_get_language());

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug = $smtpDebug;
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = ($smtpEncryption === 'ssl')
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;

    if ($skipTlsVerify) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    $mail->setFrom($fromAddress, $fromName);
    $mail->addAddress($recipientEmail);

    $mail->isHTML(true);
    $mail->Subject = t('mail_subject_reset');
    $mail->Body = t('mail_body_reset_html', ['url' => $resetUrl]);
    $mail->AltBody = t('mail_body_reset_text', ['url' => $resetUrl]);

    $mail->send();
}

function ensurePasswordResetColumns(PDO $pdo): void
{
    static $ensured = false;

    if ($ensured) {
        return;
    }

    $ensured = true;

    try {
        $pdo->exec("ALTER TABLE users ADD reset_token VARCHAR(255) DEFAULT NULL");
    } catch (\Throwable $e) {
    }

    try {
        $pdo->exec("ALTER TABLE users ADD reset_token_expires_at DATETIME DEFAULT NULL");
    } catch (\Throwable $e) {
    }
}

// MySQL Connection Config
$host = 'localhost';
$dbname = 'agrosafe_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(t('err_connection_failed'));
}

$error = '';
$success = '';
$activeForm = 'login';
ensurePasswordResetColumns($pdo);


// =========================
// 1. HANDLE REGISTER
// =========================
if (isset($_POST['register'])) {
    $activeForm = 'register';

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $passwordInput = (string) ($_POST['password'] ?? '');
    $mobileNumber = trim((string) ($_POST['mobile_number'] ?? ''));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $state = trim((string) ($_POST['state'] ?? ''));
    $country = trim((string) ($_POST['country'] ?? ''));
    $taluku = trim((string) ($_POST['taluku'] ?? ''));
    $district = trim((string) ($_POST['district'] ?? ''));
    $panchayath = trim((string) ($_POST['panchayath'] ?? ''));
    $pincode = trim((string) ($_POST['pincode'] ?? ''));

    $requiredFields = [
        $username,
        $email,
        $passwordInput,
        $mobileNumber,
        $gender,
        $state,
        $country,
        $taluku,
        $district,
        $panchayath,
        $pincode,
    ];

    if (in_array('', $requiredFields, true)) {
        $error = t('err_complete_profile_fields');
    } elseif (!preg_match('/^\+?[0-9]{8,15}$/', $mobileNumber)) {
        $error = t('err_invalid_mobile');
    } elseif (!preg_match('/^[A-Za-z0-9\s-]{4,12}$/', $pincode)) {
        $error = t('err_invalid_pincode');
    } else {

        $password = password_hash($passwordInput, PASSWORD_DEFAULT);
        $role = 'user';
        $allowedGenders = ['Male', 'Female', 'Other'];
        if (!in_array($gender, $allowedGenders, true)) {
            $gender = 'Other';
        }

        // generate verification token
        $token = bin2hex(random_bytes(32));

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $usernameExists = $stmt->rowCount() > 0;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $emailExists = $stmt->rowCount() > 0;

        if ($usernameExists) {

            $error = t('err_username_taken');
        } elseif ($emailExists) {
            $error = t('err_email_taken');

        } else {

            try {
                $pdo->beginTransaction();

                // Insert user first to ensure account is saved even if SMTP fails.
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, email, password, mobile_number, gender, state, country, taluku, district, panchayath, pincode, role, verification_token, is_verified)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"
                );
                $stmt->execute([
                    $username,
                    $email,
                    $password,
                    $mobileNumber,
                    $gender,
                    $state,
                    $country,
                    $taluku,
                    $district,
                    $panchayath,
                    $pincode,
                    $role,
                    $token,
                ]);

                $newUserId = (int) $pdo->lastInsertId();
                $pdo->commit();

            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Registration DB error: ' . $e->getMessage());
                $error = t('err_registration_failed');
                $newUserId = 0;
            }

            if ($error === '') {
                try {
                sendVerificationEmail($email, $token, $username);
                    $success = t('success_account_created_verify');
                    $activeForm = 'login';
                } catch (\Throwable $e) {
                    error_log('Registration mail error (fallback auto-verify): ' . $e->getMessage());

                    // Fallback for local/dev environments with SMTP issues:
                    // account remains created and can login immediately.
                    if ($newUserId > 0) {
                        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
                        $stmt->execute([$newUserId]);
                    }
                    $success = t('success_account_created_mail_skipped');
                    $activeForm = 'login';
                }
            }
        }
    }
}


// =========================
// 2. HANDLE FORGOT PASSWORD
// =========================
if (isset($_POST['forgot_password'])) {
    $activeForm = 'forgot';

    $forgotEmail = trim((string) ($_POST['forgot_email'] ?? ''));

    if ($forgotEmail === '') {
        $error = t('err_email_required');
    } elseif (!filter_var($forgotEmail, FILTER_VALIDATE_EMAIL)) {
        $error = t('err_invalid_email_address');
    } else {
        // If duplicate emails exist from older data, use newest account deterministically.
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$forgotEmail]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($targetUser) {
            try {
                $token = bin2hex(random_bytes(32));

                // Use DB time for expiry to avoid PHP/MySQL timezone mismatch.
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?");
                $stmt->execute([$token, $targetUser['id']]);

                sendPasswordResetEmail($forgotEmail, $token);
            } catch (\Throwable $e) {
                $mailError = $e->getMessage();
                error_log('Forgot password mail error: ' . $mailError);

                if (stripos($mailError, 'SMTP credentials are not configured') !== false) {
                    $error = t('err_smtp_not_configured');
                } elseif (stripos($mailError, 'Could not authenticate') !== false) {
                    $error = t('err_smtp_auth_failed');
                } else {
                    $error = t('err_password_reset_email_send');
                }
            }
        }

        if ($error === '') {
            // Security-safe generic response: same message for existing/non-existing emails.
            $success = t('success_password_reset_email_sent');
        }
    }
}



// =========================
// 3. HANDLE LOGIN
// =========================
if (isset($_POST['login'])) {
    $activeForm = 'login';

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Support login by username or email.
    // For duplicate legacy emails, pick newest user deterministically.
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$username]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($user && password_verify($password, $user['password'])) {

        // CHECK EMAIL VERIFIED
        if ($user['is_verified'] == 0) {

            $error = t('err_verify_email_before_login');
        }
        else {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Remember Me
            if (isset($_POST['remember'])) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    session_id(),
                    time() + (30 * 24 * 60 * 60),
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            if ($user['role'] == 'admin') {

                header("Location: admin/index.php");

            } else {

                header("Location: index.php");

            }

            exit();
        }

    } else {

        $error = t('err_invalid_credentials');

    }
}
?>
