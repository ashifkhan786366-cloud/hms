<?php
require_once 'config/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    die("Patient ID not specified.");
}

$pid = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$pid]);
$p = $stmt->fetch();

if (!$p)
    die("Patient not found.");

// Fetch History
$app_stmt = $pdo->prepare("SELECT a.*, u.full_name as doctor_name FROM appointments a JOIN users u ON a.doctor_id = u.id WHERE a.patient_id = ? ORDER BY a.visit_date DESC");
$app_stmt->execute([$pid]);
$history = $app_stmt->fetchAll();

$bill_stmt = $pdo->prepare("SELECT * FROM bills WHERE patient_id = ? ORDER BY bill_date DESC");
$bill_stmt->execute([$pid]);
$bills = $bill_stmt->fetchAll();

// Fetch Lab History
$lab_stmt = $pdo->prepare("
    SELECT bi.*, b.bill_date 
    FROM bill_items bi 
    JOIN bills b ON bi.bill_id = b.id 
    WHERE b.patient_id = ? AND bi.report_status IN ('Pending', 'Completed') 
    ORDER BY b.bill_date DESC
");
$lab_stmt->execute([$pid]);
$labs = $lab_stmt->fetchAll();

// Fetch IPD History
$ipd_stmt = $pdo->prepare("SELECT * FROM ipd_admissions WHERE patient_id = ? ORDER BY admission_date DESC");
$ipd_stmt->execute([$pid]);
$ipd_history = $ipd_stmt->fetchAll();

// Build Timeline Array
$timeline = [];
foreach ($history as $h) {
    $timeline[] = [
        'date' => $h['visit_date'],
        'type' => 'OPD Visit',
        'icon' => 'fa-stethoscope',
        'color' => 'success',
        'desc' => "Consulted Dr. {$h['doctor_name']} for {$h['symptoms']}."
    ];
}
foreach ($ipd_history as $ih) {
    $timeline[] = [
        'date' => date('Y-m-d', strtotime($ih['admission_date'])),
        'type' => 'IPD Admission',
        'icon' => 'fa-procedures',
        'color' => 'warning',
        'desc' => "Admitted to {$ih['ward_bed']} under {$ih['consultant']}."
    ];
}
foreach ($labs as $l) {
    $timeline[] = [
        'date' => date('Y-m-d', strtotime($l['bill_date'])),
        'type' => 'Lab Test',
        'icon' => 'fa-flask',
        'color' => 'info',
        'desc' => "Test: {$l['service_name']} - Status: {$l['report_status']}."
    ];
}
usort($timeline, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Add Follow-Up Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_followup'])) {
    $f_date = $_POST['followup_date'];
    $f_note = $_POST['notes'];
    $stmt = $pdo->prepare("INSERT INTO follow_ups (patient_id, doctor_id, followup_date, notes, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->execute([$pid, $_SESSION['user_id'], $f_date, $f_note]);
    // Refresh to show new data
    header("Location: patient_view.php?id=$pid");
    exit;
}

// Fetch Follow-Ups
$fu_stmt = $pdo->prepare("SELECT f.*, u.full_name as doctor_name FROM follow_ups f LEFT JOIN users u ON f.doctor_id = u.id WHERE f.patient_id = ? ORDER BY f.followup_date DESC");
$fu_stmt->execute([$pid]);
$follow_ups = $fu_stmt->fetchAll();

// Add Prescription Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_prescription'])) {
    $apt_id = $_POST['appointment_id'];
    $meds = $_POST['medicine_name']; // Array
    $dosages = $_POST['dosage'];
    $durations = $_POST['duration'];
    $notes = $_POST['notes'];

    $p_stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_id, appointment_id, medicine_name, dosage, duration, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    for ($i = 0; $i < count($meds); $i++) {
        if (!empty(trim($meds[$i]))) {
            $p_stmt->execute([
                $pid,
                $_SESSION['user_id'],
                $apt_id,
                $meds[$i],
                $dosages[$i] ?? '',
                $durations[$i] ?? '',
                $notes[$i] ?? ''
            ]);
        }
    }
    header("Location: patient_view.php?id=$pid");
    exit;
}
?>

<div class="container-fluid mt-4">
    <!-- Patient Profile Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if ($p['photo_path']): ?>
                        <img src="<?php echo $p['photo_path']; ?>" class="img-fluid rounded-circle" style="max-height: 120px;">
                    <?php
else: ?>
                        <i class="fas fa-user-circle fa-6x text-secondary"></i>
                    <?php
endif; ?>
                </div>
                <div class="col-md-8">
                    <h2 class="mb-1"><?php echo $p['full_name']; ?> <small class="text-muted fs-5">(<?php echo $p['gender']; ?>, <?php echo $p['age']; ?> Y)</small></h2>
                    <p class="mb-1"><strong>MR No:</strong> <span class="text-primary"><?php echo $p['mr_number']; ?></span> | <strong>Phone:</strong> <?php echo $p['phone']; ?></p>
                    <p class="mb-1"><strong>Address:</strong> <?php echo $p['address']; ?></p>
                </div>
                <div class="col-md-2 text-end">
                    <a href="opd.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-success mb-2 w-100"><i class="fas fa-stethoscope"></i> New OPD</a>
                    <a href="ipd.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-warning w-100"><i class="fas fa-procedures"></i> Admit IPD</a>
                    <button class="btn btn-info w-100 mt-2 text-white" data-bs-toggle="modal" data-bs-target="#followupModal"><i class="fas fa-calendar-alt"></i> Set Follow-Up</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Follow Up Modal -->
    <div class="modal fade" id="followupModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST">
                <input type="hidden" name="add_followup" value="1">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Set Follow-Up Reminder</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Follow-Up Date <span class="text-danger">*</span></label>
                            <input type="date" name="followup_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label>Notes / Reason</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Review reports, check BP, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info text-white">Save Reminder</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs for EMR -->
    <ul class="nav nav-tabs" id="emrTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="visits-tab" data-bs-toggle="tab" href="#visits" role="tab"><i class="fas fa-stethoscope"></i> OPD Visits</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ipd-tab" data-bs-toggle="tab" href="#ipd" role="tab"><i class="fas fa-procedures"></i> IPD History</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="lab-tab" data-bs-toggle="tab" href="#lab" role="tab"><i class="fas fa-flask"></i> Lab Investigations</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="bills-tab" data-bs-toggle="tab" href="#bills" role="tab"><i class="fas fa-file-invoice-dollar"></i> Billing History</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="followup-tab" data-bs-toggle="tab" href="#followups" role="tab"><i class="fas fa-calendar-check"></i> Follow-Ups</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="timeline-tab" data-bs-toggle="tab" href="#timeline" role="tab"><i class="fas fa-stream"></i> Timeline</a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-3 bg-white" id="emrTabContent">
        <!-- Visits Tab -->
        <div class="tab-pane fade show active" id="visits" role="tabpanel">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Token</th>
                        <th>Doctor</th>
                        <th>Symptoms</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?php echo $h['visit_date']; ?></td>
                        <td><?php echo $h['token_number']; ?></td>
                        <td><?php echo $h['doctor_name']; ?></td>
                        <td><?php echo $h['symptoms']; ?></td>
                        <td><?php echo $h['status']; ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="opd_print.php?id=<?php echo $h['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Print OPD Slip"><i class="fas fa-file-medical"></i> Slip</a>
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#rxModal<?php echo $h['id']; ?>" title="Add Prescription"><i class="fas fa-plus"></i> Rx</button>
                                <a href="print_prescription.php?id=<?php echo $h['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Print Prescription"><i class="fas fa-print"></i> Rx</a>
                            </div>

                            <!-- Rx Modal -->
                            <div class="modal fade" id="rxModal<?php echo $h['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <form method="POST">
                                        <input type="hidden" name="add_prescription" value="1">
                                        <input type="hidden" name="appointment_id" value="<?php echo $h['id']; ?>">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Digital Prescription (<?php echo $h['visit_date']; ?>)</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body" id="rx-wrapper-<?php echo $h['id']; ?>">
                                                <div class="row mb-2 rx-row">
                                                    <div class="col-4"><input type="text" name="medicine_name[]" class="form-control" placeholder="Medicine Name" required></div>
                                                    <div class="col-3"><input type="text" name="dosage[]" class="form-control" placeholder="Dosage (e.g. 1-0-1)"></div>
                                                    <div class="col-2"><input type="text" name="duration[]" class="form-control" placeholder="Days"></div>
                                                    <div class="col-3"><input type="text" name="notes[]" class="form-control" placeholder="Notes (After food)"></div>
                                                </div>
                                            </div>
                                            <div class="modal-footer d-flex justify-content-between">
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="addRxRow(<?php echo $h['id']; ?>)">+ Add Medicine</button>
                                                <button type="submit" class="btn btn-info text-white">Save Prescription</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Bills Tab -->
        <div class="tab-pane fade" id="bills" role="tabpanel">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bills) > 0): ?>
                        <?php foreach ($bills as $b):
                            $isPending = in_array($b['payment_status'] ?? '', ['Pending', 'Partial']);
                            $balanceDue = (float)($b['net_amount'] ?? 0) - (float)($b['paid_amount'] ?? 0);
                        ?>
                        <tr class="<?= $isPending ? 'table-warning' : '' ?>">
                            <td><strong><?= htmlspecialchars($b['bill_number']) ?></strong></td>
                            <td><?= !empty($b['bill_date']) ? date('d/m/Y', strtotime($b['bill_date'])) : '—' ?></td>
                            <td>₹<?= number_format((float)$b['net_amount'], 2) ?></td>
                            <td>₹<?= number_format((float)($b['paid_amount'] ?? 0), 2) ?></td>
                            <td class="<?= $balanceDue > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                ₹<?= number_format($balanceDue, 2) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($b['payment_status'] === 'Paid') ? 'success' : (($b['payment_status'] === 'Partial') ? 'warning text-dark' : 'danger') ?>">
                                    <?= htmlspecialchars($b['payment_status'] ?? 'Pending') ?>
                                </span>
                            </td>
                            <td class="d-flex gap-1 flex-wrap">
                                <a href="bill_print.php?id=<?= $b['id'] ?>" target="_blank"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print"></i> Invoice
                                </a>
                                 <a href="bill_modify.php?id=<?= $b['id'] ?>"
                                    class="btn btn-sm btn-warning"
                                    title="Modify this bill — change items, amounts, payment">
                                     <i class="fas fa-edit"></i> Modify
                                 </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php
                        // Total due footer row
                        $totalDue = array_sum(array_map(fn($b) =>
                            max(0, (float)($b['net_amount'] ?? 0) - (float)($b['paid_amount'] ?? 0)), $bills));
                        if ($totalDue > 0): ?>
                        <tr class="table-danger">
                            <td colspan="4" class="text-end fw-bold">Total Outstanding Balance:</td>
                            <td class="fw-bold text-danger">₹<?= number_format($totalDue, 2) ?></td>
                            <td colspan="2"></td>
                        </tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No billing history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <!-- IPD History Tab -->
        <div class="tab-pane fade" id="ipd" role="tabpanel">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Admission Date</th>
                        <th>Discharge Date</th>
                        <th>Ward/Bed</th>
                        <th>Consultant</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($ipd_history) > 0): ?>
                        <?php foreach ($ipd_history as $ih): ?>
                        <tr>
                            <td><?php echo $ih['admission_date']; ?></td>
                            <td><?php echo $ih['discharge_date'] ?: '-'; ?></td>
                            <td><?php echo htmlspecialchars($ih['ward_bed']); ?></td>
                            <td><?php echo htmlspecialchars($ih['consultant']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($ih['status'] == 'Discharged') ? 'secondary' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($ih['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No IPD history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Lab Investigations Tab -->
        <div class="tab-pane fade" id="lab" role="tabpanel">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Test Name</th>
                        <th>Result</th>
                        <th>Report Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($labs) > 0): ?>
                        <?php foreach ($labs as $l): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($l['bill_date'])); ?></td>
                            <td><?php echo htmlspecialchars($l['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($l['lab_result']) ?: '<span class="text-muted">Not uploaded</span>'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($l['report_status'] == 'Completed') ? 'success' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($l['report_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No lab investigations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Follow-Ups Tab -->
        <div class="tab-pane fade" id="followups" role="tabpanel">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Set Date</th>
                        <th>Follow-Up Date</th>
                        <th>Doctor</th>
                        <th>Notes</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($follow_ups) > 0): ?>
                        <?php foreach ($follow_ups as $f): ?>
                        <tr class="<?php echo ($f['followup_date'] == date('Y-m-d') && $f['status'] == 'Pending') ? 'table-warning' : ''; ?>">
                            <td><?php echo date('d-M-Y', strtotime($f['created_at'])); ?></td>
                            <td><strong><?php echo date('d-M-Y', strtotime($f['followup_date'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($f['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($f['notes']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($f['status'] == 'Completed') ? 'success' : 'warning text-dark'; ?>">
                                    <?php echo htmlspecialchars($f['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No follow-ups found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Timeline Tab -->
        <div class="tab-pane fade" id="timeline" role="tabpanel">
            <div class="accordion" id="timelineAccordion">
                <?php if (count($timeline) > 0): ?>
                    <?php 
                    $i = 0;
                    foreach ($timeline as $item): 
                        $i++;
                        $collapseId = "collapse" . $i;
                    ?>
                    <div class="accordion-item mb-2 border rounded">
                        <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>">
                                <div class="d-flex align-items-center w-100">
                                    <div class="bg-<?php echo $item['color']; ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas <?php echo $item['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo $item['type']; ?></h6>
                                        <small class="text-muted"><?php echo date('d M Y', strtotime($item['date'])); ?></small>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" data-bs-parent="#timelineAccordion">
                            <div class="accordion-body bg-light">
                                <?php echo htmlspecialchars($item['desc']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center p-4 text-muted">No timeline events found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function addRxRow(id) {
    const wrapper = document.getElementById('rx-wrapper-' + id);
    const div = document.createElement('div');
    div.className = 'row mb-2 rx-row';
    div.innerHTML = `
        <div class="col-4"><input type="text" name="medicine_name[]" class="form-control" placeholder="Medicine Name" required></div>
        <div class="col-3"><input type="text" name="dosage[]" class="form-control" placeholder="Dosage"></div>
        <div class="col-2"><input type="text" name="duration[]" class="form-control" placeholder="Days"></div>
        <div class="col-2"><input type="text" name="notes[]" class="form-control" placeholder="Notes"></div>
        <div class="col-1"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>
    `;
    wrapper.appendChild(div);
}
</script>

<?php require_once 'includes/footer.php'; ?>
