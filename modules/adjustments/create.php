<?php
// =========================================================================
// ឯកសារ: modules/adjustments/create.php
// គោលបំណង: កែសម្រួលស្តុកដោយដៃ (ខូច, បាត់, ផុតកំណត់)
// =========================================================================

$page_title = 'កែតម្រូវស្តុកទំនិញ';
require_once __DIR__ . '/../../includes/header.php';

$products = $pdo->query("SELECT id, name, current_stock FROM products ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $type       = $_POST['type'] ?? 'subtract'; // 'add' (លើស) or 'subtract' (ខូច/បាត់)
    $quantity   = (int)($_POST['quantity'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');
    $user_id    = $_SESSION['user_id'];

    if ($product_id <= 0 || $quantity <= 0 || $reason === '') {
        set_flash('danger', 'សូមបំពេញព័ត៌មានឱ្យបានគ្រប់ជ្រុងជ្រោយ!');
    } else {
        try {
            $pdo->beginTransaction();

            $prod = db_query($pdo, "SELECT current_stock FROM products WHERE id = ? FOR UPDATE", [$product_id])->fetch();
            if (!$prod) throw new Exception("រកមិនឃើញទំនិញ!");

            $qty_change = ($type === 'add') ? $quantity : -$quantity;
            $new_balance = $prod['current_stock'] + $qty_change;

            if ($new_balance < 0) {
                throw new Exception("ចំនួនស្តុកមិនអាចតិចជាងសូន្យឡើយ!");
            }

            // ក. បញ្ចូលក្នុង stock_adjustments
            $stmt_adj = $pdo->prepare("INSERT INTO stock_adjustments (user_id, reason) VALUES (?, ?) RETURNING id");
            $stmt_adj->execute([$user_id, $reason]);
            $adj_id = $stmt_adj->fetchColumn();

            // ខ. បញ្ចូលក្នុង stock_adjustment_items
            db_query($pdo, "INSERT INTO stock_adjustment_items (adjustment_id, product_id, quantity_adjusted) VALUES (?, ?, ?)", [$adj_id, $product_id, $qty_change]);

            // គ. កែសម្រួលស្តុកក្នុង products
            db_query($pdo, "UPDATE products SET current_stock = ? WHERE id = ?", [$new_balance, $product_id]);

            // ឃ. កត់ត្រាក្នុង Movement Log
            record_stock_movement($pdo, $product_id, 'ADJUSTMENT', $adj_id, $qty_change, $new_balance, "កែតម្រូវ: {}");

            $pdo->commit();
            set_flash('success', 'បានកែតម្រូវស្តុកដោយជោគជ័យ!');
            redirect('/modules/movements/index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'បរាជ័យ៖ ' . $e->getMessage());
        }
    }
}
?>

<div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 600px;">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-warning text-dark"><i class="fa fa-sliders me-2"></i>ទម្រង់កែតម្រូវស្តុក</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">ជ្រើសរើសទំនិញ *</label>
                <select name="product_id" class="form-select" required>
                    <option value="">-- ជ្រើសរើសទំនិញ --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (ស្តុកបច្ចុប្បន្ន: <?= $p['current_stock'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">ប្រភេទកែតម្រូវ *</label>
                    <select name="type" class="form-select" required>
                        <option value="subtract">ដកចេញ (-) (ខូច, បាត់, ផុតកំណត់)</option>
                        <option value="add">បូកបន្ថែម (+) (រាប់សារពើភ័ណ្ឌលើស)</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">ចំនួនកែប្រែ *</label>
                    <input type="number" name="quantity" class="form-control" min="1" required placeholder="ចំនួន">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">មូលហេតុនៃការកែតម្រូវ *</label>
                <textarea name="reason" class="form-control" rows="3" required placeholder="ឧទាហរណ៍: ទំនិញធ្លាក់បែកពេលរៀបចំ, ផុតកាលបរិច្ឆេទប្រើប្រាស់..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/index.php" class="btn btn-light px-4">ត្រឡប់ក្រោយ</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i> រក្សាទុក</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
