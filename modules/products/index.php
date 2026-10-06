<?php
// =========================================================================
// ឯកសារ: modules/products/index.php
// គោលបំណង: បញ្ជីទំនិញ - គ្រប់គ្រងតាមសិទ្ធិ Tick Box (Admin/Staff/Cashier)
// =========================================================================

$page_title = 'គ្រប់គ្រងមុខទំនិញ';
require_once __DIR__ . '/../../includes/header.php';
require_permission('products');

$search = trim($_GET['search'] ?? '');
$params = [];

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";

if ($search !== '') {
    $sql .= " WHERE p.name ILIKE ? OR p.barcode ILIKE ?";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY p.id DESC";

$products = db_query($pdo, $sql, $params)->fetchAll();
$can_edit = has_permission('products') && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff');
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form method="GET" class="d-flex gap-2" style="max-width: 350px;">
                <input type="text" name="search" class="form-control" placeholder="ស្វែងរកតាមឈ្មោះ ឬបាកូដ..." value="<?= e($search) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-light"><i class="fa fa-times"></i></a>
                <?php endif; ?>
            </form>
            
            <?php if ($can_edit): ?>
                <div class="d-flex gap-2">
                    <a href="create.php" class="btn btn-primary"><i class="fa fa-plus me-1"></i> បន្ថែមទំនិញថ្មី</a>
                    <?php if (has_permission('reports')): ?>
                        <a href="/modules/reports/export_excel.php" class="btn btn-success shadow-sm">
                            <i class="fa fa-file-excel me-1"></i> Export Excel
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>រូបភាព</th>
                        <th>ឈ្មោះទំនិញ</th>
                        <th>ប្រភេទ</th>
                        <th class="text-center">ឯកតា</th>
                        <?php if ($can_edit): ?>
                            <th>តម្លៃទិញដើម</th>
                        <?php endif; ?>
                        <th>តម្លៃលក់</th>
                        <th class="text-center">ស្តុកនៅសល់</th>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <th class="text-center" width="90">សកម្មភាព</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">មិនមានទិន្នន័យទំនិញឡើយ</td></tr>
                    <?php else: foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php if (!empty($p['image_path'])): ?>
                                    <img src="/<?= e($p['image_path']) ?>" class="rounded" width="45" height="45" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded text-center text-muted pt-2" style="width:45px;height:45px;"><i class="fa fa-box"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($p['name']) ?></div>
                                <?php if (!empty($p['barcode'])): ?>
                                    <small class="text-muted"><code><?= e($p['barcode']) ?></code></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($p['category_name'] ?: 'ទូទៅ') ?></span></td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= e($p['unit'] ?: 'ដើម') ?></span></td>
                            
                            <?php if ($can_edit): ?>
                                <td class="text-muted small"><?= format_money($p['cost_price']) ?></td>
                            <?php endif; ?>

                            <td class="text-success fw-bold"><?= format_money($p['sale_price']) ?></td>
                            
                            <td class="text-center">
                                <span class="badge <?= $p['current_stock'] <= ($p['min_stock_alert'] ?? 0) ? 'bg-danger' : 'bg-primary' ?> px-2 py-1 fs-6">
                                    <?= $p['current_stock'] ?> <?= e($p['unit'] ?: '') ?>
                                </span>
                            </td>
                            
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary" title="កែប្រែ"><i class="fa fa-edit"></i></a>
                                        <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-outline-danger" title="លុប" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបទំនិញនេះមែនទេ?')"><i class="fa fa-trash"></i></a>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
