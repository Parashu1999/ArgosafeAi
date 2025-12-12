<?php
// setup_db.php
$host = 'localhost';
$user = 'root';      // Default XAMPP user
$pass = '';          // Default XAMPP password is empty
$dbname = 'agrosafe_db';

try {
    // 1. Connect to MySQL Server (without selecting a DB yet)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create Database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "✅ Database `$dbname` checked/created.<br>";

    // 3. Connect to the new Database
    $pdo->exec("USE `$dbname`");

    // 4. Create Users Table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Table `users` checked/created successfully.<br>";
    echo "<hr><strong>🎉 System is ready! You can now go to <a href='login.php'>login.php</a></strong>";

} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage());
}
?>