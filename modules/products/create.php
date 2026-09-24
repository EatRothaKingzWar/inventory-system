<?php
// =========================================================================
// ឯកសារ: modules/products/create.php
// គោលបំណង: បន្ថែមមុខទំនិញថ្មី និងផ្ទុករូបភាពចូលទៅ uploads/products/
// =========================================================================

$page_title = 'បន្ថែមទំនិញថ្មី';
require_once __DIR__ . '/../../includes/header.php';

// ១. ទាញបញ្ជី Categories មកដាក់ក្នុង Dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// ២. ដំណើរការពេលអ្នកប្រើចុចប៊ូតុង Submit (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode         = trim($_POST['barcode'] ?? '') ?: null;
    $name            = trim($_POST['name'] ?? '');
    $category_id     = $_POST['category_id'] ?: null;
    $cost_price      = (float)($_POST['cost_price'] ?? 0);
    $sale_price      = (float)($_POST['sale_price'] ?? 0);
    $current_stock   = (int)($_POST['current_stock'] ?? 0);
    $min_stock_alert = (int)($_POST['min_stock_alert'] ?? 5);

    // --- ៣. ដំណើរការ Upload រូបភាពផលិតផល ---
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp']; // ប្រភេទរូបភាពដែលអនុញ្ញាត

        if (in_array($ext, $allowed_extensions)) {
            // ប្តូរឈ្មោះរូបភាពជា random uniqid ដើម្បីកុំឱ្យជាន់ឈ្មោះគ្នា
            $new_filename = uniqid('prod_') . '.' . $ext;
            $target_dir = __DIR__ . '/../../uploads/products/';
            
            if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
                $image_path = 'uploads/products/' . $new_filename;
            }
        }
    }

    // --- ៤. ផ្ទៀងផ្ទាត់ទិន្នន័យ និង Insert ចូល Database ---
    if ($name === '') {
        set_flash('danger', 'សូមបញ្ចូលឈ្មោះទំនិញ!');
    } else {
        $sql = "INSERT INTO products (category_id, barcode, name, cost_price, sale_price, current_stock, min_stock_alert, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            db_query($pdo, $sql, [$category_id, $barcode, $name, $cost_price, $sale_price, $current_stock, $min_stock_alert, $image_path]);
            set_flash('success', 'បានបន្ថែមទំនិញថ្មីដោយជោគជ័យ!');
            redirect('/modules/products/index.php');
        } catch (PDOException $e) {
            // ចាប់កំហុសករណីបាកូដស្ទួន (Duplicate barcode)
            set_flash('danger', 'កំហុស៖ បាកូដនេះមានក្នុងប្រព័ន្ធរួចហើយ ឬទិន្នន័យមិនត្រឹមត្រូវ!');
        }
    }
}
?>

<div class="card border-0 shadow-sm rounded-3" style="max-width: 750px; margin: auto;">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-box-open me-2"></i>ទម្រង់បន្ថែមទំនិញថ្មី</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">បាកូដ (Barcode)</label>
                    <input type="text" name="barcode" class="form-control" placeholder="ស្កេន ឬវាយដោយដៃ">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ប្រភេទ (Category)</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- ជ្រើសរើសប្រភេទ --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">ឈ្មោះទំនិញ <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="ឧទាហរណ៍: Coca Cola 330ml">
                </div>
                <div class="col-md-6">
                    <label class="form-label">តម្លៃទិញចូល ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="cost_price" class="form-control" required placeholder="0.00">
                </div>
                <div class="col-md-6">
                    <label class="form-label">តម្លៃលក់ចេញ ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="sale_price" class="form-control" required placeholder="0.00">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ចំនួនស្តុកដំបូង</label>
                    <input type="number" name="current_stock" class="form-control" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">កម្រិតជូនដំណឹងជិតអស់ស្តុក</label>
                    <input type="number" name="min_stock_alert" class="form-control" value="5">
                </div>
                <div class="col-12">
                    <label class="form-label">រូបភាពផលិតផល</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-light px-4">ត្រឡប់ក្រោយ</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i> រក្សាទុក</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
