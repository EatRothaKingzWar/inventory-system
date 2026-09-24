<?php
// =========================================================================
// ឯកសារ: modules/stock-in/index.php
// គោលបំណង: បង្ហាញបញ្ជីប្រវត្តិនាំចូលស្តុកទាំងអស់
// =========================================================================

$page_title = 'ប្រវត្តិនាំចូលស្តុក';
require_once __DIR__ . '/../../includes/header.php';

$sql = "SELECT si.*, s.name AS supplier_name, u.full_name AS received_by 
        FROM stock_ins si 
        LEFT JOIN suppliers s ON si.supplier_id = s.id 
        LEFT JOIN users u ON si.user_id = u.id 
        ORDER BY si.id DESC";

$stock_ins = $pdo->query($sql)->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">ប្រវត្តិនាំចូលស្តុក</h5>
            <a href="create.php" class="btn btn-primary"><i class="fa fa-plus me-1"></i> បង្កើតការនាំចូលថ្មី</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>លេខយោងប័ណ្ណ</th>
                        <th>កាលបរិច្ឆេទ</th>
                        <th>អ្នកផ្គត់ផ្គង់</th>
                        <th>អ្នកទទួលស្តុក</th>
                        <th class="text-end">ចំណាយសរុប</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stock_ins)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">មិនទាន់មានប្រវត្តិនាំចូលស្តុកឡើយ</td></tr>
                    <?php else: foreach ($stock_ins as $row): ?>
                        <tr>
                            <td class="fw-bold"><code><?= e($row['reference_no']) ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td><?= e($row['supplier_name'] ?: 'ទូទៅ') ?></td>
                            <td><?= e($row['received_by'] ?: 'បុគ្គលិក') ?></td>
                            <td class="text-end fw-bold text-primary"><?= format_money($row['total_cost']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
