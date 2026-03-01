<?php
require_once __DIR__ . '/includes/language.php';
app_handle_language_request();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=agrosafe_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(t('verify_db_failed'));
}

if (isset($_GET['token'])) {
    $token = trim((string) $_GET['token']);

    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        echo "<h2>" . htmlspecialchars(t('verify_success')) . "</h2>";
        echo "<a href='login.php'>" . htmlspecialchars(t('verify_login_now')) . "</a>";
    } else {
        echo htmlspecialchars(t('verify_invalid_or_expired'));
    }
} else {
    echo htmlspecialchars(t('verify_invalid'));
}
?>
