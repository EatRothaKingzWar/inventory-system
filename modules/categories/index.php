<?php
// =========================================================================
// ឯកសារ: modules/categories/index.php
// គោលបំណង: គ្រប់គ្រងប្រភេទមុខទំនិញ (បង្កើត និងលុប)
// =========================================================================

$page_title = 'គ្រប់គ្រងប្រភេទមុខទំនិញ (Categories)';
require_once __DIR__ . '/../../includes/header.php';

// ១. ដំណើរការបន្ថែមប្រភេទថ្មី (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        set_flash('danger', 'សូមបញ្ចូលឈ្មោះប្រភេទ!');
    } else {
        db_query($pdo, "INSERT INTO categories (name, description) VALUES (?, ?)", [$name, $description]);
        set_flash('success', 'បានបន្ថែមប្រភេទថ្មីដោយជោគជ័យ!');
        redirect('/modules/categories/index.php');
    }
}

// ២. ដំណើរការលុបប្រភេទ (GET ?delete=id)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db_query($pdo, "DELETE FROM categories WHERE id = ?", [$id]);
    set_flash('success', 'បានលុបប្រភេទរួចរាល់!');
    redirect('/modules/categories/index.php');
}

// ៣. ទាញទិន្នន័យប្រភេទទាំងអស់ និងរាប់ចំនួនទំនិញក្នុងប្រភេទនីមួយៗ
$sql = "SELECT c.*, COUNT(p.id) AS total_products 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.id DESC";
$categories = $pdo->query($sql)->fetchAll();
?>

<div class="row g-4">
    <!-- ទម្រង់បន្ថែមប្រភេទថ្មី -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fa fa-plus-circle me-2"></i>បន្ថែមប្រភេទថ្មី</h6>
            <form method="POST">
                <input type="hidden" name="create_category" value="1">
                <div class="mb-3">
                    <label class="form-label">ឈ្មោះប្រភេទ *</label>
                    <input type="text" name="name" class="form-control" required placeholder="ឧទាហរណ៍: ភេសជ្ជៈ, គ្រឿងទេស">
                </div>
                <div class="mb-3">
                    <label class="form-label">ការពិពណ៌នា</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="ព័ត៌មានបន្ថែម..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fa fa-save me-1"></i> រក្សាទុក</button>
            </form>
        </div>
    </div>

    <!-- តារាងបញ្ជីប្រភេទ -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fa fa-list me-2"></i>បញ្ជីប្រភេទទាំងអស់</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ឈ្មោះប្រភេទ</th>
                            <th>ការពិពណ៌នា</th>
                            <th class="text-center">ចំនួនទំនិញ</th>
                            <th class="text-center" width="80">សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">មិនទាន់មានប្រភេទនៅឡើយទេ</td></tr>
                        <?php else: foreach ($categories as $cat): ?>
                            <tr>
                                <td class="fw-bold"><?= e($cat['name']) ?></td>
                                <td class="text-muted small"><?= e($cat['description'] ?: 'គ្មាន') ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?= $cat['total_products'] ?> មុខ</span></td>
                                <td class="text-center">
                                    <a href="index.php?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបប្រភេទនេះមែនទេ?')" title="លុប"><i class="fa fa-trash"></i></a>
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
