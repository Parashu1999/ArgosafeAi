<?php
// pages/market.php

// 1. ACCESS CONTROL
if (!isset($_SESSION['user_id'])) { die("Access Denied"); }

// 2. DEFINE MARKET DATA (This fixes the 'Undefined variable' errors)
$market_data = [
    'crop_value' => 150.00,      // Value per sqm
    'fungicide_cost' => 45.00,   // Cost per ml
    'labor_cost' => 500.00       // Daily Rate
];

// 3. DEFINE EXCHANGE RATE (This fixes the '$current_rate' error)
$current_rate = 58.50; 
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        --glass-surface: rgba(255, 255, 255, 0.98);
        --radius-lg: 24px;
        --radius-md: 16px;
        --accent-glow: 0 8px 30px rgba(46, 204, 113, 0.3);
    }

    /* ANIMATIONS */
    .animate-fade-in { animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    .animate-pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

    /* HERO */
    .hero-market {
        background: var(--primary-gradient);
        border-radius: var(--radius-lg); color: white; padding: 2.5rem;
        position: relative; overflow: hidden; margin-bottom: 2.5rem;
        box-shadow: var(--accent-glow);
    }
    .hero-pattern { position: absolute; top: -20%; right: -5%; opacity: 0.1; font-size: 20rem; transform: rotate(-15deg); }

    /* CARDS */
    .card-modern {
        background: var(--glass-surface);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: var(--radius-lg);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.3s ease;
        height: 100%; position: relative; overflow: hidden;
    }
    .card-modern:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1); }

    /* ICON STYLES */
    .ticker-icon {
        width: 60px; height: 60px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 1rem;
    }
    .trend-badge {
        font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;
        display: inline-flex; align-items: center;
    }
    .trend-up { background: #e8f5e9; color: #2ecc71; }
    .trend-down { background: #ffebee; color: #e74c3c; }
    .trend-flat { background: #f3f6f8; color: #636e72; }
    /* 1. The Red Container */
    .live-badge-red {
        background: linear-gradient(135deg, #ff0000ff 0%, #cc0000 100%); /* Depth gradient */
        color: white;
        padding: 6px 16px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        
        /* The "Spectacular" Pulse Animation */
        animation: glowing-pulse 2s infinite;
        box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.7);
    }

    /* 2. The White Text */
    .live-text-white {
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2); /* Slight drop shadow for readability */
    }

    /* 3. The Blinking White Dot */
    .live-indicator-white {
        width: 8px;
        height: 8px;
        background-color: #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 6px rgba(255, 255, 255, 0.8); /* White Glow */
        animation: rapid-blink 1s infinite;
    }

    /* --- ANIMATIONS --- */

    /* Background Pulse (Heartbeat effect) */
    @keyframes glowing-pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.7);
        }
        70% {
            transform: scale(1.02); /* Slight pop */
            box-shadow: 0 0 0 10px rgba(255, 77, 77, 0); /* Ripple fades out */
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 77, 77, 0);
        }
    }

    /* Dot Blink */
    @keyframes rapid-blink {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }
</style>

<div class="container-fluid px-0 animate-fade-in">

    <div class="hero-market">
        <i class="fas fa-chart-line hero-pattern"></i>
        <div class="position-relative" style="z-index: 2;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center">
                    <span class="badge bg-white text-success bg-opacity-25 border border-white border-opacity-25 px-3 py-1 rounded-pill me-2">
                        <i class="fas fa-globe me-1"></i> Global Link
                    </span>
                    <small class="opacity-75">Last Sync: <?php echo date("h:i:s A"); ?></small>
                </div>
<div class="live-badge-red">
    <div class="live-indicator-white"></div>
    <span class="live-text-white">LIVE STREAM</span>
</div>
            </div>
            <h1 class="fw-bold mb-1 display-5">Market Intelligence</h1>
            <p class="mb-0 opacity-90 fs-5">Real-time agricultural rates and forex data.</p>
        </div>
    </div>

    <div class="card-modern p-4 mb-5 border-start border-4 border-primary">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                </div>
                <div>
                    <small class="text-uppercase text-muted fw-bold d-block">Global Exchange Rate</small>
                    <h3 class="fw-bold text-dark m-0">USD <i class="fas fa-arrow-right text-muted mx-2 fa-xs"></i> PHP</h3>
                </div>
            </div>
            <div class="text-end">
                <h1 class="display-4 fw-bold text-primary m-0">₱<?php echo number_format($current_rate, 2); ?></h1>
                <span class="trend-badge trend-up"><i class="fas fa-arrow-trend-up me-1"></i> +0.05% Today</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-4 col-md-6">
            <div class="card-modern p-4 border-top border-4 border-warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="ticker-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-wheat"></i>
                    </div>
                    <span class="trend-badge trend-up"><i class="fas fa-caret-up me-1"></i> 2.4%</span>
                </div>
                <small class="text-uppercase text-muted fw-bold">Crop Yield Value</small>
                <h2 class="fw-bold text-dark mt-1 mb-0">₱<?php echo number_format($market_data['crop_value'], 2); ?></h2>
                <small class="text-muted">per sqm (Real-time)</small>
                
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted fst-italic">Demand is High</small>
                    <i class="fas fa-chart-area text-warning opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card-modern p-4 border-top border-4 border-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="ticker-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-flask"></i>
                    </div>
                    <span class="trend-badge trend-flat"><i class="fas fa-minus me-1"></i> Stable</span>
                </div>
                <small class="text-uppercase text-muted fw-bold">Chemical Cost</small>
                <h2 class="fw-bold text-dark mt-1 mb-0">₱<?php echo number_format($market_data['fungicide_cost'], 2); ?></h2>
                <small class="text-muted">per ml (Imported)</small>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted fst-italic">Supply Chain: Good</small>
                    <i class="fas fa-box text-info opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card-modern p-4 border-top border-4 border-success">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="ticker-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="trend-badge trend-up"><i class="fas fa-caret-up me-1"></i> 1.2%</span>
                </div>
                <small class="text-uppercase text-muted fw-bold">Labor Rate</small>
                <h2 class="fw-bold text-dark mt-1 mb-0">₱<?php echo number_format($market_data['labor_cost'], 2); ?></h2>
                <small class="text-muted">Local Standard / Day</small>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted fst-italic">Region: Calabarzon</small>
                    <i class="fas fa-map-marker-alt text-success opacity-50"></i>
                </div>
            </div>
        </div>

    </div>
</div>