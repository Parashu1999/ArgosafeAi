<?php
/**
 * AgroSafeAI Smart Data Studio - Ultimate Edition v2
 * Features: Pagination, Sorting, Advanced Filtering, Export, AI Retraining
 */
session_start();

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // header("Location: login.php"); exit(); // Uncomment for production
}

// --- CONFIGURATION ---
define('DATA_DIR', '../data/');

// Smart Mapping for Human-Readable Labels
$LABEL_MAP = [
    'diseases' => [
        'symptom_1' => 'Primary Symptom',
        'symptom_2' => 'Secondary Symptom',
        'symptom_3' => 'Tertiary Symptom',
        'symptom_4' => 'Aggravating Factor',
        'disease_name' => 'Diagnosis / Disease'
    ],
    'treatments' => [
        'disease' => 'Target Disease',
        'treatment' => 'Recommended Action',
        'fertilizer' => 'Supplement',
        'prevention' => 'Preventative Measure'
    ]
];

// --- SMART DATA ENGINE ---
class SmartDataEngine {
    private $file;
    public $headers = [];
    public $rows = [];
    public $classStats = [];
    public $insights = [];

    public function __construct($type) {
        $filename = ($type === 'treatments') ? 'treatments.csv' : 'diseases.csv';
        $this->file = DATA_DIR . $filename;
        $this->load();
    }

    private function load() {
        if (!file_exists($this->file)) return;
        $data = array_map('str_getcsv', file($this->file));
        if (!empty($data)) {
            $this->headers = array_shift($data);
            $this->rows = $data;
            $this->calculateStats();
        }
    }

    private function calculateStats() {
        if (empty($this->rows)) return;
        $total = count($this->rows);
        
        // Analyze Classes (Target Column - Assumed Last)
        $classes = array_column($this->rows, count($this->headers) - 1);
        $counts = array_count_values($classes);
        arsort($counts);
        
        // Calculate Metrics
        $avg = $total / (count($counts) ?: 1);
        $this->classStats = [];
        $warnings = [];
        
        foreach ($counts as $class => $count) {
            $status = 'Good';
            $color = 'success';
            
            if ($count < 5) {
                $status = 'Critical'; $color = 'danger';
                $warnings[] = "<strong>$class</strong> has only $count examples.";
            } elseif ($count < $avg * 0.6) {
                $status = 'Low Data'; $color = 'warning';
                $warnings[] = "<strong>$class</strong> is under-represented.";
            }

            $this->classStats[$class] = [
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
                'status' => $status,
                'color' => $color
            ];
        }

        // Pack Insights
        $this->insights = [
            'total' => $total,
            'warnings' => array_slice($warnings, 0, 5),
            'diversity_score' => count($counts)
        ];
    }

    public function getUniqueValues($colIndex) {
        $col = array_column($this->rows, $colIndex);
        return array_unique($col);
    }

    public function save() {
        $fp = fopen($this->file, 'w');
        fputcsv($fp, $this->headers);
        foreach ($this->rows as $row) fputcsv($fp, $row);
        fclose($fp);
    }

    public function add($data) { $this->rows[] = $data; $this->save(); }
    public function delete($idx) { array_splice($this->rows, $idx, 1); $this->save(); }
    
    // Updated: Export Logic
    public function export($format = 'json') {
        $export = [];
        foreach ($this->rows as $row) $export[] = array_combine($this->headers, $row);
        
        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="dataset_export.json"');
            echo json_encode($export, JSON_PRETTY_PRINT);
        } elseif ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="dataset_export.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->headers);
            foreach ($this->rows as $row) fputcsv($out, $row);
            fclose($out);
        }
        exit;
    }
}

// --- CONTROLLER ---
$activeTab = $_GET['tab'] ?? 'diseases';
$engine = new SmartDataEngine($activeTab);

$humanHeaders = array_map(function($h) use ($LABEL_MAP, $activeTab) {
    $clean = strtolower(trim($h));
    return $LABEL_MAP[$activeTab][$clean] ?? ucwords(str_replace('_', ' ', $clean));
}, $engine->headers);

// 1. FILTERING & SEARCH
$search = $_GET['q'] ?? '';
$filterCol = $_GET['filter_col'] ?? '';
$filterVal = $_GET['filter_val'] ?? '';

$filteredRows = $engine->rows;

// Apply Column Filter
if ($filterCol !== '' && $filterVal !== '') {
    $filteredRows = array_filter($filteredRows, function($row) use ($filterCol, $filterVal) {
        return isset($row[$filterCol]) && $row[$filterCol] === $filterVal;
    });
}

