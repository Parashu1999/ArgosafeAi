<?php
// admin/login.php
session_start();

// PHPMailer import (MOVED inside PHP block)
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Database Connection
$host = 'localhost';
$db = 'agrosafe_db';
$user = 'root';
$pass = '';

$error = '';

try {

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Database connection failed");

}


// If already logged in as admin → go to dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {

    header("Location: dashboard.php");
    exit();

}


// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);


    // Fetch admin user from admin table
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);


    // Verify password AND check email verification
    if ($admin && password_verify($password, $admin['password'])) {

        // OPTIONAL: check is_verified column if exists
        if (isset($admin['is_verified']) && $admin['is_verified'] == 0) {

            $error = "Please verify your email before login.";

        } else {

            // Set session
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = 'admin';

            header("Location: dashboard.php");
            exit();
        }

    } else {

        $error = "Invalid admin username or password.";

    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - AgroSafeAI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#111c44;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.card{
padding:30px;
border-radius:12px;
width:400px;
}

</style>

</head>

<body>

<div class="card">

<h3 class="text-center mb-4">Admin Login</h3>

<?php if($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>


<form method="POST">

<input type="text" name="username" class="form-control mb-3" placeholder="Admin Username" required>

<input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

<button class="btn btn-primary w-100">Login</button>

</form>

</div>

</body>
</html>