<?php
require_once __DIR__ . '/includes/language.php';
app_handle_language_request();

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function verifyMailConfig(): array
{
    $fileConfig = [];
    $configPath = __DIR__ . '/includes/mail_config.php';
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

function verifyBaseUrl(): string
{
    $config = verifyMailConfig();
    if (!empty($config['app_url'])) {
        return normalizeVerifyBaseUrl((string) $config['app_url']);
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
    if (isVerifyLocalHost($hostOnly)) {
        $lanIp = detectVerifyLanIp();
        if ($lanIp !== null) {
            $host = $lanIp . $hostPort;
        }
    }
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/');

    return $scheme . '://' . $host . $scriptDir;
}

function isVerifyLocalHost(string $host): bool
{
    $normalized = strtolower(trim($host));
    return in_array($normalized, ['localhost', '127.0.0.1', '::1'], true);
}

function detectVerifyLanIp(): ?string
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

function normalizeVerifyBaseUrl(string $baseUrl): string
{
    $baseUrl = rtrim($baseUrl, '/');
    $parts = parse_url($baseUrl);

    if ($parts === false || empty($parts['host'])) {
        return $baseUrl;
    }

    $host = (string) $parts['host'];
    if (!isVerifyLocalHost($host)) {
        return $baseUrl;
    }

    $lanIp = detectVerifyLanIp();
    if ($lanIp === null) {
        return $baseUrl;
    }

    $scheme = $parts['scheme'] ?? 'http';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = $parts['path'] ?? '';

    return rtrim($scheme . '://' . $lanIp . $port . $path, '/');
}

function sendPostVerificationEmail(string $recipientEmail, string $username): void
{
    $config = verifyMailConfig();
    $smtpUsername = $config['username'];
    $smtpPassword = preg_replace('/\s+/', '', (string) $config['password']);
    $fromAddress = $config['from_email'] !== '' ? $config['from_email'] : $smtpUsername;

    if ($smtpUsername === '' || $smtpPassword === '' || $fromAddress === '') {
        throw new RuntimeException('SMTP credentials are not configured.');
    }

    $loginUrl = verifyBaseUrl() . '/login.php?lang=' . urlencode(app_get_language());
    $safeName = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug = $config['debug'];
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = ($config['encryption'] === 'ssl')
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['port'];

    if ((bool) $config['skip_tls_verify']) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    $mail->setFrom($fromAddress, $config['from_name']);
    $mail->addAddress($recipientEmail);

    $mail->isHTML(true);
    $mail->Subject = 'Account Successfully Verified - AgroSafeAI';
    $mail->Body = "
<div style='font-family: Arial, sans-serif; background-color:#f4f6f9; padding:20px;'>
    <div style='max-width:600px; margin:auto; background:#ffffff; padding:30px; border-radius:8px;'>
        <h2 style='color:#2e7d32; text-align:center; margin-top:0;'>Account Successfully Verified</h2>

        <p>Dear <strong>{$safeName}</strong>,</p>

        <p>Your AgroSafeAI account has been successfully verified.</p>
        <p>You can now log in and start using our AI-powered crop disease detection system.</p>

        <div style='text-align:center; margin:25px 0;'>
            <a href='{$safeUrl}'
               style='background:#2e7d32; color:#ffffff; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block;'>
                Login to AgroSafeAI
            </a>
        </div>

        <p style='word-break:break-all; font-size:13px; color:#444;'>If button does not open on mobile, copy this link:<br>{$safeUrl}</p>

        <hr style='margin-top:30px;'>

        <p style='font-size:13px; color:#555;'>
            Thank you for choosing AgroSafeAI.<br>
            We are committed to empowering farmers through smart technology.<br><br>
            <strong>AgroSafeAI Team</strong><br>
            Smart Agriculture • AI Powered • Farmer First
        </p>
    </div>
</div>
";
    $mail->AltBody = "Dear {$username}, your AgroSafeAI account is verified. Login here: {$loginUrl}";
    $mail->send();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=agrosafe_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(t('verify_db_failed'));
}

$status = 'error';
$title = t('verify_invalid_or_expired');
$message = t('verify_invalid_or_expired');
$loginUrl = 'login.php?lang=' . urlencode(app_get_language());
$username = '';

if (isset($_GET['token'])) {
    $token = trim((string) $_GET['token']);
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE verification_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);

        try {
            sendPostVerificationEmail((string) $user['email'], (string) $user['username']);
        } catch (\Throwable $e) {
            error_log('Post-verification mail error: ' . $e->getMessage());
        }

        $status = 'success';
        $title = t('verify_success');
        $message = 'Your account is active now. Login and start disease analysis, weather insights, and subsidy tracking.';
        $username = (string) $user['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(app_get_language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('app_name') . ' - ' . t('verify_login_now')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f4f7f6 0%, #e8f5e9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-card {
            width: 100%;
            max-width: 620px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 16px 36px rgba(0,0,0,0.08);
            padding: 28px;
        }
        .verify-title {
            font-size: 1.9rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .verify-msg {
            color: #4b5563;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <?php if ($status === 'success'): ?>
            <div class="alert alert-success border-0">
                <strong><?php echo htmlspecialchars($title); ?></strong>
            </div>
            <h1 class="verify-title">Welcome <?php echo htmlspecialchars($username); ?>!</h1>
            <p class="verify-msg"><?php echo htmlspecialchars($message); ?></p>
            <div class="mb-3">
                <h6 class="fw-bold mb-2">What next?</h6>
                <ul class="mb-0">
                    <li>Login with your username or email and password.</li>
                    <li>Run AI crop disease diagnosis from dashboard.</li>
                    <li>Track weather and government subsidy updates.</li>
                </ul>
            </div>
            <a class="btn btn-success px-4 py-2 fw-bold" href="<?php echo htmlspecialchars($loginUrl); ?>">
                <?php echo htmlspecialchars(t('verify_login_now')); ?>
            </a>
        <?php else: ?>
            <div class="alert alert-danger border-0">
                <strong><?php echo htmlspecialchars($title); ?></strong>
            </div>
            <p class="verify-msg"><?php echo htmlspecialchars($message); ?></p>
            <a class="btn btn-outline-secondary px-4 py-2 fw-bold" href="<?php echo htmlspecialchars($loginUrl); ?>">
                <?php echo htmlspecialchars(t('verify_login_now')); ?>
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
