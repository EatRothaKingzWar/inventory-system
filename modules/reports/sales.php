<?php
// =========================================================================
// ឯកសារ: modules/reports/sales.php
// គោលបំណង: របាយការណ៍លក់ និងគណនាប្រាក់ចំណេញដុល (Gross Profit Report)
// =========================================================================

$page_title = 'របាយការណ៍លក់ និងប្រាក់ចំណេញ';
require_once __DIR__ . '/../../includes/header.php';

// ១. ទទួលចន្លោះកាលបរិច្ឆេទពី Filter
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date'] ?? date('Y-m-d');

// ២. ទាញទិន្នន័យចំណូល ចំណាយថ្លៃដើម និងការបញ្ចុះតម្លៃ
$sql_summary = "SELECT 
    COUNT(DISTINCT s.id) AS total_invoices,
    COALESCE(SUM(si.quantity * si.unit_price), 0) AS gross_sales,
    COALESCE(SUM(s.discount), 0) AS total_discount,
    COALESCE(SUM(si.quantity * si.cost_price), 0) AS total_cost
FROM sales s
LEFT JOIN sale_items si ON s.id = si.sale_id
WHERE DATE(s.created_at) BETWEEN ? AND ?";

$stmt = db_query($pdo, $sql_summary, [$from_date, $to_date]);
$summary = $stmt->fetch();

$revenue = $summary['gross_sales'] - $summary['total_discount'];
$cogs    = $summary['total_cost']; // ថ្លៃដើម
$profit  = $revenue - $cogs;      // ប្រាក់ចំណេញដុល

// ៣. ទាញទំនិញលក់ដាច់បំផុត ៥ មុខ (Top 5 Best Sellers)
$sql_top = "SELECT p.name, SUM(si.quantity) AS total_qty, SUM(si.subtotal) AS total_amount
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY p.id, p.name
            ORDER BY total_qty DESC LIMIT 5";
$top_products = db_query($pdo, $sql_top, [$from_date, $to_date])->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
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
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-filter me-1"></i> មើលរបាយការណ៍</button>
            </div>
        </form>

        <!-- ផ្ទាំងសង្ខេបចំណូល ចំណាយ និងប្រាក់ចំណេញ -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-light border p-3 rounded-3 text-center">
                    <small class="text-muted text-uppercase">វិក្កយបត្រសរុប</small>
                    <h3 class="fw-bold mb-0 text-dark"><?= number_format($summary['total_invoices']) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border p-3 rounded-3 text-center">
                    <small class="text-muted text-uppercase">ចំណូលសរុប (Revenue)</small>
                    <h3 class="fw-bold mb-0 text-primary"><?= format_money($revenue) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border p-3 rounded-3 text-center">
                    <small class="text-muted text-uppercase">ថ្លៃដើមទំនិញ (COGS)</small>
                    <h3 class="fw-bold mb-0 text-danger"><?= format_money($cogs) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border p-3 rounded-3 text-center">
                    <small class="text-muted text-uppercase">ប្រាក់ចំណេញដុល (Profit)</small>
                    <h3 class="fw-bold mb-0 text-success"><?= format_money($profit) ?></h3>
                </div>
            </div>
        </div>

        <!-- បញ្ជីទំនិញលក់ដាច់បំផុត -->
        <h6 class="fw-bold mb-3"><i class="fa fa-fire text-danger me-2"></i>ទំនិញលក់ដាច់បំផុតទាំង ៥ មុខ</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ឈ្មោះទំនិញ</th>
                        <th class="text-center">ចំនួនលក់ចេញសរុប</th>
                        <th class="text-end">ទឹកប្រាក់សរុប</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_products)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">គ្មានទិន្នន័យលក់ក្នុងចន្លោះថ្ងៃនេះទេ</td></tr>
                    <?php else: foreach ($top_products as $prod): ?>
                        <tr>
                            <td class="fw-bold"><?= e($prod['name']) ?></td>
                            <td class="text-center"><span class="badge bg-primary px-3 py-2"><?= $prod['total_qty'] ?></span></td>
                            <td class="text-end fw-bold text-success"><?= format_money($prod['total_amount']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
