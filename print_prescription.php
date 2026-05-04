<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$print_settings = get_print_settings($pdo, 'opd');

if (!isset($_GET['id']))
    die("Invalid Request");
$id = $_GET['id']; // Appointment ID

$sql = "SELECT a.*, p.full_name, p.age, p.gender, p.mr_number, p.phone as mobile, p.address as p_address,
        u.full_name as doctor_name
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN users u ON a.doctor_id = u.id 
        WHERE a.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data)
    die("Appointment not found");

// Fetch Prescriptions
$rx_stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE appointment_id = ? ORDER BY id ASC");
$rx_stmt->execute([$id]);
$prescriptions = $rx_stmt->fetchAll();

if (count($prescriptions) == 0) {
    die("No digital prescription found for this visit. Please add medicines first.");
}

$patient_id = $data['mr_number'];
$token_no = $data['token_number'];
$name = strtoupper($data['full_name']);
$gen = strtoupper($data['gender']); 
$age = $data['age'] . 'Y';
$mobile = $data['mobile'];
$date = str_replace('-', '\\', date('d-m-y', strtotime($data['visit_date']))); 
$clean_doc_name = preg_replace('/^dr\.?\s*/i', '', $data['doctor_name']);
$ref_by = "DR. " . strtoupper($clean_doc_name);
$address_part = strtoupper(substr($data['p_address'], 0, 15));
$time = date('h:i A', strtotime($data['created_at']));
$bp = $data['bp'] ?: '......./.......';
$spo2 = '......'; 
$plus = $data['pulse'] ?: '.......';
$hight = '..............'; 
$waight = $data['weight'] ?: '..............';
$blood_group = '..........'; 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Prescription - <?php echo $name; ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: <?php echo $print_settings['font_family']; ?>;
            margin: 0;
            padding: 0;
            font-size: <?php echo $print_settings['font_size']; ?>px;
            --primary-color: <?php echo $print_settings['primary_color']; ?>;
        }

        .print-container {
            padding-top: <?php echo $print_settings['margin_top']; ?>px;
            padding-bottom: <?php echo $print_settings['margin_bottom']; ?>px;
            padding-left: <?php echo $print_settings['margin_left']; ?>px;
            padding-right: <?php echo $print_settings['margin_right']; ?>px;
            position: relative;
            min-height: 100vh;
            box-sizing: border-box;
        }
        
        .dynamic-header {
            text-align: center;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .dynamic-footer {
            position: absolute;
            bottom: <?php echo $print_settings['margin_bottom']; ?>px;
            left: <?php echo $print_settings['margin_left']; ?>px;
            right: <?php echo $print_settings['margin_right']; ?>px;
            text-align: center;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }

        .header-grid {
            display: grid;
            grid-template-columns: 32% 16% 28% 24%;
            row-gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
            text-transform: uppercase;
            align-items: baseline;
        }

        .header-grid > div {
            white-space: nowrap; 
        }

        .vitals-row {
            display: flex;
            margin-bottom: 20px; 
            font-weight: bold;
            font-size: 14px; 
            text-transform: uppercase;
        }

        .vital-item {
            margin-right: 25px; 
        }

        .rx-symbol {
            font-size: 38px; 
            font-weight: bold;
            font-family: Arial, sans-serif;
            margin-left: 20px; 
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .medicine-table {
            width: 90%;
            margin-left: 5%;
            border-collapse: collapse;
        }

        .medicine-table th, .medicine-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .medicine-table th {
            border-bottom: 2px solid #000;
            font-weight: bold;
        }

        .footer-text {
            position: absolute;
            bottom: 1.0in; 
            right: 40px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 15px; 
        }

        strong, b {
            font-weight: bold;
            color: black;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
        
        .controls {
            margin-bottom: 10px;
            background: #f4f4f4;
            padding: 10px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        .controls button, .controls a {
            padding: 8px 16px;
            margin-left: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .controls a {
            background-color: #6c757d;
        }

    </style>
</head>

<body>

    <div class="controls no-print">
        <button onclick="window.print()">🖨 Print Prescription</button>
        <button onclick="window.close()" style="background-color: #6c757d;">Close</button>
    </div>

    <div class="print-container">
        
        <?php if (!empty($print_settings['header_text'])): ?>
        <div class="dynamic-header">
            <?php echo $print_settings['header_text']; ?>
        </div>
        <?php endif; ?>

        <!-- Unified Header Grid -->
        <div class="header-grid">
            <!-- Row 1 -->
            <div>PATIENT ID _ <strong><?php echo $patient_id; ?></strong></div>
            <div>TOKEN No. : <strong><?php echo $token_no; ?></strong></div>
            <div>NAME : <?php echo $name; ?></div>
            <div>GEN. <?php echo $gen; ?> &nbsp;&nbsp; AGE.<?php echo $age; ?></div>

            <!-- Row 2 -->
            <div style="grid-column: 1 / span 2;">MOBILE NO : <?php echo $mobile; ?></div>
            <div></div> <!-- Empty cell for column 3 alignment -->
            <div>DATE _ <strong><?php echo $date; ?></strong></div>

            <!-- Row 3 -->
            <div style="grid-column: 1 / span 2;">REF. BY - <strong><?php echo $ref_by; ?></strong></div>
            <div>ADDRESS : <?php echo $address_part; ?></div>
            <div>TIMING : <?php echo $time; ?></div>
        </div>

        <!-- Row 4 (Vitals) -->
        <div class="vitals-row">
            <div class="vital-item">BP <?php echo $bp; ?></div>
            <div class="vital-item">SPO2 <?php echo $spo2; ?></div>
            <div class="vital-item">PLUS <?php echo $plus; ?></div>
            <div class="vital-item">HIGHT <?php echo $hight; ?></div>
            <div class="vital-item">WAIGHT <?php echo $waight; ?></div>
            <div class="vital-item" style="margin-right: 0;">BLOOD GROUP <?php echo $blood_group; ?></div>
        </div>

        <!-- Rx Section -->
        <div>
            <div class="rx-symbol">Rx</div>
            
            <table class="medicine-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="40%">Medicine Name</th>
                        <th width="20%">Dosage</th>
                        <th width="15%">Duration</th>
                        <th width="20%">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    foreach ($prescriptions as $rx): ?>
                        <tr>
                            <td><?php echo $sr++; ?></td>
                            <td><strong><?php echo htmlspecialchars($rx['medicine_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($rx['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($rx['duration']); ?></td>
                            <td><?php echo htmlspecialchars($rx['notes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer-text">
            VALID FOR 3 DAYS
        </div>
        
        <?php if (!empty($print_settings['footer_text'])): ?>
        <div class="dynamic-footer">
            <?php echo $print_settings['footer_text']; ?>
        </div>
        <?php endif; ?>
    </div>

</body>
</html>
