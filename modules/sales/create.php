<?php
// =========================================================================
// ឯកសារ: modules/sales/create.php
// គោលបំណង: ផ្ទាំងលក់ទំនិញ (POS) - អាចវាយចំនួន Qty ដោយដៃ និងស្កេនបាកូដ
// =========================================================================

$page_title = 'កន្លែងលក់ទំនិញផ្ទាល់ (POS)';
require_once __DIR__ . '/../../includes/header.php';

define('EXCHANGE_RATE', 4100); // 1$ = 4,100 ៛

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cart = json_decode($_POST['cart_data'] ?? '[]', true);
    $discount = (float)($_POST['discount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $user_id = $_SESSION['user_id'];

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

            $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, user_id, subtotal, discount, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
            $stmt->execute([$invoice_no, $user_id, $subtotal, $discount, $total_amount, $payment_method]);
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

                record_stock_movement($pdo, $item['id'], 'SALE', $sale_id, -$item['qty'], $new_stock, "លក់ផ្ទាល់ វិក្កយបត្រ #{}");
            }

            $pdo->commit();
            set_flash('success', "ការលក់ជោគជ័យ!");
            redirect("/modules/sales/invoice.php?id={}");

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'បរាជ័យ៖ ' . $e->getMessage());
        }
    }
}

$products = $pdo->query("SELECT id, barcode, name, sale_price, current_stock FROM products WHERE current_stock > 0 ORDER BY name ASC")->fetchAll();
?>

<div class="row g-3">
    <!-- ផ្នែកខាងឆ្វេង៖ ជ្រើសរើសទំនិញ -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa fa-boxes me-2"></i>ទំនិញក្នុងហាង</h6>
                <small class="text-muted">អត្រាប្តូរប្រាក់: <strong>1$ = <?= number_format(EXCHANGE_RATE) ?> ៛</strong></small>
            </div>
            
            <div class="input-group mb-3">
                <span class="input-group-text bg-white"><i class="fa fa-barcode"></i></span>
                <input type="text" id="barcode-input" class="form-control" placeholder="ស្កេនបាកូដ ឬវាយឈ្មោះទំនិញ (ចុច Enter ដើម្បីបញ្ចូល)..." autofocus>
            </div>
            
            <div class="row row-cols-2 row-cols-lg-3 g-2" style="max-height: 520px; overflow-y: auto;">
                <?php foreach ($products as $p): ?>
                    <div class="col product-item" data-id="<?= $p['id'] ?>" data-name="<?= strtolower(e($p['name'])) ?>" data-barcode="<?= e($p['barcode']) ?>">
                        <div class="card p-2 h-100 border text-center shadow-sm product-card" 
                             style="cursor: pointer;"
                             onclick="addItemToCart(<?= $p['id'] ?>, '<?= addslashes(e($p['name'])) ?>', <?= $p['sale_price'] ?>, <?= $p['current_stock'] ?>)">
                            <div class="fw-bold text-dark text-truncate"><?= e($p['name']) ?></div>
                            <div class="text-success fw-bold fs-5 mb-0">$<?= number_format($p['sale_price'], 2) ?></div>
                            <small class="text-danger fw-bold"><?= number_format($p['sale_price'] * EXCHANGE_RATE) ?> ៛</small>
                            <div class="mt-1"><span class="badge bg-light text-secondary border">ស្តុក: <?= $p['current_stock'] ?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ផ្នែកខាងស្តាំ៖ កន្ត្រកទំនិញ & គិតលុយ -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3 text-success"><i class="fa fa-shopping-cart me-2"></i>កន្ត្រកទំនិញ (Cart)</h6>
            
            <div class="table-responsive" style="min-height: 180px; max-height: 240px; overflow-y: auto;">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ទំនិញ</th>
                            <th width="115" class="text-center">ចំនួន (Qty)</th>
                            <th>តម្លៃ</th>
                            <th>សរុប</th>
                            <th width="25"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-list">
                        <tr><td colspan="5" class="text-center text-muted py-4">សូមចុចលើទំនិញខាងឆ្វេងដើម្បីលក់</td></tr>
                    </tbody>
                </table>
            </div>

            <form method="POST" class="mt-2 border-top pt-2">
                <input type="hidden" name="checkout" value="1">
                <input type="hidden" name="cart_data" id="cart-data-json">

                <div class="d-flex justify-content-between mb-1">
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

                <!-- គណនាប្រាក់អាប់ -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary mb-1">ប្រាក់ទទួលពីភ្ញៀវ ($)</label>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" id="cash-received" class="form-control form-control-lg fw-bold text-primary" placeholder="0.00" oninput="calcChange()">
                    </div>
                    <div class="d-flex justify-content-between text-success fw-bold p-2 bg-white border rounded">
                        <span>ប្រាក់អាប់ជូនភ្ញៀវ:</span>
                        <span id="change-text">.00 (0 ៛)</span>
                    </div>
                </div>

                <input type="hidden" name="payment_method" value="cash">
                <button type="submit" class="btn btn-success w-100 py-2 fw-bold fs-6"><i class="fa fa-check-circle me-1"></i> គិតលុយ & ចេញវិក្កយបត្រ</button>
            </form>
        </div>
    </div>
