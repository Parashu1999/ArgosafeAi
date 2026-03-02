<?php
// master_setup.php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'agrosafe_db';

echo "<h1>🛠️ AgroSafeAI Database Fixer</h1>";

try {
    // 1. Connect to MySQL Server (Root)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "✅ Database `$dbname` is ready.<br>";
    
    // 3. Connect to that Database
    $pdo->exec("USE `$dbname`");

    // 4. Create USERS Table (If missing)
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        mobile_number VARCHAR(20) DEFAULT NULL,
        gender VARCHAR(20) DEFAULT NULL,
        state VARCHAR(100) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        taluku VARCHAR(100) DEFAULT NULL,
        district VARCHAR(100) DEFAULT NULL,
        panchayath VARCHAR(100) DEFAULT NULL,
        pincode VARCHAR(12) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        role VARCHAR(20) NOT NULL DEFAULT 'farmer',
        is_verified TINYINT(1) DEFAULT 0,
        verification_token VARCHAR(255) DEFAULT NULL,
        reset_token VARCHAR(255) DEFAULT NULL,
        reset_token_expires_at DATETIME DEFAULT NULL
    )";
    $pdo->exec($sql_users);
    echo "✅ Table `users` is ready.<br>";

    // 5. Create SUBSIDIES Table
    $sql_subsidies = "CREATE TABLE IF NOT EXISTS subsidies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scheme_name VARCHAR(150) NOT NULL,
        department VARCHAR(150) DEFAULT NULL,
        category VARCHAR(100) DEFAULT NULL,
        subsidy_amount DECIMAL(12,2) DEFAULT NULL,
        eligibility TEXT NOT NULL,
        application_url VARCHAR(255) DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_subsidies);
    echo "✅ Table `subsidies` is ready.<br>";

    // 5. Create HISTORY Table (With ALL new columns)
    // We define the FULL structure here so new installs get everything immediately.
    $sql_history = "CREATE TABLE IF NOT EXISTS history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        symptom TEXT,
        diagnosis VARCHAR(100),
        roi DECIMAL(10, 2),
        status VARCHAR(20) DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        severity INT DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql_history);
    echo "✅ Table `history` is ready.<br>";

    // 6. FORCE UPDATE (For existing tables)
    // If the table already existed from an old version, it might miss columns.
    // We try to add them. If they exist, it fails silently (catch block).
    
    $updates = [
        "ALTER TABLE history ADD status VARCHAR(20) DEFAULT 'Pending'",
        "ALTER TABLE history ADD notes TEXT DEFAULT NULL",
        "ALTER TABLE history ADD severity INT DEFAULT 1",
        // Fix for the symptom column if it was too short (VARCHAR) -> change to TEXT
        "ALTER TABLE history MODIFY symptom TEXT",
        "ALTER TABLE users ADD mobile_number VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE users ADD gender VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE users ADD state VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE users ADD country VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE users ADD taluku VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE users ADD district VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE users ADD panchayath VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE users ADD pincode VARCHAR(12) DEFAULT NULL",
        "ALTER TABLE users ADD role VARCHAR(20) NOT NULL DEFAULT 'farmer'",
        "ALTER TABLE users ADD is_verified TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD verification_token VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD reset_token VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD reset_token_expires_at DATETIME DEFAULT NULL"
    ];

    foreach ($updates as $sql) {
        try {
            $pdo->exec($sql);
            echo "🔹 Applied update: " . htmlspecialchars($sql) . "<br>";
        } catch (Exception $e) {
            // Ignore error if column already exists
        }
    }

    echo "<hr><h2 style='color:green'>🎉 Repair Complete!</h2>";
    echo "<p>Your database is now fully compatible with the new features.</p>";
    echo "<a href='index.php?page=history' style='font-size:1.2rem; font-weight:bold;'>Go to History Page</a>";

} catch (PDOException $e) {
    die("<h2 style='color:red'>❌ Error: " . $e->getMessage() . "</h2>");
}
?>
