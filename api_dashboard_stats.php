<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

try {
    $today = date('Y-m-d');
    
    // OPD Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE visit_date = ?");
    $stmt->execute([$today]);
    $opd_count = $stmt->fetchColumn();

    // New Patients Added
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $patient_count = $stmt->fetchColumn();

    // IPD Admitted
    $stmt = $pdo->query("SELECT COUNT(*) FROM ipd_admissions WHERE status = 'Admitted'");
    $ipd_count = $stmt->fetchColumn();

    // Today's Lab Patients
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT b.patient_id) FROM bill_items bi JOIN bills b ON bi.bill_id = b.id WHERE DATE(b.bill_date) = ? AND bi.report_status IN ('Pending', 'Completed')");
    $stmt->execute([$today]);
    $lab_patient_count = $stmt->fetchColumn() ?: 0;

    echo json_encode([
        'success' => true,
        'opd' => $opd_count,
        'lab' => $lab_patient_count,
        'ipd' => $ipd_count,
        'patients' => $patient_count
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
