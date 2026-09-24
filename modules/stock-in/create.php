<?php
// =========================================================================
// ឯកសារ: modules/stock-in/create.php
// គោលបំណង: នាំចូលស្តុក + អាចវាយឈ្មោះអ្នកផ្គត់ផ្គង់ (Supplier) ផ្ទាល់ដៃ
// =========================================================================

$page_title = 'នាំចូលស្តុក (Stock In)';
require_once __DIR__ . '/../../includes/header.php';

// ១. ទាញបញ្ជីអ្នកផ្គត់ផ្គង់ និងទំនិញ
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

$sql_products = "SELECT p.id, p.name, p.barcode, p.cost_price, p.current_stock, c.name AS category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 ORDER BY p.name ASC";
$products = $pdo->query($sql_products)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $reference_no  = trim($_POST['reference_no'] ?? '') ?: 'PO-' . time();
    $product_ids   = $_POST['product_id'] ?? [];
    $quantities    = $_POST['quantity'] ?? [];
    $cost_prices   = $_POST['cost_price'] ?? [];
    $user_id       = $_SESSION['user_id'];

    if (empty($product_ids)) {
        set_flash('danger', 'សូមជ្រើសរើសទំនិញយ៉ាងហោចណាស់មួយមុខ!');
    } else {
        try {
            $pdo->beginTransaction();

            // ២. ពិនិត្យឈ្មោះ Supplier: បើជាឈ្មោះថ្មី បញ្ចូលទៅ suppliers ដោយស្វ័យប្រវត្តិ
            $supplier_id = null;
            if ($supplier_name !== '') {
                $stmt_s = $pdo->prepare("SELECT id FROM suppliers WHERE name = ?");
                $stmt_s->execute([$supplier_name]);
                $existing_sup = $stmt_s->fetch();

                if ($existing_sup) {
                    $supplier_id = $existing_sup['id'];
                } else {
                    $stmt_ins = $pdo->prepare("INSERT INTO suppliers (name) VALUES (?) RETURNING id");
                    $stmt_ins->execute([$supplier_name]);
                    $supplier_id = $stmt_ins->fetchColumn();
                }
            }

            $total_cost = 0;
            foreach ($product_ids as $i => $pid) {
                $qty  = (int)$quantities[$i];
                $cost = (float)$cost_prices[$i];
                $total_cost += ($qty * $cost);
            }

            // ៣. បញ្ចូលក្នុង stock_ins
            $stmt = $pdo->prepare("INSERT INTO stock_ins (supplier_id, reference_no, user_id, total_cost) VALUES (?, ?, ?, ?) RETURNING id");
            $stmt->execute([$supplier_id, $reference_no, $user_id, $total_cost]);
            $stock_in_id = $stmt->fetchColumn();

            // ៤. បញ្ចូល items និងបូកបន្ថែមស្តុក
            foreach ($product_ids as $i => $pid) {
                $qty  = (int)$quantities[$i];
                $cost = (float)$cost_prices[$i];

                if ($qty <= 0) continue;

                $stmt_item = $pdo->prepare("INSERT INTO stock_in_items (stock_in_id, product_id, quantity, cost_price) VALUES (?, ?, ?, ?)");
                $stmt_item->execute([$stock_in_id, $pid, $qty, $cost]);

                $prod = db_query($pdo, "SELECT current_stock FROM products WHERE id = ? FOR UPDATE", [$pid])->fetch();
                $new_balance = $prod['current_stock'] + $qty;

                db_query($pdo, "UPDATE products SET current_stock = ?, cost_price = ? WHERE id = ?", [$new_balance, $cost, $pid]);

                record_stock_movement($pdo, $pid, 'IN', $stock_in_id, $qty, $new_balance, "នាំចូលតាមប័ណ្ណ #" . $reference_no);
            }

            $pdo->commit();
            set_flash('success', "នាំចូលស្តុកជោគជ័យ! ប័ណ្ណលេខ: " . $reference_no);
            redirect('/modules/stock-in/index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'បរាជ័យ៖ ' . $e->getMessage());
        }
    }
}
?>

<div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 900px;">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-truck-loading me-2"></i>ទម្រង់នាំចូលទំនិញក្នុងស្តុក</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3 mb-4">
                <!-- ប្រអប់វាយឈ្មោះ Supplier ផ្ទាល់ដៃ ឬជ្រើសរើសពី Datalist -->
                <div class="col-md-6">
                    <label class="form-label small fw-bold">អ្នកផ្គត់ផ្គង់ (វាយឈ្មោះថ្មី ឬជ្រើសរើស)</label>
                    <input list="suppliers-list" name="supplier_name" class="form-control form-control-sm" placeholder="វាយឈ្មោះអ្នកផ្គត់ផ្គង់ (ឧ. ដេប៉ូ A)...">
                    <datalist id="suppliers-list">
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= e($sup['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label small fw-bold">លេខយោងវិក្កយបត្រទិញចូល (Reference No)</label>
                    <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="PO-<?= time() ?>">
                </div>
            </div>

            <table class="table table-bordered align-middle" id="stock-in-table">
                <thead class="table-light">
                    <tr>
                        <th>ទំនិញ [ប្រភេទ]</th>
                        <th width="120">ចំនួននាំចូល</th>
                        <th width="150">តម្លៃទិញ ($)</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody id="rows-container">
                    <tr>
                        <td>
                            <select name="product_id[]" class="form-select form-select-sm" required>
                                <option value="">-- ជ្រើសរើសទំនិញ --</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-cost="<?= $p['cost_price'] ?>">
                                        <?= e($p['name']) ?> [<?= e($p['category_name'] ?: 'ទូទៅ') ?>] (នៅសល់: <?= $p['current_stock'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="quantity[]" class="form-control form-control-sm" min="1" value="1" required></td>
                        <td><input type="number" step="0.01" name="cost_price[]" class="form-control form-control-sm" required placeholder="0.00"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn btn-sm btn-outline-secondary mb-4" onclick="addRow()"><i class="fa fa-plus me-1"></i> បន្ថែមជួរទំនិញ</button>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="index.php" class="btn btn-light px-4">ត្រឡប់ក្រោយ</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i> រក្សាទុកការនាំចូល</button>
            </div>
        </form>
    </div>
</div>

<script>
function addRow() {
    const container = document.getElementById('rows-container');
    const firstRow = container.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('input').forEach(input => input.value = '');
    container.appendChild(newRow);
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#rows-container tr');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    } else {
        alert("ត្រូវមានជួរទំនិញយ៉ាងហោចមួយ!");
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
