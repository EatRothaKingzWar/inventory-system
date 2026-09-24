<?php
// =========================================================================
// ឯកសារ: modules/sales/print_a4.php
// គោលបំណង: ប័ណ្ណដឹកជញ្ជូន និងវិក្កយបត្រខ្នាត A4/A5 សម្រាប់ដេប៉ូគ្រឿងសំណង់
// =========================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$sale_id = (int)($_GET['id'] ?? 0);

$sql = "SELECT s.*, u.full_name AS cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?";
$sale = db_query($pdo, $sql, [$sale_id])->fetch();
if (!$sale) die("រកមិនឃើញវិក្កយបត្រ!");

$sql_items = "SELECT si.*, p.name, p.unit FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?";
$items = db_query($pdo, $sql_items, [$sale_id])->fetchAll();
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ប័ណ្ណដឹកជញ្ជូន - <?= e($sale['invoice_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background: #fff;
            color: #000;
            padding: 20px;
            font-size: 13px;
        }
        .container { max-width: 800px; margin: auto; }
        .header-title { font-size: 22px; font-weight: bold; color: #1e3a8a; }
        .sub-title { font-size: 13px; color: #555; }
        .border-box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #94a3b8; padding: 8px 10px; }
        th { background-color: #f1f5f9; text-align: left; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .badge-status {
            display: inline-block; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;
        }
        .badge-paid { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; text-align: center; }
        .sig-box { width: 30%; }
        .sig-line { border-bottom: 1px dashed #000; height: 60px; margin-bottom: 8px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
<div class="container">

    <!-- ក្បាលវិក្កយបត្រដេប៉ូ -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 15px;">
        <div>
            <div class="header-title">ដេប៉ូផ្គត់ផ្គង់គ្រឿងសំណង់ / DEPOT MATERIALS</div>
            <div class="sub-title">មានលក់: ស៊ីម៉ងត៍, ដែក, ខ្សាច់, ថ្ម, ការ៉ូ, ថ្នាំលាប និងសម្ភារៈសំណង់គ្រប់ប្រភេទ</div>
            <div class="sub-title">រាជធានីភ្នំពេញ | ទូរស័ព្ទ: 012 345 678 / 098 765 432</div>
        </div>
        <div style="text-align: right;">
            <h3 style="margin: 0; color: #1e3a8a;">ប័ណ្ណដឹកជញ្ជូនទំនិញ</h3>
            <div style="font-size: 11px; color: #64748b;">DELIVERY NOTE & INVOICE</div>
            <div style="margin-top: 5px;">
                <?php if (($sale['payment_status'] ?? 'paid') === 'unpaid'): ?>
                    <span class="badge-status badge-unpaid">⏳ ទិញជំពាក់ (Credit)</span>
                <?php else: ?>
                    <span class="badge-status badge-paid">✅ ទូទាត់ដាច់ (Paid)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ព័ត៌មានអតិថិជន និងការដ្ឋាន -->
    <div style="display: flex; gap: 15px;">
        <div class="border-box" style="flex: 1;">
            <div class="fw-bold" style="color: #1e3a8a; margin-bottom: 5px;">ព័ត៌មានអតិថិជន / ការដ្ឋាន:</div>
            <div>ឈ្មោះមេការ / អតិថិជន: <strong><?= e($sale['customer_name'] ?: 'អតិថិជនទូទៅ') ?></strong></div>
            <div>លេខទូរស័ព្ទ: <strong><?= e($sale['customer_phone'] ?: 'N/A') ?></strong></div>
            <div>ទីតាំងការដ្ឋានដឹកជញ្ជូន: <strong><?= e($sale['delivery_address'] ?: 'ដឹកដល់កន្លែង') ?></strong></div>
        </div>
        <div class="border-box" style="flex: 1;">
            <div class="fw-bold" style="color: #1e3a8a; margin-bottom: 5px;">ព័ត៌មានប័ណ្ណ:</div>
            <div>លេខប័ណ្ណ: <strong><?= e($sale['invoice_no']) ?></strong></div>
            <div>កាលបរិច្ឆេទ: <strong><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></strong></div>
            <div>អ្នកគិតលុយ: <strong><?= e($sale['cashier_name'] ?: 'បុគ្គលិក') ?></strong></div>
        </div>
    </div>

    <!-- តារាងទំនិញ -->
    <table>
        <thead>
            <tr>
                <th width="40" class="text-center">#</th>
                <th>បរិយាយមុខទំនិញ (Description)</th>
                <th width="70" class="text-center">ឯកតា</th>
                <th width="70" class="text-center">ចំនួន</th>
                <th width="110" class="text-end">តម្លៃឯកតា ($)</th>
                <th width="130" class="text-end">ទឹកប្រាក់សរុប ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $it): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td class="fw-bold"><?= e($it['name']) ?></td>
                    <td class="text-center"><?= e($it['unit'] ?: 'ដើម') ?></td>
                    <td class="text-center fw-bold"><?= $it['quantity'] ?></td>
                    <td class="text-end">$<?= number_format($it['unit_price'], 2) ?></td>
                    <td class="text-end fw-bold">$<?= number_format($it['subtotal'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" rowspan="3" style="vertical-align: middle;">
                    <div class="small">
                        <strong>សម្គាល់:</strong> សូមពិនិត្យចំនួន និងគុណភាពទំនិញឱ្យបានត្រឹមត្រូវ មុនពេលចុះហត្ថលេខាទទួល។
                    </div>
                </td>
                <td class="text-end fw-bold">សរុបរង:</td>
                <td class="text-end fw-bold">$<?= number_format($sale['subtotal'], 2) ?></td>
            </tr>
            <?php if ($sale['discount'] > 0): ?>
                <tr>
                    <td class="text-end text-danger fw-bold">បញ្ចុះតម្លៃ:</td>
                    <td class="text-end text-danger fw-bold">-$<?= number_format($sale['discount'], 2) ?></td>
                </tr>
            <?php endif; ?>
            <tr style="background: #f8fafc;">
                <td class="text-end fw-bold" style="font-size: 15px; color: #1e3a8a;">ទឹកប្រាក់ត្រូវទូទាត់:</td>
                <td class="text-end fw-bold" style="font-size: 15px; color: #1e3a8a;">
                    $<?= number_format($sale['total_amount'], 2) ?>
                    <div style="font-size: 12px; color: #64748b;"><?= number_format($sale['total_amount'] * 4100) ?> ៛</div>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- កន្លែងចុះហត្ថលេខា ៣ ផ្នែក -->
    <div class="signature-section">
        <div class="sig-box">
            <div>អ្នកចេញទំនិញ</div>
            <div class="sig-line"></div>
            <div>ហត្ថលេខា & ឈ្មោះ</div>
        </div>
        <div class="sig-box">
            <div>អ្នកដឹកជញ្ជូន</div>
            <div class="sig-line"></div>
            <div>ហត្ថលេខា & ឈ្មោះ</div>
        </div>
        <div class="sig-box">
            <div><strong>អ្នកទទួលទំនិញ / មេការ</strong></div>
            <div class="sig-line"></div>
            <div>ហត្ថលេខា & ឈ្មោះ</div>
        </div>
    </div>

</div>
</body>
</html>
