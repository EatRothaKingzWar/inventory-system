<?php
// =========================================================================
// ឯកសារ: modules/sales/print.php
// គោលបំណង: បោះពុម្ពប័ណ្ណទូទាត់សម្រាប់ហាងលក់នៅផ្ទះ (Thermal Receipt 80mm)
// =========================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$sale_id = (int)($_GET['id'] ?? 0);

$sale = db_query($pdo, "SELECT s.*, u.full_name AS cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?", [$sale_id])->fetch();
if (!$sale) die("រកមិនឃើញវិក្កយបត្រ!");

$items = db_query($pdo, "SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?", [$sale_id])->fetchAll();
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>Print - <?= e($sale['invoice_no']) ?></title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body {
            font-family: 'Courier New', monospace, sans-serif;
            width: 76mm;
            margin: auto;
            padding: 8px 4px;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .border-dashed { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        .border-top-dashed { border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 3px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print();">

    <div class="text-center border-dashed">
        <h3 style="margin:0; font-size:16px;">ហាងលក់ទំនិញ / HOME STORE</h3>
        <p style="margin:2px 0;">រាជធានីភ្នំពេញ | Tel: 012 345 678</p>
        <p style="margin:2px 0;"><strong>ប័ណ្ណទូទាត់ប្រាក់ / RECEIPT</strong></p>
    </div>

    <div style="font-size: 11px;" class="border-dashed">
        <div>លេខវិក្កយបត្រ: <?= e($sale['invoice_no']) ?></div>
        <div>កាលបរិច្ឆេទ: <?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></div>
        <div>អ្នកគិតលុយ: <?= e($sale['cashier_name'] ?: 'Staff') ?></div>
    </div>

    <table>
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th align="left">ទំនិញ</th>
                <th align="center">ចំនួន</th>
                <th align="right">តម្លៃ</th>
                <th align="right">សរុប</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['name']) ?></td>
                    <td align="center"><?= $it['quantity'] ?></td>
                    <td align="right">$<?= number_format($it['unit_price'], 2) ?></td>
                    <td align="right">$<?= number_format($it['subtotal'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="border-top-dashed" style="line-height: 1.6;">
        <div style="display:flex; justify-content:space-between;">
            <span>សរុបរង:</span>
            <span>$<?= number_format($sale['subtotal'], 2) ?></span>
        </div>
        <?php if ($sale['discount'] > 0): ?>
            <div style="display:flex; justify-content:space-between;">
                <span>បញ្ចុះតម្លៃ:</span>
                <span>-$<?= number_format($sale['discount'], 2) ?></span>
            </div>
        <?php endif; ?>
        <div class="fw-bold border-top-dashed" style="display:flex; justify-content:space-between; font-size:14px;">
            <span>សរុប (USD):</span>
            <span>$<?= number_format($sale['total_amount'], 2) ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; font-weight:bold;">
            <span>ជាប្រាក់រៀល (4,100៛):</span>
            <span><?= number_format($sale['total_amount'] * 4100) ?> ៛</span>
        </div>
    </div>

    <div class="text-center border-top-dashed" style="margin-top: 10px;">
        <p style="margin: 3px 0;">សូមអរគុណ! សូមអញ្ជើញមកម្តងទៀត!</p>
        <small>Thank you for your visit!</small>
    </div>

</body>
</html>