</div>

<script>
const RATE = <?= EXCHANGE_RATE ?>;
const allProducts = <?= json_encode($products) ?>;
let cart = [];

function addItemToCart(id, name, price, maxStock) {
    let item = cart.find(x => x.id === id);
    if (item) {
        if (item.qty < maxStock) {
            item.qty++;
        } else {
            alert('ទំនិញនេះមានក្នុងស្តុកតែ ' + maxStock + ' ប៉ុណ្ណោះ!');
        }
    } else {
        cart.push({ id: id, name: name, price: parseFloat(price), qty: 1, maxStock: maxStock });
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

// អនុគមន៍អនុញ្ញាតឱ្យវាយចំនួន (Typing Qty) ផ្ទាល់ដៃ
function setTypedQty(id, value) {
    let item = cart.find(x => x.id === id);
    if (!item) return;
    let qty = parseInt(value) || 1;
    if (qty <= 0) {
        qty = 1;
    } else if (qty > item.maxStock) {
        qty = item.maxStock;
        alert('ស្តុកមានត្រឹមតែ ' + item.maxStock + ' ប៉ុណ្ណោះ!');
    }
    item.qty = qty;
    renderCartView();
}

function renderCartView() {
    const tbody = document.getElementById('cart-list');
    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">សូមចុចលើទំនិញខាងឆ្វេងដើម្បីលក់</td></tr>';
        document.getElementById('subtotal-val').innerText = '.00';
        document.getElementById('grand-total-usd').innerText = '.00';
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
            '<td class="small fw-bold text-truncate" style="max-width:110px;">' + item.name + '</td>' +
            '<td>' +
                '<div class="input-group input-group-sm" style="width: 105px;">' +
                    '<button type="button" class="btn btn-outline-secondary px-2" onclick="changeQty(' + item.id + ', -1)">-</button>' +
                    '<input type="number" min="1" max="' + item.maxStock + '" value="' + item.qty + '" ' +
                           'class="form-control text-center p-0 fw-bold" ' +
                           'onchange="setTypedQty(' + item.id + ', this.value)" ' +
                           'onkeydown="if(event.key===\'Enter\'){event.preventDefault(); this.blur();}" ' +
                           'onfocus="this.select()">' +
                    '<button type="button" class="btn btn-outline-secondary px-2" onclick="changeQty(' + item.id + ', 1)">+</button>' +
                '</div>' +
            '</td>' +
            '<td class="small">$' + item.price.toFixed(2) + '</td>' +
            '<td class="fw-bold text-success small">$' + total.toFixed(2) + '</td>' +
            '<td><button type="button" class="btn btn-sm text-danger p-0" onclick="changeQty(' + item.id + ', -' + item.qty + ')"><i class="fa fa-times"></i></button></td>' +
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

    document.getElementById('subtotal-val').innerText = '$' + subtotal.toFixed(2);
    document.getElementById('grand-total-usd').innerText = '$' + grandUSD.toFixed(2);
    document.getElementById('grand-total-khr').innerText = grandKHR.toLocaleString() + ' ៛';

    calcChange(grandUSD);
}

function calcChange(grandUSD) {
    if (typeof grandUSD === 'undefined') {
        let text = document.getElementById('grand-total-usd').innerText.replace('$', '');
        grandUSD = parseFloat(text) || 0;
    }
    let received = parseFloat(document.getElementById('cash-received').value) || 0;
    let changeUSD = Math.max(0, received - grandUSD);
    let changeKHR = Math.round(changeUSD * RATE);

    if (received > 0) {
        document.getElementById('change-text').innerText = '$' + changeUSD.toFixed(2) + ' (' + changeKHR.toLocaleString() + ' ៛)';
    } else {
        document.getElementById('change-text').innerText = '.00 (0 ៛)';
    }
}

// ស្កេនបាកូដ USB
document.getElementById('barcode-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        let val = this.value.trim().toLowerCase();
        if (!val) return;

        let found = allProducts.find(p => (p.barcode && p.barcode.toLowerCase() === val) || p.name.toLowerCase() === val);
        if (found) {
            addItemToCart(found.id, found.name, found.sale_price, found.current_stock);
            this.value = '';
        } else {
            alert('រកមិនឃើញទំនិញដែលមានបាកូដ ' + val + ' នេះទេ!');
        }
    }
});

// ស្វែងរកតាមឈ្មោះ
document.getElementById('barcode-input').addEventListener('input', function(e) {
    let q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('.product-item').forEach(function(el) {
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
