<?php
// =========================================================================
// ឯកសារ: modules/products/index.php
// គោលបំណង: បង្ហាញបញ្ជីផលិតផលទាំងអស់ និងស្វែងរកតាមឈ្មោះ ឬបាកូដ
// =========================================================================

$page_title = 'គ្រប់គ្រងមុខទំនិញ';
require_once __DIR__ . '/../../includes/header.php';

// --- ១. ទទួលតម្លៃស្វែងរកពី URL (GET Request) ---
$search = trim($_GET['search'] ?? '');
$params = [];

// --- ២. សរសេរ Query ទាញទិន្នន័យផលិតផលភ្ជាប់ជាមួយ Category Name ---
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";

// បើមានការវាយពាក្យស្វែងរក
if ($search !== '') {
    $sql .= " WHERE p.name ILIKE ? OR p.barcode ILIKE ?";
    $params = ["%{}%", "%{}%"];
}
$sql .= " ORDER BY p.id DESC";

$stmt = db_query($pdo, $sql, $params);
$products = $stmt->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <!-- របារស្វែងរក និងប៊ូតុងបន្ថែម -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form method="GET" class="d-flex gap-2" style="max-width: 350px;">
                <input type="text" name="search" class="form-control" placeholder="ស្វែងរកតាមឈ្មោះ ឬបាកូដ..." value="<?= e($search) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                <?php if ($search !== ''): ?>
                    <!-- ប៊ូតុង Reset ការស្វែងរក -->
                    <a href="index.php" class="btn btn-light"><i class="fa fa-times"></i></a>
                <?php endif; ?>
            </form>
            <a href="create.php" class="btn btn-primary"><i class="fa fa-plus me-1"></i> បន្ថែមទំនិញថ្មី</a>
        </div>

        <!-- តារាងទិន្នន័យ -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>រូបភាព</th>
                        <th>បាកូដ</th>
                        <th>ឈ្មោះទំនិញ</th>
                        <th>ប្រភេទ</th>
                        <th>តម្លៃទិញ</th>
                        <th>តម្លៃលក់</th>
                        <th>ស្តុកនៅសល់</th>
                        <th class="text-center">សកម្មភាព</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">មិនមានទិន្នន័យទំនិញឡើយ</td></tr>
                    <?php else: foreach ($products as $p): ?>
                        <tr>
                            <!-- បង្ហាញរូបភាព (បើគ្មានរូប បង្ហាញរូប Icon ជំនួស) -->
                            <td>
                                <?php if ($p['image_path']): ?>
                                    <img src="/<?= e($p['image_path']) ?>" class="rounded" width="45" height="45" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded text-center text-muted pt-2" style="width:45px;height:45px;"><i class="fa fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($p['barcode'] ?: 'គ្មានបាកូដ') ?></code></td>
                            <td class="fw-bold"><?= e($p['name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($p['category_name'] ?: 'ទូទៅ') ?></span></td>
                            <td><?= format_money($p['cost_price']) ?></td>
                            <td class="text-success fw-bold"><?= format_money($p['sale_price']) ?></td>
                            
                            <!-- ប្រសិនបើស្តុក <= min_stock_alert បង្ហាញពណ៌ក្រហម -->
                            <td>
                                <span class="badge <?= $p['current_stock'] <= $p['min_stock_alert'] ? 'bg-danger' : 'bg-primary' ?>">
                                    <?= $p['current_stock'] ?>
                                </span>
                            </td>
                            
                            <!-- ប៊ូតុង Edit & Delete -->
                            <td class="text-center">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="កែប្រែ"><i class="fa fa-edit"></i></a>
                                <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" title="លុប" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបទំនិញនេះមែនទេ?')"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
