<?php
// =========================================================================
// ឯកសារ: modules/products/edit.php
// គោលបំណង: កែប្រែព័ត៌មានទំនិញ និងដូររូបភាព
// =========================================================================

$page_title = 'កែប្រែទំនិញ';
require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db_query($pdo, "SELECT * FROM products WHERE id = ?", [$id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'រកមិនឃើញទំនិញនេះទេ!');
    redirect('/modules/products/index.php');
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode         = trim($_POST['barcode'] ?? '') ?: null;
    $name            = trim($_POST['name'] ?? '');
    $category_id     = $_POST['category_id'] ?: null;
    $cost_price      = (float)($_POST['cost_price'] ?? 0);
    $sale_price      = (float)($_POST['sale_price'] ?? 0);
    $current_stock   = (int)($_POST['current_stock'] ?? 0);
    $min_stock_alert = (int)($_POST['min_stock_alert'] ?? 5);
    $image_path      = $product['image_path']; // រក្សារូបភាពចាស់ទុកសិន

    // ពិនិត្យមើលថាតើមាន Upload រូបភាពថ្មីមកជំនួសឬទេ
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid('prod_') . '.' . $ext;
            $target = __DIR__ . '/../../uploads/products/' . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $image_path = 'uploads/products/' . $new_filename;
            }
        }
    }

    if ($name === '') {
        set_flash('danger', 'សូមបញ្ចូលឈ្មោះទំនិញ!');
    } else {
        $sql = "UPDATE products SET category_id=?, barcode=?, name=?, cost_price=?, sale_price=?, current_stock=?, min_stock_alert=?, image_path=? WHERE id=?";
        try {
            db_query($pdo, $sql, [$category_id, $barcode, $name, $cost_price, $sale_price, $current_stock, $min_stock_alert, $image_path, $id]);
            set_flash('success', 'បានកែប្រែទិន្នន័យដោយជោគជ័យ!');
            redirect('/modules/products/index.php');
        } catch (PDOException $e) {
            set_flash('danger', 'កំហុសបាកូដស្ទួន ឬទិន្នន័យមិនត្រឹមត្រូវ!');
        }
    }
}
?>

<div class="card border-0 shadow-sm rounded-3" style="max-width: 750px; margin: auto;">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-edit me-2"></i>កែប្រែទំនិញ: <?= e($product['name']) ?></h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">បាកូដ (Barcode)</label>
                    <input type="text" name="barcode" class="form-control" value="<?= e($product['barcode']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ប្រភេទ (Category)</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- ជ្រើសរើសប្រភេទ --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">ឈ្មោះទំនិញ *</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($product['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">តម្លៃទិញចូល ($) *</label>
                    <input type="number" step="0.01" name="cost_price" class="form-control" required value="<?= $product['cost_price'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">តម្លៃលក់ចេញ ($) *</label>
                    <input type="number" step="0.01" name="sale_price" class="form-control" required value="<?= $product['sale_price'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ចំនួនស្តុកបច្ចុប្បន្ន</label>
                    <input type="number" name="current_stock" class="form-control" value="<?= $product['current_stock'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">កម្រិតជូនដំណឹងជិតអស់ស្តុក</label>
                    <input type="number" name="min_stock_alert" class="form-control" value="<?= $product['min_stock_alert'] ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">រូបភាពផលិតផល (ទុកទំនេរបើមិនចង់ដូរ)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if ($product['image_path']): ?>
                        <div class="mt-2"><img src="/<?= e($product['image_path']) ?>" class="rounded" width="70"></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-light px-4">ត្រឡប់ក្រោយ</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i> រក្សាទុកការកែប្រែ</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
