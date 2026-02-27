<?php
// includes/config.php

// =====================================
// 1. Load Composer Dependencies
// =====================================
require_once __DIR__ . '/../vendor/autoload.php';


// =====================================
// 2. DATABASE CONFIGURATION (IMPORTANT)
// =====================================
$host = "localhost";
$username = "root";
$password = "";
$database = "agrosafe_db";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}


// =====================================
// 3. Base Prices (USD)
// =====================================
$base_prices_usd = [
    'crop_value'     => 0.25,
    'fungicide_cost' => 0.05,
    'labor_cost'     => 15.00
];


// =====================================
// 4. Get Live Exchange Rate (USD → INR)
// =====================================
function getLiveExchangeRate() {

    // USD to INR API
    $api_url = "https://api.exchangerate-api.com/v4/latest/USD";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);

    curl_close($ch);

    if ($response) {

        $data = json_decode($response, true);

        // INR rate
        if (isset($data['rates']['INR'])) {
            return $data['rates']['INR'];
        }

    }

    // fallback INR rate
    return 83.00;
}


// =====================================
// 5. Calculate Market Data (INR)
// =====================================
$current_rate = getLiveExchangeRate();

$market_data = [

    'crop_value' =>
        $base_prices_usd['crop_value'] * $current_rate,

    'fungicide_cost' =>
        $base_prices_usd['fungicide_cost'] * $current_rate,

    'labor_cost' =>
        $base_prices_usd['labor_cost'] * $current_rate
];

?>