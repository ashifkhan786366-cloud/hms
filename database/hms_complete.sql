-- Complete HMS Database Setup
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','doctor','nurse','staff','pharmacist','lab','accountant') DEFAULT 'staff',
  `phone` varchar(15) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  -- We also add username and full_name for backward compatibility
  `full_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `users` (`name`,`email`,`password`,`role`,`status`,`full_name`,`username`) VALUES
('Super Admin','admin@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','superadmin',1,'Super Admin','admin'),
('Dr. B.K. Sankhla','doctor@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','doctor',1,'Dr. B.K. Sankhla','doctor'),
('Reception Desk','reception@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','staff',1,'Reception Desk','reception');

CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mr_number` varchar(50) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Male',
  `age` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text,
  `blood_group` varchar(10) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
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

CREATE TABLE IF NOT EXISTS `opd_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_number` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `symptoms` text,
  `diagnosis` text,
  `fee` decimal(10,2) DEFAULT 0.00,
  `payment_status` varchar(50) DEFAULT 'pending',
  `status` varchar(50) DEFAULT 'waiting',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ipd_admissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipd_number` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `bed_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `admission_date` datetime DEFAULT NULL,
  `discharge_date` datetime DEFAULT NULL,
  `bed_number` varchar(50) DEFAULT NULL,
  `ward_type` varchar(50) DEFAULT 'General',
  `diagnosis` text,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'admitted',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `beds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bed_number` varchar(50) NOT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `ward_name` varchar(100) DEFAULT NULL,
  `bed_type` varchar(100) DEFAULT 'General',
  `rate_per_day` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'available',
  `patient_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `total_beds` int(11) DEFAULT 0,
  `floor` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `type` varchar(50) DEFAULT 'info',
  `for_role` varchar(50) DEFAULT 'All',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `ipd_id` int(11) DEFAULT NULL,
  `ipd_admission_id` int(11) DEFAULT NULL,
  `opd_id` int(11) DEFAULT NULL,
  `bill_date` datetime DEFAULT NULL,
  `bill_type` varchar(50) DEFAULT 'OPD',
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `discount_type` varchar(20) DEFAULT 'amount',
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `balance_due` decimal(10,2) DEFAULT 0.00,
  `payment_mode` varchar(50) DEFAULT 'Cash',
  `payment_method` varchar(50) DEFAULT 'Cash',
  `payment_mode_cash` decimal(10,2) DEFAULT 0.00,
  `payment_mode_upi` decimal(10,2) DEFAULT 0.00,
  `payment_status` varchar(50) DEFAULT 'Pending',
  `report_status` varchar(50) DEFAULT 'pending',
  `corporate_id` int(11) DEFAULT NULL,
  `notes` text,
  `generated_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `last_edited_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 1,
  `rate` decimal(10,2) DEFAULT 0.00,
  `cost` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) DEFAULT 0.00,
  `item_type` varchar(50) DEFAULT 'General',
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `report_status` varchar(20) DEFAULT NULL,
  `lab_result` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `marg_bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `bill_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `report_status` varchar(50) DEFAULT 'pending',
  `status` varchar(50) DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT 0.00,
  `cost` decimal(10,2) DEFAULT 0.00,
  `tax_percent` decimal(5,2) DEFAULT 0.00,
  `type` varchar(50) DEFAULT 'OPD',
  `category` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `stock_qty` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `purchase_rate` decimal(10,2) DEFAULT 0.00,
  `sale_rate` decimal(10,2) DEFAULT 0.00,
  `price_per_unit` decimal(10,2) DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `medicine_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pharmacy_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `payment_mode` varchar(50) DEFAULT 'Cash',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pharmacy_sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `rate` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lab_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `normal_range` varchar(255) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lab_test_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lab_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `bill_item_id` int(11) DEFAULT NULL,
  `test_name` varchar(150) DEFAULT NULL,
  `ordered_by` varchar(100) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `report_status` varchar(50) DEFAULT 'pending',
  `status` varchar(50) DEFAULT 'ordered',
  `result` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lab_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `result_value` varchar(255) DEFAULT NULL,
  `remarks` text,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `corporate_tpa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('Corporate','TPA','Insurance') DEFAULT 'Corporate',
  `contact` varchar(15) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
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

CREATE TABLE IF NOT EXISTS `financial_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_name` varchar(255) NOT NULL,
  `account_type` varchar(50) DEFAULT 'Cash',
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'income',
  `amount` decimal(10,2) DEFAULT 0.00,
  `category` varchar(100) DEFAULT NULL,
  `description` text,
  `transaction_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `hospital_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(150) NOT NULL UNIQUE,
  `setting_value` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `print_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(150) NOT NULL,
  `template_type` varchar(100) DEFAULT NULL,
  `content` longtext,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext,
  `new_values` longtext,
  `details` text,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `daily_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_date` date DEFAULT NULL,
  `total_opd` decimal(10,2) DEFAULT 0.00,
  `total_ipd` decimal(10,2) DEFAULT 0.00,
  `total_lab` decimal(10,2) DEFAULT 0.00,
  `total_pharmacy` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT 0.00,
  `cash` decimal(10,2) DEFAULT 0.00,
  `card` decimal(10,2) DEFAULT 0.00,
  `upi` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS `accounting_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('Income','Expense') NOT NULL DEFAULT 'Income',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text,
  `reference_id` varchar(100) DEFAULT NULL,
  `transaction_date` timestamp DEFAULT CURRENT_TIMESTAMP,
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

-- Seed Wards
INSERT IGNORE INTO `wards` (`name`,`type`,`total_beds`,`floor`) VALUES
('General Ward','General',20,'Ground Floor'),
('ICU','ICU',5,'First Floor'),
('Private Ward','Private',10,'Second Floor'),
('Emergency','Emergency',8,'Ground Floor');

-- Seed Beds
INSERT IGNORE INTO `beds` (`ward_name`,`bed_number`,`status`) VALUES
('General Ward','GW-01','Available'),('General Ward','GW-02','Available'),
('General Ward','GW-03','Available'),('General Ward','GW-04','Available'),
('ICU','ICU-01','Available'),('ICU','ICU-02','Available'),
('Private Room','PR-01','Available'),('Private Room','PR-02','Available');

-- Seed Settings
INSERT IGNORE INTO `hospital_settings` (`setting_key`,`setting_value`) VALUES
('hospital_name','SANKHLA Hospital'),
('APP_NAME','SANKHLA HOSPITAL'),
('APP_SHORT_NAME','SANKHLA'),
('hospital_address','GOVT. DISS.NEAR KANJI PETROL PUMP,NEWARU ROAD,JHOTWARA,JAIPUR'),
('APP_ADDRESS','GOVT. DISS.NEAR KANJI PETROL PUMP,NEWARU ROAD,JHOTWARA,JAIPUR'),
('hospital_phone','9829208462'),
('APP_PHONE','9829208462'),
('hospital_email','bksankhlahospital@gmail.com'),
('APP_EMAIL','bksankhlahospital@gmail.com'),
('APP_LOGO','assets/logo.png'),
('PRIMARY_COLOR','#0d6efd'),
('SECONDARY_COLOR','#212529'),
('HEADER_FONT','Arial, sans-serif'),
('CURRENCY','₹'),
('currency_symbol','₹'),
('date_format','d-m-Y'),
('mr_prefix','MR'),
('bill_prefix','BILL'),
('opd_prefix','OPD'),
('ipd_prefix','IPD');

-- Seed Categories
INSERT IGNORE INTO `service_categories` (`name`) VALUES
('Consultation'),('Procedure'),('OT Charges'),
('Room Charges'),('Nursing'),('Investigation');

INSERT IGNORE INTO `medicine_categories` (`name`) VALUES
('Tablet'),('Capsule'),('Syrup'),('Injection'),
('Ointment'),('Drops'),('Powder');

INSERT IGNORE INTO `lab_test_categories` (`name`) VALUES
('Haematology'),('Biochemistry'),('Microbiology'),
('Serology'),('Urine Analysis'),('Radiology');

SET FOREIGN_KEY_CHECKS=1;
