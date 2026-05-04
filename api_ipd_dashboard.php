<?php
/**
 * API for IPD Dashboard
 * Handles AJAX requests for saving and fetching tab data.
 */
require_once 'includes/auth_check.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // --- TAB 1: OVERVIEW ---
        case 'save_overview':
            $adm_id = intval($_POST['admission_id']);
            $att_name = $_POST['attendant_name'] ?? '';
            $att_rel = $_POST['attendant_relation'] ?? '';
            $att_contact = $_POST['attendant_contact'] ?? '';
            $exp_discharge = !empty($_POST['expected_discharge_date']) ? $_POST['expected_discharge_date'] : null;
            $allergies = $_POST['drug_allergies'] ?? '';
            $fall = isset($_POST['fall_risk']) ? intval($_POST['fall_risk']) : 0;
            $diabetic = isset($_POST['is_diabetic']) ? intval($_POST['is_diabetic']) : 0;
            $dnr = isset($_POST['dnr_status']) ? intval($_POST['dnr_status']) : 0;
            $surg = $_POST['surgery_history'] ?? '';
            $notes = $_POST['special_notes'] ?? '';
            
            // Update details
            $sql = "UPDATE ipd_patient_details SET 
                    attendant_name = ?, attendant_relation = ?, attendant_contact = ?, 
                    expected_discharge_date = ?, drug_allergies = ?, fall_risk = ?, 
                    is_diabetic = ?, dnr_status = ?, surgery_history = ?, special_notes = ? 
                    WHERE admission_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$att_name, $att_rel, $att_contact, $exp_discharge, $allergies, $fall, $diabetic, $dnr, $surg, $notes, $adm_id]);
            
            // Update Diagnosis and Status in ipd_admissions
            $new_status = $_POST['patient_status'] ?? 'Admitted';
            $diagnosis = $_POST['diagnosis'] ?? '';
            
            $uStmt = $pdo->prepare("UPDATE ipd_admissions SET status = ?, diagnosis = ? WHERE id = ?");
            $uStmt->execute([$new_status, $diagnosis, $adm_id]);
            
            echo json_encode(['status' => 'success', 'new_status' => $new_status]);
            break;

        // --- TAB 2: VITALS ---
        case 'save_vitals':
            $adm_id = intval($_POST['admission_id']);
            $date = $_POST['record_date'];
            $hr = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : null;
            $sys = !empty($_POST['bp_systolic']) ? intval($_POST['bp_systolic']) : null;
            $dia = !empty($_POST['bp_diastolic']) ? intval($_POST['bp_diastolic']) : null;
            $spo2 = !empty($_POST['spo2']) ? intval($_POST['spo2']) : null;
            $temp = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
            $rr = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;
            $sugar = !empty($_POST['blood_sugar']) ? intval($_POST['blood_sugar']) : null;
            $shift = $_POST['shift'];
            $rec_by = $_POST['recorded_by'];

            $sql = "INSERT INTO ipd_vitals (admission_id, record_date, heart_rate, bp_systolic, bp_diastolic, spo2, temperature, respiratory_rate, blood_sugar, shift, recorded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$adm_id, $date, $hr, $sys, $dia, $spo2, $temp, $rr, $sugar, $shift, $rec_by]);
            
            echo json_encode(['status' => 'success']);
            break;

        case 'get_vitals':
            $adm_id = intval($_GET['admission_id']);
            $stmt = $pdo->prepare("SELECT * FROM ipd_vitals WHERE admission_id = ? ORDER BY record_date DESC");
            $stmt->execute([$adm_id]);
            $vitals = $stmt->fetchAll();
            
            // Format dates
            foreach($vitals as &$v) {
                $v['record_date'] = date('d-M-y H:i', strtotime($v['record_date']));
            }
            
            echo json_encode($vitals);
            break;

        // --- TAB 3: MEDICATIONS ---
        case 'save_medicine':
            $adm_id = intval($_POST['admission_id']);
            $med = $_POST['medicine_name'];
            $dose = $_POST['dose'];
            $route = $_POST['route'];
            $freq = $_POST['frequency'];
            $start = $_POST['start_date'];
            $end = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $inst = $_POST['instructions'];
            $pres = $_POST['prescribed_by'];
            $notes = $_POST['notes'];

            $sql = "INSERT INTO ipd_medications (admission_id, medicine_name, dose, route, frequency, start_date, end_date, instructions, prescribed_by, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$adm_id, $med, $dose, $route, $freq, $start, $end, $inst, $pres, $notes]);
            echo json_encode(['status' => 'success']);
            break;

        case 'get_medicines':
            $adm_id = intval($_GET['admission_id']);
            $stmt = $pdo->prepare("SELECT m.*, 
                                    (SELECT COUNT(*) FROM ipd_med_given WHERE medication_id = m.id AND DATE(given_at) = CURDATE()) as given_today,
                                    (SELECT given_at FROM ipd_med_given WHERE medication_id = m.id ORDER BY given_at DESC LIMIT 1) as last_given
                                   FROM ipd_medications m WHERE admission_id = ? ORDER BY status ASC, id DESC");
            $stmt->execute([$adm_id]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'mark_med_given':
            $id = intval($_POST['id']);
            $by = $_POST['given_by'];
            $stmt = $pdo->prepare("INSERT INTO ipd_med_given (medication_id, given_at, given_by) VALUES (?, NOW(), ?)");
            $stmt->execute([$id, $by]);
            echo json_encode(['status' => 'success']);
            break;

        case 'stop_medicine':
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE ipd_medications SET status = 'Stopped', end_date = CURDATE() WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'delete_medicine':
            $id = intval($_POST['id']);
            $pdo->prepare("DELETE FROM ipd_med_given WHERE medication_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM ipd_medications WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        // --- TAB 4: IV FLUIDS ---
        case 'save_iv_fluid':
            $adm_id = intval($_POST['admission_id']);
            $fluid = $_POST['fluid_name'];
            $vol = !empty($_POST['volume_ml']) ? intval($_POST['volume_ml']) : null;
            $rate = !empty($_POST['rate_ml_hr']) ? intval($_POST['rate_ml_hr']) : null;
            $add = $_POST['additive'];
            $bot = $_POST['bottle_no'];
            $start = $_POST['start_time'];
            $status = $_POST['status'];
            $by = $_POST['given_by'];

            $sql = "INSERT INTO ipd_iv_fluids (admission_id, fluid_name, volume_ml, additive, rate_ml_hr, bottle_no, start_time, status, given_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$adm_id, $fluid, $vol, $add, $rate, $bot, $start, $status, $by]);
            echo json_encode(['status' => 'success']);
            break;

        case 'get_iv_fluids':
            $adm_id = intval($_GET['admission_id']);
            $stmt = $pdo->prepare("SELECT * FROM ipd_iv_fluids WHERE admission_id = ? ORDER BY id DESC");
            $stmt->execute([$adm_id]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'update_iv_status':
            $id = intval($_POST['id']);
            $status = $_POST['status'];
            $end = ($status == 'Completed') ? " , end_time = NOW()" : "";
            $stmt = $pdo->prepare("UPDATE ipd_iv_fluids SET status = ? $end WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'delete_iv_fluid':
            $id = intval($_POST['id']);
            $pdo->prepare("DELETE FROM ipd_iv_fluids WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        // --- TAB 5: I/O CHART ---
        case 'save_io':
            $adm_id = intval($_POST['admission_id']);
            $date = $_POST['record_date'];
            $shift = $_POST['shift'];
            $oral = intval($_POST['oral_ml']);
            $iv = intval($_POST['iv_ml']);
            $oth_in = intval($_POST['other_intake_ml']);
            $urine = intval($_POST['urine_ml']);
            $vomit = intval($_POST['vomit_ml']);
            $ngt = intval($_POST['ngt_ml']);
            $drain = intval($_POST['drain_ml']);
            $oth_out = intval($_POST['other_output_ml']);
            $by = $_POST['recorded_by'];

            $sql = "INSERT INTO ipd_io_chart (admission_id, record_date, shift, oral_ml, iv_ml, other_intake_ml, urine_ml, vomit_ml, ngt_ml, drain_ml, other_output_ml, recorded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$adm_id, $date, $shift, $oral, $iv, $oth_in, $urine, $vomit, $ngt, $drain, $oth_out, $by]);
            echo json_encode(['status' => 'success']);
            break;

        case 'get_io_records':
            $adm_id = intval($_GET['admission_id']);
            $stmt = $pdo->prepare("SELECT * FROM ipd_io_chart WHERE admission_id = ? ORDER BY record_date DESC, id DESC");
            $stmt->execute([$adm_id]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'delete_io':
            $pdo->prepare("DELETE FROM ipd_io_chart WHERE id = ?")->execute([intval($_POST['id'])]);
            echo json_encode(['status' => 'success']);
            break;

        // --- TAB 6: DOCTOR NOTES ---
        case 'save_drnote':
            $adm_id = intval($_POST['admission_id']);
            $dt = $_POST['note_datetime'];
            $dr = $_POST['doctor_name'];
            $type = $_POST['note_type'];
            $subj = $_POST['subjective'];
            $obj = $_POST['objective'];
            $ass = $_POST['assessment'];
            $plan = $_POST['plan'];
            $next = !empty($_POST['next_review_date']) ? $_POST['next_review_date'] : null;

            $sql = "INSERT INTO ipd_doctor_notes (admission_id, note_datetime, doctor_name, note_type, subjective, objective, assessment, plan, next_review_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$adm_id, $dt, $dr, $type, $subj, $obj, $ass, $plan, $next]);
            echo json_encode(['status' => 'success']);
            break;

        case 'get_dr_notes':
            $adm_id = intval($_GET['admission_id']);
            $stmt = $pdo->prepare("SELECT * FROM ipd_doctor_notes WHERE admission_id = ? ORDER BY note_datetime DESC");
            $stmt->execute([$adm_id]);
            $notes = $stmt->fetchAll();
            foreach($notes as &$n) $n['note_datetime'] = date('d-M-y H:i', strtotime($n['note_datetime']));
            echo json_encode($notes);
            break;

        case 'delete_drnote':
            $pdo->prepare("DELETE FROM ipd_doctor_notes WHERE id = ?")->execute([intval($_POST['id'])]);
            echo json_encode(['status' => 'success']);
            break;

        // --- TAB 7: NURSING NOTES ---
        case 'save_nursenote':
            $adm_id = intval($_POST['admission_id']);
            $dt = $_POST['note_datetime'];
            $nurse = $_POST['nurse_name'];
            $shift = $_POST['shift'];
            $cond = $_POST['patient_condition'];
            $cons = $_POST['consciousness'];
            $obs = $_POST['observations'];
            $act = $_POST['actions_taken'];
            $comp = $_POST['patient_complaints'];
            $bp = $_POST['bp'];
            $hr = !empty($_POST['hr']) ? intval($_POST['hr']) : null;
            $temp = !empty($_POST['temp']) ? floatval($_POST['temp']) : null;
            $spo2 = !empty($_POST['spo2']) ? intval($_POST['spo2']) : null;

            $sql = "INSERT INTO ipd_nursing_notes (admission_id, note_datetime, nurse_name, shift, patient_condition, consciousness, observations, actions_taken, patient_complaints, bp, hr, temp, spo2) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$adm_id, $dt, $nurse, $shift, $cond, $cons, $obs, $act, $comp, $bp, $hr, $temp, $spo2]);
            echo json_encode(['status' => 'success']);
            break;

        case 'get_nurse_notes':
            $adm_id = intval($_GET['admission_id']);
            $stmt = $pdo->prepare("SELECT * FROM ipd_nursing_notes WHERE admission_id = ? ORDER BY note_datetime DESC");
            $stmt->execute([$adm_id]);
            $notes = $stmt->fetchAll();
            foreach($notes as &$n) $n['note_datetime'] = date('d-M-y H:i', strtotime($n['note_datetime']));
            echo json_encode($notes);
            break;

        case 'delete_nursenote':
            $pdo->prepare("DELETE FROM ipd_nursing_notes WHERE id = ?")->execute([intval($_POST['id'])]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
