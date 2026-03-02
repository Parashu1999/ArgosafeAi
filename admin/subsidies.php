<?php
// admin/subsidies.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$db = 'agrosafe_db';
$u = 'root';
$p = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $u, $p);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Error");
}

// Ensure subsidies table exists for older installations.
$pdo->exec("
    CREATE TABLE IF NOT EXISTS subsidies (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

function redirectWithMessage(string $type, string $message): void
{
    $param = $type === 'error' ? 'error' : 'msg';
    header('Location: subsidies.php?' . $param . '=' . urlencode($message));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_subsidy') {
        $subsidyId = (int) ($_POST['subsidy_id'] ?? 0);
        $schemeName = trim((string) ($_POST['scheme_name'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        $eligibility = trim((string) ($_POST['eligibility'] ?? ''));
        $applicationUrl = trim((string) ($_POST['application_url'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'active'));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $amountInput = trim((string) ($_POST['subsidy_amount'] ?? ''));

        if ($schemeName === '' || $eligibility === '') {
            redirectWithMessage('error', 'Scheme name and eligibility are required.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'inactive';
        }

        $subsidyAmount = null;
        if ($amountInput !== '') {
            $subsidyAmount = (float) $amountInput;
        }

        $startDate = $startDate !== '' ? $startDate : null;
        $endDate = $endDate !== '' ? $endDate : null;
        $department = $department !== '' ? $department : null;
        $category = $category !== '' ? $category : null;
        $applicationUrl = $applicationUrl !== '' ? $applicationUrl : null;

        if ($subsidyId > 0) {
            $stmt = $pdo->prepare("
                UPDATE subsidies
                SET scheme_name = ?, department = ?, category = ?, subsidy_amount = ?, eligibility = ?, application_url = ?, start_date = ?, end_date = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $schemeName,
                $department,
                $category,
                $subsidyAmount,
                $eligibility,
                $applicationUrl,
                $startDate,
                $endDate,
                $status,
                $subsidyId,
            ]);
            redirectWithMessage('msg', 'Subsidy scheme updated successfully.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO subsidies (scheme_name, department, category, subsidy_amount, eligibility, application_url, start_date, end_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $schemeName,
                $department,
                $category,
                $subsidyAmount,
                $eligibility,
                $applicationUrl,
                $startDate,
                $endDate,
                $status,
            ]);
            redirectWithMessage('msg', 'Subsidy scheme added successfully.');
        }
    }

    if ($action === 'delete_subsidy') {
        $subsidyId = (int) ($_POST['subsidy_id'] ?? 0);
        if ($subsidyId > 0) {
            $stmt = $pdo->prepare("DELETE FROM subsidies WHERE id = ?");
            $stmt->execute([$subsidyId]);
            redirectWithMessage('msg', 'Subsidy scheme deleted successfully.');
        }
        redirectWithMessage('error', 'Invalid subsidy record.');
    }
}

$msg = isset($_GET['msg']) ? (string) $_GET['msg'] : '';
$error = isset($_GET['error']) ? (string) $_GET['error'] : '';

$editing = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM subsidies WHERE id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$subsidies = $pdo->query("SELECT * FROM subsidies ORDER BY updated_at DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subsidies - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-bg: #f4f7fe;
            --sidebar-width: 260px;
            --sidebar-bg: #111c44;
            --primary: #4318FF;
        }
        body { background-color: var(--admin-bg); font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0;
            background: var(--sidebar-bg); color: white; padding: 24px;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        .brand { font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; letter-spacing: 1px; }
        .nav-link {
            color: #a3aed0; padding: 14px 10px; margin-bottom: 5px; border-radius: 10px;
            font-weight: 500; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: 0.2s;
        }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { border-right: 4px solid var(--primary); border-radius: 10px 0 0 10px; }
        .main-content { margin-left: var(--sidebar-width); padding: 30px; }
        .card-custom {
            background: white; border: none; border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 24px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="brand"><i class="fas fa-leaf text-success me-2"></i> AGRO<span class="text-white">SAFE</span></div>
        <small class="fw-bold text-uppercase text-light mb-4 d-block opacity-75" style="font-size:0.7rem;">Admin Panel</small>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Manage Farmer</a>
            <a href="scans.php" class="nav-link"><i class="fas fa-database"></i> Scan History</a>
            <a href="subsidies.php" class="nav-link active"><i class="fas fa-hand-holding-dollar"></i> Manage Subsidies</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-sliders-h"></i> System Settings</a>
            <a href="security.php" class="nav-link"><i class="fas fa-lock"></i> Security & Privacy</a>
            <a href="dataset.php" class="nav-link"><i class="fa-solid fa-circle-nodes"></i> Dataset Manager</a>
            <div style="margin-top: auto; padding-top: 100px;">
                <a href="../logout.php?redirect=admin" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <h3 class="fw-bold mb-4">Government Subsidy Schemes</h3>

        <?php if ($msg !== ''): ?>
            <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card-custom mb-4">
            <h5 class="fw-bold mb-3"><?php echo $editing ? 'Edit Subsidy Scheme' : 'Add New Subsidy Scheme'; ?></h5>
            <form method="POST">
                <input type="hidden" name="action" value="save_subsidy">
                <input type="hidden" name="subsidy_id" value="<?php echo htmlspecialchars((string) ($editing['id'] ?? '0')); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Scheme Name</label>
                        <input type="text" name="scheme_name" class="form-control" value="<?php echo htmlspecialchars((string) ($editing['scheme_name'] ?? '')); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Department</label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars((string) ($editing['department'] ?? '')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Category</label>
                        <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars((string) ($editing['category'] ?? '')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Subsidy Amount</label>
                        <input type="number" step="0.01" min="0" name="subsidy_amount" class="form-control" value="<?php echo htmlspecialchars((string) ($editing['subsidy_amount'] ?? '')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <?php $statusValue = $editing['status'] ?? 'active'; ?>
                            <option value="active" <?php echo $statusValue === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $statusValue === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars((string) ($editing['start_date'] ?? '')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars((string) ($editing['end_date'] ?? '')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Eligibility</label>
                        <textarea name="eligibility" class="form-control" rows="3" required><?php echo htmlspecialchars((string) ($editing['eligibility'] ?? '')); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Application URL</label>
                        <input type="url" name="application_url" class="form-control" value="<?php echo htmlspecialchars((string) ($editing['application_url'] ?? '')); ?>" placeholder="https://...">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i><?php echo $editing ? 'Update Subsidy' : 'Add Subsidy'; ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="subsidies.php" class="btn btn-outline-secondary px-4">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Scheme</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Application Window</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subsidies)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No subsidy schemes added yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subsidies as $row): ?>
                                <tr>
                                    <td>#<?php echo (int) $row['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['scheme_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['department'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo $row['subsidy_amount'] !== null ? '₹' . number_format((float) $row['subsidy_amount'], 2) : '-'; ?></td>
                                    <td>
                                        <?php if (($row['status'] ?? '') === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo !empty($row['start_date']) ? htmlspecialchars($row['start_date']) : '-'; ?>
                                            to
                                            <?php echo !empty($row['end_date']) ? htmlspecialchars($row['end_date']) : '-'; ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <a href="subsidies.php?edit=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this subsidy scheme?');">
                                            <input type="hidden" name="action" value="delete_subsidy">
                                            <input type="hidden" name="subsidy_id" value="<?php echo (int) $row['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
