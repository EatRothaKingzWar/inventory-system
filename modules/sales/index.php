<?php
// =========================================================================
// ឯកសារ: modules/sales/index.php
// គោលបំណង: បញ្ជីវិក្កយបត្រ + ប៊ូតុងកត់ត្រាពេលមេការមកសងលុយ (Mark as Paid)
// =========================================================================

$page_title = 'បញ្ជីវិក្កយបត្រលក់';
require_once __DIR__ . '/../../includes/header.php';

// ១. ដំណើរការពេលចុចប៊ូតុង «សងលុយ»
if (isset($_GET['mark_paid'])) {
    $settle_id = (int)$_GET['mark_paid'];
    if ($settle_id > 0) {
        db_query($pdo, "UPDATE sales SET payment_status = 'paid' WHERE id = ?", [$settle_id]);
        set_flash('success', 'បានកត់ត្រាការទូទាត់លុយសងរួចរាល់ដោយជោគជ័យ!');
        redirect('/modules/sales/index.php');
    }
}

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date'] ?? date('Y-m-d');

$sql = "SELECT 
            s.*, 
            u.full_name AS cashier_name,
            STRING_AGG(p.name || ' (x' || si.quantity || ' ' || COALESCE(p.unit, 'ដើម') || ')', ', ') AS product_summary
        FROM sales s 
        LEFT JOIN users u ON s.user_id = u.id 
        LEFT JOIN sale_items si ON s.id = si.sale_id
        LEFT JOIN products p ON si.product_id = p.id
        WHERE DATE(s.created_at) BETWEEN ? AND ? 
        GROUP BY s.id, u.full_name
        ORDER BY s.id DESC";

$sales = db_query($pdo, $sql, [$from_date, $to_date])->fetchAll();

$total_revenue = 0;
foreach ($sales as $s) {
    $total_revenue += $s['total_amount'];
}
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="GET" class="row g-2 align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label small fw-bold">ចាប់ពីថ្ងៃ</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= e($from_date) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">ដល់ថ្ងៃ</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= e($to_date) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-filter me-1"></i> ស្វែងរក</button>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="text-muted small d-block">ចំណូលសរុបក្នុងចន្លោះនេះ:</span>
                <span class="fs-4 fw-bold text-success">$<?= number_format($total_revenue, 2) ?></span>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>លេខវិក្កយបត្រ</th>
                        <th>កាលបរិច្ឆេទ</th>
                        <th>មេការ / អតិថិជន</th>
                        <th width="32%">មុខទំនិញដែលបានទិញ (ចំនួន & ឯកតា)</th>
                        <th class="text-center" width="110">ស្ថានភាព</th>
                        <th class="text-end">ទឹកប្រាក់ជាក់ស្តែង</th>
                        <th class="text-center" width="110">សកម្មភាព</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">មិនមានទិន្នន័យការលក់ក្នុងចន្លោះកាលបរិច្ឆេទនេះទេ</td></tr>
                    <?php else: foreach ($sales as $row): ?>
                        <tr>
                            <td class="fw-bold"><code><?= e($row['invoice_no']) ?></code></td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            
                            <td>
                                <div class="fw-bold text-dark"><?= e($row['customer_name'] ?: 'អតិថិជនទូទៅ') ?></div>
                                <?php if ($row['customer_phone']): ?>
                                    <small class="text-muted"><i class="fa fa-phone me-1"></i><?= e($row['customer_phone']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php 
                                if ($row['product_summary']): 
                                    $items = explode(', ', $row['product_summary']);
                                    foreach ($items as $it): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 mb-1 fs-6 fw-normal">
                                            <i class="fa fa-box me-1"></i><?= e($it) ?>
                                        </span>
                                    <?php endforeach; 
                                else: ?>
                                    <span class="text-muted small">គ្មានទំនិញ</span>
                                <?php endif; ?>
                            </td>

                            <!-- ស្ថានភាពទូទាត់ + ប៊ូតុងសងលុយ -->
                            <td class="text-center">
                                <?php if (($row['payment_status'] ?? 'paid') === 'unpaid'): ?>
                                    <span class="badge bg-danger mb-1 d-block">⏳ ជំពាក់</span>
                                    <a href="index.php?mark_paid=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-outline-success py-0 px-2 fw-bold" 
                                       style="font-size:11px;" 
                                       onclick="return confirm('តើអ្នកពិតជាបានទទួលលុយសងពីមេការនេះគ្រប់ចំនួនហើយមែនទេ?')"
                                       title="ចុចដើម្បីកត់ត្រាថាបានសងលុយ">
                                        <i class="fa fa-hand-holding-dollar me-1"></i>សងលុយ
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-success">✅ បង់ដាច់</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-end fw-bold text-success fs-6">
                                $<?= number_format($row['total_amount'], 2) ?>
                                <div class="small text-muted fw-normal"><?= number_format($row['total_amount'] * 4100) ?> ៛</div>
                            </td>

                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="invoice.php?id=<?= $row['id'] ?>" class="btn btn-outline-info" title="មើលលម្អិត"><i class="fa fa-eye"></i></a>
                                    <a href="print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="ព្រីនតូច (80mm)"><i class="fa fa-receipt"></i></a>
                                    <a href="print_a4.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-outline-primary" title="ព្រីនប័ណ្ណដឹកជញ្ជូន A4"><i class="fa fa-file-invoice"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
