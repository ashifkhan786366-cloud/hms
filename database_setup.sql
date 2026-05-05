-- HMS Complete Database Setup
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Receptionist','Doctor','Nurse','Lab Technician','Pharmacist','Accountant') NOT NULL DEFAULT 'Receptionist',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `users` (`full_name`,`username`,`password`,`role`,`email`) VALUES
('Super Admin','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Admin','admin@hospital.com'),
('Dr. B.K. Sankhla','doctor','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Doctor','doctor@hospital.com'),
('Reception Desk','reception','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Receptionist','reception@hospital.com');

CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mr_number` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `age` int(11) NOT NULL DEFAULT 0,
  `dob` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mr_number` (`mr_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `token_number` int(11) NOT NULL DEFAULT 0,
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `symptoms` text,
  `bp` varchar(20) DEFAULT NULL,
  `pulse` varchar(20) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `follow_ups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `followup_date` date NOT NULL,
  `notes` text,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `beds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ward_name` varchar(100) NOT NULL,
  `bed_number` varchar(50) NOT NULL,
  `status` enum('Available','Occupied','Maintenance') DEFAULT 'Available',
  `patient_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `beds` (`ward_name`,`bed_number`,`status`) VALUES
('General Ward','GW-01','Available'),('General Ward','GW-02','Available'),
('General Ward','GW-03','Available'),('General Ward','GW-04','Available'),
('ICU','ICU-01','Available'),('ICU','ICU-02','Available'),
('Private Room','PR-01','Available'),('Private Room','PR-02','Available');

CREATE TABLE IF NOT EXISTS `ipd_admissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `admission_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `discharge_date` datetime DEFAULT NULL,
  `bed_number` varchar(50) DEFAULT NULL,
  `ward_type` varchar(50) DEFAULT 'General',
  `diagnosis` text,
  `status` enum('Admitted','Discharged') DEFAULT 'Admitted',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT 'Info',
  `message` text,
  `for_role` varchar(50) DEFAULT 'All',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate` decimal(10,2) DEFAULT 0.00,
  `type` enum('OPD','Lab','IPD','Other') DEFAULT 'OPD',
  `category` varchar(100) DEFAULT NULL,
  `tax_percent` decimal(5,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `services` (`service_name`,`cost`,`type`) VALUES
('OPD Consultation',500.00,'OPD'),('ECG',300.00,'Lab'),
('X-Ray',400.00,'Lab'),('Blood Sugar',100.00,'Lab'),
('General Ward Bed Charge',1000.00,'IPD'),('ICU Bed Charge',3000.00,'IPD');

CREATE TABLE IF NOT EXISTS `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(50) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `ipd_admission_id` int(11) DEFAULT NULL,
  `bill_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `discount_type` varchar(20) DEFAULT 'amount',
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance_due` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('Pending','Partial','Paid') DEFAULT 'Pending',
  `payment_method` enum('Cash','Card','UPI','Other') DEFAULT 'Cash',
  `payment_mode_cash` decimal(10,2) DEFAULT 0.00,
  `payment_mode_upi` decimal(10,2) DEFAULT 0.00,
  `bill_type` varchar(20) DEFAULT 'OPD',
  `generated_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `last_edited_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate` decimal(10,2) DEFAULT 0.00,
  `quantity` int(11) DEFAULT 1,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_type` varchar(50) DEFAULT 'General',
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `report_status` varchar(20) DEFAULT NULL,
  `lab_result` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bill_partitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partition_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `sub_total_visible` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `bill_partitions` (`partition_key`,`label`,`sort_order`) VALUES
('CONSULTATION','Consultation Charges',1),('LAB','Laboratory / Investigations',2),
('PROCEDURE','Procedures',3),('MEDICINE','Medicines / Pharmacy',4),
('ROOM','Room / Bed Charges',5),('OTHER','Other Charges',6);

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_mode` varchar(30) DEFAULT 'Cash',
  `cash_amount` decimal(10,2) DEFAULT 0.00,
  `upi_amount` decimal(10,2) DEFAULT 0.00,
  `card_amount` decimal(10,2) DEFAULT 0.00,
  `remarks` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` text,
  `advice` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prescription_medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prescription_id` int(11) NOT NULL,
  `medicine_name` varchar(100) NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `instruction` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prescription_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prescription_id` int(11) NOT NULL,
  `test_name` varchar(100) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `stock_qty` int(11) DEFAULT 0,
  `price_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `manufacturer` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `hospital_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `hospital_settings` (`setting_key`,`setting_value`) VALUES
('APP_NAME','SANKHLA HOSPITAL'),('APP_SHORT_NAME','SANKHLA'),
('APP_ADDRESS','GOVT. DISS.NEAR KANJI PETROL PUMP,NEWARU ROAD,JHOTWARA,JAIPUR'),
('APP_PHONE','9829208462'),('APP_EMAIL','bksankhlahospital@gmail.com'),
('APP_LOGO','assets/logo.png'),('PRIMARY_COLOR','#0d6efd'),
('SECONDARY_COLOR','#212529'),('HEADER_FONT','Arial, sans-serif'),('CURRENCY','INR');

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `accounting_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('Income','Expense') NOT NULL DEFAULT 'Income',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text,
  `reference_id` varchar(100) DEFAULT NULL,
  `transaction_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_name` varchar(255) NOT NULL,
  `account_type` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_journal_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_journal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(10,2) DEFAULT 0.00,
  `credit` decimal(10,2) DEFAULT 0.00,
  `narration` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `daily_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `total_opd` int(11) DEFAULT 0,
  `total_ipd` int(11) DEFAULT 0,
  `total_collection` decimal(10,2) DEFAULT 0.00,
  `cash_collection` decimal(10,2) DEFAULT 0.00,
  `upi_collection` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `daily_report_locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `locked_by` int(11) DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `daily_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_date` date NOT NULL,
  `voucher_type` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `description` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tpa_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('Corporate','TPA') DEFAULT 'TPA',
  `contact` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `treatment_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `amount` decimal(10,2) DEFAULT 0.00,
  `duration_days` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bill_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `print_template_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(100) NOT NULL,
  `template_value` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `print_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `n8n_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_type` varchar(100) DEFAULT NULL,
  `payload` text,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lab_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `bill_item_id` int(11) DEFAULT NULL,
  `test_name` varchar(150) DEFAULT NULL,
  `ordered_by` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Ordered',
  `result` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IPD Dashboard Tables
CREATE TABLE IF NOT EXISTS `ipd_patient_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `attendant_name` varchar(100) DEFAULT NULL,
  `attendant_relation` varchar(50) DEFAULT NULL,
  `attendant_contact` varchar(20) DEFAULT NULL,
  `expected_discharge_date` date DEFAULT NULL,
  `patient_photo` varchar(255) DEFAULT NULL,
  `drug_allergies` text,
  `fall_risk` tinyint(1) DEFAULT 0,
  `is_diabetic` tinyint(1) DEFAULT 0,
  `dnr_status` tinyint(1) DEFAULT 0,
  `surgery_history` text,
  `special_notes` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admission_id` (`admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_vitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `record_date` datetime NOT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `bp_systolic` int(11) DEFAULT NULL,
  `bp_diastolic` int(11) DEFAULT NULL,
  `spo2` int(11) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `blood_sugar` int(11) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `recorded_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_medications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `dose` varchar(50) DEFAULT NULL,
  `route` varchar(50) DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `instructions` text,
  `prescribed_by` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_medication_doses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medication_id` int(11) NOT NULL,
  `given_at` datetime NOT NULL,
  `given_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_iv_fluids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `fluid_name` varchar(100) NOT NULL,
  `volume_ml` int(11) DEFAULT NULL,
  `rate_ml_hr` int(11) DEFAULT NULL,
  `additives` text,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `bottle_number` varchar(50) DEFAULT NULL,
  `given_by` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Running',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_io_chart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `intake_oral` int(11) DEFAULT 0,
  `intake_iv` int(11) DEFAULT 0,
  `intake_other` int(11) DEFAULT 0,
  `output_urine` int(11) DEFAULT 0,
  `output_vomit` int(11) DEFAULT 0,
  `output_drain` int(11) DEFAULT 0,
  `recorded_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_doctor_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `note_time` datetime NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `doctor_name` varchar(100) DEFAULT NULL,
  `note_type` varchar(50) DEFAULT NULL,
  `clinical_note` text,
  `plan` text,
  `next_review_date` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_nursing_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `note_time` datetime NOT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `nurse_name` varchar(100) DEFAULT NULL,
  `patient_condition` varchar(50) DEFAULT NULL,
  `observations` text,
  `actions_taken` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_billing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `bill_number` varchar(20) DEFAULT NULL,
  `bill_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT 0.00,
  `total_advance` decimal(10,2) DEFAULT 0.00,
  `balance_due` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'Pending',
  `payment_mode` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_billing_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipd_bill_id` int(11) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `rate` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_billing_advances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_mode` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `received_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_med_given` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `medication_id` int(11) DEFAULT NULL,
  `given_at` datetime NOT NULL,
  `given_by` varchar(100) DEFAULT NULL,
  `dose_given` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
