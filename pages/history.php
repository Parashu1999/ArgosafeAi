<?php
// pages/history.php

// 1. AUTH & CONNECTION
if (!isset($_SESSION['user_id'])) { die("Access Denied"); }
$user_id = $_SESSION['user_id'];

// Database Connection
$host = 'localhost'; $db = 'agrosafe_db'; $u = 'root'; $p = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Error"); }

// --- HANDLE ACTIONS (Standard PHP) ---
if (isset($_POST['action'])) {
    // Update Status
    if ($_POST['action'] === 'update_status') {
        $stmt = $pdo->prepare("UPDATE history SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['new_status'], $_POST['record_id'], $user_id]);
        echo "<script>window.location.href='index.php?page=history&msg=updated';</script>";
        exit;
    }
    // Delete Record
    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM history WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['record_id'], $user_id]);
        echo "<script>window.location.href='index.php?page=history&msg=deleted';</script>";
        exit;
    }
    // Save Note
    if ($_POST['action'] === 'save_note') {
        $stmt = $pdo->prepare("UPDATE history SET notes = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['note'], $_POST['record_id'], $user_id]);
        echo "<script>window.location.href='index.php?page=history&msg=saved';</script>";
        exit;
    }
}

// --- FETCH DATA ---
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM history WHERE user_id = ?";
if ($filter === 'pending') { $sql .= " AND status = 'Pending'"; }
if ($filter === 'resolved') { $sql .= " AND status = 'Resolved'"; }
$sql .= " ORDER BY date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats for HUD
$statStmt = $pdo->prepare("SELECT status, roi FROM history WHERE user_id = ?");
$statStmt->execute([$user_id]);
$all_rows = $statStmt->fetchAll(PDO::FETCH_ASSOC);

