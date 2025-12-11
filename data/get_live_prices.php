<?php
// data/get_live_prices.php
header('Content-Type: application/json');

// 1. Simulate fetching Global Base Prices (USD)
// (In a real startup, these would come from a Commodities Database)
$base_prices = [
    'crop'      => 12.50, // $12.50 per sqm
    'fungicide' => 0.15,  // $0.15 per ml
    'labor'     => 20.00  // $20.00 flat
];

// 2. Get Live Exchange Rate (USD -> PHP)
$api_url = "https://api.exchangerate-api.com/v4/latest/USD";
$rate = 56.00; // Fallback default

// Suppress errors to keep JSON clean
$json = @file_get_contents($api_url); 
if ($json) {
    $data = json_decode($json, true);
    $rate = $data['rates']['PHP'] ?? 56.00;
}

// 3. Calculate Real-Time Local Prices
$response = [
    'rate' => number_format($rate, 2),
    'crop_php'      => number_format($base_prices['crop'] * $rate, 2),
    'fungicide_php' => number_format($base_prices['fungicide'] * $rate, 2),
    'labor_php'     => number_format($base_prices['labor'] * $rate, 2),
    'timestamp'     => date("h:i:s A")
];

echo json_encode($response);
?>