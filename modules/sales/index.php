<?php
// =========================================================================
// ឯកសារ: modules/sales/index.php
// គោលបំណង: បង្ហាញបញ្ជីប្រវត្តិវិក្កយបត្រទាំងអស់ និងស្វែងរកតាមកាលបរិច្ឆេទ
// =========================================================================

$page_title = 'បញ្ជីវិក្កយបត្រលក់';
require_once __DIR__ . '/../../includes/header.php';

// ១. ទទួលចន្លោះកាលបរិច្ឆេទពី Filter
$from_date = $_GET['from_date'] ?? date('Y-m-01'); // ថ្ងៃទី ១ នៃខែបច្ចុប្បន្ន
$to_date   = $_GET['to_date'] ?? date('Y-m-d');

$sql = "SELECT s.*, u.full_name AS cashier_name 
        FROM sales s 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE DATE(s.created_at) BETWEEN ? AND ? 
        ORDER BY s.id DESC";

$stmt = db_query($pdo, $sql, [$from_date, $to_date]);
$sales = $stmt->fetchAll();

// គណនាផលបូកសរុបក្នុងចន្លោះកាលបរិច្ឆេទនោះ
$total_revenue = 0;
foreach ($sales as $s) {
    $total_revenue += $s['total_amount'];
}
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <!-- របារ Filter តាមកាលបរិច្ឆេទ -->
        <form method="GET" class="row g-2 align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label small">ចាប់ពីថ្ងៃ</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= e($from_date) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">ដល់ថ្ងៃ</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= e($to_date) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-filter me-1"></i> ស្វែងរក</button>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="text-muted small d-block">ចំណូលសរុបក្នុងចន្លោះនេះ:</span>
                <span class="fs-4 fw-bold text-success"><?= format_money($total_revenue) ?></span>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>លេខវិក្កយបត្រ</th>
                        <th>កាលបរិច្ឆេទ</th>
                        <th>អ្នកគិតលុយ</th>
                        <th>វិធីទូទាត់</th>
                        <th>សរុបរង</th>
                        <th>បញ្ចុះតម្លៃ</th>
                        <th>ទឹកប្រាក់ជាក់ស្តែង</th>
                        <th class="text-center">សកម្មភាព</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">មិនមានទិន្នន័យការលក់ក្នុងចន្លោះកាលបរិច្ឆេទនេះទេ</td></tr>
                    <?php else: foreach ($sales as $row): ?>
                        <tr>
                            <td class="fw-bold"><code><?= e($row['invoice_no']) ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td><?= e($row['cashier_name'] ?: 'N/A') ?></td>
                            <td><span class="badge bg-light text-dark border text-uppercase"><?= e($row['payment_method']) ?></span></td>
                            <td><?= format_money($row['subtotal']) ?></td>
                            <td class="text-danger">-<?= format_money($row['discount']) ?></td>
                            <td class="fw-bold text-success"><?= format_money($row['total_amount']) ?></td>
                            <td class="text-center">
                                <a href="invoice.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info" title="មើលលម្អិត"><i class="fa fa-eye"></i></a>
                                <a href="print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="ព្រីន"><i class="fa fa-print"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