$pending = 0; $resolved = 0; $savings = 0;
foreach ($all_rows as $h) {
    // Check Status (Handle potential nulls)
    $st = $h['status'] ?? 'Pending';
    if ($st === 'Pending' || $st === 'In Progress') $pending++;
    if ($st === 'Resolved') $resolved++;
    $savings += $h['roi'];
}
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        --glass-surface: rgba(255, 255, 255, 0.98);
        --radius-lg: 24px;
        --radius-md: 16px;
        --input-bg: #f3f6f8;
        --accent-glow: 0 8px 30px rgba(46, 204, 113, 0.3);
    }

    /* ANIMATIONS */
    .animate-fade-in { animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* CARD SYSTEM */
    .card-modern {
        background: var(--glass-surface);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: var(--radius-lg);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.3s ease;
    }
    .card-modern:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.05); }

    /* HERO HEADER */
    .hero-history {
        background: var(--primary-gradient);
        border-radius: var(--radius-lg); color: white; padding: 2.5rem;
        position: relative; overflow: hidden; margin-bottom: 2.5rem;
        box-shadow: var(--accent-glow);
    }
    .hero-pattern { position: absolute; top: -10%; right: -5%; opacity: 0.1; font-size: 18rem; transform: rotate(10deg); }

    /* AVATARS & BADGES */
    .avatar-initials {
        width: 45px; height: 45px;
        background: #e9ecef; color: #495057;
        border-radius: 12px; display: flex;
        align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .status-badge {
        padding: 8px 16px; border-radius: 30px;
        font-size: 0.75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.5px;
        display: inline-flex; align-items: center;
    }
    .status-Pending { background: #fff8e1; color: #f39c12; border: 1px solid rgba(243, 156, 18, 0.2); }
    .status-InProgress { background: #e0f7fa; color: #00bcd4; border: 1px solid rgba(0, 188, 212, 0.2); }
    .status-Resolved { background: #e8f5e9; color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.2); }

    /* TABLE STYLING */
    .table-custom { border-collapse: separate; border-spacing: 0; }
    .table-custom th {
        background: #f8f9fa; border: none;
        color: #95a5a6; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
        padding: 1.2rem 1.5rem;
    }
    .table-custom td {
        padding: 1.2rem 1.5rem; border-bottom: 1px solid #f1f3f5; vertical-align: middle;
        background: white; transition: all 0.2s;
    }
    .table-custom tr:last-child td { border-bottom: none; }
    .table-custom tr:hover td { background: #fafbfc; }
    
    /* MODAL STYLING */
    .modal-content { border: none; border-radius: var(--radius-lg); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    .modal-header { border-bottom: 1px solid #f1f3f5; padding: 1.5rem; }
    .modal-body { padding: 2rem; }
    
    .form-control, .form-select {
        background-color: var(--input-bg); border: 2px solid transparent;
        border-radius: 12px; padding: 12px; font-weight: 600; color: #2d3436;
    }
    .form-control:focus, .form-select:focus { background-color: white; border-color: #2ecc71; box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.1); }
</style>

<div class="container-fluid px-0 animate-fade-in">

    <div class="hero-history">
        <i class="fas fa-history hero-pattern"></i>
        <div class="position-relative" style="z-index: 2;">
            <div class="d-flex align-items-center mb-2">
                <span class="badge bg-white text-success bg-opacity-25 border border-white border-opacity-25 px-3 py-1 rounded-pill me-2">
                    <i class="fas fa-database me-1"></i> Records
                </span>
                <small class="opacity-75">Updated: <?php echo date('h:i A'); ?></small>
            </div>
            <h1 class="fw-bold mb-1 display-5">Diagnostic Archive</h1>
            <p class="mb-0 opacity-90 fs-5">Track history, treatment progress, and financial recovery.</p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-modern p-4 d-flex align-items-center justify-content-between border-start border-4 border-warning">
                <div>
                    <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem">Active Threats</small>
                    <h2 class="fw-bold text-dark m-0 mt-1"><?php echo $pending; ?></h2>
                    <small class="text-warning fw-bold">Requires Action</small>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-exclamation-circle fa-xl"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card-modern p-4 d-flex align-items-center justify-content-between border-start border-4 border-success">
                <div>
                    <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem">Resolved Cases</small>
                    <h2 class="fw-bold text-success m-0 mt-1"><?php echo $resolved; ?></h2>
                    <small class="text-muted">Successfully Treated</small>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-check-double fa-xl"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card-modern p-4 d-flex align-items-center justify-content-between border-start border-4 border-info">
                <div>
                    <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem">Total Value Preserved</small>
                    <h2 class="fw-bold text-info m-0 mt-1">₱<?php echo number_format($savings); ?></h2>
                    <small class="text-muted">ROI Generated</small>
                </div>
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-coins fa-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark"><i class="fas fa-list-ul text-primary me-2"></i>Recent Scans</h4>
        </div>
        <div class="d-flex bg-white p-1 rounded-pill shadow-sm border">
            <a href="index.php?page=history&filter=all" class="btn btn-sm rounded-pill px-3 <?php echo $filter=='all'?'btn-dark':'btn-white text-muted'; ?>">All Records</a>
            <a href="index.php?page=history&filter=pending" class="btn btn-sm rounded-pill px-3 <?php echo $filter=='pending'?'btn-warning text-dark fw-bold':'btn-white text-muted'; ?>">Pending</a>
            <a href="index.php?page=history&filter=resolved" class="btn btn-sm rounded-pill px-3 <?php echo $filter=='resolved'?'btn-success text-white fw-bold':'btn-white text-muted'; ?>">Resolved</a>
        </div>
    </div>

    <div class="card-modern overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Timeline</th>
                        <th>Pathogen / Issue</th>
                        <th>Observed Symptoms</th>
                        <th>Current Status</th>
                        <th>Value Impact</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history) > 0): ?>
                        <?php foreach ($history as $row): ?>
                        <?php 
                            // Dynamic Styling based on status
                            $status = $row['status'] ?? 'Pending';
                            $rowColor = ($status === 'Resolved') ? 'success' : 'danger';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($row['date'])); ?></span>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($row['date'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initials me-3 text-<?php echo $rowColor; ?> bg-<?php echo $rowColor; ?> bg-opacity-10">
                                        <?php echo substr($row['diagnosis'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['diagnosis']); ?></div>
                                        <small class="text-muted">ID: #<?php echo $row['id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td style="max-width: 200px;">
                                <?php 
                                    $syms = explode(',', $row['symptom']);
                                    $count = count($syms);
                                    echo '<span class="badge bg-light text-dark border fw-normal me-1"><i class="fas fa-eye me-1 text-muted"></i>'.htmlspecialchars(trim($syms[0])).'</span>';
                                    if ($count > 1) echo '<span class="badge bg-light text-muted border fw-normal">+ '.($count-1).'</span>';
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo str_replace(' ', '', $status); ?>">
                                    <i class="fas fa-circle me-2" style="font-size: 0.5rem;"></i> <?php echo $status; ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-success">+₱<?php echo number_format($row['roi']); ?></div>
                                <small class="text-muted" style="font-size:0.7rem">Projected</small>
                            </td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-outline-dark rounded-pill px-3 border-2 fw-bold" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewModal<?php echo $row['id']; ?>">
                                    Open File
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="opacity-50">
                                    <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                                    <h5 class="text-muted fw-bold">No records found</h5>
                                    <p class="small text-muted">Start a new diagnosis from the dashboard.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($history as $row): ?>
<?php $status = $row['status'] ?? 'Pending'; ?>
<div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            
            <div class="modal-header d-flex align-items-center justify-content-between bg-light">
                <div>
                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size:0.65rem">CASE FILE ID</small>
                    <h5 class="modal-title fw-bold text-dark">#<?php echo $row['id']; ?></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                
                <div class="p-3 bg-opacity-10 bg-<?php echo ($status=='Resolved'?'success':'warning'); ?> rounded-3 mb-4 border border-<?php echo ($status=='Resolved'?'success':'warning'); ?> border-opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="fw-bold text-<?php echo ($status=='Resolved'?'success':'warning'); ?> text-uppercase d-block" style="font-size:0.7rem">Treatment Status</small>
                            <span class="h6 fw-bold text-dark mb-0">Current: <?php echo $status; ?></span>
                        </div>
                        
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                            <select name="new_status" class="form-select form-select-sm border-0 shadow-sm" onchange="this.form.submit()" style="width: auto;">
                                <option value="Pending" <?php echo $status=='Pending'?'selected':''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $status=='In Progress'?'selected':''; ?>>In Progress</option>
                                <option value="Resolved" <?php echo $status=='Resolved'?'selected':''; ?>>Resolved</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <small class="text-uppercase text-muted fw-bold" style="font-size:0.65rem">PRIMARY DIAGNOSIS</small>
                        <h4 class="text-danger fw-bold m-0"><?php echo htmlspecialchars($row['diagnosis']); ?></h4>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded-3 text-center">
                            <small class="text-muted fw-bold d-block" style="font-size:0.65rem">AFFECTED AREA</small>
                            <span class="fw-bold text-dark"><?php echo $row['affected_sqm'] ?? 0; ?> sqm</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded-3 text-center">
                            <small class="text-muted fw-bold d-block" style="font-size:0.65rem">SEVERITY</small>
                            <?php if(($row['severity'] ?? 1) == 2): ?>
                                <span class="badge bg-danger text-white">Severe</span>
                            <?php else: ?>
                                <span class="badge bg-success text-white">Mild</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <small class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size:0.65rem">OBSERVED SYMPTOMS</small>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach(explode(',', $row['symptom']) as $s): ?>
                            <span class="badge bg-light text-dark border fw-normal px-3 py-2 rounded-pill">
                                <i class="fas fa-check text-success me-1"></i> <?php echo htmlspecialchars(trim($s)); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-2">
                    <small class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size:0.65rem">FIELD NOTES</small>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_note">
                        <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                        <div class="form-floating mb-2">
                            <textarea class="form-control" placeholder="Add note" name="note" style="height: 100px; resize: none;"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></textarea>
                            <label class="text-muted small">Treatment progress notes...</label>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill"><i class="fas fa-save me-2"></i>Save Field Note</button>
                    </form>
                </div>

            </div>

            <div class="modal-footer bg-light border-0 justify-content-center pb-3">
                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this record?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn btn-link text-danger text-decoration-none btn-sm opacity-75 hover-opacity-100">
                        <i class="fas fa-trash-alt me-1"></i> Delete this record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>