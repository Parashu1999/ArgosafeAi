<?php
// admin/login.php
session_start();

// Database Connection
$host = 'localhost'; $db = 'agrosafe_db'; $u = 'root'; $p = '';
$pdo = null;
$error = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    // This error won't stop the page from loading but informs the user
    $error = "Database connection error. Please check XAMPP/MySQL service.";
}

// Check if already logged in
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 1. Fetch credentials from settings table - FIXED SQL QUERY
    // This query selects exactly 2 columns: the key and the value, resolving the error.
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('admin_username', 'admin_password_hash')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stored_username = $settings['admin_username'] ?? '';
    $stored_password_hash = $settings['admin_password_hash'] ?? '';

    // 2. Verification using password_verify()
    if ($username === $stored_username && password_verify($password, $stored_password_hash)) {
        // Successful login
        $_SESSION['user_id'] = 'ADMIN_SESSION_ID'; 
        $_SESSION['role'] = 'admin';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid Username or Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - AgroSafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4318FF;
            --primary-green: #2ecc71;
            --bg-dark: #111c44;
        }
        body {
            background-color: var(--bg-dark); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .logo { 
            color: var(--primary-green); 
            font-size: 2.5rem; 
            font-weight: 800; 
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo i { margin-right: 8px; }
        .form-control { 
            border-radius: 10px; 
            padding: 12px; 
            margin-bottom: 15px;
            border: 1px solid #ccc;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(67, 24, 255, 0.25);
        }
        .btn-login { 
            background: var(--primary-blue); 
            border: none; 
            color: white; 
            padding: 12px; 
            font-weight: 700; 
            border-radius: 10px; 
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="fas fa-leaf"></i> AgroSafe
        </div>
        <h4 class="fw-bold mb-4">Admin Access</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
            <button type="submit" class="btn btn-login w-100 mt-3">Log In</button>
        </form>
        
        <small class="text-muted d-block mt-3">Default Credentials: admin / password</small>
    </div>
</body>
</html>