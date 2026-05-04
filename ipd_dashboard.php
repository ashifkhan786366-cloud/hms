<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Validate Admission ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Invalid Admission ID. <a href='ipd.php'>Go Back</a></div></div>";
    require_once 'includes/footer.php';
    exit;
}

$admission_id = intval($_GET['id']);

// Fetch Admission & Patient Details
$sql = "SELECT i.*, p.full_name, p.mr_number, p.age, p.gender, u.full_name as doctor_name 
        FROM ipd_admissions i 
        JOIN patients p ON i.patient_id = p.id 
        JOIN users u ON i.doctor_id = u.id 
        WHERE i.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$admission_id]);
$adm = $stmt->fetch();

if (!$adm) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Admission not found. <a href='ipd.php'>Go Back</a></div></div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch or Initialize Patient Details (Tab 1)
$det_stmt = $pdo->prepare("SELECT * FROM ipd_patient_details WHERE admission_id = ?");
$det_stmt->execute([$admission_id]);
$details = $det_stmt->fetch();

if (!$details) {
    // Insert empty record
    $pdo->prepare("INSERT INTO ipd_patient_details (admission_id) VALUES (?)")->execute([$admission_id]);
    $details = ['fall_risk' => 0, 'is_diabetic' => 0, 'dnr_status' => 0, 'attendant_name' => '', 'attendant_relation' => '', 'attendant_contact' => '', 'expected_discharge_date' => '', 'drug_allergies' => '', 'surgery_history' => '', 'special_notes' => ''];
}

// Calculate days admitted
$admit_date = new DateTime($adm['admission_date']);
$now = new DateTime();
$days_admitted = $admit_date->diff($now)->days;
if ($days_admitted == 0) $days_admitted = 1;

?>

