<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Admit Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admit_enc'])) {
    $pid = $_POST['patient_id'];
    $doc = $_POST['doctor_id'];
    $bed_id = $_POST['bed_id']; // Using bed_id instead of manual entry
    $diag = $_POST['diagnosis'];

    // Get bed info
    $b_stmt = $pdo->prepare("SELECT ward_name, bed_number FROM beds WHERE id = ?");
    $b_stmt->execute([$bed_id]);
    $bed_info = $b_stmt->fetch();

    if ($bed_info) {
        $stmt = $pdo->prepare("INSERT INTO ipd_admissions (patient_id, doctor_id, admission_date, bed_number, ward_type, diagnosis, status) VALUES (?, ?, NOW(), ?, ?, ?, 'Admitted')");
        $stmt->execute([$pid, $doc, $bed_info['bed_number'], $bed_info['ward_name'], $diag]);
        
        $admit_id = $pdo->lastInsertId();

        // Update beds table
        $u_stmt = $pdo->prepare("UPDATE beds SET status = 'Occupied', patient_id = ? WHERE id = ?");
        $u_stmt->execute([$pid, $bed_id]);

        // Notification
        $p_stmt = $pdo->prepare("SELECT full_name FROM patients WHERE id = ?");
        $p_stmt->execute([$pid]);
        $p_name = $p_stmt->fetchColumn();
        
        $nStmt = $pdo->prepare("INSERT INTO notifications (type, message, for_role) VALUES ('IPD Admit', ?, 'All')");
        $nStmt->execute(["Patient {$p_name} admitted to {$bed_info['ward_name']} ({$bed_info['bed_number']})."]);
        
        if (function_exists('log_audit')) {
            log_audit($pdo, 'Admit', 'IPD', $admit_id, "Patient {$p_name} admitted to {$bed_info['ward_name']} ({$bed_info['bed_number']}).");
        }
    }
}

// Discharge Patient
if (isset($_GET['discharge_id'])) {
    $id = $_GET['discharge_id'];
    
    // Get admission details to free the bed
    $a_stmt = $pdo->prepare("SELECT patient_id, bed_number, ward_type FROM ipd_admissions WHERE id = ?");
    $a_stmt->execute([$id]);
    $adm = $a_stmt->fetch();

    if ($adm) {
        $stmt = $pdo->prepare("UPDATE ipd_admissions SET discharge_date = NOW(), status = 'Discharged' WHERE id = ?");
        $stmt->execute([$id]);

        $u_stmt = $pdo->prepare("UPDATE beds SET status = 'Available', patient_id = NULL WHERE bed_number = ? AND ward_name = ?");
        $u_stmt->execute([$adm['bed_number'], $adm['ward_type']]);

        // Notification
        $p_stmt = $pdo->prepare("SELECT full_name FROM patients WHERE id = ?");
        $p_stmt->execute([$adm['patient_id']]);
        $p_name = $p_stmt->fetchColumn();
        
        $nStmt = $pdo->prepare("INSERT INTO notifications (type, message, for_role) VALUES ('IPD Discharge', ?, 'All')");
        $nStmt->execute(["Patient {$p_name} discharged from {$adm['ward_type']}. Bed {$adm['bed_number']} is now available."]);
        
        if (function_exists('log_audit')) {
            log_audit($pdo, 'Discharge', 'IPD', $id, "Patient {$p_name} discharged from {$adm['ward_type']}. Bed {$adm['bed_number']} available.");
        }
    }
    echo "<script>window.location.href='ipd.php';</script>";
}

// Fetch Admissions
$sql = "SELECT i.*, p.full_name, p.mr_number, u.full_name as doctor_name FROM ipd_admissions i JOIN patients p ON i.patient_id = p.id JOIN users u ON i.doctor_id = u.id ORDER BY i.id DESC";
$admissions = $pdo->query($sql)->fetchAll();

// Fetch Doctors for Dropdown
$docs = $pdo->query("SELECT id, full_name FROM users WHERE role='Doctor'")->fetchAll();

// Seed beds if empty
$bed_count = $pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn();
if ($bed_count == 0) {
    $pdo->exec("INSERT INTO beds (ward_name, bed_number, status) VALUES 
        ('General Ward', 'GW-01', 'Available'),
        ('General Ward', 'GW-02', 'Available'),
        ('ICU', 'ICU-01', 'Available'),
        ('Private Room', 'PR-01', 'Available')
    ");
}

// Fetch available beds
$avail_beds = $pdo->query("SELECT * FROM beds WHERE status = 'Available'")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>IPD Management (Admissions)</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#admitModal"><i class="fas fa-procedures"></i> Admit New Patient</button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>MR No</th>
                        <th>Patient Name</th>
                        <th>Doctor</th>
                        <th>Ward/Bed</th>
                        <th>Admission Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admissions as $a): ?>
                    <tr>
                        <td><?php echo $a['mr_number']; ?></td>
                        <td><?php echo $a['full_name']; ?></td>
                        <td><?php echo $a['doctor_name']; ?></td>
                        <td><?php echo $a['ward_type'] . ' - ' . $a['bed_number']; ?></td>
                        <td><?php echo $a['admission_date']; ?></td>
                        <td><span class="badge bg-<?php echo($a['status'] == 'Admitted' ? 'danger' : 'success'); ?>"><?php echo $a['status']; ?></span></td>
                        <td>
                            <?php if ($a['status'] == 'Admitted'): ?>
                                <a href="ipd_dashboard.php?id=<?php echo $a['id']; ?>" class="btn btn-info btn-sm text-white me-1">Manage <i class="fas fa-arrow-right"></i></a>
                                <a href="?discharge_id=<?php echo $a['id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Discharge this patient?')">Discharge</a>
                            <?php
    else: ?>
                                <a href="ipd_dashboard.php?id=<?php echo $a['id']; ?>" class="btn btn-secondary btn-sm text-white me-1">View File</a>
                                <small><?php echo $a['discharge_date']; ?></small>
                            <?php
    endif; ?>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Admit Modal -->
<div class="modal fade" id="admitModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="admit_enc" value="1">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admit Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Patient ID (System ID)</label>
                        <input type="number" name="patient_id" class="form-control" required value="<?php echo $_GET['patient_id'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label>Doctor</label>
                        <select name="doctor_id" class="form-select" required>
                            <?php foreach ($docs as $d):
    echo "<option value='{$d['id']}'>{$d['full_name']}</option>";
endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label>Ward & Bed</label>
                            <select name="bed_id" class="form-select" required>
                                <option value="">-- Select Available Bed --</option>
                                <?php foreach ($avail_beds as $ab): ?>
                                    <option value="<?php echo $ab['id']; ?>"><?php echo htmlspecialchars($ab['ward_name'] . ' - ' . $ab['bed_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Diagnosis/Reason</label>
                        <textarea name="diagnosis" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Admit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
