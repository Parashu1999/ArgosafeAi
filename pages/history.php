<?php
// Logic: Read History CSV specifically for this page
$full_history = [];
if (file_exists(__DIR__ . '/../data/history.csv')) {
    if (($h = fopen(__DIR__ . '/../data/history.csv', "r")) !== FALSE) {
        while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {
            $full_history[] = $data;
        }
        fclose($h);
    }
    // Show newest first
    $full_history = array_reverse($full_history);
}
?>

<div class="custom-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">Scan History</h4>
        <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 rounded-start">Date</th>
                    <th class="border-0">Symptom</th>
                    <th class="border-0">Diagnosis</th>
                    <th class="border-0 rounded-end">Saved (ROI)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($full_history)): ?>
                    <?php foreach ($full_history as $log): ?>
                        <?php if(count($log) >= 4): ?>
                        <tr>
                            <td><small class="text-muted"><?php echo htmlspecialchars($log[0]); ?></small></td>
                            <td><?php echo htmlspecialchars($log[1]); ?></td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <?php echo htmlspecialchars($log[2]); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-success">+$<?php echo htmlspecialchars($log[3]); ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No history yet. Start diagnosing!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>