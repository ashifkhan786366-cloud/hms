<?php
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

// RBAC: Only Admin or Super Admin can view audit logs
check_role(['Admin', 'Super Admin']);

// Log that the admin viewed the audit log
if (!isset($_SESSION['audit_view_logged'])) {
    log_audit($pdo, 'View Audit Log', 'System', null, 'Admin viewed the audit log page');
    $_SESSION['audit_view_logged'] = true;
}

// ─── Export Logic ─────────────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    $export = $_GET['export'];
    
    // Re-build query without pagination for export
    $where  = [];
    $params = [];
    
    if (!empty($_GET['module'])) { $where[] = "module = ?"; $params[] = $_GET['module']; }
    if (!empty($_GET['action'])) { $where[] = "action LIKE ?"; $params[] = "%{$_GET['action']}%"; }
    if (!empty($_GET['user']))   { $where[] = "username LIKE ?"; $params[] = "%{$_GET['user']}%"; }
    if (!empty($_GET['role']))   { $where[] = "role = ?"; $params[] = $_GET['role']; }
    if (!empty($_GET['ip']))     { $where[] = "ip_address LIKE ?"; $params[] = "%{$_GET['ip']}%"; }
    
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to']   ?? '';
    
    if ($from) { $where[] = "DATE(created_at) >= ?"; $params[] = $from; }
    if ($to)   { $where[] = "DATE(created_at) <= ?"; $params[] = $to; }
    
    $where_sql = $where ? "WHERE " . implode(' AND ', $where) : "";
    $sort_col  = $_GET['sort'] ?? 'created_at';
    $sort_dir  = (isset($_GET['dir']) && $_GET['dir'] == 'asc') ? 'ASC' : 'DESC';
    
    // Allowed sort columns
    $allowed_sort = ['id', 'created_at', 'username', 'role', 'module', 'action', 'ip_address'];
    if (!in_array($sort_col, $allowed_sort)) $sort_col = 'created_at';
    
    $sql = "SELECT id, created_at, username, role, module, action, record_id, description, ip_address 
            FROM audit_log $where_sql ORDER BY $sort_col $sort_dir";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $export_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($export === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=audit_log_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Date Time', 'User', 'Role', 'Module', 'Action', 'Record ID', 'Description', 'IP Address']);
        foreach ($export_data as $row) {
            fputcsv($output, [
                $row['id'], $row['created_at'], $row['username'], $row['role'], 
                $row['module'], $row['action'], $row['record_id'], $row['description'], $row['ip_address']
            ]);
        }
        fclose($output);
        exit;
    }
    
    if ($export === 'pdf') {
        if (file_exists('libs/tcpdf/tcpdf.php')) {
            require_once('libs/tcpdf/tcpdf.php');
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('Audit Log Export');
            $pdf->SetMargins(10, 10, 10);
            $pdf->AddPage('L');
            $pdf->SetFont('helvetica', '', 9);
            
            $html = '<h2>Audit Log Export - ' . date('Y-m-d H:i') . '</h2>';
            $html .= '<table border="1" cellpadding="4">
                <tr style="background-color:#f0f0f0;font-weight:bold;">
                    <th width="5%">ID</th>
                    <th width="15%">Date Time</th>
                    <th width="12%">User</th>
                    <th width="10%">Role</th>
                    <th width="12%">Module</th>
                    <th width="15%">Action</th>
                    <th width="20%">Description</th>
                    <th width="11%">IP</th>
                </tr>';
            foreach ($export_data as $row) {
                $html .= '<tr>
                    <td>'.$row['id'].'</td>
                    <td>'.$row['created_at'].'</td>
                    <td>'.$row['username'].'</td>
                    <td>'.$row['role'].'</td>
                    <td>'.$row['module'].'</td>
                    <td>'.$row['action'].'</td>
                    <td>'.$row['description'].'</td>
                    <td>'.$row['ip_address'].'</td>
                </tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('audit_log_' . date('Y-m-d') . '.pdf', 'D');
            exit;
        } else {
            die("TCPDF library not found. Please install it in libs/tcpdf.");
        }
    }
}

// ─── Regular Page Load ────────────────────────────────────────────────────────

