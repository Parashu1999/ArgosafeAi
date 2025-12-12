<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0">Live Market Rates (PHP)</h4>
    <div class="text-end">
        <span class="badge bg-danger animate-pulse">● LIVE CONNECTION</span>
        <small class="text-muted d-block mt-1"><?php echo date("h:i:s A"); ?></small>
    </div>
</div>

<div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
    <i class="fas fa-satellite-dish fa-2x me-3 opacity-50"></i>
    <div>
        <strong>Global Exchange Link Active</strong><br>
        Current USD/PHP Rate: <strong>₱<?php echo number_format($current_rate, 2); ?></strong>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="custom-card text-center border-bottom border-4 border-warning">
            <i class="fas fa-coins fa-2x text-warning mb-3"></i>
            <h5>Crop Yield Value</h5>
            <h3 class="fw-bold">₱<?php echo number_format($market_data['crop_value'], 2); ?></h3>
            <small class="text-muted">per sqm (Real-time)</small>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="custom-card text-center border-bottom border-4 border-info">
            <i class="fas fa-flask fa-2x text-info mb-3"></i>
            <h5>Chemical Cost</h5>
            <h3 class="fw-bold">₱<?php echo number_format($market_data['fungicide_cost'], 2); ?></h3>
            <small class="text-muted">per ml (Imported)</small>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="custom-card text-center border-bottom border-4 border-success">
            <i class="fas fa-users fa-2x text-success mb-3"></i>
            <h5>Labor Rate</h5>
            <h3 class="fw-bold">₱<?php echo number_format($market_data['labor_cost'], 2); ?></h3>
            <small class="text-muted">Local Standard</small>
        </div>
    </div>
</div>