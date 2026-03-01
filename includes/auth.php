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
use PHPMailer\PHPMailer\Exception;

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
        'skip_tls_verify' => filter_var(
            getenv('MAIL_SKIP_TLS_VERIFY') ?: ($fileConfig['skip_tls_verify'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        ),
        'debug' => (int) (getenv('MAIL_DEBUG') ?: ($fileConfig['debug'] ?? 0)),
    ];
}

function appBaseUrl(): string
{
    $configuredUrl = getenv('APP_URL');
    if (!empty($configuredUrl)) {
        return rtrim($configuredUrl, '/');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/');

    return $scheme . '://' . $host . $scriptDir;
}

function sendVerificationEmail(string $recipientEmail, string $token): void
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
        throw new Exception('SMTP credentials are not configured.');
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
    $mail->Subject = t('mail_subject_verify');
    $mail->Body = t('mail_body_html', ['url' => $verifyUrl]);
    $mail->AltBody = t('mail_body_text', ['url' => $verifyUrl]);

    $mail->send();
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


// =========================
// 1. HANDLE REGISTER
// =========================
if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $role = 'user';

    // generate verification token
    $token = bin2hex(random_bytes(32));

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {

        $error = t('err_username_taken');

    } else {

        try {
            $pdo->beginTransaction();

            // Insert user as unverified until email link is clicked.
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$username, $email, $password, $role, $token]);

            sendVerificationEmail($email, $token);

            $pdo->commit();
            $success = t('success_account_created_verify');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mailError = $e->getMessage();
            error_log('Registration mail error: ' . $mailError);

            if (stripos($mailError, 'SMTP credentials are not configured') !== false) {
                $error = t('err_smtp_not_configured');
            } elseif (stripos($mailError, 'Could not authenticate') !== false) {
                $error = t('err_smtp_auth_failed');
            } else {
                $error = t('err_registration_email_send');
            }
        }
    }
}



// =========================
// 2. HANDLE LOGIN
// =========================
if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

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
