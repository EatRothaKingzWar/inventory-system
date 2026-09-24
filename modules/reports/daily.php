<?php
// =========================================================================
// ឯកសារ: modules/reports/daily.php
// គោលបំណង: របាយការណ៍បិទបញ្ជីលុយប្រចាំថ្ងៃ (Daily Cash Close)
// =========================================================================

$page_title = 'បិទបញ្ជីប្រចាំថ្ងៃ (Daily Close)';
require_once __DIR__ . '/../../includes/header.php';

$selected_date = $_GET['date'] ?? date('Y-m-d');
$exchange_rate = 4100;

// ទាញទិន្នន័យសរុបថ្ងៃនេះ
$stmt = db_query($pdo, "SELECT 
    COUNT(*) AS total_sales,
    COALESCE(SUM(subtotal), 0) AS subtotal,
    COALESCE(SUM(discount), 0) AS total_discount,
    COALESCE(SUM(total_amount), 0) AS total_cash
FROM sales WHERE DATE(created_at) = ?", [$selected_date]);
$daily = $stmt->fetch();

$total_usd = $daily['total_cash'];
$total_khr = $total_usd * $exchange_rate;

// ទាញបញ្ជីទំនិញដែលលក់ដាច់ក្នុងថ្ងៃនេះ
$items_stmt = db_query($pdo, "SELECT p.name, SUM(si.quantity) AS qty, SUM(si.subtotal) AS amount
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.created_at) = ?
    GROUP BY p.id, p.name ORDER BY qty DESC", [$selected_date]);
$today_items = $items_stmt->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 850px;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h4 class="fw-bold text-primary mb-0"><i class="fa fa-cash-register me-2"></i>បិទបញ្ជីលុយប្រចាំថ្ងៃ</h4>
                <small class="text-muted">កាលបរិច្ឆេទ: <strong><?= date('d/m/Y', strtotime($selected_date)) ?></strong></small>
            </div>
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="date" class="form-control form-control-sm" value="<?= e($selected_date) ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary">មើល</button>
            </form>
        </div>

        <!-- ផ្ទាំងលុយសរុបក្នុងកន្ត្រក -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card bg-success text-white border-0 p-3 rounded-3 text-center shadow-sm">
                    <small class="text-uppercase text-white-50">លុយសុទ្ធសរុប (USD)</small>
                    <h2 class="fw-bold mb-0">$<?= number_format($total_usd, 2) ?></h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-primary text-white border-0 p-3 rounded-3 text-center shadow-sm">
                    <small class="text-uppercase text-white-50">គិតជាប្រាក់រៀល (KHR 4,100៛)</small>
                    <h2 class="fw-bold mb-0"><?= number_format($total_khr) ?> ៛</h2>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-4 text-center border-end">
                <small class="text-muted">វិក្កយបត្រសរុប</small>
                <h5 class="fw-bold mb-0"><?= $daily['total_sales'] ?></h5>
            </div>
            <div class="col-4 text-center border-end">
                <small class="text-muted">សរុបរង</small>
                <h5 class="fw-bold mb-0">$<?= number_format($daily['subtotal'], 2) ?></h5>
            </div>
            <div class="col-4 text-center">
                <small class="text-muted">បញ្ចុះតម្លៃ</small>
                <h5 class="fw-bold text-danger mb-0">-$<?= number_format($daily['total_discount'], 2) ?></h5>
            </div>
        </div>

        <h6 class="fw-bold mb-3"><i class="fa fa-list-check me-2"></i>ទំនិញលក់បានក្នុងថ្ងៃនេះ</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ឈ្មោះទំនិញ</th>
                        <th class="text-center" width="100">ចំនួនលក់</th>
                        <th class="text-end" width="140">ទឹកប្រាក់សរុប</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($today_items)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">គ្មានការលក់ក្នុងថ្ងៃនេះទេ</td></tr>
                    <?php else: foreach ($today_items as $item): ?>
                        <tr>
                            <td class="fw-bold"><?= e($item['name']) ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $item['qty'] ?></span></td>
                            <td class="text-end fw-bold text-success">$<?= number_format($item['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
