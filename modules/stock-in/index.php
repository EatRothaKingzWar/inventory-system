<?php
// =========================================================================
// ឯកសារ: modules/stock-in/index.php
// គោលបំណង: បង្ហាញប្រវត្តិនាំចូលស្តុក ភ្ជាប់ជាមួយឈ្មោះទំនិញ និងចំនួននាំចូល
// =========================================================================

$page_title = 'ប្រវត្តិនាំចូលស្តុក';
require_once __DIR__ . '/../../includes/header.php';

// Query ទាញទិន្នន័យនាំចូល ភ្ជាប់ជាមួយឈ្មោះទំនិញដោយប្រើ STRING_AGG របស់ PostgreSQL
$sql = "SELECT 
            si.*, 
            s.name AS supplier_name, 
            u.full_name AS received_by,
            STRING_AGG(p.name || ' (x' || sii.quantity || ')', ', ') AS product_summary
        FROM stock_ins si 
        LEFT JOIN suppliers s ON si.supplier_id = s.id 
        LEFT JOIN users u ON si.user_id = u.id 
        LEFT JOIN stock_in_items sii ON si.id = sii.stock_in_id
        LEFT JOIN products p ON sii.product_id = p.id
        GROUP BY si.id, s.name, u.full_name
        ORDER BY si.id DESC";

$stock_ins = $pdo->query($sql)->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h5 class="fw-bold mb-0 text-primary"><i class="fa fa-truck-loading me-2"></i>ប្រវត្តិនាំចូលស្តុក</h5>
            <a href="create.php" class="btn btn-primary"><i class="fa fa-plus me-1"></i> បង្កើតការនាំចូលថ្មី</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>លេខយោងប័ណ្ណ</th>
                        <th>កាលបរិច្ឆេទ</th>
                        <th>អ្នកផ្គត់ផ្គង់</th>
                        <th width="35%">មុខទំនិញដែលបាននាំចូល (ចំនួន)</th>
                        <th>អ្នកទទួលស្តុក</th>
                        <th class="text-end">ចំណាយសរុប</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stock_ins)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">មិនទាន់មានប្រវត្តិនាំចូលស្តុកឡើយ</td></tr>
                    <?php else: foreach ($stock_ins as $row): ?>
                        <tr>
                            <td class="fw-bold"><code><?= e($row['reference_no']) ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($row['supplier_name'] ?: 'ទូទៅ') ?></span></td>
                            
                            <!-- បង្ហាញឈ្មោះទំនិញ និងចំនួននាំចូល -->
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

                            <td><?= e($row['received_by'] ?: 'Super Admin') ?></td>
                            <td class="text-end fw-bold text-success fs-6"><?= format_money($row['total_cost']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