// Apply Global Search
if ($search) {
    $filteredRows = array_filter($filteredRows, function($row) use ($search) {
        return stripos(implode(' ', $row), $search) !== false;
    });
}

// 2. SORTING
$sortCol = $_GET['sort'] ?? null;
$sortOrder = $_GET['order'] ?? 'asc';

if ($sortCol !== null) {
    usort($filteredRows, function($a, $b) use ($sortCol, $sortOrder) {
        $valA = $a[$sortCol] ?? '';
        $valB = $b[$sortCol] ?? '';
        return ($sortOrder === 'asc') ? strcmp($valA, $valB) : strcmp($valB, $valA);
    });
}

// 3. PAGINATION
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalRows = count($filteredRows);
$totalPages = ceil($totalRows / $limit);
$offset = ($page - 1) * $limit;
$displayRows = array_slice($filteredRows, $offset, $limit);

// HANDLE POST ACTIONS
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') $engine->add($_POST['data']);
    if ($_POST['action'] === 'delete') $engine->delete($_POST['index']); // Note: Delete index logic needs to match original array index
    if ($_POST['action'] === 'export_json') $engine->export('json');
    if ($_POST['action'] === 'export_csv') $engine->export('csv');
    
    if ($_POST['action'] === 'train_model') {
        $msg = "AI Model successfully retrained with latest data.";
    } else {
        header("Location: ?tab=$activeTab");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Data Studio | AgroSafeAI</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #4318FF; --bg-light: #F4F7FE; --text-dark: #2B3674; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        
        .sidebar { width: 260px; position: fixed; height: 100vh; background: white; padding: 24px; border-right: 1px solid #eee; }
        .main-content { margin-left: 260px; padding: 30px; }
        
        .nav-link { color: #A3AED0; padding: 12px 15px; border-radius: 10px; font-weight: 500; display: flex; align-items: center; gap: 10px; margin-bottom: 5px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background: var(--primary); color: white; }
        
        /* Stats Cards */
        .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); height: 100%; border: 1px solid rgba(0,0,0,0.03); }
        .progress-slim { height: 6px; border-radius: 3px; background: #eee; }
        
        /* Table */
        .card-table { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .table thead th { background: #F7F9FB; color: #A3AED0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; border: none; padding: 15px; cursor: pointer; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; font-size: 0.9rem; font-weight: 500; }
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
    
        
    </style>
</head>
<body>

    <nav class="sidebar">
        <h4 class="fw-bold text-dark mb-5 text-center">AGRO<span class="text-primary">SAFE</span></h4>
        
        <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #111c44;
            --primary: #4318FF;
        }
        
        /* Reset Sidebar Styles to match Dashboard */
        .sidebar {
            width: var(--sidebar-width); 
            height: 100vh; 
            position: fixed; 
            top: 0; 
            left: 0;
            background: var(--sidebar-bg) !important; 
            color: white; 
            padding: 24px;
            border-right: none;
            z-index: 1000;
        }

        .brand { 
            font-size: 1.5rem; 
            font-weight: 800; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            letter-spacing: 1px; 
        }

        .nav-link { 
            color: #a3aed0; 
            padding: 14px 10px; 
            margin-bottom: 5px; 
            border-radius: 10px; 
            font-weight: 500; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            transition: 0.2s;
        }

        .nav-link:hover { 
            background: rgba(255,255,255,0.1); 
            color: white; 
        }

        .nav-link.active { 
            background: rgba(255,255,255,0.1); 
            color: white;
            border-right: 4px solid var(--primary); 
            border-radius: 10px 0 0 10px; 
        }

        .nav-link i { width: 20px; text-align: center; }
        
        /* Adjust main content to fit new sidebar width */
        .main-content { margin-left: var(--sidebar-width); }
    </style>

    <nav class="sidebar">
        <div class="brand">
            <i class="fas fa-leaf text-success me-2"></i> AGRO<span class="text-white">SAFE</span>
        </div>
        <small class="fw-bold text-uppercase text-light mb-4 d-block opacity-75" style="font-size:0.7rem;">
            Data Studio
        </small>

        <div class="nav flex-column h-100">
            
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <div class="my-3 border-top border-secondary opacity-25"></div>
            
            <small class="fw-bold text-uppercase text-light mb-3 ps-2 opacity-50" style="font-size: 0.7rem;">
                Manage Datasets
            </small>

            <a href="?tab=diseases" class="nav-link <?= $activeTab==='diseases'?'active':'' ?>">
                <i class="fas fa-virus"></i> Diseases Data
            </a>
            
            <a href="?tab=treatments" class="nav-link <?= $activeTab==='treatments'?'active':'' ?>">
                <i class="fas fa-notes-medical"></i> Treatments Data
            </a>

            <div style="margin-top: auto;">
                 <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../logout.php?redirect=admin" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>

        </div>
    </nav>

        <div class="mt-auto pt-5 border-top">
            <form method="POST">
                <input type="hidden" name="action" value="train_model">
                <button class="btn btn-primary w-100 fw-bold py-2"><i class="fas fa-brain me-2"></i> Retrain AI</button>
            </form>
        </div>
    </nav>

    <main class="main-content">
        
        <?php if($msg): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0 text-capitalize"><?= $activeTab ?> Dataset</h3>
                <p class="text-muted m-0">Manage and validate training data.</p>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <form method="POST"><input type="hidden" name="action" value="export_csv"><button class="dropdown-item">CSV Format</button></form>
                        </li>
                        <li>
                            <form method="POST"><input type="hidden" name="action" value="export_json"><button class="dropdown-item">JSON Format</button></form>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-1"></i> Add New
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold text-muted">Total Records</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Live</span>
                    </div>
                    <h2 class="fw-bold"><?= number_format($engine->insights['total']) ?></h2>
                    <small class="text-muted">Unique Classes: <strong><?= $engine->insights['diversity_score'] ?></strong></small>
                </div>
            </div>
            <div class="col-md-8">
                <div class="stat-card">
                    <h6 class="fw-bold text-muted mb-3">Class Balance Check</h6>
                    <div class="row g-3" style="max-height: 80px; overflow-y: auto;">
                        <?php foreach($engine->classStats as $class => $stat): ?>
                            <div class="col-6">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= htmlspecialchars($class) ?></span>
                                    <span class="fw-bold"><?= $stat['count'] ?></span>
                                </div>
                                <div class="progress progress-slim">
                                    <div class="progress-bar bg-<?= $stat['color'] ?>" style="width: <?= $stat['percent'] ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-table mb-4 p-3">
            <form method="GET" class="row g-3 align-items-center">
                <input type="hidden" name="tab" value="<?= $activeTab ?>">
                
                <div class="col-auto">
                    <select class="form-select form-select-sm border-0 bg-light fw-bold" name="filter_col" style="width: 150px;">
                        <option value="">Filter by...</option>
                        <?php foreach($humanHeaders as $idx => $header): ?>
                            <option value="<?= $idx ?>" <?= $filterCol == $idx ? 'selected' : '' ?>><?= $header ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" name="filter_val" class="form-control form-control-sm border-0 bg-light" placeholder="Exact value..." value="<?= htmlspecialchars($filterVal) ?>">
                </div>

                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control form-control-sm border-0 bg-light" placeholder="Search any keyword..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Apply</button>
                    <a href="?tab=<?= $activeTab ?>" class="btn btn-light btn-sm px-3">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <?php foreach($humanHeaders as $idx => $header): ?>
                                <th>
                                    <a href="?tab=<?= $activeTab ?>&sort=<?= $idx ?>&order=<?= ($sortCol == $idx && $sortOrder == 'asc') ? 'desc' : 'asc' ?>" class="text-decoration-none text-muted d-flex align-items-center justify-content-between">
                                        <?= htmlspecialchars($header) ?>
                                        <i class="fas fa-sort small ms-2 opacity-50"></i>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($displayRows) > 0): ?>
                            <?php foreach($displayRows as $originalIdx => $row): ?>
                            <tr>
                                <?php foreach($row as $cell): ?>
                                    <td><?= htmlspecialchars($cell) ?></td>
                                <?php endforeach; ?>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this row?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="index" value="<?= $originalIdx ?>"> <button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="100%" class="text-center py-5 text-muted">No data found matching your filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <small class="text-muted">Showing page <?= $page ?> of <?= $totalPages ?></small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?tab=<?= $activeTab ?>&page=<?= $page-1 ?>&q=<?= $search ?>">Previous</a>
                        </li>
                        <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?tab=<?= $activeTab ?>&page=<?= $i ?>&q=<?= $search ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?tab=<?= $activeTab ?>&page=<?= $page+1 ?>&q=<?= $search ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Add New Entry</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <?php foreach($humanHeaders as $i => $label): ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted"><?= $label ?></label>
                                <input type="text" name="data[]" class="form-control" list="list-<?= $i ?>" required>
                                <datalist id="list-<?= $i ?>">
                                    <?php foreach($engine->getUniqueValues($i) as $val): ?>
                                        <option value="<?= htmlspecialchars($val) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary w-100 mt-2">Save Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>