// Filters
$filter_module = clean_input($_GET['module'] ?? '');
$filter_action = clean_input($_GET['action'] ?? '');
$filter_user   = clean_input($_GET['user'] ?? '');
$filter_role   = clean_input($_GET['role'] ?? '');
$filter_ip     = clean_input($_GET['ip'] ?? '');
$filter_from   = clean_input($_GET['from'] ?? date('Y-m-d', strtotime('-7 days')));
$filter_to     = clean_input($_GET['to'] ?? date('Y-m-d'));

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) && is_numeric($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$offset = ($page - 1) * $per_page;

// Sorting
$sort_col = clean_input($_GET['sort'] ?? 'created_at');
$sort_dir = (isset($_GET['dir']) && $_GET['dir'] == 'asc') ? 'ASC' : 'DESC';
$allowed_sort = ['id', 'created_at', 'username', 'role', 'module', 'action', 'ip_address'];
if (!in_array($sort_col, $allowed_sort)) $sort_col = 'created_at';

// Build Query
$where  = [];
$params = [];

if ($filter_module) { $where[] = "module = ?"; $params[] = $filter_module; }
if ($filter_action) { $where[] = "action LIKE ?"; $params[] = "%$filter_action%"; }
if ($filter_user)   { $where[] = "username LIKE ?"; $params[] = "%$filter_user%"; }
if ($filter_role)   { $where[] = "role = ?"; $params[] = $filter_role; }
if ($filter_ip)     { $where[] = "ip_address LIKE ?"; $params[] = "%$filter_ip%"; }
if ($filter_from)   { $where[] = "DATE(created_at) >= ?"; $params[] = $filter_from; }
if ($filter_to)     { $where[] = "DATE(created_at) <= ?"; $params[] = $filter_to; }

$where_sql = $where ? "WHERE " . implode(' AND ', $where) : "";

// Get Total Count
$count_sql = "SELECT COUNT(*) FROM audit_log $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get Records
$sql = "SELECT * FROM audit_log $where_sql ORDER BY $sort_col $sort_dir LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown Options
$modules = $pdo->query("SELECT DISTINCT module FROM audit_log WHERE module IS NOT NULL ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
$roles   = $pdo->query("SELECT DISTINCT role FROM audit_log WHERE role IS NOT NULL ORDER BY role")->fetchAll(PDO::FETCH_COLUMN);

// Dashboard Cards Stats
$today = date('Y-m-d');
$stats_today_actions = $pdo->query("SELECT COUNT(*) FROM audit_log WHERE DATE(created_at) = '$today'")->fetchColumn();
$stats_today_logins = $pdo->query("SELECT COUNT(*) FROM audit_log WHERE action LIKE '%login%' AND DATE(created_at) = '$today'")->fetchColumn();
$active_row = $pdo->query("SELECT username, COUNT(*) as c FROM audit_log WHERE DATE(created_at) = '$today' AND username IS NOT NULL GROUP BY username ORDER BY c DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$stats_active_user = $active_row ? htmlspecialchars($active_row['username']) . " ({$active_row['c']})" : 'None';
$last_action = $pdo->query("SELECT created_at FROM audit_log ORDER BY created_at DESC LIMIT 1")->fetchColumn();
$stats_last_action = $last_action ? date('d-M-Y H:i', strtotime($last_action)) : 'N/A';

require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Dashboard Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-chart-line"></i> Today's Actions</h6>
                    <h3 class="mb-0"><?= number_format($stats_today_actions) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-sign-in-alt"></i> Today's Logins</h6>
                    <h3 class="mb-0"><?= number_format($stats_today_logins) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-user-clock"></i> Most Active User (Today)</h6>
                    <h4 class="mb-0"><?= $stats_active_user ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-history"></i> Last Action Time</h6>
                    <h4 class="mb-0"><?= $stats_last_action ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-shield-alt text-danger"></i> System Audit Log</h4>
        <div>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-file-csv"></i> CSV</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" class="btn btn-outline-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body pb-2">
            <!-- Quick Filters -->
            <div class="mb-3 d-flex gap-2 align-items-center">
                <strong class="small text-muted me-2">Quick:</strong>
                <button type="button" class="btn btn-sm btn-light border" onclick="applyQuickFilter('today')">Today</button>
                <button type="button" class="btn btn-sm btn-light border" onclick="applyQuickFilter('7days')">Last 7 Days</button>
                <button type="button" class="btn btn-sm btn-light border" onclick="applyQuickFilter('30days')">Last 30 Days</button>
                <button type="button" class="btn btn-sm btn-light border" onclick="applyQuickFilter('thismonth')">This Month</button>
            </div>
            
            <form method="GET" class="row g-2 align-items-end" id="filterForm">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_col) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($sort_dir) ?>">
                
                <div class="col-md-2">
                    <label class="form-label small fw-bold">From</label>
                    <input type="date" id="filterFrom" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">To</label>
                    <input type="date" id="filterTo" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_to) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Module</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($modules as $m): if(!$m) continue; ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $filter_module === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Role</label>
                    <select name="role" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($roles as $r): if(!$r) continue; ?>
                            <option value="<?= htmlspecialchars($r) ?>" <?= $filter_role === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Action</label>
                    <input type="text" name="action" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_action) ?>" placeholder="e.g. Create">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">User</label>
                    <input type="text" name="user" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_user) ?>" placeholder="Username">
                </div>
                <div class="col-md-2 mt-2">
                    <label class="form-label small fw-bold">IP Address</label>
                    <input type="text" name="ip" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_ip) ?>" placeholder="IP Address">
                </div>
                <div class="col-md-2 mt-2">
                    <label class="form-label small fw-bold">Per Page</label>
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                <div class="col-md-4 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-50"><i class="fas fa-search"></i> Filter</button>
                    <a href="audit_log.php" class="btn btn-secondary btn-sm w-50"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Log Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
            <span class="small fw-bold">Showing <?= count($logs) ?> of <?= number_format($total_records) ?> records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle small">
                    <thead class="table-dark">
                        <tr>
                            <?php 
                            $cols = [
                                'created_at' => 'Date & Time', 
                                'username' => 'User', 
                                'role' => 'Role', 
                                'module' => 'Module', 
                                'action' => 'Action', 
                                'ip_address' => 'IP'
                            ];
                            foreach($cols as $col_key => $col_name): 
                                $is_active = ($sort_col === $col_key);
                                $new_dir = ($is_active && $sort_dir === 'ASC') ? 'desc' : 'asc';
                                $icon = $is_active ? ($sort_dir === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
                            ?>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['sort' => $col_key, 'dir' => $new_dir])) ?>" class="text-white text-decoration-none">
                                    <?= $col_name ?> <i class="fas <?= $icon ?> ms-1"></i>
                                </a>
                            </th>
                            <?php endforeach; ?>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): 
                                // Color Code Badges
                                $action_badge = 'secondary';
                                $action_lower = strtolower($log['action']);
                                if (strpos($action_lower, 'create') !== false || strpos($action_lower, 'add') !== false || strpos($action_lower, 'register') !== false) $action_badge = 'success';
                                elseif (strpos($action_lower, 'update') !== false || strpos($action_lower, 'edit') !== false || strpos($action_lower, 'modify') !== false) $action_badge = 'primary';
                                elseif (strpos($action_lower, 'delete') !== false || strpos($action_lower, 'remove') !== false) $action_badge = 'danger';
                                elseif (strpos($action_lower, 'login') !== false || strpos($action_lower, 'logout') !== false) $action_badge = 'warning text-dark';
                                elseif (strpos($action_lower, 'view') !== false) $action_badge = 'info text-dark';
                            ?>
                            <tr>
                                <td style="white-space: nowrap;"><?= date('d-M-Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($log['role'] ?? 'N/A') ?></span></td>
                                <td><?= htmlspecialchars($log['module'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-<?= $action_badge ?>"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td class="font-monospace text-muted"><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-2" 
                                        onclick="showDetails('<?= htmlspecialchars(addslashes($log['action'])) ?>', '<?= htmlspecialchars(addslashes($log['description'])) ?>', '<?= htmlspecialchars(addslashes($log['record_id'])) ?>', '<?= $log['created_at'] ?>')">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486803.png" alt="No data" style="width: 80px; opacity: 0.3" class="mb-3">
                                    <br><h5>No audit entries found</h5>
                                    <p class="small">Try adjusting your filters or date range.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white pb-0">
            <nav>
                <ul class="pagination pagination-sm justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Prev</a>
                    </li>
                    <?php 
                    $start_p = max(1, $page - 2);
                    $end_p = min($total_pages, $page + 2);
                    for ($i = $start_p; $i <= $end_p; $i++): 
                    ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-info-circle text-primary"></i> Log Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body small">
        <table class="table table-bordered">
            <tr><th width="30%" class="bg-light">Action</th><td id="m_action"></td></tr>
            <tr><th class="bg-light">Time</th><td id="m_time"></td></tr>
            <tr><th class="bg-light">Record ID</th><td id="m_record" class="font-monospace"></td></tr>
            <tr><th class="bg-light">Description</th><td id="m_desc" style="white-space: pre-wrap;"></td></tr>
        </table>
      </div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function applyQuickFilter(type) {
    let from = document.getElementById('filterFrom');
    let to = document.getElementById('filterTo');
    let today = new Date();
    
    let formatDate = (d) => {
        let month = '' + (d.getMonth() + 1), day = '' + d.getDate(), year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    };

    if (type === 'today') {
        from.value = formatDate(today);
        to.value = formatDate(today);
    } else if (type === '7days') {
        let past = new Date();
        past.setDate(today.getDate() - 7);
        from.value = formatDate(past);
        to.value = formatDate(today);
    } else if (type === '30days') {
        let past = new Date();
        past.setDate(today.getDate() - 30);
        from.value = formatDate(past);
        to.value = formatDate(today);
    } else if (type === 'thismonth') {
        let start = new Date(today.getFullYear(), today.getMonth(), 1);
        from.value = formatDate(start);
        to.value = formatDate(today);
    }
    document.getElementById('filterForm').submit();
}

function showDetails(action, desc, record, time) {
    document.getElementById('m_action').innerText = action;
    document.getElementById('m_time').innerText = time;
    document.getElementById('m_record').innerText = record || 'N/A';
    document.getElementById('m_desc').innerText = desc || 'No additional details provided.';
    var myModal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    myModal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
