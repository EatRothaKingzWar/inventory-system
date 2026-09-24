<?php
// =========================================================================
// ឯកសារ: index.php
// គោលបំណង: ផ្ទាំងគ្រប់គ្រងទូទៅ (Professional Executive Dashboard)
// =========================================================================

$page_title = 'ផ្ទាំងគ្រប់គ្រងទូទៅ (Dashboard)';
require_once __DIR__ . '/includes/header.php';

define('EXCHANGE_RATE', 4100);

// ១. ទាញទិន្នន័យស្ថិតិសំខាន់ៗ (KPIs)
$total_products   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_stock_qty  = $pdo->query("SELECT COALESCE(SUM(current_stock), 0) FROM products")->fetchColumn();
$total_stock_cost = $pdo->query("SELECT COALESCE(SUM(current_stock * cost_price), 0) FROM products")->fetchColumn();
$low_stock_count  = $pdo->query("SELECT COUNT(*) FROM products WHERE current_stock <= min_stock_alert")->fetchColumn();

// ២. ទិន្នន័យការលក់ថ្ងៃនេះ និងខែនេះ
$today_sales  = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn();
$today_orders = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn();
$month_sales  = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn();

// ៣. ទាញការលក់ ៥ ចុងក្រោយ (Recent Sales)
$recent_sales = $pdo->query("SELECT s.*, u.full_name AS cashier_name 
                             FROM sales s 
                             LEFT JOIN users u ON s.user_id = u.id 
                             ORDER BY s.id DESC LIMIT 5")->fetchAll();

// ៤. ទាញទំនិញជិតអស់ពីស្តុក ៥ មុខ
$low_stock_items = $pdo->query("SELECT p.*, c.name AS category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.id 
                                WHERE p.current_stock <= p.min_stock_alert 
                                ORDER BY p.current_stock ASC LIMIT 5")->fetchAll();

// ៥. ទាញទំនិញលក់ដាច់បំផុតប្រចាំខែ ៥ មុខ (Top 5 Best Sellers)
$top_sellers = $pdo->query("SELECT p.name, c.name AS category_name, SUM(si.quantity) AS total_qty, SUM(si.subtotal) AS total_amount
                            FROM sale_items si
                            JOIN products p ON si.product_id = p.id
                            LEFT JOIN categories c ON p.category_id = c.id
                            JOIN sales s ON si.sale_id = s.id
                            WHERE DATE_TRUNC('month', s.created_at) = DATE_TRUNC('month', CURRENT_DATE)
                            GROUP BY p.id, p.name, c.name
                            ORDER BY total_qty DESC LIMIT 5")->fetchAll();
?>

<style>
.stat-card {
    border: none;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.25s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.09);
}
.icon-shape {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}
.bg-soft-success { background-color: #ecfdf5; color: #10b981; }
.bg-soft-primary { background-color: #eff6ff; color: #3b82f6; }
.bg-soft-warning { background-color: #fffbeb; color: #f59e0b; }
.bg-soft-danger  { background-color: #fef2f2; color: #ef4444; }
.bg-soft-purple  { background-color: #f5f3ff; color: #8b5cf6; }
.dashboard-table thead th {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e2e8f0;
}
</style>

<!-- របារស្វាគមន៍ & ប៊ូតុងរហ័ស (Welcome Banner & Quick Action Buttons) -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark">សួស្តី, <?= e($_SESSION['user_name'] ?? 'Super Admin') ?> 👋</h4>
        <p class="text-muted small mb-0">នេះជាទិដ្ឋភាពទូទៅនៃហាងទំនិញ និងចរន្តសាច់ប្រាក់របស់អ្នកនៅថ្ងៃនេះ</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/modules/sales/create.php" class="btn btn-success shadow-sm px-3 fw-bold">
            <i class="fa fa-cash-register me-1"></i> កន្លែងលក់ (POS)
        </a>
        <a href="/modules/stock-in/create.php" class="btn btn-outline-primary bg-white shadow-sm px-3">
            <i class="fa fa-truck-loading me-1"></i> នាំចូលស្តុក
        </a>
        <a href="/modules/products/create.php" class="btn btn-outline-secondary bg-white shadow-sm px-3">
            <i class="fa fa-plus me-1"></i> បន្ថែមទំនិញ
        </a>
    </div>
</div>

<!-- បញ្ជីកាតស្ថិតិ ៤ (Modern Stat Cards) -->
<div class="row g-3 mb-4">
    <!-- ១. ការលក់ថ្ងៃនេះ -->
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold">ការលក់ថ្ងៃនេះ</span>
                    <h3 class="fw-bold mb-0 text-success mt-1">$<?= number_format($today_sales, 2) ?></h3>
                    <small class="text-muted"><?= number_format($today_sales * EXCHANGE_RATE) ?> ៛ (<?= $today_orders ?> វិក្កយបត្រ)</small>
                </div>
                <div class="icon-shape bg-soft-success">
                    <i class="fa fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ២. ការលក់ប្រចាំខែ -->
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold">ការលក់ខែនេះ (<?= date('M Y') ?>)</span>
                    <h3 class="fw-bold mb-0 text-primary mt-1">$<?= number_format($month_sales, 2) ?></h3>
                    <small class="text-muted"><?= number_format($month_sales * EXCHANGE_RATE) ?> ៛</small>
                </div>
                <div class="icon-shape bg-soft-primary">
                    <i class="fa fa-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ៣. មុខទំនិញក្នុងស្តុក -->
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold">មុខទំនិញក្នុងហាង</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1"><?= number_format($total_products) ?> មុខ</h3>
                    <small class="text-muted">ចំនួនសរុប: <strong><?= number_format($total_stock_qty) ?></strong> កំប៉ុង/កញ្ចប់</small>
                </div>
                <div class="icon-shape bg-soft-purple">
                    <i class="fa fa-boxes-stacked"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ៤. ជិតអស់ពីស្តុក -->
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold">ជិតអស់ពីស្តុក</span>
                    <h3 class="fw-bold mb-0 <?= $low_stock_count > 0 ? 'text-danger' : 'text-secondary' ?> mt-1">
                        <?= number_format($low_stock_count) ?> មុខ
                    </h3>
                    <small class="<?= $low_stock_count > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?= $low_stock_count > 0 ? '⚠️ ត្រូវទិញបន្ថែម' : 'ស្តុកគ្រប់គ្រាន់ល្អ' ?>
                    </small>
                </div>
                <div class="icon-shape <?= $low_stock_count > 0 ? 'bg-soft-danger' : 'bg-light' ?>">
                    <i class="fa fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ផ្នែកកណ្តាល៖ តារាងលក់ចុងក្រោយ & ព័ត៌មានជំនួយ -->
<div class="row g-4">
    <!-- ខាងឆ្វេង (8 Cols)៖ ប្រវត្តិការលក់ចុងក្រោយ -->
    <div class="col-lg-8">
        <div class="card stat-card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa fa-receipt text-primary me-2"></i>ការលក់ចុងក្រោយ (Recent Sales)</h6>
                <a href="/modules/sales/index.php" class="btn btn-sm btn-link text-decoration-none">មើលទាំងអស់ <i class="fa fa-arrow-right small"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle dashboard-table mb-0">
                    <thead>
                        <tr>
                            <th>លេខវិក្កយបត្រ</th>
                            <th>ម៉ោង</th>
                            <th>អ្នកគិតលុយ</th>
                            <th>វិធីទូទាត់</th>
                            <th class="text-end">ទឹកប្រាក់</th>
                            <th class="text-center" width="60"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_sales)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">មិនទាន់មានការលក់នៅឡើយទេ</td></tr>
                        <?php else: foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td class="fw-bold"><code><?= e($sale['invoice_no']) ?></code></td>
                                <td class="small text-muted"><?= date('H:i d/m', strtotime($sale['created_at'])) ?></td>
                                <td><?= e($sale['cashier_name'] ?: 'បុគ្គលិក') ?></td>
                                <td><span class="badge bg-light text-dark border text-uppercase" style="font-size:11px;"><?= e($sale['payment_method']) ?></span></td>
                                <td class="text-end fw-bold text-success">$<?= number_format($sale['total_amount'], 2) ?></td>
                                <td class="text-center">
                                    <a href="/modules/sales/invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-light border p-1 px-2" title="មើលលម្អិត"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- បញ្ជីទំនិញជិតអស់ពីស្តុក (Low Stock Alert Box) -->
        <?php if (!empty($low_stock_items)): ?>
            <div class="card stat-card p-3 border-start border-danger border-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-danger"><i class="fa fa-bell me-2"></i>ទំនិញដល់កម្រិតត្រូវបន្ថែមស្តុកបន្ទាន់</h6>
                    <a href="/modules/stock-in/create.php" class="btn btn-sm btn-danger px-3">នាំចូលស្តុកភ្លាម</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ឈ្មោះទំនិញ</th>
                                <th>ប្រភេទ</th>
                                <th class="text-center">នៅសល់</th>
                                <th class="text-center">កម្រិតកំណត់</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($item['name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($item['category_name'] ?: 'ទូទៅ') ?></span></td>
                                    <td class="text-center text-danger fw-bold"><?= $item['current_stock'] ?></td>
                                    <td class="text-center text-muted"><?= $item['min_stock_alert'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ខាងស្តាំ (4 Cols)៖ ទំនិញលក់ដាច់ & សង្ខេបតម្លៃស្តុក -->
    <div class="col-lg-4">
        <!-- សង្ខេបតម្លៃស្តុកទំនិញក្នុងហាង -->
        <div class="card stat-card p-3 mb-4 bg-primary text-white">
            <span class="text-white-50 small fw-bold text-uppercase">តម្លៃដើមនៃស្តុកសរុបក្នុងហាង</span>
            <h3 class="fw-bold my-2 text-white">$<?= number_format($total_stock_cost, 2) ?></h3>
            <div class="text-white-50 small">គិតជាប្រាក់រៀល: <?= number_format($total_stock_cost * EXCHANGE_RATE) ?> ៛</div>
        </div>

        <!-- ទំនិញលក់ដាច់បំផុត ៥ មុខ -->
        <div class="card stat-card p-3">
            <h6 class="fw-bold mb-3 text-dark pb-2 border-bottom">
                <i class="fa fa-fire text-danger me-2"></i>ទំនិញលក់ដាច់ប្រចាំខែ
            </h6>
            <?php if (empty($top_sellers)): ?>
                <div class="text-center text-muted py-4 small">មិនទាន់មានទិន្នន័យលក់</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($top_sellers as $idx => $prod): ?>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="badge rounded-pill <?= $idx === 0 ? 'bg-warning text-dark' : 'bg-light text-secondary border' ?> me-2 px-2 py-1">
                                    #<?= $idx + 1 ?>
                                </span>
                                <div>
                                    <div class="fw-bold text-truncate" style="max-width: 140px;"><?= e($prod['name']) ?></div>
                                    <small class="text-muted"><?= e($prod['category_name'] ?: 'ទូទៅ') ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <?= $prod['total_qty'] ?> កញ្ចប់
                                </span>
                                <div class="small fw-bold text-dark mt-1">$<?= number_format($prod['total_amount'], 2) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
