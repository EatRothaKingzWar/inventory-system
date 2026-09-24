<?php
// =========================================================================
// ឯកសារ: modules/sales/create.php
// គោលបំណង: ផ្ទាំងលក់ទំនិញ (POS Cashier) + កាត់ស្តុកដោយស្វ័យប្រវត្តិ (Atomic Transaction)
// =========================================================================

$page_title = 'កន្លែងលក់ទំនិញ (POS)';
require_once __DIR__ . '/../../includes/header.php';

// ១. ដំណើរការទូទាត់ប្រាក់ និងកាត់ស្តុក
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cart = json_decode($_POST['cart_data'] ?? '[]', true);
    $discount = (float)($_POST['discount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $user_id = $_SESSION['user_id'];

    if (empty($cart)) {
        set_flash('danger', 'កន្ត្រកទំនិញនៅទំនេរ!');
    } else {
        try {
            // --- ចាប់ផ្តើម DATABASE TRANSACTION ---
            $pdo->beginTransaction();

            $subtotal = 0;
            $invoice_no = 'INV-' . strtoupper(uniqid());

            // គណនាផលបូកសរុប និងផ្ទៀងផ្ទាត់ស្តុកម្តងទៀត
            foreach ($cart as $item) {
                // Lock ជួរទិន្នន័យទំនិញដើម្បីការពារការកាត់ស្តុកជាន់គ្នា (Race Condition)
                $stmt = $pdo->prepare("SELECT id, name, current_stock, cost_price, sale_price FROM products WHERE id = ? FOR UPDATE");
                $stmt->execute([$item['id']]);
                $prod = $stmt->fetch();

                if (!$prod || $prod['current_stock'] < $item['qty']) {
                    throw new Exception("ទំនិញ '{['name']}' មិនមានស្តុកគ្រប់គ្រាន់ទេ! (នៅសល់តែ {['current_stock']})");
                }

                $subtotal += ($prod['sale_price'] * $item['qty']);
            }

            $total_amount = max(0, $subtotal - $discount);

            // ២. បញ្ចូលវិក្កយបត្រមេចូលតារាង sales
            $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, user_id, subtotal, discount, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
            $stmt->execute([$invoice_no, $user_id, $subtotal, $discount, $total_amount, $payment_method]);
            $sale_id = $stmt->fetchColumn();

            // ៣. កាត់ស្តុក និងបញ្ចូលទំនិញនីមួយៗចូល sale_items & stock_movements
            foreach ($cart as $item) {
                $prod_stmt = $pdo->prepare("SELECT current_stock, cost_price, sale_price FROM products WHERE id = ?");
                $prod_stmt->execute([$item['id']]);
                $p = $prod_stmt->fetch();

                $item_subtotal = $p['sale_price'] * $item['qty'];

                // ក. បញ្ចូលចូលក្នុង sale_items
                $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, cost_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_item->execute([$sale_id, $item['id'], $item['qty'], $p['sale_price'], $p['cost_price'], $item_subtotal]);

                // ខ. ដកចំនួនស្តុកចេញពីតារាង products
                $new_stock = $p['current_stock'] - $item['qty'];
                $stmt_stock = $pdo->prepare("UPDATE products SET current_stock = ? WHERE id = ?");
                $stmt_stock->execute([$new_stock, $item['id']]);

                // គ. កត់ត្រាក្នុង stock_movements (Audit Log)
                record_stock_movement($pdo, $item['id'], 'SALE', $sale_id, -$item['qty'], $new_stock, "លក់ចេញវិក្កយបត្រ #{}");
            }

            // ប្រតិបត្តិការទាំងអស់ជោគជ័យ -> COMMIT
            $pdo->commit();
            set_flash('success', "ការលក់ជោគជ័យ! លេខវិក្កយបត្រ: {}");
            redirect("/modules/sales/invoice.php?id={}");

        } catch (Exception $e) {
            // បើមានបញ្ហា ឬស្តុកមិនគ្រប់ -> ROLLBACK ត្រឡប់ក្រោយវិញទាំងអស់
            $pdo->rollBack();
            set_flash('danger', 'បរាជ័យ៖ ' . $e->getMessage());
        }
    }
}

// ទាញយកផលិតផលដែលមានក្នុងស្តុកសម្រាប់លក់
$products = $pdo->query("SELECT id, barcode, name, sale_price, current_stock FROM products WHERE current_stock > 0 ORDER BY name ASC")->fetchAll();
?>

<!-- UI ផ្ទាំងលក់ POS -->
<div class="row g-3">
    <!-- ខាងឆ្វេង៖ បញ្ជីជ្រើសរើសទំនិញ -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="fa fa-boxes me-2"></i>ជ្រើសរើសទំនិញលក់</h6>
            <input type="text" id="barcode-search" class="form-control mb-3" placeholder="ស្កេនបាកូដ ឬវាយឈ្មោះទំនិញ..." autofocus>
            
            <div class="row row-cols-2 row-cols-lg-3 g-2" style="max-height: 480px; overflow-y: auto;">
                <?php foreach ($products as $prod): ?>
                    <div class="col product-card-item" data-name="<?= strtolower(e($prod['name'])) ?>" data-barcode="<?= e($prod['barcode']) ?>">
                        <div class="card p-2 h-100 border text-center shadow-none" role="button" onclick="addToCart(<?= $prod['id'] ?>, '<?= addslashes(e($prod['name'])) ?>', <?= $prod['sale_price'] ?>, <?= $prod['current_stock'] ?>)">
                            <div class="fw-bold text-truncate"><?= e($prod['name']) ?></div>
                            <div class="text-success fw-bold"><?= format_money($prod['sale_price']) ?></div>
                            <small class="text-muted">ស្តុក: <span class="badge bg-light text-dark border"><?= $prod['current_stock'] ?></span></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ខាងស្តាំ៖ កន្ត្រកទំនិញ និងគិតលុយ -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fa fa-shopping-cart me-2"></i>កន្ត្រកទំនិញ (Cart)</h6>
            <div class="table-responsive" style="min-height: 220px; max-height: 280px; overflow-y: auto;">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ទំនិញ</th>
                            <th width="80">ចំនួន</th>
                            <th>តម្លៃ</th>
                            <th>សរុប</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cart-items">
                        <tr><td colspan="5" class="text-center text-muted py-4">គ្មានទំនិញក្នុងកន្ត្រក</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- សរុបទឹកប្រាក់ -->
            <form method="POST" class="mt-3 border-top pt-3">
                <input type="hidden" name="checkout" value="1">
                <input type="hidden" name="cart_data" id="cart-data-input">
                
                <div class="d-flex justify-content-between mb-1">
                    <span>សរុបរង:</span>
                    <span class="fw-bold" id="subtotal-text">.00</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>បញ្ចុះតម្លៃ ($):</span>
                    <input type="number" step="0.01" name="discount" id="discount-input" class="form-control form-control-sm text-end" style="width: 100px;" value="0" oninput="renderCart()">
                </div>
                <div class="d-flex justify-content-between fs-5 fw-bold text-danger mb-3 border-top pt-2">
                    <span>ត្រូវទូទាត់:</span>
                    <span id="grand-total-text">.00</span>
                </div>

                <div class="mb-3">
                    <label class="form-label small">វិធីទូទាត់ប្រាក់</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="cash">សាច់ប្រាក់ (Cash)</option>
                        <option value="khqr">KHQR / ធនាគារ</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 fw-bold"><i class="fa fa-check-circle me-1"></i> គិតលុយ (Checkout)</button>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript គ្រប់គ្រងកន្ត្រក POS ក្នុង Browser -->
<script>
let cart = [];

function addToCart(id, name, price, maxStock) {
    let item = cart.find(x => x.id === id);
    if (item) {
        if (item.qty < maxStock) {
            item.qty++;
        } else {
            alert("ស្តុកមានត្រឹមតែ " + maxStock + " ប៉ុណ្ណោះ!");
        }
    } else {
        cart.push({ id, name, price, qty: 1, maxStock });
    }
    renderCart();
}

function updateQty(id, delta) {
    let item = cart.find(x => x.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(x => x.id !== id);
    } else if (item.qty > item.maxStock) {
        item.qty = item.maxStock;
        alert("លើសចំនួនស្តុកដែលមាន!");
    }
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cart-items');
    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">គ្មានទំនិញក្នុងកន្ត្រក</td></tr>';
        document.getElementById('subtotal-text').innerText = '.00';
        document.getElementById('grand-total-text').innerText = '.00';
        document.getElementById('cart-data-input').value = '[]';
        return;
    }

    let subtotal = 0;
    let html = '';
    cart.forEach(item => {
        let total = item.price * item.qty;
        subtotal += total;
        html += <tr>
            <td class="small fw-bold">\</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light" onclick="updateQty(\, -1)">-</button>
                    <span class="px-2 py-1 bg-white border">\</span>
                    <button type="button" class="btn btn-light" onclick="updateQty(\, 1)">+</button>
                </div>
            </td>
            <td>$\</td>
            <td class="fw-bold">$\</td>
            <td><button type="button" class="btn btn-sm text-danger p-0" onclick="updateQty(\, -\)"><i class="fa fa-times"></i></button></td>
        </tr>;
    });

    tbody.innerHTML = html;
    let discount = parseFloat(document.getElementById('discount-input').value) || 0;
    let grandTotal = Math.max(0, subtotal - discount);

    document.getElementById('subtotal-text').innerText = '$' + subtotal.toFixed(2);
    document.getElementById('grand-total-text').innerText = '$' + grandTotal.toFixed(2);
    document.getElementById('cart-data-input').value = JSON.stringify(cart);
}

// ស្វែងរកទំនិញតាម Real-time Input
document.getElementById('barcode-search').addEventListener('input', function(e) {
    let q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('.product-card-item').forEach(el => {
        let name = el.getAttribute('data-name');
        let barcode = el.getAttribute('data-barcode');
        if (name.includes(q) || barcode.includes(q)) {
            el.style.display = '';
        } else {
            el.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
