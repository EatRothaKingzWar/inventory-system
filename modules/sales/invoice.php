<?php
// =========================================================================
// ឯកសារ: modules/sales/invoice.php
// គោលបំណង: បង្ហាញព័ត៌មានលម្អិតនៃវិក្កយបត្រ និងទំនិញដែលបានទិញ
// =========================================================================

$page_title = 'ព័ត៌មានលម្អិតវិក្កយបត្រ';
require_once __DIR__ . '/../../includes/header.php';

// ១. ទទួល Sale ID ពី URL
$sale_id = (int)($_GET['id'] ?? 0);

// ២. ទាញព័ត៌មានវិក្កយបត្រមេ ភ្ជាប់ជាមួយឈ្មោះអ្នកគិតលុយ (User)
$sql_sale = "SELECT s.*, u.full_name AS cashier_name 
             FROM sales s 
             LEFT JOIN users u ON s.user_id = u.id 
             WHERE s.id = ?";
$stmt = db_query($pdo, $sql_sale, [$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    set_flash('danger', 'រកមិនឃើញវិក្កយបត្រនេះទេ!');
    redirect('/modules/sales/index.php');
}

// ៣. ទាញបញ្ជីទំនិញទាំងអស់ដែលមានក្នុងវិក្កយបត្រនេះ
$sql_items = "SELECT si.*, p.name AS product_name, p.barcode 
              FROM sale_items si 
              JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = ?";
$items = db_query($pdo, $sql_items, [$sale_id])->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 800px;">
    <div class="card-body p-4">
        <!-- ក្បាលវិក្កយបត្រ -->
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <div>
                <h4 class="fw-bold text-primary mb-1"><i class="fa fa-file-invoice me-2"></i>វិក្កយបត្រ / INVOICE</h4>
                <div class="text-muted small">លេខវិក្កយបត្រ: <strong class="text-dark"><?= e($sale['invoice_no']) ?></strong></div>
            </div>
            <div class="text-end">
                <span class="badge bg-success fs-6 px-3 py-2">ទូទាត់រួចរាល់</span>
                <div class="text-muted small mt-1">កាលបរិច្ឆេទ: <?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <small class="text-muted text-uppercase">អ្នកគិតលុយ (Cashier)</small>
                <div class="fw-bold"><?= e($sale['cashier_name'] ?: 'បុគ្គលិក') ?></div>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted text-uppercase">វិធីទូទាត់ (Payment)</small>
                <div class="fw-bold text-uppercase"><?= e($sale['payment_method']) ?></div>
            </div>
        </div>

        <!-- តារាងទំនិញ -->
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th>ទំនិញ</th>
                    <th class="text-center" width="90">ចំនួន</th>
                    <th class="text-end" width="130">តម្លៃរាយ</th>
                    <th class="text-end" width="140">សរុប</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $item): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <strong><?= e($item['product_name']) ?></strong>
                            <?php if ($item['barcode']): ?>
                                <small class="text-muted d-block"><?= e($item['barcode']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end"><?= format_money($item['unit_price']) ?></td>
                        <td class="text-end fw-bold"><?= format_money($item['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">សរុបរង (Subtotal):</th>
                    <th class="text-end"><?= format_money($sale['subtotal']) ?></th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end text-danger">បញ្ចុះតម្លៃ (Discount):</th>
                    <th class="text-end text-danger">-<?= format_money($sale['discount']) ?></th>
                </tr>
                <tr class="table-light fs-5">
                    <th colspan="4" class="text-end fw-bold text-primary">ទឹកប្រាក់សរុប (Total Paid):</th>
                    <th class="text-end fw-bold text-primary"><?= format_money($sale['total_amount']) ?></th>
                </tr>
            </tfoot>
        </table>

        <!-- ប៊ូតុង Print និងត្រឡប់ក្រោយ -->
        <div class="d-flex justify-content-between mt-4">
            <a href="/modules/sales/create.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> ទៅកន្លែងលក់វិញ</a>
            <a href="print.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-primary px-4"><i class="fa fa-print me-1"></i> បោះពុម្ពប័ណ្ណទូទាត់ (Print Receipt)</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
