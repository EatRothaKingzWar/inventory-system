<?php
// =========================================================================
// ឯកសារ: modules/reports/monthly.php
// គោលបំណង: របាយការណ៍ហិរញ្ញវត្ថុ ស្តុកចេញ និងបំណុលប្រចាំខែសម្រាប់ដេប៉ូគ្រឿងសំណង់
// =========================================================================

$page_title = 'របាយការណ៍ប្រចាំខែ (Monthly Report)';
require_once __DIR__ . '/../../includes/header.php';

define('EXCHANGE_RATE', 4100);

// ១. ទទួលខែដែលបានជ្រើសរើស (លំនាំដើម: ខែបច្ចុប្បន្ន)
$selected_month = $_GET['month'] ?? date('Y-m');
$month_start    = $selected_month . '-01';
$month_end      = date('Y-m-t', strtotime($month_start));

// ២. ទាញទិន្នន័យសង្ខេបហិរញ្ញវត្ថុ
$sql_summary = "SELECT 
    COUNT(DISTINCT s.id) AS total_invoices,
    COALESCE(SUM(s.total_amount), 0) AS total_revenue,
    COALESCE(SUM(CASE WHEN s.payment_status = 'unpaid' THEN s.total_amount ELSE 0 END), 0) AS total_debt,
    COALESCE(SUM(CASE WHEN s.payment_status = 'paid' THEN s.total_amount ELSE 0 END), 0) AS total_paid,
    COALESCE(SUM(s.discount), 0) AS total_discount,
    COALESCE(SUM(si.quantity), 0) AS total_units_sold,
    COALESCE(SUM(si.quantity * si.cost_price), 0) AS total_cogs
FROM sales s
LEFT JOIN sale_items si ON s.id = si.sale_id
WHERE DATE(s.created_at) BETWEEN ? AND ?";

$summary = db_query($pdo, $sql_summary, [$month_start, $month_end])->fetch();

$revenue = (float)$summary['total_revenue'];
$cogs    = (float)$summary['total_cogs'];
$profit  = $revenue - $cogs; // ប្រាក់ចំណេញដុល
$debt    = (float)$summary['total_debt'];

// ៣. ទាញបញ្ជីមុខទំនិញ និងប្រាក់ចំណេញតាមមុខទំនិញ
$sql_items = "SELECT 
    p.name, p.unit, c.name AS category_name,
    COALESCE(SUM(si.quantity), 0) AS qty_sold,
    COALESCE(SUM(si.quantity * si.cost_price), 0) AS item_cogs,
    COALESCE(SUM(si.subtotal), 0) AS item_revenue,
    COALESCE(SUM(si.subtotal) - SUM(si.quantity * si.cost_price), 0) AS item_profit
FROM sale_items si
JOIN products p ON si.product_id = p.id
LEFT JOIN categories c ON p.category_id = c.id
JOIN sales s ON si.sale_id = s.id
WHERE DATE(s.created_at) BETWEEN ? AND ?
GROUP BY p.id, p.name, p.unit, c.name
ORDER BY item_revenue DESC";
$monthly_items = db_query($pdo, $sql_items, [$month_start, $month_end])->fetchAll();

// ៤. ទាញបញ្ជីអតិថិជន/មេការដែលជំពាក់លុយក្នុងខែនេះ
$sql_debt = "SELECT s.*, u.full_name AS cashier_name
              FROM sales s
              LEFT JOIN users u ON s.user_id = u.id
              WHERE s.payment_status = 'unpaid' AND DATE(s.created_at) BETWEEN ? AND ?
              ORDER BY s.id DESC";
