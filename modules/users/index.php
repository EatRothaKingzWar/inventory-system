<?php
// =========================================================================
// ឯកសារ: modules/users/index.php
// គោលបំណង: គ្រប់គ្រងអ្នកប្រើប្រាស់ និងសិទ្ធិ (អនុញ្ញាតតែ Admin ប៉ុណ្ណោះ)
// =========================================================================

$page_title = 'គ្រប់គ្រងអ្នកប្រើប្រាស់ (Users)';
require_once __DIR__ . '/../../includes/header.php';
require_role(['admin']); // ការពារសុវត្ថិភាព៖ អនុញ្ញាតតែ Admin

// ១. ដំណើរការបង្កើតគណនីថ្មី
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role      = $_POST['role'] ?? 'staff';

    if ($username === '' || $password === '') {
        set_flash('danger', 'សូមបញ្ចូលឈ្មោះគណនី និងពាក្យសម្ងាត់!');
    } else {
        // Hash ពាក្យសម្ងាត់ដោយប្រើ BCRYPT ការពារសុវត្ថិភាព
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            db_query($pdo, "INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)", [$username, $hash, $full_name, $role]);
            set_flash('success', 'បានបង្កើតគណនីថ្មីដោយជោគជ័យ!');
            redirect('/modules/users/index.php');
        } catch (PDOException $e) {
            set_flash('danger', 'កំហុស៖ ឈ្មោះគណនីនេះមានអ្នកប្រើរួចហើយ!');
        }
    }
}

// ២. ទាញបញ្ជីអ្នកប្រើប្រាស់ទាំងអស់
$users = $pdo->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY id DESC")->fetchAll();
?>

<div class="row g-4">
    <!-- ទម្រង់បង្កើតគណនី -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fa fa-user-plus me-2"></i>បង្កើតអ្នកប្រើប្រាស់ថ្មី</h6>
            <form method="POST">
                <input type="hidden" name="add_user" value="1">
                <div class="mb-2">
                    <label class="form-label small">ឈ្មោះពេញ (Full Name)</label>
                    <input type="text" name="full_name" class="form-control form-control-sm" placeholder="សុខ ចាន់">
                </div>
                <div class="mb-2">
                    <label class="form-label small">ឈ្មោះគណនី (Username) *</label>
                    <input type="text" name="username" class="form-control form-control-sm" required placeholder="sokchan">
                </div>
                <div class="mb-2">
                    <label class="form-label small">ពាក្យសម្ងាត់ *</label>
                    <input type="password" name="password" class="form-control form-control-sm" required placeholder="••••••••">
                </div>
                <div class="mb-3">
                    <label class="form-label small">តួនាទី (Role) *</label>
                    <select name="role" class="form-select form-select-sm" required>
                        <option value="staff">បុគ្គលិក (Staff)</option>
                        <option value="cashier">អ្នកគិតលុយ (Cashier)</option>
                        <option value="admin">អ្នកគ្រប់គ្រង (Admin)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-save me-1"></i> បង្កើតគណនី</button>
            </form>
        </div>
    </div>

    <!-- តារាងបញ្ជីអ្នកប្រើប្រាស់ -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fa fa-users me-2"></i>បញ្ជីអ្នកប្រើប្រាស់ទាំងអស់</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ឈ្មោះពេញ</th>
                            <th>ឈ្មោះគណនី</th>
                            <th>តួនាទី</th>
                            <th>ថ្ងៃបង្កើត</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="fw-bold"><?= e($u['full_name'] ?: 'N/A') ?></td>
                                <td><code><?= e($u['username']) ?></code></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php elseif ($u['role'] === 'cashier'): ?>
                                        <span class="badge bg-success">Cashier</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Staff</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
