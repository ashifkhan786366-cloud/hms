<?php
/**
 * daily_report.php — Daily Collection Report Main Module
 * Handles: Time filter, Lab card, Summary cards, Bills table, Voucher panel, Print
 */
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

// ── Parameters ───────────────────────────────────────────────
$report_date = $_GET['date']      ?? date('Y-m-d');
$time_from   = $_GET['time_from'] ?? '07:00';
$time_to     = $_GET['time_to']   ?? '21:00';
$opening_bal = (float)($_GET['opening_bal'] ?? 0);

// Sanitise
$report_date = preg_replace('/[^0-9\-]/', '', $report_date);
$time_from   = preg_replace('/[^0-9:]/', '', $time_from);
$time_to     = preg_replace('/[^0-9:]/', '', $time_to);

$dt_from = $report_date . ' ' . $time_from . ':00';
$dt_to   = $report_date . ' ' . $time_to   . ':59';

// ── Bill Summary Query ────────────────────────────────────────
$bills = [];
$total_collection = 0;
$cash_total = $upi_total = $card_total = $other_total = $lab_collection = 0;
$total_pending_balance = 0;   // FIX 5: sum of unpaid balance

try {
    // Query Transactions table for accurate payment mode sums
    $t_stmt = $pdo->prepare("
        SELECT payment_mode, SUM(amount) as total_amount
        FROM transactions
        WHERE created_at BETWEEN ? AND ?
        GROUP BY payment_mode
    ");
    $t_stmt->execute([$dt_from, $dt_to]);
    $t_rows = $t_stmt->fetchAll();

    foreach ($t_rows as $row) {
        $pm = strtolower($row['payment_mode'] ?? 'cash');
        $amt = (float)$row['total_amount'];
        $total_collection += $amt;
        
        if ($pm === 'cash') {
            $cash_total += $amt;
        } elseif ($pm === 'upi') {
            $upi_total += $amt;
        } elseif ($pm === 'card') {
            $card_total += $amt;
        } else {
            $other_total += $amt;
        }
    }

    // Pending balance (sum of unpaid balance from bills)
    $bal_stmt = $pdo->prepare("
        SELECT SUM(COALESCE(balance_due, (net_amount - paid_amount))) 
        FROM bills WHERE bill_date BETWEEN ? AND ?
    ");
    $bal_stmt->execute([$dt_from, $dt_to]);
    $total_pending_balance = max(0, (float)$bal_stmt->fetchColumn());

    // Lab collection from transactions table (bill_type = 'Lab')
    $lab_stmt = $pdo->prepare("
        SELECT SUM(amount) FROM transactions
        WHERE created_at BETWEEN ? AND ? AND bill_type = 'Lab'
    ");
    $lab_stmt->execute([$dt_from, $dt_to]);
    $lab_collection = (float)$lab_stmt->fetchColumn();

    // Fallback Lab collection if transactions don't have bill_type='Lab'
    if ($lab_collection == 0) {
        $stmt2 = $pdo->prepare("
            SELECT COALESCE(SUM(bi.amount),0) as lab_total
            FROM bill_items bi
            JOIN bills b ON bi.bill_id = b.id
            WHERE b.bill_date BETWEEN ? AND ?
              AND (bi.item_type LIKE '%lab%' OR bi.item_type LIKE '%Lab%'
                   OR bi.item_type LIKE '%pathol%' OR bi.item_type LIKE '%diagnostic%')
        ");
        $stmt2->execute([$dt_from, $dt_to]);
        $lab_collection = (float)$stmt2->fetchColumn();
    }

    // All bills in date+time range — for table display
    $stmt = $pdo->prepare("
        SELECT b.id, b.bill_number, b.bill_date,
               b.net_amount, b.paid_amount, b.payment_method,
               COALESCE(b.payment_mode_cash, 0) as pm_cash,
               COALESCE(b.payment_mode_upi,  0) as pm_upi,
               COALESCE(b.balance_due, (b.net_amount - b.paid_amount)) as bal_due,
               b.payment_status,
               p.full_name as patient_name
        FROM bills b
        LEFT JOIN patients p ON b.patient_id = p.id
        WHERE b.bill_date BETWEEN ? AND ?
        ORDER BY b.bill_date ASC
    ");
    $stmt->execute([$dt_from, $dt_to]);
    $bills = $stmt->fetchAll();

} catch (PDOException $e) {
    $db_error = $e->getMessage();
}


// ── Voucher Summary ───────────────────────────────────────────
$v_receipts = 0; $v_payments = 0;
try {
    $vstmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN voucher_type='Receipt' THEN amount ELSE 0 END),0) as receipts,
            COALESCE(SUM(CASE WHEN voucher_type='Payment' THEN amount ELSE 0 END),0) as payments
        FROM daily_vouchers WHERE voucher_date = ?
    ");
    $vstmt->execute([$report_date]);
    $vsumm = $vstmt->fetch();
    $v_receipts = (float)($vsumm['receipts'] ?? 0);
    $v_payments = (float)($vsumm['payments'] ?? 0);
} catch (PDOException $e) { /* table may not exist yet */ }

$net_balance = $opening_bal + $total_collection + $v_receipts - $v_payments;

// ── Lock Status ───────────────────────────────────────────────
$is_locked = false;
try {
    $ls = $pdo->prepare("SELECT id FROM daily_report_locks WHERE report_date = ?");
    $ls->execute([$report_date]);
    $is_locked = (bool)$ls->fetch();
} catch (PDOException $e) {}

// ── Shift Label ───────────────────────────────────────────────
function shiftLabel(string $from, string $to): string {
    $fh = (int)explode(':', $from)[0];
    $th = (int)explode(':', $to)[0];
    if ($fh >= 7  && $th <= 14) return 'Morning Shift (7AM–2PM)';
    if ($fh >= 14 && $th <= 21) return 'Evening Shift (2PM–9PM)';
    if ($fh >= 7  && $th <= 21) return 'Full Day (7AM–9PM)';
    return 'Custom Shift';
}
$shift_label = shiftLabel($time_from, $time_to);

// ── Display Date ─────────────────────────────────────────────
$display_date = date('d/m/Y', strtotime($report_date));

require_once __DIR__ . '/includes/header.php';
?>

<?php include __DIR__ . '/views/daily_report_view.php'; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
