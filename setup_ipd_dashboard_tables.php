<?php
/**
 * IPD Dashboard Tables Setup Script
 * Creates all necessary tables for the 18 tabs of the IPD One Station System.
 */

require_once 'config/db.php';

echo "Starting IPD Database Schema Setup...<br><br>";

$queries = [
    // Tab 1: Overview & Details
    "CREATE TABLE IF NOT EXISTS ipd_patient_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        attendant_name VARCHAR(100),
        attendant_relation VARCHAR(50),
        attendant_contact VARCHAR(20),
        expected_discharge_date DATE,
        patient_photo VARCHAR(255),
        drug_allergies TEXT,
        fall_risk BOOLEAN DEFAULT 0,
        is_diabetic BOOLEAN DEFAULT 0,
        dnr_status BOOLEAN DEFAULT 0,
        surgery_history TEXT,
        special_notes TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE(admission_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 2: Vitals
    "CREATE TABLE IF NOT EXISTS ipd_vitals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        record_date DATETIME NOT NULL,
        heart_rate INT,
        bp_systolic INT,
        bp_diastolic INT,
        spo2 INT,
        temperature DECIMAL(5,2),
        respiratory_rate INT,
        blood_sugar INT,
        shift VARCHAR(20),
        recorded_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 3: Medications
    "CREATE TABLE IF NOT EXISTS ipd_medications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        medicine_name VARCHAR(150) NOT NULL,
        dose VARCHAR(50),
        route VARCHAR(50),
        frequency VARCHAR(50),
        start_date DATE,
        end_date DATE,
        instructions TEXT,
        prescribed_by VARCHAR(100),
        status VARCHAR(20) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS ipd_medication_doses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        medication_id INT NOT NULL,
        given_at DATETIME NOT NULL,
        given_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 4: IV Fluids
    "CREATE TABLE IF NOT EXISTS ipd_iv_fluids (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        fluid_name VARCHAR(100) NOT NULL,
        volume_ml INT,
        rate_ml_hr INT,
        additives TEXT,
        start_time DATETIME,
        end_time DATETIME,
        bottle_number VARCHAR(50),
        given_by VARCHAR(100),
        status VARCHAR(20) DEFAULT 'Running',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 5: I/O Chart
    "CREATE TABLE IF NOT EXISTS ipd_io_chart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        record_date DATE NOT NULL,
        intake_oral INT DEFAULT 0,
        intake_iv INT DEFAULT 0,
        intake_other INT DEFAULT 0,
        output_urine INT DEFAULT 0,
        output_vomit INT DEFAULT 0,
        output_drain INT DEFAULT 0,
        recorded_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(admission_id, record_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 6: Doctor Notes
    "CREATE TABLE IF NOT EXISTS ipd_doctor_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        note_time DATETIME NOT NULL,
        doctor_id INT,
        doctor_name VARCHAR(100),
        note_type VARCHAR(50),
        clinical_note TEXT,
        plan TEXT,
        next_review_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 7: Nursing Notes
    "CREATE TABLE IF NOT EXISTS ipd_nursing_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        note_time DATETIME NOT NULL,
        shift VARCHAR(20),
        nurse_name VARCHAR(100),
        patient_condition VARCHAR(50),
        observations TEXT,
        actions_taken TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 8: Lab Orders (Using existing services table for lookup)
    "CREATE TABLE IF NOT EXISTS ipd_lab_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        service_id INT NOT NULL,
        test_name VARCHAR(150),
        ordered_by VARCHAR(100),
        order_time DATETIME,
        priority VARCHAR(20),
        sample_type VARCHAR(50),
        status VARCHAR(50) DEFAULT 'Ordered',
        result_value VARCHAR(255),
        result_file VARCHAR(255),
        is_abnormal BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 9: Diet Chart
    "CREATE TABLE IF NOT EXISTS ipd_diet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        diet_type VARCHAR(50),
        special_instructions TEXT,
        set_by VARCHAR(100),
        from_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS ipd_diet_meals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        meal_date DATE NOT NULL,
        meal_type VARCHAR(20),
        is_served BOOLEAN DEFAULT 0,
        consumed_status VARCHAR(20),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 10: Pain Assessment
    "CREATE TABLE IF NOT EXISTS ipd_pain_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        record_time DATETIME NOT NULL,
        pain_score INT NOT NULL,
        pain_location VARCHAR(50),
        pain_type VARCHAR(50),
        duration VARCHAR(50),
        action_taken VARCHAR(100),
        recorded_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 11: Procedures
    "CREATE TABLE IF NOT EXISTS ipd_procedures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        procedure_type VARCHAR(50),
        procedure_name VARCHAR(150),
        procedure_time DATETIME,
        surgeon VARCHAR(100),
        anesthetist VARCHAR(100),
        anesthesia_type VARCHAR(50),
        duration_mins INT,
        pre_op_notes TEXT,
        post_op_notes TEXT,
        complications TEXT,
        status VARCHAR(50) DEFAULT 'Scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 12: Transfusions
    "CREATE TABLE IF NOT EXISTS ipd_transfusions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        transfusion_time DATETIME NOT NULL,
        blood_product VARCHAR(50),
        blood_group_units VARCHAR(100),
        donor_info VARCHAR(100),
        pre_bp VARCHAR(20),
        pre_hr INT,
        post_bp VARCHAR(20),
        post_hr INT,
        reaction VARCHAR(255),
        administered_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 13: Ventilator
    "CREATE TABLE IF NOT EXISTS ipd_ventilator (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        record_time DATETIME NOT NULL,
        mode VARCHAR(50),
        fio2 INT,
        peep DECIMAL(5,2),
        tidal_volume INT,
        rate_set_actual VARCHAR(20),
        peak_pressure INT,
        gcs_score VARCHAR(20),
        pupil_reaction VARCHAR(50),
        sedation_level VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 14: Dialysis
    "CREATE TABLE IF NOT EXISTS ipd_dialysis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        session_time DATETIME NOT NULL,
        duration_hrs DECIMAL(4,1),
        pre_weight DECIMAL(5,2),
        pre_bp VARCHAR(20),
        post_weight DECIMAL(5,2),
        post_bp VARCHAR(20),
        fluid_removed_ml INT,
        access_type VARCHAR(50),
        heparin_units INT,
        complications TEXT,
        performed_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 15: History
    "CREATE TABLE IF NOT EXISTS ipd_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        known_conditions TEXT,
        previous_surgeries TEXT,
        previous_hospitalizations TEXT,
        current_medications TEXT,
        family_history TEXT,
        smoking_history VARCHAR(50),
        alcohol_history VARCHAR(50),
        tobacco_history VARCHAR(50),
        occupation VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(admission_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 16: Discharge & Summary
    "CREATE TABLE IF NOT EXISTS ipd_discharge_summary (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        discharge_condition VARCHAR(50),
        primary_diagnosis TEXT,
        secondary_diagnosis TEXT,
        presenting_complaints TEXT,
        investigations_summary TEXT,
        treatment_summary TEXT,
        home_instructions TEXT,
        followup_plan TEXT,
        medicines_to_continue TEXT,
        diet_instructions TEXT,
        activity_restrictions TEXT,
        warning_signs TEXT,
        referral_info TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(admission_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tab 17 & 18: IPD Billing (Isolated from OPD Bills)
    "CREATE TABLE IF NOT EXISTS ipd_billing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        bill_number VARCHAR(20) UNIQUE,
        bill_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        subtotal DECIMAL(10,2) DEFAULT 0.00,
        discount DECIMAL(10,2) DEFAULT 0.00,
        tax DECIMAL(10,2) DEFAULT 0.00,
        grand_total DECIMAL(10,2) DEFAULT 0.00,
        total_advance DECIMAL(10,2) DEFAULT 0.00,
        balance_due DECIMAL(10,2) DEFAULT 0.00,
        status VARCHAR(20) DEFAULT 'Pending',
        payment_mode VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS ipd_billing_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ipd_bill_id INT NOT NULL,
        admission_id INT NOT NULL,
        item_name VARCHAR(150) NOT NULL,
        category VARCHAR(50),
        service_date DATE,
        qty INT DEFAULT 1,
        rate DECIMAL(10,2) DEFAULT 0.00,
        amount DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS ipd_billing_advances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_mode VARCHAR(50),
        receipt_number VARCHAR(50),
        received_by VARCHAR(100),
        received_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "Table block " . ($i + 1) . " created/verified successfully.<br>";
    } catch (PDOException $e) {
        echo "<div style='color:red;'>Error executing query block " . ($i + 1) . ": " . $e->getMessage() . "</div><br>";
    }
}

echo "<br><b>Schema Setup Complete!</b>";
?>
