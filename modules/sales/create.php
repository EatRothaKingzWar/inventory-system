<?php
// =========================================================================
// ឯកសារ: modules/sales/create.php
// គោលបំណង: ផ្ទាំងលក់ POS - រៀបចំប្រអប់ចំនួន (Qty) ឱ្យធំទូលាយ មិនធ្លាក់ជួរ
// =========================================================================

$page_title = 'កន្លែងលក់គ្រឿងសំណង់ (POS)';
require_once __DIR__ . '/../../includes/header.php';

define('EXCHANGE_RATE', 4100);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cart             = json_decode($_POST['cart_data'] ?? '[]', true);
    $discount         = (float)($_POST['discount'] ?? 0);
    $payment_method   = $_POST['payment_method'] ?? 'cash';
    $payment_status   = $_POST['payment_status'] ?? 'paid';
    $customer_name    = trim($_POST['customer_name'] ?? '') ?: 'អតិថិជនទូទៅ';
    $customer_phone   = trim($_POST['customer_phone'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $user_id          = $_SESSION['user_id'];

    if (empty($cart)) {
        set_flash('danger', 'កន្ត្រកទំនិញនៅទំនេរ! សូមជ្រើសរើសទំនិញជាមុនសិន។');
    } else {
        try {
            $pdo->beginTransaction();

            $subtotal = 0;
            $invoice_no = 'INV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));

            foreach ($cart as $item) {
                $stmt = $pdo->prepare("SELECT id, name, current_stock, cost_price, sale_price FROM products WHERE id = ? FOR UPDATE");
                $stmt->execute([$item['id']]);
                $prod = $stmt->fetch();

                if (!$prod || $prod['current_stock'] < $item['qty']) {
                    throw new Exception("ទំនិញ '{['name']}' មិនមានស្តុកគ្រប់គ្រាន់ទេ!");
                }
                $subtotal += ($prod['sale_price'] * $item['qty']);
            }

            $total_amount = max(0, $subtotal - $discount);

            $sql_sale = "INSERT INTO sales (invoice_no, user_id, subtotal, discount, total_amount, payment_method, payment_status, customer_name, customer_phone, delivery_address) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
            $stmt = $pdo->prepare($sql_sale);
            $stmt->execute([$invoice_no, $user_id, $subtotal, $discount, $total_amount, $payment_method, $payment_status, $customer_name, $customer_phone, $delivery_address]);
            $sale_id = $stmt->fetchColumn();

            foreach ($cart as $item) {
                $prod_stmt = $pdo->prepare("SELECT current_stock, cost_price, sale_price FROM products WHERE id = ?");
                $prod_stmt->execute([$item['id']]);
                $p = $prod_stmt->fetch();

                $item_subtotal = $p['sale_price'] * $item['qty'];

                $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, cost_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_item->execute([$sale_id, $item['id'], $item['qty'], $p['sale_price'], $p['cost_price'], $item_subtotal]);

                $new_stock = $p['current_stock'] - $item['qty'];
                db_query($pdo, "UPDATE products SET current_stock = ? WHERE id = ?", [$new_stock, $item['id']]);

                record_stock_movement($pdo, $item['id'], 'SALE', $sale_id, -$item['qty'], $new_stock, "លក់ជូន: {} (#{})");
            }

            $pdo->commit();
            set_flash('success', "ការលក់ជោគជ័យ! ប័ណ្ណលេខ: " . $invoice_no);
            redirect("/modules/sales/invoice.php?id={}");

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'បរាជ័យ៖ ' . $e->getMessage());
        }
    }
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$sql_products = "SELECT p.id, p.barcode, p.name, p.unit, p.sale_price, p.current_stock, p.category_id, c.name AS category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.current_stock > 0 
                 ORDER BY p.name ASC";
$products = $pdo->query($sql_products)->fetchAll();
?>

<div class="row g-3">
    <!-- ផ្នែកខាងឆ្វេង៖ ជ្រើសរើសទំនិញ -->
    <div class="col-lg-7 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa fa-boxes me-2"></i>ទំនិញគ្រឿងសំណង់</h6>
                <small class="text-muted">1$ = <strong><?= number_format(EXCHANGE_RATE) ?> ៛</strong></small>
            </div>

            <!-- Filter ប្រភេទ -->
            <div class="d-flex gap-1 mb-2 overflow-x-auto pb-1" style="white-space: nowrap;">
                <button type="button" class="btn btn-sm btn-primary category-btn active px-3" onclick="filterCategory('all', this)">
                    <i class="fa fa-th-large me-1"></i>ទាំងអស់
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary category-btn px-3" onclick="filterCategory(<?= $cat['id'] ?>, this)">
                        <?= e($cat['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="input-group mb-3">
                <span class="input-group-text bg-white"><i class="fa fa-barcode"></i></span>
                <input type="text" id="barcode-input" class="form-control" placeholder="ស្កេនបាកូដ ឬវាយឈ្មោះទំនិញ..." autofocus>
            </div>
            
            <div class="row row-cols-2 row-cols-xl-3 g-2" style="max-height: 520px; overflow-y: auto;">
                <?php foreach ($products as $p): ?>
                    <div class="col product-item" 
                         data-id="<?= $p['id'] ?>" 
                         data-name="<?= strtolower(e($p['name'])) ?>" 
                         data-barcode="<?= e($p['barcode']) ?>"
                         data-category-id="<?= $p['category_id'] ?: 0 ?>">
                        
                        <div class="card p-2 h-100 border text-center shadow-sm product-card" 
                             style="cursor: pointer;"
                             onclick="addItemToCart(<?= $p['id'] ?>, '<?= addslashes(e($p['name'])) ?>', <?= $p['sale_price'] ?>, <?= $p['current_stock'] ?>, '<?= addslashes(e($p['unit'] ?: 'ដើម')) ?>')">
                            
                            <div class="mb-1">
                                <span class="badge bg-secondary-subtle text-secondary border small px-2 py-1">
                                    <?= e($p['category_name'] ?: 'ទូទៅ') ?>
                                </span>
                            </div>

                            <div class="fw-bold text-dark text-truncate" title="<?= e($p['name']) ?>"><?= e($p['name']) ?></div>
                            <div class="text-success fw-bold fs-5 mb-0">$<?= number_format($p['sale_price'], 2) ?> <small class="text-muted fs-6">/ <?= e($p['unit'] ?: 'ដើម') ?></small></div>
                            <small class="text-danger fw-bold"><?= number_format($p['sale_price'] * EXCHANGE_RATE) ?> ៛</small>
                            <div class="mt-1"><span class="badge bg-light text-secondary border">ស្តុក: <?= $p['current_stock'] ?> <?= e($p['unit'] ?: '') ?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ផ្នែកខាងស្តាំ៖ ព័ត៌មានមេការ & កន្ត្រកទំនិញ -->
    <div class="col-lg-5 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3 text-success"><i class="fa fa-shopping-cart me-2"></i>កន្ត្រកទំនិញ (Cart)</h6>
            
            <form method="POST">
                <input type="hidden" name="checkout" value="1">
                <input type="hidden" name="cart_data" id="cart-data-json">

                <div class="p-2 bg-light rounded-3 mb-2 border">
                    <div class="row g-2">
                        <div class="col-7">
                            <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="ឈ្មោះមេការ / អតិថិជន">
                        </div>
                        <div class="col-5">
                            <input type="text" name="customer_phone" class="form-control form-control-sm" placeholder="លេខទូរស័ព្ទ">
                        </div>
                        <div class="col-12">
                            <input type="text" name="delivery_address" class="form-control form-control-sm" placeholder="ទីតាំងការដ្ឋានដឹកជញ្ជូន">
                        </div>
                    </div>
                </div>

                <!-- តារាងកន្ត្រកទំនិញ (រៀបចំជួរឱ្យធំទូលាយ មិនធ្លាក់ជួរ) -->
                <div class="table-responsive" style="min-height: 180px; max-height: 230px; overflow-y: auto;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ទំនិញ</th>
                                <th width="115" class="text-center">ចំនួន (Qty)</th>
                                <th class="text-end" width="70">តម្លៃ</th>
                                <th class="text-end" width="75">សរុប</th>
                                <th width="25"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-list">
                            <tr><td colspan="5" class="text-center text-muted py-4">សូមជ្រើសរើសទំនិញខាងឆ្វេងដើម្បីលក់</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between my-1 pt-2 border-top">
                    <span class="text-muted small">សរុបរង:</span>
                    <span class="fw-bold" id="subtotal-val">.00</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">បញ្ចុះតម្លៃ ($):</span>
                    <input type="number" step="0.01" name="discount" id="discount-val" class="form-control form-control-sm text-end" style="width: 90px;" value="0" oninput="updateCalculation()">
                </div>
                
                <div class="p-2 bg-light rounded-3 mb-2 border">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-6">ត្រូវទូទាត់ (USD):</span>
                        <span class="fw-bold text-danger fs-4" id="grand-total-usd">.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-muted small">ជាប្រាក់រៀល (KHR):</span>
                        <span class="fw-bold text-primary fs-5" id="grand-total-khr">0 ៛</span>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold mb-1">ស្ថានភាពទូទាត់</label>
                        <select name="payment_status" id="payment-status-select" class="form-select form-select-sm" onchange="togglePaymentStatus()">
                            <option value="paid">✅ បង់ដាច់ (Paid)</option>
                            <option value="unpaid">⏳ ជំពាក់ (Credit)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold mb-1">វិធីទូទាត់</label>
                        <select name="payment_method" class="form-select form-select-sm">
                            <option value="cash">សាច់ប្រាក់ (Cash)</option>
                            <option value="khqr">KHQR / ធនាគារ</option>
                        </select>
                    </div>
                </div>

                <div id="cash-change-box" class="mb-3">
                    <div class="input-group input-group-sm mb-1">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" id="cash-received" class="form-control fw-bold text-primary" placeholder="ប្រាក់ទទួលពីភ្ញៀវ ($)" oninput="calcChange()">
                    </div>
                    <div class="d-flex justify-content-between text-success fw-bold p-1 px-2 bg-white border rounded small">
                        <span>ប្រាក់អាប់:</span>
                        <span id="change-text">.00 (0 ៛)</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 fw-bold fs-6 shadow-sm">
                    <i class="fa fa-check-circle me-1"></i> ចេញវិក្កយបត្រ & ប័ណ្ណដឹកទំនិញ
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const RATE = <?= EXCHANGE_RATE ?>;
const allProducts = <?= json_encode($products) ?>;
const D_SIGN = String.fromCharCode(36); // សញ្ញា $ ការពារកុំឱ្យ PowerShell លុប
let cart = [];
let currentCategory = 'all';

function togglePaymentStatus() {
    let status = document.getElementById('payment-status-select').value;
    let box = document.getElementById('cash-change-box');
    box.style.display = (status === 'unpaid') ? 'none' : 'block';
}

function addItemToCart(id, name, price, maxStock, unit) {
    let item = cart.find(x => x.id === id);
    if (item) {
        if (item.qty < maxStock) {
            item.qty++;
        } else {
            alert('ទំនិញនេះមានក្នុងស្តុកតែ ' + maxStock + ' ' + unit + ' ប៉ុណ្ណោះ!');
        }
    } else {
        cart.push({ id: id, name: name, price: parseFloat(price), qty: 1, maxStock: maxStock, unit: unit });
    }
    renderCartView();
}

function changeQty(id, delta) {
    let item = cart.find(x => x.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(x => x.id !== id);
    } else if (item.qty > item.maxStock) {
        item.qty = item.maxStock;
        alert('លើសចំនួនស្តុកដែលមាន!');
    }
    renderCartView();
}

function setTypedQty(id, value) {
    let item = cart.find(x => x.id === id);
    if (!item) return;
    let qty = parseInt(value) || 1;
    if (qty <= 0) {
        qty = 1;
    } else if (qty > item.maxStock) {
        qty = item.maxStock;
        alert('ស្តុកមានត្រឹមតែ ' + item.maxStock + ' ' + item.unit + ' ប៉ុណ្ណោះ!');
    }
    item.qty = qty;
    renderCartView();
}

function renderCartView() {
    const tbody = document.getElementById('cart-list');
    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">សូមជ្រើសរើសទំនិញខាងឆ្វេងដើម្បីលក់</td></tr>';
        document.getElementById('subtotal-val').innerText = D_SIGN + '0.00';
        document.getElementById('grand-total-usd').innerText = D_SIGN + '0.00';
        document.getElementById('grand-total-khr').innerText = '0 ៛';
        document.getElementById('cart-data-json').value = '[]';
        calcChange();
        return;
    }

    let subtotal = 0;
    let html = '';
    cart.forEach(function(item) {
        let total = item.price * item.qty;
        subtotal += total;
        html += '<tr>' +
            '<td>' +
                '<div class="fw-bold text-dark text-truncate" style="max-width:110px;" title="' + item.name + '">' + item.name + '</div>' +
                '<span class="badge bg-light text-secondary border" style="font-size:10px;">' + item.unit + '</span>' +
            '</td>' +
            '<td>' +
                '<div class="input-group input-group-sm mx-auto" style="width: 105px;">' +
                    '<button type="button" class="btn btn-outline-secondary px-2" onclick="changeQty(' + item.id + ', -1)">-</button>' +
                    '<input type="number" min="1" max="' + item.maxStock + '" value="' + item.qty + '" ' +
                           'class="form-control text-center p-0 fw-bold fs-6" ' +
                           'onchange="setTypedQty(' + item.id + ', this.value)" ' +
                           'onkeydown="if(event.key===\'Enter\'){event.preventDefault(); this.blur();}" ' +
                           'onfocus="this.select()">' +
                    '<button type="button" class="btn btn-outline-secondary px-2" onclick="changeQty(' + item.id + ', 1)">+</button>' +
                '</div>' +
            '</td>' +
            '<td class="text-end small">' + D_SIGN + item.price.toFixed(2) + '</td>' +
            '<td class="text-end fw-bold text-success small">' + D_SIGN + total.toFixed(2) + '</td>' +
            '<td><button type="button" class="btn btn-sm text-danger p-0 ms-1" onclick="changeQty(' + item.id + ', -' + item.qty + ')"><i class="fa fa-times"></i></button></td>' +
        '</tr>';
    });

    tbody.innerHTML = html;
    document.getElementById('cart-data-json').value = JSON.stringify(cart);
    updateCalculation(subtotal);
}

function updateCalculation(subtotal) {
    if (typeof subtotal === 'undefined') {
        subtotal = 0;
        cart.forEach(x => subtotal += (x.price * x.qty));
    }
    let discount = parseFloat(document.getElementById('discount-val').value) || 0;
    let grandUSD = Math.max(0, subtotal - discount);
    let grandKHR = Math.round(grandUSD * RATE);

    document.getElementById('subtotal-val').innerText = D_SIGN + subtotal.toFixed(2);
    document.getElementById('grand-total-usd').innerText = D_SIGN + grandUSD.toFixed(2);
    document.getElementById('grand-total-khr').innerText = grandKHR.toLocaleString() + ' ៛';

    calcChange(grandUSD);
}

function calcChange(grandUSD) {
    if (typeof grandUSD === 'undefined') {
        let text = document.getElementById('grand-total-usd').innerText.replace(D_SIGN, '');
        grandUSD = parseFloat(text) || 0;
    }
    let received = parseFloat(document.getElementById('cash-received').value) || 0;
    let changeUSD = Math.max(0, received - grandUSD);
    let changeKHR = Math.round(changeUSD * RATE);

    if (received > 0) {
        document.getElementById('change-text').innerText = D_SIGN + changeUSD.toFixed(2) + ' (' + changeKHR.toLocaleString() + ' ៛)';
    } else {
        document.getElementById('change-text').innerText = D_SIGN + '0.00 (0 ៛)';
    }
}

function filterCategory(catId, btn) {
    currentCategory = catId;
    document.querySelectorAll('.category-btn').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-primary', 'active');
    applyFilters();
}

function applyFilters() {
    let q = document.getElementById('barcode-input').value.toLowerCase().trim();
    document.querySelectorAll('.product-item').forEach(function(el) {
        let name = el.getAttribute('data-name');
        let barcode = el.getAttribute('data-barcode');
        let catId = el.getAttribute('data-category-id');

        let matchSearch = name.includes(q) || barcode.includes(q);
        let matchCat = (currentCategory === 'all' || catId == currentCategory);

        if (matchSearch && matchCat) {
            el.style.display = '';
        } else {
            el.style.display = 'none';
        }
    });
}

document.getElementById('barcode-input').addEventListener('input', applyFilters);

document.getElementById('barcode-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        let val = this.value.trim().toLowerCase();
        if (!val) return;

        let found = allProducts.find(p => (p.barcode && p.barcode.toLowerCase() === val) || p.name.toLowerCase() === val);
        if (found) {
            addItemToCart(found.id, found.name, found.sale_price, found.current_stock, found.unit || 'ដើម');
            this.value = '';
            applyFilters();
        } else {
            alert('រកមិនឃើញទំនិញដែលមានបាកូដ ' + val + ' នេះទេ!');
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
