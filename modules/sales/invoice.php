<?php
// =========================================================================
// ឯកសារ: modules/sales/invoice.php
// គោលបំណង: បង្ហាញព័ត៌មានវិក្កយបត្រ + ជម្រើសបោះពុម្ពប័ណ្ណដឹកជញ្ជូន A4/A5
// =========================================================================

$page_title = 'ព័ត៌មានលម្អិតវិក្កយបត្រ';
require_once __DIR__ . '/../../includes/header.php';

$sale_id = (int)($_GET['id'] ?? 0);

$sql_sale = "SELECT s.*, u.full_name AS cashier_name 
             FROM sales s 
             LEFT JOIN users u ON s.user_id = u.id 
             WHERE s.id = ?";
$sale = db_query($pdo, $sql_sale, [$sale_id])->fetch();

if (!$sale) {
    set_flash('danger', 'រកមិនឃើញវិក្កយបត្រនេះទេ!');
    redirect('/modules/sales/index.php');
}

$sql_items = "SELECT si.*, p.name AS product_name, p.unit, p.barcode 
              FROM sale_items si 
              JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = ?";
$items = db_query($pdo, $sql_items, [$sale_id])->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 850px;">
    <div class="card-body p-4">
        <!-- ក្បាលវិក្កយបត្រ -->
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <div>
                <h4 class="fw-bold text-primary mb-1"><i class="fa fa-file-invoice me-2"></i>វិក្កយបត្រ & ប័ណ្ណដឹកជញ្ជូន</h4>
                <div class="text-muted small">លេខវិក្កយបត្រ: <strong class="text-dark"><?= e($sale['invoice_no']) ?></strong></div>
            </div>
            <div class="text-end">
                <?php if (($sale['payment_status'] ?? 'paid') === 'unpaid'): ?>
                    <span class="badge bg-danger fs-6 px-3 py-2">⏳ ទិញជំពាក់ (Credit)</span>
                <?php else: ?>
                    <span class="badge bg-success fs-6 px-3 py-2">✅ ទូទាត់ដាច់ (Paid)</span>
                <?php endif; ?>
                <div class="text-muted small mt-1"><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></div>
            </div>
        </div>

        <!-- ព័ត៌មានមេការ & ការដ្ឋាន -->
        <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
            <div class="col-md-6">
                <small class="text-muted text-uppercase fw-bold">មេការ / អតិថិជន:</small>
                <div class="fw-bold fs-6 text-dark"><?= e($sale['customer_name'] ?: 'អតិថិជនទូទៅ') ?></div>
                <div class="small text-muted"><i class="fa fa-phone me-1"></i><?= e($sale['customer_phone'] ?: 'គ្មាន') ?></div>
            </div>
            <div class="col-md-6">
                <small class="text-muted text-uppercase fw-bold">ទីតាំងការដ្ឋានដឹកជញ្ជូន:</small>
                <div class="fw-bold fs-6 text-dark"><i class="fa fa-map-marker-alt text-danger me-1"></i><?= e($sale['delivery_address'] ?: 'ដឹកដល់កន្លែង') ?></div>
            </div>
        </div>

        <!-- តារាងទំនិញ -->
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th width="40">#</th>
                    <th>បរិយាយទំនិញ</th>
                    <th class="text-center" width="90">ឯកតា</th>
                    <th class="text-center" width="80">ចំនួន</th>
                    <th class="text-end" width="120">តម្លៃរាយ</th>
                    <th class="text-end" width="130">សរុប</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $item): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td class="fw-bold"><?= e($item['product_name']) ?></td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary"><?= e($item['unit'] ?: 'ដើម') ?></span></td>
                        <td class="text-center fw-bold"><?= $item['quantity'] ?></td>
                        <td class="text-end"><?= format_money($item['unit_price']) ?></td>
                        <td class="text-end fw-bold text-success"><?= format_money($item['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-end">សរុបរង:</th>
                    <th class="text-end"><?= format_money($sale['subtotal']) ?></th>
                </tr>
                <?php if ($sale['discount'] > 0): ?>
                    <tr>
                        <th colspan="5" class="text-end text-danger">បញ្ចុះតម្លៃ:</th>
                        <th class="text-end text-danger">-<?= format_money($sale['discount']) ?></th>
                    </tr>
                <?php endif; ?>
                <tr class="table-light fs-5">
                    <th colspan="5" class="text-end fw-bold text-primary">ទឹកប្រាក់សរុប:</th>
                    <th class="text-end fw-bold text-primary"><?= format_money($sale['total_amount']) ?></th>
                </tr>
            </tfoot>
        </table>

        <!-- ប៊ូតុង Print ជម្រើស ២ -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-2 border-top">
            <a href="/modules/sales/create.php" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> ទៅកន្លែងលក់វិញ
            </a>
            <div class="d-flex gap-2">
                <a href="print.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-outline-dark">
                    <i class="fa fa-receipt me-1"></i> ព្រីនវិក្កយបត្រតូច (80mm)
                </a>
                <a href="print_a4.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-primary px-3 shadow-sm">
                    <i class="fa fa-file-invoice me-1"></i> ព្រីនប័ណ្ណដឹកជញ្ជូន (A4/A5)
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
