<?php
// =========================================================================
// ឯកសារ: modules/movements/index.php
// គោលបំណង: Audit Trail មើលរាល់ចលនាចេញ-ចូលនៃស្តុកទំនិញ
// =========================================================================

$page_title = 'ប្រវត្តិចលនាស្តុក (Audit Trail)';
require_once __DIR__ . '/../../includes/header.php';

$sql = "SELECT sm.*, p.name AS product_name, p.barcode 
        FROM stock_movements sm 
        JOIN products p ON sm.product_id = p.id 
        ORDER BY sm.id DESC LIMIT 100";

$movements = $pdo->query($sql)->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="fa fa-history me-2"></i>ចលនាស្តុកទំនិញចុងក្រោយ (១០០ ចុងក្រោយ)</h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>កាលបរិច្ឆេទ</th>
                        <th>ទំនិញ</th>
                        <th>ប្រភេទចលនា</th>
                        <th class="text-center">ចំនួនប្រែប្រួល</th>
                        <th class="text-center">ស្តុកនៅសល់ចុងក្រោយ</th>
                        <th>កំណត់សម្គាល់</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">មិនទាន់មានចលនាស្តុកឡើយ</td></tr>
                    <?php else: foreach ($movements as $m): ?>
                        <tr>
                            <td class="small"><?= date('d/m/Y H:i:s', strtotime($m['created_at'])) ?></td>
                            <td class="fw-bold"><?= e($m['product_name']) ?> <small class="text-muted">(<?= e($m['barcode'] ?: 'N/A') ?>)</small></td>
                            <td>
                                <?php if ($m['movement_type'] === 'IN'): ?>
                                    <span class="badge bg-success">នាំចូល (IN)</span>
                                <?php elseif ($m['movement_type'] === 'SALE'): ?>
                                    <span class="badge bg-primary">លក់ចេញ (SALE)</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">កែតម្រូវ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold <?= $m['quantity_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $m['quantity_change'] > 0 ? '+' . $m['quantity_change'] : $m['quantity_change'] ?>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= $m['balance_after'] ?></span></td>
                            <td class="small text-muted"><?= e($m['note'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