<style>
/* Dashboard Specific Styles - Daily Collection Report Theme */
.dashboard-header {
    background-color: var(--bs-light);
    border-bottom: 1px solid var(--bs-gray-300);
    padding: 15px 0;
    margin-bottom: 20px;
}
.stat-card-custom {
    color: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 15px;
}
.stat-card-custom h6 { margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; opacity: 0.9; }
.stat-card-custom h3 { margin-bottom: 0; font-weight: 700; }
.stat-green { background: linear-gradient(45deg, #2e7d32, #4caf50); }
.stat-blue { background: linear-gradient(45deg, #1565c0, #1e88e5); }
.stat-orange { background: linear-gradient(45deg, #e65100, #ff9800); }
.stat-red { background: linear-gradient(45deg, #c62828, #ef5350); }
.stat-purple { background: linear-gradient(45deg, #6a1b9a, #ab47bc); }
.stat-teal { background: linear-gradient(45deg, #00695c, #26a69a); }
.stat-grey { background: linear-gradient(45deg, #424242, #757575); }

/* Table Theme */
.table-custom-header thead th {
    background-color: #1a237e !important;
    color: white !important;
    font-weight: 600;
    border-bottom: none;
}
.table-custom-header tbody tr:nth-of-type(odd) { background-color: #f8f9fa; }
.table-custom-header tbody tr:hover { background-color: #e3f2fd; }

.tab-scroller {
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 20px;
    background: #fff;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 2px;
}
.tab-scroller .nav-tabs {
    flex-wrap: nowrap;
    border-bottom: none;
}
.tab-scroller .nav-link {
    white-space: nowrap;
    border: none;
    border-bottom: 3px solid transparent;
    color: #495057;
    font-weight: 600;
}
.tab-scroller .nav-link.active {
    border-color: #1565c0;
    color: #1565c0;
    background: none;
}
.abnormal-text { color: #d32f2f; font-weight: bold; }
.toast-container { position: fixed; top: 20px; right: 20px; z-index: 1055; }
.section-title {
    border-left: 5px solid #1565c0;
    padding-left: 10px;
    margin-bottom: 15px;
    font-weight: bold;
    color: #1a237e;
}
.note-card {
    border-left: 5px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 15px;
}
.note-Progress { border-left-color: #1565c0; }
.note-Consultation { border-left-color: #6a1b9a; }
.note-Procedure { border-left-color: #e65100; }
.shift-Morning { border-left-color: #ffb300; }
.shift-Evening { border-left-color: #fb8c00; }
.shift-Night { border-left-color: #3949ab; }
</style>

<!-- Toast Container for Auto-Save Notifications -->
<div class="toast-container"></div>

<!-- Quick Stats & Header -->
<div class="container-fluid dashboard-header shadow-sm">
    <div class="row align-items-center">
        <div class="col-md-5">
            <h4 class="mb-0 text-primary"><i class="fas fa-procedures"></i> <?php echo htmlspecialchars($adm['full_name']); ?></h4>
            <div class="text-muted mt-1">
                <strong>MR No:</strong> <?php echo $adm['mr_number']; ?> | 
                <strong>Age/Sex:</strong> <?php echo $adm['age'] . '/' . $adm['gender']; ?> | 
                <strong>Ward:</strong> <?php echo $adm['ward_type'] . ' - ' . $adm['bed_number']; ?>
            </div>
            <div class="text-muted">
                <strong>Doctor:</strong> <?php echo $adm['doctor_name']; ?> | 
                <strong>Admitted:</strong> <?php echo date('d-M-Y H:i', strtotime($adm['admission_date'])); ?>
            </div>
        </div>
        <div class="col-md-7">
            <div class="row">
                <div class="col-3">
                    <div class="stat-box">
                        <div class="text-muted small">Status</div>
                        <div class="stat-value">
                            <span class="badge bg-<?php echo ($adm['status'] == 'Admitted') ? 'danger' : 'success'; ?>" id="patientStatusBadge">
                                <?php echo $adm['status']; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-box">
                        <div class="text-muted small">Days Admitted</div>
                        <div class="stat-value"><?php echo $days_admitted; ?> Days</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-box">
                        <div class="text-muted small">Latest Vitals</div>
                        <div class="stat-value" id="quickVitals">--/--</div>
                    </div>
                </div>
                <div class="col-3 text-end">
                    <a href="ipd.php" class="btn btn-outline-secondary mt-2"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Horizontal Scrollable Tabs -->
    <div class="tab-scroller shadow-sm">
        <ul class="nav nav-tabs" id="ipdDashboardTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="tab-overview" data-bs-toggle="tab" data-bs-target="#content-overview" type="button" role="tab" onclick="loadTab('overview')"><i class="fas fa-info-circle"></i> 1. Overview</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-vitals" data-bs-toggle="tab" data-bs-target="#content-vitals" type="button" role="tab" onclick="loadTab('vitals')"><i class="fas fa-heartbeat"></i> 2. Vitals</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-meds" data-bs-toggle="tab" data-bs-target="#content-meds" type="button" role="tab" onclick="loadTab('meds')"><i class="fas fa-pills"></i> 3. Medications</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-iv" data-bs-toggle="tab" data-bs-target="#content-iv" type="button" role="tab" onclick="loadTab('iv')"><i class="fas fa-tint"></i> 4. IV Fluids</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-io" data-bs-toggle="tab" data-bs-target="#content-io" type="button" role="tab" onclick="loadTab('io')"><i class="fas fa-balance-scale"></i> 5. I/O Chart</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-drnotes" data-bs-toggle="tab" data-bs-target="#content-drnotes" type="button" role="tab" onclick="loadTab('drnotes')"><i class="fas fa-user-md"></i> 6. Doctor Notes</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-nursenotes" data-bs-toggle="tab" data-bs-target="#content-nursenotes" type="button" role="tab" onclick="loadTab('nursenotes')"><i class="fas fa-user-nurse"></i> 7. Nursing Notes</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-labs" data-bs-toggle="tab" data-bs-target="#content-labs" type="button" role="tab" onclick="loadTab('labs')"><i class="fas fa-flask"></i> 8. Lab/Rad</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-diet" data-bs-toggle="tab" data-bs-target="#content-diet" type="button" role="tab" onclick="loadTab('diet')"><i class="fas fa-utensils"></i> 9. Diet</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-pain" data-bs-toggle="tab" data-bs-target="#content-pain" type="button" role="tab" onclick="loadTab('pain')"><i class="fas fa-grimace"></i> 10. Pain</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-proc" data-bs-toggle="tab" data-bs-target="#content-proc" type="button" role="tab" onclick="loadTab('proc')"><i class="fas fa-scalpel"></i> 11. Procedures</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-blood" data-bs-toggle="tab" data-bs-target="#content-blood" type="button" role="tab" onclick="loadTab('blood')"><i class="fas fa-burn"></i> 12. Blood</button></li>
            <?php if ($adm['ward_type'] == 'ICU'): ?>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-vent" data-bs-toggle="tab" data-bs-target="#content-vent" type="button" role="tab" onclick="loadTab('vent')"><i class="fas fa-lungs"></i> 13. Ventilator</button></li>
            <?php endif; ?>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-dialysis" data-bs-toggle="tab" data-bs-target="#content-dialysis" type="button" role="tab" onclick="loadTab('dialysis')"><i class="fas fa-filter"></i> 14. Dialysis</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-history" data-bs-toggle="tab" data-bs-target="#content-history" type="button" role="tab" onclick="loadTab('history')"><i class="fas fa-history"></i> 15. History</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-discharge" data-bs-toggle="tab" data-bs-target="#content-discharge" type="button" role="tab" onclick="loadTab('discharge')"><i class="fas fa-file-export"></i> 16. Discharge</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-bill" data-bs-toggle="tab" data-bs-target="#content-bill" type="button" role="tab" onclick="loadTab('bill')"><i class="fas fa-file-invoice-dollar"></i> 17. Live Bill</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-finalbill" data-bs-toggle="tab" data-bs-target="#content-finalbill" type="button" role="tab" onclick="loadTab('finalbill')"><i class="fas fa-print"></i> 18. Final Print</button></li>
        </ul>
    </div>

    <!-- Tab Content Area -->
    <div class="tab-content" id="ipdDashboardContent">
        
        <!-- ================= TAB 1: OVERVIEW ================= -->
        <div class="tab-pane fade show active" id="content-overview" role="tabpanel">
            <form id="form-overview" onsubmit="event.preventDefault(); saveOverview();">
                <input type="hidden" name="action" value="save_overview">
                <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">Basic Admission Info</div>
                            <div class="card-body row">
                                <div class="col-md-12 mb-3">
                                    <label>Current Diagnosis</label>
                                    <input type="text" name="diagnosis" class="form-control fw-bold" value="<?php echo htmlspecialchars($adm['diagnosis']); ?>" onblur="saveOverview()">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Attendant Name</label>
                                    <input type="text" name="attendant_name" class="form-control" value="<?php echo htmlspecialchars($details['attendant_name'] ?? ''); ?>" onblur="saveOverview()">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Attendant Relation</label>
                                    <input type="text" name="attendant_relation" class="form-control" value="<?php echo htmlspecialchars($details['attendant_relation'] ?? ''); ?>" onblur="saveOverview()">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Attendant Contact</label>
                                    <input type="text" name="attendant_contact" class="form-control" value="<?php echo htmlspecialchars($details['attendant_contact'] ?? ''); ?>" onblur="saveOverview()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Expected Discharge Date</label>
                                    <input type="date" name="expected_discharge_date" class="form-control" value="<?php echo htmlspecialchars($details['expected_discharge_date'] ?? ''); ?>" onblur="saveOverview()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Patient Status (Update)</label>
                                    <select name="patient_status" class="form-select" onchange="saveOverview()">
                                        <option value="Admitted" <?php if($adm['status']=='Admitted') echo 'selected'; ?>>Stable (Admitted)</option>
                                        <option value="Critical" <?php if($adm['status']=='Critical') echo 'selected'; ?>>Critical</option>
                                        <option value="Improving" <?php if($adm['status']=='Improving') echo 'selected'; ?>>Improving</option>
                                        <option value="Guarded" <?php if($adm['status']=='Guarded') echo 'selected'; ?>>Guarded</option>
                                        <option value="Discharged" <?php if($adm['status']=='Discharged') echo 'selected'; ?>>Discharged</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow-sm border-danger mb-4">
                            <div class="card-header bg-danger text-white"><i class="fas fa-exclamation-triangle"></i> Allergy & Risk Flags</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="text-danger fw-bold">Drug Allergies</label>
                                    <textarea name="drug_allergies" class="form-control border-danger" rows="2" placeholder="e.g. Penicillin" onblur="saveOverview()"><?php echo htmlspecialchars($details['drug_allergies'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="fall_risk" id="fall_risk" value="1" <?php if(isset($details['fall_risk']) && $details['fall_risk']) echo 'checked'; ?> onchange="saveOverview()">
                                    <label class="form-check-label text-warning fw-bold" for="fall_risk">Fall Risk</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_diabetic" id="is_diabetic" value="1" <?php if(isset($details['is_diabetic']) && $details['is_diabetic']) echo 'checked'; ?> onchange="saveOverview()">
                                    <label class="form-check-label text-primary fw-bold" for="is_diabetic">Diabetic</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="dnr_status" id="dnr_status" value="1" <?php if(isset($details['dnr_status']) && $details['dnr_status']) echo 'checked'; ?> onchange="saveOverview()">
                                    <label class="form-check-label text-dark fw-bold" for="dnr_status">DNR Status (Do Not Resuscitate)</label>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Surgery History</label>
                                    <textarea name="surgery_history" class="form-control" rows="2" onblur="saveOverview()"><?php echo htmlspecialchars($details['surgery_history'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Special Notes</label>
                                    <textarea name="special_notes" class="form-control bg-warning bg-opacity-10" rows="3" onblur="saveOverview()"><?php echo htmlspecialchars($details['special_notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ================= TAB 2: VITALS ================= -->
        <div class="tab-pane fade" id="content-vitals" role="tabpanel">
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between">
                    <h4>Vital Signs Record</h4>
                    <button class="btn btn-primary" onclick="showAddVitalsModal()"><i class="fas fa-plus"></i> Add Vitals</button>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-custom-header" id="vitalsTable">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Shift</th>
                                    <th>HR (bpm)</th>
                                    <th>BP (mmHg)</th>
                                    <th>SpO2 (%)</th>
                                    <th>Temp (°F)</th>
                                    <th>RR (breaths/min)</th>
                                    <th>Blood Sugar</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody id="vitalsTbody">
                                <tr><td colspan="9" class="text-center">Loading vitals...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Vitals Chart (Placeholder) -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title">Vitals Trend (Last 7 Readings)</h5>
                    <div style="height: 250px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px dashed #ccc;">
                        <span class="text-muted">Chart Integration Pending (e.g. Chart.js)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Placeholders for other tabs (will be lazy loaded) -->
        <!-- ================= TAB 3: MEDICATIONS ================= -->
        <div class="tab-pane fade" id="content-meds" role="tabpanel">
            <div class="row mb-3">
                <div class="col-md-3"><div class="stat-card-custom stat-green"><h6>Active Medicines</h6><h3 id="stat-med-active">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-blue"><h6>Doses Given Today</h6><h3 id="stat-med-given">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-orange"><h6>Pending Doses</h6><h3 id="stat-med-pending">-</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-red"><h6>Stopped</h6><h3 id="stat-med-stopped">0</h3></div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0">Active Medications</h5>
                <button class="btn btn-primary" onclick="showAddMedModal()"><i class="fas fa-plus"></i> Add Medicine</button>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom-header mb-0" id="medsTable">
                            <thead>
                                <tr><th>Medicine</th><th>Dose</th><th>Route</th><th>Freq</th><th>Instructions</th><th>Start Date</th><th>By</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="medsTbody"><tr><td colspan="9" class="text-center">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <h5 class="section-title text-muted mt-4">Stopped Medications</h5>
            <div class="table-responsive"><table class="table table-sm text-muted text-decoration-line-through"><tbody id="stoppedMedsTbody"></tbody></table></div>
        </div>

        <!-- ================= TAB 4: IV FLUIDS ================= -->
        <div class="tab-pane fade" id="content-iv" role="tabpanel">
            <div class="row mb-3">
                <div class="col-md-3"><div class="stat-card-custom stat-blue"><h6>Running Fluids</h6><h3 id="stat-iv-running">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-green"><h6>Volume Today (ml)</h6><h3 id="stat-iv-vol">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-orange"><h6>Bottles Used</h6><h3 id="stat-iv-bottles">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-grey"><h6>Completed</h6><h3 id="stat-iv-completed">0</h3></div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0">IV Fluid Record</h5>
                <button class="btn btn-primary" onclick="showAddIvModal()"><i class="fas fa-plus"></i> Add IV Fluid</button>
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom-header mb-0">
                            <thead><tr><th>Bottle#</th><th>Fluid</th><th>Volume</th><th>Additive</th><th>Rate</th><th>Start</th><th>Status</th><th>By</th><th>Actions</th></tr></thead>
                            <tbody id="ivTbody"><tr><td colspan="9" class="text-center">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- ================= TAB 5: I/O CHART ================= -->
        <div class="tab-pane fade" id="content-io" role="tabpanel">
            <div class="row mb-3">
                <div class="col-md-3"><div class="stat-card-custom stat-blue"><h6>Total Intake Today (ml)</h6><h3 id="stat-io-in">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-green"><h6>Total Output Today (ml)</h6><h3 id="stat-io-out">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-purple"><h6>Net Balance</h6><h3 id="stat-io-net">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-orange"><h6>Urine Output</h6><h3 id="stat-io-urine">0</h3></div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0">Input/Output Chart</h5>
                <button class="btn btn-primary" onclick="showAddIoModal()"><i class="fas fa-plus"></i> Add I/O Record</button>
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom-header mb-0 text-center">
                            <thead>
                                <tr><th rowspan="2" class="align-middle">Date</th><th colspan="4">INTAKE (ml)</th><th colspan="6">OUTPUT (ml)</th><th rowspan="2" class="align-middle">Balance</th><th rowspan="2" class="align-middle">Shift</th><th rowspan="2" class="align-middle">By</th></tr>
                                <tr><th>Oral</th><th>IV</th><th>Other</th><th>Total IN</th><th>Urine</th><th>Vomit</th><th>NGT</th><th>Drain</th><th>Other</th><th>Total OUT</th></tr>
                            </thead>
                            <tbody id="ioTbody"><tr><td colspan="15">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= TAB 6: DOCTOR NOTES ================= -->
        <div class="tab-pane fade" id="content-drnotes" role="tabpanel">
            <div class="row mb-3">
                <div class="col-md-3"><div class="stat-card-custom stat-blue"><h6>Total Notes</h6><h3 id="stat-dr-total">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-green"><h6>Progress Notes</h6><h3 id="stat-dr-prog">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-purple"><h6>Consultation Notes</h6><h3 id="stat-dr-cons">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-orange"><h6>Today's Notes</h6><h3 id="stat-dr-today">0</h3></div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0">Doctor Notes</h5>
                <button class="btn btn-primary" onclick="showAddDrNoteModal()"><i class="fas fa-plus"></i> Add Doctor Note</button>
            </div>
            <div id="drNotesContainer">
                <div class="text-center p-3 text-muted">Loading notes...</div>
            </div>
        </div>

        <!-- ================= TAB 7: NURSING NOTES ================= -->
        <div class="tab-pane fade" id="content-nursenotes" role="tabpanel">
            <div class="row mb-3">
                <div class="col-md-3"><div class="stat-card-custom stat-green"><h6>Total Notes</h6><h3 id="stat-nr-total">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-blue"><h6>Today's Notes</h6><h3 id="stat-nr-today">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-orange"><h6>Morning Shift</h6><h3 id="stat-nr-morn">0</h3></div></div>
                <div class="col-md-3"><div class="stat-card-custom stat-purple"><h6>Evening/Night Shift</h6><h3 id="stat-nr-eve">0</h3></div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0">Nursing Notes</h5>
                <button class="btn btn-primary" onclick="showAddNurseNoteModal()"><i class="fas fa-plus"></i> Add Nursing Note</button>
            </div>
            <div id="nurseNotesContainer">
                <div class="text-center p-3 text-muted">Loading notes...</div>
            </div>
        </div>
        <div class="tab-pane fade" id="content-labs" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Labs & Radiology...</p></div></div>
        <div class="tab-pane fade" id="content-diet" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Diet Chart...</p></div></div>
        <div class="tab-pane fade" id="content-pain" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Pain Assessment...</p></div></div>
        <div class="tab-pane fade" id="content-proc" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Procedures...</p></div></div>
        <div class="tab-pane fade" id="content-blood" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Blood Transfusions...</p></div></div>
        <div class="tab-pane fade" id="content-vent" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Ventilator Record...</p></div></div>
        <div class="tab-pane fade" id="content-dialysis" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Dialysis Record...</p></div></div>
        <div class="tab-pane fade" id="content-history" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Medical History...</p></div></div>
        <div class="tab-pane fade" id="content-discharge" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Discharge Planning...</p></div></div>
        <div class="tab-pane fade" id="content-bill" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Live Bill...</p></div></div>
        <div class="tab-pane fade" id="content-finalbill" role="tabpanel"><div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading Final Print...</p></div></div>
        
    </div>
</div>

<!-- ================= MODALS ================= -->
<!-- Add Vitals Modal -->
<div class="modal fade" id="addVitalsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form-add-vitals" onsubmit="event.preventDefault(); submitVitals();">
            <input type="hidden" name="action" value="save_vitals">
            <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Vital Signs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="record_date" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Shift</label>
                        <select name="shift" class="form-select">
                            <option value="Morning">Morning (7am-2pm)</option>
                            <option value="Evening">Evening (2pm-9pm)</option>
                            <option value="Night">Night (9pm-7am)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label>Heart Rate (BPM)</label>
                        <input type="number" name="heart_rate" class="form-control" placeholder="60-100">
                    </div>
                    <div class="col-md-4">
                        <label>BP Systolic (mmHg)</label>
                        <input type="number" name="bp_systolic" class="form-control" placeholder="120">
                    </div>
                    <div class="col-md-4">
                        <label>BP Diastolic (mmHg)</label>
                        <input type="number" name="bp_diastolic" class="form-control" placeholder="80">
                    </div>
                    
                    <div class="col-md-4">
                        <label>SpO2 (%)</label>
                        <input type="number" name="spo2" class="form-control" placeholder="95-100">
                    </div>
                    <div class="col-md-4">
                        <label>Temperature (°F)</label>
                        <input type="number" step="0.1" name="temperature" class="form-control" placeholder="98.6">
                    </div>
                    <div class="col-md-4">
                        <label>Respiratory Rate</label>
                        <input type="number" name="respiratory_rate" class="form-control" placeholder="12-20">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Blood Sugar (mg/dL)</label>
                        <input type="number" name="blood_sugar" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label>Recorded By</label>
                        <input type="text" name="recorded_by" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Vitals</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Medicine Modal -->
<div class="modal fade" id="addMedModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form-add-med" onsubmit="event.preventDefault(); submitMedicine();">
            <input type="hidden" name="action" value="save_medicine">
            <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title">Prescribe Medicine</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label>Medicine Name <span class="text-danger">*</span></label><input type="text" name="medicine_name" class="form-control" required></div>
                    <div class="col-md-3"><label>Dose <span class="text-danger">*</span></label><input type="text" name="dose" class="form-control" placeholder="e.g. 500mg" required></div>
                    <div class="col-md-3"><label>Route</label><select name="route" class="form-select"><option>Oral</option><option>IV</option><option>IM</option><option>SC</option><option>Topical</option><option>Inhaler</option></select></div>
                    <div class="col-md-4"><label>Frequency</label><select name="frequency" class="form-select"><option>OD</option><option>BD</option><option>TDS</option><option>QID</option><option>SOS</option><option>Stat</option><option>Weekly</option></select></div>
                    <div class="col-md-4"><label>Start Date <span class="text-danger">*</span></label><input type="date" name="start_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="col-md-4"><label>End Date</label><input type="date" name="end_date" class="form-control"></div>
                    <div class="col-md-6"><label>Instructions</label><select name="instructions" class="form-select"><option>Any Time</option><option>Empty Stomach</option><option>After Food</option><option>Before Sleep</option></select></div>
                    <div class="col-md-6"><label>Prescribed By</label><input type="text" name="prescribed_by" class="form-control" value="<?php echo htmlspecialchars($adm['doctor_name']); ?>"></div>
                    <div class="col-12"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Medicine</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Add IV Fluid Modal -->
<div class="modal fade" id="addIvModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form-add-iv" onsubmit="event.preventDefault(); submitIvFluid();">
            <input type="hidden" name="action" value="save_iv_fluid">
            <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title">Start IV Fluid</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label>Fluid Name <span class="text-danger">*</span></label><select name="fluid_name" class="form-select" required><option value="NS 0.9%">NS 0.9%</option><option value="Ringer Lactate">Ringer Lactate</option><option value="DNS">DNS</option><option value="D5W">D5W</option><option value="Custom">Custom</option></select></div>
                    <div class="col-md-3"><label>Volume (ml)</label><input type="number" name="volume_ml" class="form-control" value="500"></div>
                    <div class="col-md-3"><label>Rate (ml/hr)</label><input type="number" name="rate_ml_hr" class="form-control"></div>
                    <div class="col-md-6"><label>Additives</label><input type="text" name="additive" class="form-control" placeholder="e.g. KCl 20mEq"></div>
                    <div class="col-md-6"><label>Bottle Number</label><input type="text" name="bottle_no" class="form-control" placeholder="Auto/Manual"></div>
                    <div class="col-md-6"><label>Start Date & Time</label><input type="datetime-local" name="start_time" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>"></div>
                    <div class="col-md-6"><label>Status</label><select name="status" class="form-select"><option>Running</option><option>On Hold</option><option>Completed</option></select></div>
                    <div class="col-12"><label>Given By</label><input type="text" name="given_by" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save IV Fluid</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Add I/O Modal -->
<div class="modal fade" id="addIoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form-add-io" onsubmit="event.preventDefault(); submitIoRecord();">
            <input type="hidden" name="action" value="save_io">
            <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add I/O Record</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label>Date <span class="text-danger">*</span></label><input type="date" name="record_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="col-md-4"><label>Shift <span class="text-danger">*</span></label><select name="shift" class="form-select" required><option value="Morning">Morning</option><option value="Evening">Evening</option><option value="Night">Night</option></select></div>
                        <div class="col-md-4"><label>Recorded By</label><input type="text" name="recorded_by" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="text-primary fw-bold"><i class="fas fa-sign-in-alt"></i> INTAKE (ml)</h6>
                            <div class="mb-2"><label>Oral Intake</label><input type="number" name="oral_ml" class="form-control" value="0"></div>
                            <div class="mb-2"><label>IV Fluid</label><input type="number" name="iv_ml" class="form-control" value="0"></div>
                            <div class="mb-2"><label>Other Intake</label><input type="number" name="other_intake_ml" class="form-control" value="0"></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger fw-bold"><i class="fas fa-sign-out-alt"></i> OUTPUT (ml)</h6>
                            <div class="row g-2">
                                <div class="col-6"><label>Urine</label><input type="number" name="urine_ml" class="form-control" value="0"></div>
                                <div class="col-6"><label>Vomiting</label><input type="number" name="vomit_ml" class="form-control" value="0"></div>
                                <div class="col-6"><label>NG Tube</label><input type="number" name="ngt_ml" class="form-control" value="0"></div>
                                <div class="col-6"><label>Drain</label><input type="number" name="drain_ml" class="form-control" value="0"></div>
                                <div class="col-12"><label>Other Output</label><input type="number" name="other_output_ml" class="form-control" value="0"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Record</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Add Doctor Note Modal -->
<div class="modal fade" id="addDrNoteModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form id="form-add-drnote" onsubmit="event.preventDefault(); submitDrNote();">
            <input type="hidden" name="action" value="save_drnote">
            <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Doctor Note (SOAP)</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-4"><label>Date & Time <span class="text-danger">*</span></label><input type="datetime-local" name="note_datetime" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>"></div>
                    <div class="col-md-4"><label>Doctor Name <span class="text-danger">*</span></label><input type="text" name="doctor_name" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label>Note Type <span class="text-danger">*</span></label><select name="note_type" class="form-select"><option>Daily Progress Note</option><option>Consultation Note</option><option>Procedure Note</option><option>ICU Review</option><option>Discharge Note</option></select></div>
                    
                    <div class="col-md-6"><label class="fw-bold text-primary">S - Subjective (Chief Complaint)</label><textarea name="subjective" class="form-control" rows="3"></textarea></div>
                    <div class="col-md-6"><label class="fw-bold text-success">O - Objective (Clinical Findings)</label><textarea name="objective" class="form-control" rows="3"></textarea></div>
                    <div class="col-md-6"><label class="fw-bold text-warning">A - Assessment (Diagnosis)</label><textarea name="assessment" class="form-control" rows="3"></textarea></div>
                    <div class="col-md-6"><label class="fw-bold text-danger">P - Plan (Orders/Treatment)</label><textarea name="plan" class="form-control" rows="3"></textarea></div>
                    
                    <div class="col-md-4"><label>Next Review Date</label><input type="date" name="next_review_date" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Note</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Add Nursing Note Modal -->
<div class="modal fade" id="addNurseNoteModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form id="form-add-nursenote" onsubmit="event.preventDefault(); submitNurseNote();">
            <input type="hidden" name="action" value="save_nursenote">
            <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Nursing Note</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-3"><label>Date & Time <span class="text-danger">*</span></label><input type="datetime-local" name="note_datetime" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>"></div>
                    <div class="col-md-3"><label>Nurse Name <span class="text-danger">*</span></label><input type="text" name="nurse_name" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>"></div>
                    <div class="col-md-2"><label>Shift <span class="text-danger">*</span></label><select name="shift" class="form-select"><option>Morning</option><option>Evening</option><option>Night</option></select></div>
                    <div class="col-md-2"><label>Condition</label><select name="patient_condition" class="form-select"><option>Stable</option><option>Improving</option><option>Guarded</option><option>Critical</option></select></div>
                    <div class="col-md-2"><label>Consciousness</label><select name="consciousness" class="form-select"><option>Alert</option><option>Confused</option><option>Drowsy</option><option>Unconscious</option></select></div>
                    
                    <div class="col-md-4"><label>Patient Complaints</label><textarea name="patient_complaints" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-4"><label>Observations</label><textarea name="observations" class="form-control" rows="2" required></textarea></div>
                    <div class="col-md-4"><label>Actions Taken</label><textarea name="actions_taken" class="form-control" rows="2"></textarea></div>
                    
                    <div class="col-12 mt-3"><h6 class="fw-bold border-bottom pb-2">Quick Vitals at Note Time</h6></div>
                    <div class="col-md-3"><label>BP</label><input type="text" name="bp" class="form-control" placeholder="120/80"></div>
                    <div class="col-md-3"><label>HR</label><input type="number" name="hr" class="form-control" placeholder="BPM"></div>
                    <div class="col-md-3"><label>Temp (°F)</label><input type="number" step="0.1" name="temp" class="form-control" placeholder="98.6"></div>
                    <div class="col-md-3"><label>SpO2 (%)</label><input type="number" name="spo2" class="form-control" placeholder="99"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Note</button></div>
            </div>
        </form>
    </div>
</div>

<script>
// --- Global Toast Notification ---
function showToast(message, type = 'success') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">
              <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
    `;
    const container = document.querySelector('.toast-container');
    container.innerHTML = toastHtml;
    setTimeout(() => { container.innerHTML = ''; }, 3000);
}

// --- Lazy Load Tabs ---
let loadedTabs = { 'overview': true }; // Overview is loaded on page load

function loadTab(tabName) {
    if (loadedTabs[tabName]) return; // Already loaded
    
    // For now, we only implement Vitals logic directly.
    // Future tabs will fetch HTML via AJAX from endpoints like `views/ipd_tabs/tab_meds.php`
    if (tabName === 'vitals') {
        fetchVitals();
        loadedTabs[tabName] = true;
    } else if (tabName === 'meds') {
        fetchMeds();
        loadedTabs[tabName] = true;
    } else if (tabName === 'iv') {
        fetchIvFluids();
        loadedTabs[tabName] = true;
    } else if (tabName === 'io') {
        fetchIoRecords();
        loadedTabs[tabName] = true;
    } else if (tabName === 'drnotes') {
        fetchDrNotes();
        loadedTabs[tabName] = true;
    } else if (tabName === 'nursenotes') {
        fetchNurseNotes();
        loadedTabs[tabName] = true;
    } else {
        // Placeholder for other tabs
        const contentDiv = document.getElementById(`content-${tabName}`);
        contentDiv.innerHTML = `<div class="text-center p-5 text-muted"><i class="fas fa-tools fa-3x mb-3"></i><br><h4>${tabName.toUpperCase()} Module</h4><p>Pending implementation in next phase.</p></div>`;
        loadedTabs[tabName] = true;
    }
}

// --- Tab 1: Overview Functions ---
function saveOverview() {
    const form = document.getElementById('form-overview');
    const formData = new FormData(form);
    // Handle unchecked checkboxes
    if (!form.fall_risk.checked) formData.append('fall_risk', '0');
    if (!form.is_diabetic.checked) formData.append('is_diabetic', '0');
    if (!form.dnr_status.checked) formData.append('dnr_status', '0');

    fetch('api_ipd_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Overview saved automatically');
            // Update UI if status changed
            if (data.new_status) {
                const badge = document.getElementById('patientStatusBadge');
                badge.innerText = data.new_status;
                badge.className = `badge bg-${data.new_status === 'Admitted' ? 'danger' : 'success'}`;
            }
        } else {
            showToast(data.message || 'Error saving overview', 'danger');
        }
    }).catch(err => {
        console.error(err);
        showToast('Server error', 'danger');
    });
}

// --- Tab 2: Vitals Functions ---
let vitalsModal;

function showAddVitalsModal() {
    if(!vitalsModal) vitalsModal = new bootstrap.Modal(document.getElementById('addVitalsModal'));
    document.getElementById('form-add-vitals').reset();
    document.querySelector('#form-add-vitals [name="record_date"]').value = new Date().toISOString().slice(0, 16);
    vitalsModal.show();
}

function submitVitals() {
    const form = document.getElementById('form-add-vitals');
    const formData = new FormData(form);
    
    fetch('api_ipd_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Vitals saved successfully');
            vitalsModal.hide();
            fetchVitals(); // Reload table
        } else {
            showToast(data.message || 'Error saving vitals', 'danger');
        }
    }).catch(err => {
        console.error(err);
        showToast('Server error', 'danger');
    });
}

function fetchVitals() {
    const admId = <?php echo $admission_id; ?>;
    fetch(`api_ipd_dashboard.php?action=get_vitals&admission_id=${admId}`)
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('vitalsTbody');
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No vital records found.</td></tr>';
            return;
        }
        
        let html = '';
        data.forEach((v, index) => {
            // Highlighting abnormal values
            const hrClass = (v.heart_rate > 100 || v.heart_rate < 60) ? 'abnormal-text' : '';
            const spo2Class = (v.spo2 < 94) ? 'abnormal-text' : '';
            const tempClass = (v.temperature > 99.5) ? 'abnormal-text' : '';
            
            // Update quick stats with latest (first in array)
            if (index === 0) {
                document.getElementById('quickVitals').innerText = `BP: ${v.bp_systolic || '-'}/${v.bp_diastolic || '-'} | HR: ${v.heart_rate || '-'} | Temp: ${v.temperature || '-'}°F`;
            }

            html += `<tr>
                <td>${v.record_date}</td>
                <td>${v.shift}</td>
                <td class="${hrClass}">${v.heart_rate || '-'}</td>
                <td>${v.bp_systolic || '-'}/${v.bp_diastolic || '-'}</td>
                <td class="${spo2Class}">${v.spo2 || '-'}</td>
                <td class="${tempClass}">${v.temperature || '-'}</td>
                <td>${v.respiratory_rate || '-'}</td>
                <td>${v.blood_sugar || '-'}</td>
                <td>${v.recorded_by}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }).catch(err => {
        console.error(err);
        document.getElementById('vitalsTbody').innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading vitals.</td></tr>';
    });
}

// --- Tab 3: Medications Functions ---
let medModal;
function showAddMedModal() { 
    if(!medModal) medModal = new bootstrap.Modal(document.getElementById('addMedModal'));
    document.getElementById('form-add-med').reset(); 
    document.querySelector('#form-add-med [name="start_date"]').value = new Date().toISOString().slice(0, 10); 
    medModal.show(); 
}

function submitMedicine() {
    const form = document.getElementById('form-add-med');
    fetch('api_ipd_dashboard.php', { method: 'POST', body: new FormData(form) })
    .then(res => res.json()).then(data => {
        if(data.status==='success'){ showToast('Medicine added'); medModal.hide(); fetchMeds(); } else showToast(data.message, 'danger');
    });
}

function fetchMeds() {
    fetch(`api_ipd_dashboard.php?action=get_medicines&admission_id=<?php echo $admission_id; ?>`)
    .then(res => res.json()).then(data => {
        let actHtml = '', stopHtml = '', actCount=0, stopCount=0, givenToday=0;
        data.forEach(m => {
            if(m.status === 'Active') {
                actCount++;
                givenToday += parseInt(m.given_today);
                actHtml += `<tr>
                    <td><strong>${m.medicine_name}</strong></td><td>${m.dose}</td><td>${m.route}</td>
                    <td><span class="badge bg-secondary">${m.frequency}</span></td><td>${m.instructions}</td><td>${m.start_date}</td><td>${m.prescribed_by}</td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>
                        <button class="btn btn-sm btn-success" title="Mark Given" onclick="markMedGiven(${m.id})"><i class="fas fa-check"></i></button>
                        <button class="btn btn-sm btn-warning" title="Stop" onclick="stopMed(${m.id})"><i class="fas fa-stop-circle"></i></button>
                        <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteMed(${m.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <tr class="bg-light"><td colspan="9" class="p-1"><small class="text-muted ms-3">Last given: ${m.last_given || 'Never'} (Today: ${m.given_today})</small></td></tr>`;
            } else {
                stopCount++;
                stopHtml += `<tr><td>${m.medicine_name} - ${m.dose}</td><td>Stopped</td></tr>`;
            }
        });
        document.getElementById('medsTbody').innerHTML = actHtml || '<tr><td colspan="9" class="text-center">No active medicines</td></tr>';
        document.getElementById('stoppedMedsTbody').innerHTML = stopHtml || '<tr><td class="text-center">No stopped medicines</td></tr>';
        document.getElementById('stat-med-active').innerText = actCount;
        document.getElementById('stat-med-stopped').innerText = stopCount;
        document.getElementById('stat-med-given').innerText = givenToday;
    });
}

function markMedGiven(id) {
    if(!confirm("Record dose given?")) return;
    const formData = new FormData(); formData.append('action','mark_med_given'); formData.append('id', id); formData.append('given_by', '<?php echo $_SESSION['full_name']; ?>');
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success'){showToast('Dose recorded');fetchMeds();} });
}
function stopMed(id) {
    if(!confirm("Stop this medicine?")) return;
    const formData = new FormData(); formData.append('action','stop_medicine'); formData.append('id', id);
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success'){showToast('Medicine stopped');fetchMeds();} });
}
function deleteMed(id) {
    if(!confirm("Delete this record permanently?")) return;
    const formData = new FormData(); formData.append('action','delete_medicine'); formData.append('id', id);
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success'){showToast('Medicine deleted', 'danger');fetchMeds();} });
}

// --- Tab 4: IV Fluids Functions ---
let ivModal;
function showAddIvModal() { 
    if(!ivModal) ivModal = new bootstrap.Modal(document.getElementById('addIvModal'));
    document.getElementById('form-add-iv').reset(); 
    document.querySelector('#form-add-iv [name="start_time"]').value = new Date().toISOString().slice(0, 16); 
    ivModal.show(); 
}

function submitIvFluid() {
    const form = document.getElementById('form-add-iv');
    fetch('api_ipd_dashboard.php', { method: 'POST', body: new FormData(form) })
    .then(res => res.json()).then(data => {
        if(data.status==='success'){ showToast('IV Fluid added'); ivModal.hide(); fetchIvFluids(); } else showToast(data.message, 'danger');
    });
}

function fetchIvFluids() {
    fetch(`api_ipd_dashboard.php?action=get_iv_fluids&admission_id=<?php echo $admission_id; ?>`)
    .then(res => res.json()).then(data => {
        let html = '', running=0, completed=0, vol=0, bottles=0;
        data.forEach(iv => {
            bottles++;
            if(iv.status==='Running') running++;
            if(iv.status==='Completed') completed++;
            vol += parseInt(iv.volume_ml||0);
            
            let bclass = iv.status==='Running' ? 'success' : (iv.status==='Completed'?'secondary':'warning');
            html += `<tr>
                <td>${iv.bottle_no || '-'}</td><td><strong>${iv.fluid_name}</strong></td><td>${iv.volume_ml}ml</td>
                <td>${iv.additive||'-'}</td><td>${iv.rate_ml_hr||'-'}</td><td>${iv.start_time}</td>
                <td><span class="badge bg-${bclass}">${iv.status}</span></td><td>${iv.given_by}</td>
                <td>
                    ${iv.status==='Running' ? `<button class="btn btn-sm btn-success" onclick="updateIvStatus(${iv.id}, 'Completed')"><i class="fas fa-check"></i></button>` : ''}
                    <button class="btn btn-sm btn-danger" onclick="deleteIv(${iv.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });
        document.getElementById('ivTbody').innerHTML = html || '<tr><td colspan="9" class="text-center">No IV fluids recorded</td></tr>';
        document.getElementById('stat-iv-running').innerText = running;
        document.getElementById('stat-iv-completed').innerText = completed;
        document.getElementById('stat-iv-vol').innerText = vol;
        document.getElementById('stat-iv-bottles').innerText = bottles;
    });
}

function updateIvStatus(id, status) {
    if(!confirm(`Mark fluid as ${status}?`)) return;
    const formData = new FormData(); formData.append('action','update_iv_status'); formData.append('id', id); formData.append('status', status);
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success'){showToast(`Status updated`);fetchIvFluids();} });
}
function deleteIv(id) {
    if(!confirm("Delete this record permanently?")) return;
    const formData = new FormData(); formData.append('action','delete_iv_fluid'); formData.append('id', id);
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success'){showToast('Record deleted', 'danger');fetchIvFluids();} });
}

// --- Tab 5: I/O Chart Functions ---
let ioModal;
function showAddIoModal() { 
    if(!ioModal) ioModal = new bootstrap.Modal(document.getElementById('addIoModal'));
    document.getElementById('form-add-io').reset(); 
    document.querySelector('#form-add-io [name="record_date"]').value = new Date().toISOString().slice(0, 10);
    ioModal.show(); 
}

function submitIoRecord() {
    const form = document.getElementById('form-add-io');
    fetch('api_ipd_dashboard.php', { method: 'POST', body: new FormData(form) })
    .then(res => res.json()).then(data => {
        if(data.status==='success'){ showToast('I/O Record added'); ioModal.hide(); fetchIoRecords(); } else showToast(data.message, 'danger');
    });
}

function fetchIoRecords() {
    fetch(`api_ipd_dashboard.php?action=get_io_records&admission_id=<?php echo $admission_id; ?>`)
    .then(res => res.json()).then(data => {
        let html = '', inTot=0, outTot=0, urineTot=0;
        data.forEach(r => {
            let totalIn = parseInt(r.oral_ml) + parseInt(r.iv_ml) + parseInt(r.other_intake_ml);
            let totalOut = parseInt(r.urine_ml) + parseInt(r.vomit_ml) + parseInt(r.ngt_ml) + parseInt(r.drain_ml) + parseInt(r.other_output_ml);
            let bal = totalIn - totalOut;
            let balClass = bal >= 0 ? 'text-primary fw-bold' : 'text-danger fw-bold';
            
            // Stats for today only
            let rDate = new Date(r.record_date).toISOString().slice(0,10);
            let tDate = new Date().toISOString().slice(0,10);
            if(rDate === tDate) {
                inTot += totalIn; outTot += totalOut; urineTot += parseInt(r.urine_ml);
            }

            html += `<tr>
                <td class="align-middle">${r.record_date}</td>
                <td>${r.oral_ml}</td><td>${r.iv_ml}</td><td>${r.other_intake_ml}</td><td class="bg-light fw-bold">${totalIn}</td>
                <td>${r.urine_ml}</td><td>${r.vomit_ml}</td><td>${r.ngt_ml}</td><td>${r.drain_ml}</td><td>${r.other_output_ml}</td><td class="bg-light fw-bold">${totalOut}</td>
                <td class="${balClass}">${bal > 0 ? '+'+bal : bal}</td>
                <td>${r.shift}</td>
                <td>${r.recorded_by} <button class="btn btn-sm text-danger" onclick="deleteIo(${r.id})"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        });
        document.getElementById('ioTbody').innerHTML = html || '<tr><td colspan="15" class="text-center">No I/O records found</td></tr>';
        document.getElementById('stat-io-in').innerText = inTot;
        document.getElementById('stat-io-out').innerText = outTot;
        document.getElementById('stat-io-net').innerText = inTot - outTot;
        document.getElementById('stat-io-urine').innerText = urineTot;
    });
}
function deleteIo(id) {
    if(!confirm("Delete this I/O record?")) return;
    const formData = new FormData(); formData.append('action','delete_io'); formData.append('id', id);
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success') fetchIoRecords(); });
}

// --- Tab 6: Doctor Notes Functions ---
let drNoteModal;
function showAddDrNoteModal() { 
    if(!drNoteModal) drNoteModal = new bootstrap.Modal(document.getElementById('addDrNoteModal'));
    document.getElementById('form-add-drnote').reset(); 
    document.querySelector('#form-add-drnote [name="note_datetime"]').value = new Date().toISOString().slice(0, 16);
    drNoteModal.show(); 
}

function submitDrNote() {
    const form = document.getElementById('form-add-drnote');
    fetch('api_ipd_dashboard.php', { method: 'POST', body: new FormData(form) })
    .then(res => res.json()).then(data => {
        if(data.status==='success'){ showToast('Doctor Note added'); drNoteModal.hide(); fetchDrNotes(); } else showToast(data.message, 'danger');
    });
}

function fetchDrNotes() {
    fetch(`api_ipd_dashboard.php?action=get_dr_notes&admission_id=<?php echo $admission_id; ?>`)
    .then(res => res.json()).then(data => {
        let html = '', total=0, prog=0, cons=0, today=0;
        let tDate = new Date().toISOString().slice(0,10);
        
        data.forEach(n => {
            total++;
            if(n.note_type.includes('Progress')) prog++;
            if(n.note_type.includes('Consult')) cons++;
            if(n.note_datetime.startsWith(tDate)) today++;
            
            let typeClass = n.note_type.includes('Progress') ? 'note-Progress' : (n.note_type.includes('Consult') ? 'note-Consultation' : 'note-Procedure');
            
            html += `<div class="card note-card ${typeClass}">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div><strong>${n.doctor_name}</strong> <span class="badge bg-secondary ms-2">${n.note_type}</span></div>
                    <div class="text-muted small">${n.note_datetime}</div>
                </div>
                <div class="card-body py-2">
                    ${n.subjective ? `<p class="mb-1"><strong>S:</strong> ${n.subjective}</p>` : ''}
                    ${n.objective ? `<p class="mb-1"><strong>O:</strong> ${n.objective}</p>` : ''}
                    ${n.assessment ? `<p class="mb-1"><strong>A:</strong> ${n.assessment}</p>` : ''}
                    ${n.plan ? `<p class="mb-1"><strong>P:</strong> ${n.plan}</p>` : ''}
                </div>
                <div class="card-footer bg-white border-0 text-end py-1">
                    <button class="btn btn-sm text-danger p-0" onclick="deleteDrNote(${n.id})"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>`;
        });
        document.getElementById('drNotesContainer').innerHTML = html || '<div class="text-center p-3 text-muted">No doctor notes found</div>';
        document.getElementById('stat-dr-total').innerText = total;
        document.getElementById('stat-dr-prog').innerText = prog;
        document.getElementById('stat-dr-cons').innerText = cons;
        document.getElementById('stat-dr-today').innerText = today;
    });
}
function deleteDrNote(id) {
    if(!confirm("Delete this note?")) return;
    const formData = new FormData(); formData.append('action','delete_drnote'); formData.append('id', id);
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success') fetchDrNotes(); });
}

// --- Tab 7: Nursing Notes Functions ---
let nurseNoteModal;
function showAddNurseNoteModal() { 
    if(!nurseNoteModal) nurseNoteModal = new bootstrap.Modal(document.getElementById('addNurseNoteModal'));
    document.getElementById('form-add-nursenote').reset(); 
    document.querySelector('#form-add-nursenote [name="note_datetime"]').value = new Date().toISOString().slice(0, 16);
    nurseNoteModal.show(); 
}

function submitNurseNote() {
    const form = document.getElementById('form-add-nursenote');
    fetch('api_ipd_dashboard.php', { method: 'POST', body: new FormData(form) })
    .then(res => res.json()).then(data => {
        if(data.status==='success'){ showToast('Nursing Note added'); nurseNoteModal.hide(); fetchNurseNotes(); } else showToast(data.message, 'danger');
    });
}

function fetchNurseNotes() {
    fetch(`api_ipd_dashboard.php?action=get_nurse_notes&admission_id=<?php echo $admission_id; ?>`)
    .then(res => res.json()).then(data => {
        let html = '', total=0, today=0, morn=0, eve=0;
        let tDate = new Date().toISOString().slice(0,10);
        
        data.forEach(n => {
            total++;
            if(n.note_datetime.startsWith(tDate)) today++;
            if(n.shift === 'Morning') morn++;
            if(n.shift === 'Evening' || n.shift === 'Night') eve++;
            
            let shiftClass = 'shift-' + n.shift;
            let condBadge = n.patient_condition === 'Critical' ? 'danger' : (n.patient_condition === 'Stable' ? 'success' : 'warning');
            let vitalsStr = [];
            if(n.bp) vitalsStr.push('BP: '+n.bp);
            if(n.hr) vitalsStr.push('HR: '+n.hr);
            if(n.temp) vitalsStr.push('Temp: '+n.temp);
            if(n.spo2) vitalsStr.push('SpO2: '+n.spo2);
            let vitalsHtml = vitalsStr.length > 0 ? `<div class="bg-light p-1 mt-2 small text-muted"><strong>Vitals:</strong> ${vitalsStr.join(' | ')}</div>` : '';

            html += `<div class="card note-card ${shiftClass}">
                <div class="card-header bg-white d-flex justify-content-between align-items-center pb-1">
                    <div>
                        <strong>${n.nurse_name}</strong> <span class="text-muted small">(${n.shift} Shift)</span>
                        <span class="badge bg-${condBadge} ms-2">${n.patient_condition}</span>
                        <span class="badge bg-secondary">${n.consciousness}</span>
                    </div>
                    <div class="text-muted small">${n.note_datetime}</div>
                </div>
                <div class="card-body py-2">
                    ${n.patient_complaints ? `<p class="mb-1 text-danger"><strong>Complaints:</strong> ${n.patient_complaints}</p>` : ''}
                    <p class="mb-1"><strong>Observations:</strong> ${n.observations}</p>
                    ${n.actions_taken ? `<p class="mb-1 text-primary"><strong>Actions:</strong> ${n.actions_taken}</p>` : ''}
                    ${vitalsHtml}
                </div>
                <div class="card-footer bg-white border-0 text-end py-1">
                    <button class="btn btn-sm text-danger p-0" onclick="deleteNurseNote(${n.id})"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>`;
        });
        document.getElementById('nurseNotesContainer').innerHTML = html || '<div class="text-center p-3 text-muted">No nursing notes found</div>';
        document.getElementById('stat-nr-total').innerText = total;
        document.getElementById('stat-nr-today').innerText = today;
        document.getElementById('stat-nr-morn').innerText = morn;
        document.getElementById('stat-nr-eve').innerText = eve;
    });
}
function deleteNurseNote(id) {
    if(!confirm("Delete this nursing note?")) return;
    const formData = new FormData(); formData.append('action','delete_nursenote'); formData.append('id', id);
    fetch('api_ipd_dashboard.php', { method: 'POST', body: formData }).then(res=>res.json()).then(data=>{ if(data.status==='success') fetchNurseNotes(); });
}
</script>

<?php require_once 'includes/footer.php'; ?>
