<?php
// index.php (Dashboard)
$page_title = 'ផ្ទាំងគ្រប់គ្រងទូទៅ (Dashboard)';
require_once __DIR__ . '/includes/header.php';

$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE current_stock <= min_stock_alert")->fetchColumn();
$today_sales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn();
?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white p-3 border-0 shadow-sm rounded-3">
            <h6>ទំនិញសរុប</h6>
            <h3 class="fw-bold mb-0"><?= number_format($total_products) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white p-3 border-0 shadow-sm rounded-3">
            <h6>ជិតអស់ពីស្តុក</h6>
            <h3 class="fw-bold mb-0"><?= number_format($low_stock) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white p-3 border-0 shadow-sm rounded-3">
            <h6>ការលក់ថ្ងៃនេះ</h6>
            <h3 class="fw-bold mb-0"><?= format_money($today_sales) ?></h3>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
