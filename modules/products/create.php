<?php
// =========================================================================
// ឯកសារ: modules/products/create.php
// គោលបំណង: បន្ថែមទំនិញថ្មី + បន្ថែមឯកតាគ្រឿងសំណង់ (បាវ, ដើម, គីឡូ, ធុង...)
// =========================================================================

$page_title = 'បន្ថែមទំនិញថ្មី';
require_once __DIR__ . '/../../includes/header.php';

// បង្កើត Column 'unit' ដោយស្វ័យប្រវត្តិក្នុង PostgreSQL ប្រសិនបើមិនទាន់មាន
$pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS unit VARCHAR(30) DEFAULT 'ដើម'");

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode         = trim($_POST['barcode'] ?? '') ?: null;
    $name            = trim($_POST['name'] ?? '');
    $category_id     = $_POST['category_id'] ?: null;
    $unit            = trim($_POST['unit'] ?? '') ?: 'ដើម';
    $cost_price      = (float)($_POST['cost_price'] ?? 0);
    $sale_price      = (float)($_POST['sale_price'] ?? 0);
    $current_stock   = (int)($_POST['current_stock'] ?? 0);
    $min_stock_alert = (int)($_POST['min_stock_alert'] ?? 5);

    $image_path = null;
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
        $sql = "INSERT INTO products (category_id, barcode, name, unit, cost_price, sale_price, current_stock, min_stock_alert, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            db_query($pdo, $sql, [$category_id, $barcode, $name, $unit, $cost_price, $sale_price, $current_stock, $min_stock_alert, $image_path]);
            set_flash('success', 'បានបន្ថែមទំនិញថ្មីដោយជោគជ័យ!');
            redirect('/modules/products/index.php');
        } catch (PDOException $e) {
            set_flash('danger', 'កំហុសបាកូដស្ទួន ឬទិន្នន័យមិនត្រឹមត្រូវ!');
        }
    }
}
?>

<div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 800px;">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-box-open me-2"></i>ទម្រង់បន្ថែមទំនិញគ្រឿងសំណង់</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">បាកូដ (Barcode)</label>
                    <input type="text" name="barcode" class="form-control" placeholder="ស្កេន ឬវាយដោយដៃ">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ប្រភេទ (Category)</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- ជ្រើសរើសប្រភេទ --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-8">
                    <label class="form-label small fw-bold">ឈ្មោះទំនិញ <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="ឧទាហរណ៍: ស៊ីម៉ងត៍ កំពត បក្សី, ដែក ១២លី...">
                </div>

                <!-- ប្រអប់ជ្រើសរើស និងវាយឯកតាទំនិញ -->
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ឯកតា (Unit) <span class="text-danger">*</span></label>
                    <input list="unit-options" name="unit" class="form-control" required placeholder="ជ្រើសរើស ឬវាយ..." value="ដើម">
                    <datalist id="unit-options">
                        <option value="ដើម">ដើម (ដែក, ទុយោទឹក...)</option>
                        <option value="បាវ">បាវ (ស៊ីម៉ងត៍, ម្សៅបៀក...)</option>
                        <option value="គីឡូ">គីឡូ (ដែកគោល, លួសចងដែក...)</option>
                        <option value="ធុង">ធុង (ថ្នាំលាប, ថ្នាំទ្រនាប់...)</option>
                        <option value="ប្រអប់">ប្រអប់ (ការ៉ូ, វីស...)</option>
                        <option value="សន្លឹក">សន្លឹក (ក្តារបន្ទះ, ស័ង្កសី, ជីបស៊ុម...)</option>
                        <option value="ដុំ">ដុំ (ឥដ្ឋ, គ្រឿងតំណទុយោ...)</option>
                        <option value="ម៉ែត្រ">ម៉ែត្រ (ខ្សែភ្លើង, ទុយោទឹក...)</option>
                        <option value="ឡាន">ឡាន (ខ្សាច់, ថ្ម...)</option>
                        <option value="ម៉ែត្រគូប">ម៉ែត្រគូប (m³)</option>
                    </datalist>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">តម្លៃទិញចូល ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="cost_price" class="form-control" required placeholder="0.00">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">តម្លៃលក់ចេញ ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="sale_price" class="form-control" required placeholder="0.00">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ចំនួនស្តុកដំបូង</label>
                    <input type="number" name="current_stock" class="form-control" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">កម្រិតជូនដំណឹងជិតអស់ស្តុក</label>
                    <input type="number" name="min_stock_alert" class="form-control" value="5">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">រូបភាពផលិតផល</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-light px-4">ត្រឡប់ក្រោយ</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i> រក្សាទុកទំនិញ</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
