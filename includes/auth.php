<?php
// includes/auth.php
session_start();

// MySQL Connection Config (Default XAMPP settings)
$host = 'localhost';
$dbname = 'agrosafe_db';
$user = 'root';
$pass = '';

try {
    // connect to mysql using PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "<br>Run setup_db.php first.");
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

    // default role = user
    $role = 'user';

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {

        $error = "Username already taken.";

    } else {

        // Insert new user with role
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");

        if ($stmt->execute([$username, $email, $password, $role])) {

            $success = "Account created! Please login.";

        } else {

            $error = "Registration failed.";

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

        // Set Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // IMPORTANT


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


        // ROLE BASED REDIRECT
        if ($user['role'] == 'admin') {

            header("Location: admin/index.php");

        } else {

            header("Location: index.php");

        }

        exit();

    } else {

        $error = "Invalid credentials.";

    }
}
?>