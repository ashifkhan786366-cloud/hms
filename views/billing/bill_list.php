<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Bills Register — HMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .list-container { max-width: 1400px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #007bff; padding-bottom: 15px; margin-bottom: 15px; }
        .header h2 { margin: 0; color: #007bff; font-size: 20px; }
        .btn-new { background: #28a745; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-new:hover { background: #218838; color: #fff; }

        /* Filter Bar */
        .filter-bar {
            display: flex; gap: 10px; flex-wrap: wrap;
            background: #f8f9fa; border: 1px solid #dee2e6;
            border-radius: 8px; padding: 12px 15px;
            margin-bottom: 15px; align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 3px; }
        .filter-group label { font-size: 11px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group input, .filter-group select {
            padding: 6px 10px; border: 1px solid #ccc; border-radius: 5px;
            font-size: 13px; min-width: 130px;
        }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #007bff; }
        .filter-btn {
            padding: 7px 18px; border: none; border-radius: 5px;
            cursor: pointer; font-size: 13px; font-weight: 600;
        }
        .btn-filter { background: #007bff; color: #fff; }
        .btn-filter:hover { background: #0056b3; }
        .btn-reset { background: #6c757d; color: #fff; }
        .btn-reset:hover { background: #545b62; }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 13px; }
        table th, table td { border: 1px solid #e0e0e0; padding: 9px 10px; text-align: left; }
        table th { background-color: #e8f0ff; border-bottom: 2px solid #007bff; color: #1a3c6e; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
        table tr:hover { background-color: #f5f8ff; }
        table td { vertical-align: middle; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-partial { background: #fff3cd; color: #856404; }
        .badge-pending { background: #f8d7da; color: #721c24; }

        /* Action buttons */
        .actions a {
            display: inline-flex; align-items: center; gap: 3px;
            padding: 3px 8px; border-radius: 4px; text-decoration: none;
            font-size: 12px; font-weight: 600; margin-right: 3px; margin-bottom: 2px;
            transition: all 0.15s;
        }
        .btn-view { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
        .btn-view:hover { background: #1565c0; color: #fff; }
        .btn-edit { background: #fff8e1; color: #e65100; border: 1px solid #ffcc02; }
        .btn-edit:hover { background: #e65100; color: #fff; }
        .btn-print { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .btn-print:hover { background: #2e7d32; color: #fff; }
        .btn-pdf { background: #fce4ec; color: #880e4f; border: 1px solid #f48fb1; }
        .btn-pdf:hover { background: #880e4f; color: #fff; }
        .btn-pay { background: #e0f2f1; color: #00695c; border: 1px solid #80cbc4; cursor: pointer; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-right: 3px; margin-bottom: 2px; }
        .btn-pay:hover { background: #00695c; color: #fff; }

        .results-info { font-size: 12px; color: #666; margin-bottom: 8px; }
        .total-row { background: #e8f0ff; font-weight: bold; }
        .payment-split-badge { font-size: 11px; color: #1565c0; background: #e3f2fd; padding: 2px 6px; border-radius: 10px; }

        .empty-state { text-align: center; padding: 50px; color: #999; }
        .empty-state i { font-size: 40px; margin-bottom: 10px; }
        .empty-state h4 { margin: 10px 0 5px; }
    </style>
</head>
<body>

<div class="list-container">
    <div class="header">
        <h2><i class="fas fa-file-invoice-dollar"></i> All Bills Register</h2>
        <a href="billing_app.php?action=create" class="btn-new"><i class="fas fa-plus"></i> New Bill (F8)</a>
    </div>

    <!-- Feature 4: Filter Bar -->
    <form method="GET" action="billing_app.php" class="filter-bar">
        <input type="hidden" name="action" value="list">
        
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Search</label>
            <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Patient name / MR No / Bill No">
        </div>

        <div class="filter-group">
            <label>Date From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="filter-group">
            <label>Date To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Doctor</label>
            <select name="doctor_id">
                <option value="">-- All Doctors --</option>
                <?php if (!empty($doctors)): foreach($doctors as $doc): ?>
                    <option value="<?= $doc['id'] ?>" <?= ($_GET['doctor_id'] ?? '') == $doc['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($doc['name']) ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Bill Type</label>
            <select name="bill_type">
                <option value="">-- All Types --</option>
                <option value="OPD"      <?= ($_GET['bill_type'] ?? '') === 'OPD'      ? 'selected' : '' ?>>OPD</option>
                <option value="IPD"      <?= ($_GET['bill_type'] ?? '') === 'IPD'      ? 'selected' : '' ?>>IPD</option>
                <option value="Lab"      <?= ($_GET['bill_type'] ?? '') === 'Lab'      ? 'selected' : '' ?>>Lab</option>
                <option value="Pharmacy" <?= ($_GET['bill_type'] ?? '') === 'Pharmacy' ? 'selected' : '' ?>>Pharmacy</option>
            </select>
        </div>

        <div class="filter-group" style="justify-content: flex-end;">
            <label>&nbsp;</label>
            <div style="display: flex; gap: 6px;">
                <button type="submit" class="filter-btn btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="billing_app.php?action=list" class="filter-btn btn-reset" style="text-decoration:none; text-align:center;"><i class="fas fa-times"></i> Reset</a>
            </div>
        </div>
    </form>

    <div class="results-info">
        Showing <strong><?= count($bills) ?></strong> bills
        <?php if (!empty($_GET['search']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['doctor_id']) || !empty($_GET['bill_type'])): ?>
            (filtered)
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Bill No</th>
                <th>Patient Name</th>
                <th>MR No</th>
                <th>Type</th>
                <th>Doctor</th>
                <th>Total</th>
                <th>Discount</th>
                <th>Net Amount</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Payment Mode</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($bills)): ?>
            <tr>
                <td colspan="14">
                    <div class="empty-state">
                        <i class="fas fa-file-invoice-dollar text-muted"></i>
                        <h4>No bills found</h4>
                        <p><?= (!empty($_GET['search']) || !empty($_GET['date_from'])) ? 'Filter criteria se koi bill match nahi hua.' : 'Abhi tak koi bill nahi bana.' ?></p>
                        <a href="billing_app.php?action=create" class="btn-new">+ New Bill Banao</a>
                    </div>
                </td>
            </tr>
            <?php else: ?>
                <?php 
                $totalNet = 0; $totalPaid = 0; $totalBalance = 0;
                foreach($bills as $bill): 
                    $totalNet     += $bill['grand_total'] ?? 0;
                    $totalPaid    += $bill['paid_amount']  ?? 0;
                    $totalBalance += $bill['balance']       ?? 0;
                ?>
                <tr>
                    <td><?= date('d-M-Y H:i', strtotime($bill['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($bill['bill_number']) ?></strong></td>
                    <td><?= htmlspecialchars($bill['patient_name'] ?? 'Walk-in') ?></td>
                    <td><?= htmlspecialchars($bill['mr_number'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($bill['bill_type'] ?? '-') ?></td>
                    <td><?= $bill['doctor_name'] ? 'Dr. ' . htmlspecialchars($bill['doctor_name']) : '<i style="color:#aaa;">—</i>' ?></td>
                    <td>₹<?= number_format($bill['total_amount'] ?? 0, 2) ?></td>
                    <td style="color:#c00;"><?= ($bill['discount'] ?? 0) > 0 ? '₹' . number_format($bill['discount'], 2) : '—' ?></td>
                    <td><strong>₹<?= number_format($bill['grand_total'] ?? 0, 2) ?></strong></td>
                    <td style="color:#2e7d32;">₹<?= number_format($bill['paid_amount'] ?? 0, 2) ?></td>
                    <td style="color:<?= ($bill['balance'] ?? 0) > 0 ? 'red' : 'green' ?>;" id="bal-cell-<?= $bill['id'] ?>">
                        <?php if (($bill['balance'] ?? 0) > 0): ?>
                            ₹<?= number_format($bill['balance'], 2) ?>
                        <?php else: ?>
                            <i class="fas fa-check-circle text-success"></i> Clear
                        <?php endif; ?>
                    </td>
                    <td id="pm-cell-<?= $bill['id'] ?>">
                        <?php if ($bill['payment_method'] === 'Split'): ?>
                            <span class="payment-split-badge">💰 Cash+UPI</span>
                        <?php else: ?>
                            <?= htmlspecialchars($bill['payment_method'] ?? 'Cash') ?>
                        <?php endif; ?>
                    </td>
                    <td id="status-cell-<?= $bill['id'] ?>">
                        <?php
                        $statusClass = 'badge-paid';
                        $statusLabel = $bill['status'] ?? 'Paid';
                        if ($statusLabel === 'Partial') $statusClass = 'badge-partial';
                        else if ($statusLabel === 'Pending' || $statusLabel === 'Unpaid') $statusClass = 'badge-pending';
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                    </td>
                    <td class="actions">
                        <!-- View -->
                        <a href="billing_app.php?action=print&id=<?= $bill['id'] ?>" target="_blank" class="btn-view" title="View/Print">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <!-- Feature 2: Edit button -->
                        <a href="billing_app.php?action=edit&id=<?= $bill['id'] ?>" class="btn-edit" title="Edit Bill">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <!-- Print -->
                        <a href="billing_app.php?action=print&id=<?= $bill['id'] ?>" target="_blank" class="btn-print" title="Print">
                            <i class="fas fa-print"></i>
                        </a>
                        <!-- PDF -->
                        <a href="billing_app.php?action=pdf&id=<?= $bill['id'] ?>" class="btn-pdf" title="PDF">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                        <!-- Collect Payment -->
                        <?php if (($bill['balance'] ?? 0) > 0): ?>
                            <button type="button" class="btn-pay" onclick="openPaymentModal(<?= $bill['id'] ?>, <?= $bill['balance'] ?>, this)" title="Collect Payment">
                                <i class="fas fa-hand-holding-usd"></i> Pay
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Totals footer row -->
                <tr class="total-row">
                    <td colspan="8" style="text-align:right; font-size:12px; color:#666;">TOTALS →</td>
                    <td><strong>₹<?= number_format($totalNet, 2) ?></strong></td>
                    <td style="color:#2e7d32;"><strong>₹<?= number_format($totalPaid, 2) ?></strong></td>
                    <td style="color:red;"><strong>₹<?= number_format($totalBalance, 2) ?></strong></td>
                    <td colspan="3"></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Payment Modal -->
<div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1050; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:8px; width:350px; box-shadow:0 5px 15px rgba(0,0,0,0.3);">
        <h3 style="margin-top:0; color:#007bff; border-bottom:1px solid #eee; padding-bottom:10px;">Collect Payment</h3>
        <input type="hidden" id="pay_bill_id">
        <div style="margin-bottom:15px;">
            <label style="display:block; font-size:12px; font-weight:bold; color:#666;">Balance Due (₹)</label>
            <input type="text" id="pay_balance" readonly style="width:100%; padding:8px; border:1px solid #ddd; background:#f9f9f9; border-radius:4px; font-size:16px; font-weight:bold; color:#d32f2f;">
        </div>
        <div style="margin-bottom:15px;">
            <label style="display:block; font-size:12px; font-weight:bold; color:#666;">Amount Receiving (₹)</label>
            <input type="number" id="pay_amount" min="1" step="0.01" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
        </div>
        <div style="margin-bottom:15px;">
            <label style="display:block; font-size:12px; font-weight:bold; color:#666;">Payment Mode</label>
            <select id="pay_mode" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                <option value="Cash">Cash</option>
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closePaymentModal()" style="padding:8px 15px; border:none; background:#ccc; border-radius:4px; cursor:pointer;">Cancel</button>
            <button type="button" onclick="submitPayment()" id="btnSubmitPayment" style="padding:8px 15px; border:none; background:#28a745; color:#fff; border-radius:4px; cursor:pointer; font-weight:bold;">Save Payment</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F8') {
            e.preventDefault();
            window.location.href = 'billing_app.php?action=create';
        }
    });

    let currentPayButton = null;

    function openPaymentModal(billId, balance, btnElement) {
        document.getElementById('pay_bill_id').value = billId;
        document.getElementById('pay_balance').value = balance.toFixed(2);
        document.getElementById('pay_amount').value = balance.toFixed(2);
        document.getElementById('pay_mode').value = 'Cash';
        document.getElementById('paymentModal').style.display = 'flex';
        currentPayButton = btnElement;
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
        currentPayButton = null;
    }

    function submitPayment() {
        const billId = document.getElementById('pay_bill_id').value;
        const amount = parseFloat(document.getElementById('pay_amount').value);
        const mode = document.getElementById('pay_mode').value;
        const btn = document.getElementById('btnSubmitPayment');
        
        if (!amount || amount <= 0) {
            alert('Please enter a valid amount');
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Saving...';

        const formData = new FormData();
        formData.append('action', 'add_payment');
        formData.append('bill_id', billId);
        formData.append('amount', amount);
        formData.append('mode', mode);

        fetch('api_bill_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'Save Payment';
            if (data.success) {
                // Update UI dynamically
                const balCell = document.getElementById('bal-cell-' + billId);
                const statusCell = document.getElementById('status-cell-' + billId);
                
                if (data.new_balance <= 0) {
                    balCell.innerHTML = '<i class="fas fa-check-circle text-success"></i> Clear';
                    balCell.style.color = 'green';
                    statusCell.innerHTML = '<span class="badge badge-paid">Paid</span>';
                    if (currentPayButton) currentPayButton.remove();
                } else {
                    balCell.innerHTML = '₹' + data.new_balance;
                    statusCell.innerHTML = '<span class="badge badge-partial">Partial</span>';
                    if (currentPayButton) currentPayButton.setAttribute('onclick', `openPaymentModal(${billId}, ${data.new_balance}, this)`);
                }
                closePaymentModal();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'Save Payment';
            alert('Request failed');
        });
    }
</script>
</body>
</html>