$debt_list = db_query($pdo, $sql_debt, [$month_start, $month_end])->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <!-- របារជ្រើសរើសខែ & ប៊ូតុង Print -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-2">
            <div>
                <h4 class="fw-bold text-primary mb-0"><i class="fa fa-calendar-check me-2"></i>របាយការណ៍សរុបប្រចាំខែ (Monthly Report)</h4>
                <small class="text-muted">បូកសរុបចំណូល ចំណេញ ស្តុកចេញ និងបំណុលសម្រាប់ខែ: <strong><?= date('F Y', strtotime($month_start)) ?></strong></small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" class="d-flex gap-2">
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= e($selected_month) ?>">
                    <button type="submit" class="btn btn-sm btn-primary px-3"><i class="fa fa-filter me-1"></i> មើល</button>
                </form>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print me-1"></i> ព្រីន</button>
            </div>
        </div>

        <!-- ផ្ទាំងស្ថិតិ ៤ សំខាន់ៗ -->
        <div class="row g-3 mb-4">
            <!-- ១. ចំណូលសរុប -->
            <div class="col-md-3 col-sm-6">
                <div class="card bg-primary text-white border-0 p-3 rounded-3 shadow-sm text-center">
                    <small class="text-white-50 text-uppercase fw-bold">ចំណូលសរុប (Revenue)</small>
                    <h3 class="fw-bold my-1">$<?= number_format($revenue, 2) ?></h3>
                    <small class="text-white-50"><?= number_format($revenue * EXCHANGE_RATE) ?> ៛</small>
                </div>
            </div>

            <!-- ២. ថ្លៃដើមទំនិញ -->
            <div class="col-md-3 col-sm-6">
                <div class="card bg-secondary text-white border-0 p-3 rounded-3 shadow-sm text-center">
                    <small class="text-white-50 text-uppercase fw-bold">ថ្លៃដើមទំនិញ (COGS)</small>
                    <h3 class="fw-bold my-1">$<?= number_format($cogs, 2) ?></h3>
                    <small class="text-white-50"><?= number_format($cogs * EXCHANGE_RATE) ?> ៛</small>
                </div>
            </div>

            <!-- ៣. ប្រាក់ចំណេញដុល -->
            <div class="col-md-3 col-sm-6">
                <div class="card bg-success text-white border-0 p-3 rounded-3 shadow-sm text-center">
                    <small class="text-white-50 text-uppercase fw-bold">ប្រាក់ចំណេញដុល (Profit)</small>
                    <h3 class="fw-bold my-1">$<?= number_format($profit, 2) ?></h3>
                    <small class="text-white-50"><?= number_format($profit * EXCHANGE_RATE) ?> ៛</small>
                </div>
            </div>

            <!-- ៤. លុយជំពាក់សរុប -->
            <div class="col-md-3 col-sm-6">
                <div class="card <?= $debt > 0 ? 'bg-danger' : 'bg-dark' ?> text-white border-0 p-3 rounded-3 shadow-sm text-center">
                    <small class="text-white-50 text-uppercase fw-bold">លុយជំពាក់សរុប (Unpaid)</small>
                    <h3 class="fw-bold my-1">$<?= number_format($debt, 2) ?></h3>
                    <small class="text-white-50"><?= number_format($debt * EXCHANGE_RATE) ?> ៛</small>
                </div>
            </div>
        </div>

        <!-- ផ្នែកទី ១៖ តារាងលម្អិតមុខទំនិញដែលបានលក់ចេញ និងប្រាក់ចំណេញ -->
        <div class="card border mb-4 rounded-3">
            <div class="card-header bg-light py-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa fa-boxes me-2"></i>របាយការណ៍ស្តុកចេញ និងប្រាក់ចំណេញតាមមុខទំនិញ</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">#</th>
                            <th>ឈ្មោះទំនិញ</th>
                            <th>ប្រភេទ</th>
                            <th class="text-center">ចំនួនលក់ចេញ</th>
                            <th class="text-end">ថ្លៃដើម ($)</th>
                            <th class="text-end">ចំណូលលក់ ($)</th>
                            <th class="text-end">ចំណេញ ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthly_items)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">គ្មានទិន្នន័យទំនិញចេញក្នុងខែនេះទេ</td></tr>
                        <?php else: foreach ($monthly_items as $idx => $it): ?>
                            <tr>
                                <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                                <td class="fw-bold"><?= e($it['name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($it['category_name'] ?: 'ទូទៅ') ?></span></td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6 px-2 py-1">
                                        <?= number_format($it['qty_sold']) ?> <?= e($it['unit'] ?: 'ដើម') ?>
                                    </span>
                                </td>
                                <td class="text-end text-muted">$<?= number_format($it['item_cogs'], 2) ?></td>
                                <td class="text-end fw-bold text-dark">$<?= number_format($it['item_revenue'], 2) ?></td>
                                <td class="text-end fw-bold <?= $it['item_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format($it['item_profit'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ផ្នែកទី ២៖ បញ្ជីអតិថិជន/មេការដែលជំពាក់លុយក្នុងខែនេះ -->
        <div class="card border border-danger-subtle rounded-3">
            <div class="card-header bg-danger-subtle py-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-danger"><i class="fa fa-hand-holding-dollar me-2"></i>បញ្ជីមេការដែលជំពាក់លុយក្នុងខែនេះ (Debt List)</h6>
                <span class="badge bg-danger"><?= count($debt_list) ?> វិក្កយបត្រ</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>លេខវិក្កយបត្រ</th>
                            <th>កាលបរិច្ឆេទ</th>
                            <th>ឈ្មោះមេការ / អតិថិជន</th>
                            <th>លេខទូរស័ព្ទ</th>
                            <th>ទីតាំងការដ្ឋាន</th>
                            <th class="text-end">ទឹកប្រាក់ជំពាក់</th>
                            <th class="text-center" width="70"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($debt_list)): ?>
                            <tr><td colspan="7" class="text-center text-success py-3 fw-bold">🎉 គ្មានអតិថិជនណាជំពាក់លុយក្នុងខែនេះទេ (បង់ដាច់ទាំងអស់)!</td></tr>
                        <?php else: foreach ($debt_list as $debt_item): ?>
                            <tr>
                                <td><code><?= e($debt_item['invoice_no']) ?></code></td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($debt_item['created_at'])) ?></td>
                                <td class="fw-bold text-danger"><?= e($debt_item['customer_name'] ?: 'អតិថិជនទូទៅ') ?></td>
                                <td><?= e($debt_item['customer_phone'] ?: '-') ?></td>
                                <td class="small text-muted"><?= e($debt_item['delivery_address'] ?: '-') ?></td>
                                <td class="text-end fw-bold text-danger fs-6">$<?= number_format($debt_item['total_amount'], 2) ?></td>
                                <td class="text-center">
                                    <a href="/modules/sales/invoice.php?id=<?= $debt_item['id'] ?>" class="btn btn-sm btn-outline-danger p-1 px-2" title="មើលលម្អិត"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
