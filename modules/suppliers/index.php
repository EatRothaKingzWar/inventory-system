<?php
// =========================================================================
// ឯកសារ: modules/suppliers/index.php
// គោលបំណង: គ្រប់គ្រងបញ្ជីអ្នកផ្គត់ផ្គង់ទំនិញ (Suppliers Management)
// =========================================================================

$page_title = 'អ្នកផ្គត់ផ្គង់ (Suppliers)';
require_once __DIR__ . '/../../includes/header.php';

// ១. ដំណើរការបន្ថែមអ្នកផ្គត់ផ្គង់ថ្មី
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        set_flash('danger', 'សូមបញ្ចូលឈ្មោះអ្នកផ្គត់ផ្គង់!');
    } else {
        $sql = "INSERT INTO suppliers (name, phone, email, address) VALUES (?, ?, ?, ?)";
        db_query($pdo, $sql, [$name, $phone, $email, $address]);
        set_flash('success', 'បានបន្ថែមអ្នកផ្គត់ផ្គង់ដោយជោគជ័យ!');
        redirect('/modules/suppliers/index.php');
    }
}

// ២. ដំណើរការលុបអ្នកផ្គត់ផ្គង់
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db_query($pdo, "DELETE FROM suppliers WHERE id = ?", [$id]);
    set_flash('success', 'បានលុបអ្នកផ្គត់ផ្គង់រួចរាល់!');
    redirect('/modules/suppliers/index.php');
}

// ៣. ទាញបញ្ជីអ្នកផ្គត់ផ្គង់ទាំងអស់
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
?>

<div class="row g-4">
    <!-- ទម្រង់បន្ថែមអ្នកផ្គត់ផ្គង់ -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fa fa-truck me-2"></i>បន្ថែមអ្នកផ្គត់ផ្គង់ថ្មី</h6>
            <form method="POST">
                <input type="hidden" name="add_supplier" value="1">
                <div class="mb-2">
                    <label class="form-label small">ឈ្មោះក្រុមហ៊ុន/អ្នកផ្គត់ផ្គង់ <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" required placeholder="ឧ. ក្រុមហ៊ុន ស្រាបៀរកម្ពុជា">
                </div>
                <div class="mb-2">
                    <label class="form-label small">លេខទូរស័ព្ទ</label>
                    <input type="text" name="phone" class="form-control form-control-sm" placeholder="012 345 678">
                </div>
                <div class="mb-2">
                    <label class="form-label small">អ៊ីមែល</label>
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="supplier@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label small">អាសយដ្ឋាន</label>
                    <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="ទីតាំង..."></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-save me-1"></i> រក្សាទុក</button>
            </form>
        </div>
    </div>

    <!-- តារាងបញ្ជីអ្នកផ្គត់ផ្គង់ -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fa fa-handshake me-2"></i>បញ្ជីអ្នកផ្គត់ផ្គង់ទាំងអស់</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ឈ្មោះ</th>
                            <th>ទំនាក់ទំនង</th>
                            <th>អាសយដ្ឋាន</th>
                            <th class="text-center" width="70">សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($suppliers)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">មិនទាន់មានទិន្នន័យនៅឡើយទេ</td></tr>
                        <?php else: foreach ($suppliers as $s): ?>
                            <tr>
                                <td class="fw-bold"><?= e($s['name']) ?></td>
                                <td>
                                    <div><i class="fa fa-phone me-1 text-muted small"></i> <?= e($s['phone'] ?: 'N/A') ?></div>
                                    <?php if ($s['email']): ?>
                                        <small class="text-muted"><i class="fa fa-envelope me-1"></i> <?= e($s['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= e($s['address'] ?: 'គ្មាន') ?></td>
                                <td class="text-center">
                                    <a href="index.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបមែនទេ?')" title="លុប"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
