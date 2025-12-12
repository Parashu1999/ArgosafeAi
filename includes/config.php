<?php
// includes/config.php

// 1. Load Composer Dependencies (Adjust path to root)
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Configuration: Base Prices (USD)
$base_prices_usd = [
    'crop_value'     => 0.25,
    'fungicide_cost' => 0.05,
    'labor_cost'     => 15.00
];

// 3. API Connection Function
function getLiveExchangeRate() {
    $api_url = "https://api.exchangerate-api.com/v4/latest/USD";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        return $data['rates']['PHP'] ?? 56.00;
    }
    return 56.00; // Fallback
}

// 4. Calculate Global Market Data (Available to all pages)
$current_rate = getLiveExchangeRate();
$market_data = [
    'crop_value'     => $base_prices_usd['crop_value'] * $current_rate,
    'fungicide_cost' => $base_prices_usd['fungicide_cost'] * $current_rate,
    'labor_cost'     => $base_prices_usd['labor_cost'] * $current_rate
];
?>