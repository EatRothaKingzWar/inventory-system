<?php
// =========================================================================
// ឯកសារ: modules/users/index.php
// គោលបំណង: គ្រប់គ្រងអ្នកប្រើប្រាស់ (ដាក់ដំណើរការ POST មុន Header ការពារ Error)
// =========================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);

// ១. ដំណើរការបង្កើតគណនីថ្មី (ដំណើរការមុនពេល Render HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'cashier';

    if ($username === '' || $password === '') {
        set_flash('danger', 'សូមបញ្ចូលឈ្មោះគណនី (Username) និងពាក្យសម្ងាត់!');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            set_flash('danger', 'ឈ្មោះគណនីនេះមានអ្នកប្រើប្រាស់រួចហើយ! សូមជ្រើសរើសឈ្មោះផ្សេង។');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, ?)";
            db_query($pdo, $sql, [$full_name ?: $username, $username, $hash, $role]);
            set_flash('success', "បានបង្កើតគណនីសម្រាប់ '{}' ({}) ដោយជោគជ័យ!");
            redirect('/modules/users/index.php');
        }
    }
}

// ២. ដំណើរការលុបគណនី
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id === (int)$_SESSION['user_id']) {
        set_flash('danger', 'អ្នកមិនអាចលុបគណនីដែលកំពុងប្រើប្រាស់បានទេ!');
    } else {
        db_query($pdo, "DELETE FROM users WHERE id = ?", [$del_id]);
        set_flash('success', 'បានលុបគណនីរួចរាល់!');
    }
    redirect('/modules/users/index.php');
}

// ៣. ទាញបញ្ជីអ្នកប្រើប្រាស់
$users = $pdo->query("SELECT id, full_name, username, role, created_at FROM users ORDER BY id DESC")->fetchAll();

$page_title = 'គ្រប់គ្រងអ្នកប្រើប្រាស់ (Users)';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <!-- ទម្រង់បង្កើតគណនី Cashier -->
    <div class="col-lg-4 col-md-5">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-user-plus me-2"></i>បង្កើតគណនីបុគ្គលិកថ្មី</h6>
            <form method="POST">
                <input type="hidden" name="create_user" value="1">
                
                <div class="mb-2">
                    <label class="form-label small fw-bold">ឈ្មោះពេញ (Full Name)</label>
                    <input type="text" name="full_name" class="form-control form-control-sm" required placeholder="ឧ. សុខ សំណាង">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">ឈ្មោះគណនី Login (Username) *</label>
                    <input type="text" name="username" class="form-control form-control-sm" required placeholder="ឧ. cashier1">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">ពាក្យសម្ងាត់ (Password) *</label>
                    <input type="password" name="password" class="form-control form-control-sm" required placeholder="••••••••">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">តួនាទី (Role) *</label>
                    <select name="role" class="form-select form-select-sm" required>
                        <option value="cashier" selected>👤 បុគ្គលិកគិតលុយ (Cashier) - លក់ POS</option>
                        <option value="staff">📦 បុគ្គលិកស្តុក (Staff) - នាំចូលស្តុក</option>
                        <option value="admin">👑 អ្នកគ្រប់គ្រង (Admin) - សិទ្ធិពេញលេញ</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold">
                    <i class="fa fa-save me-1"></i> បង្កើតគណនី
                </button>
            </form>
        </div>
    </div>

    <!-- បញ្ជីអ្នកប្រើប្រាស់ -->
    <div class="col-lg-8 col-md-7">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h6 class="fw-bold mb-3 text-dark"><i class="fa fa-users me-2"></i>បញ្ជីគណនីអ្នកប្រើប្រាស់ទាំងអស់</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ឈ្មោះពេញ</th>
                            <th>Username</th>
                            <th>តួនាទី</th>
                            <th>ថ្ងៃបង្កើត</th>
                            <th class="text-center" width="60"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= e($u['full_name'] ?: 'គ្មានឈ្មោះ') ?></td>
                                <td><code><?= e($u['username']) ?></code></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">👑 Admin</span>
                                    <?php elseif ($u['role'] === 'cashier'): ?>
                                        <span class="badge bg-success">👤 Cashier</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">📦 Staff</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                                <td class="text-center">
                                    <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <a href="index.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger p-1 px-2" 
                                           onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបគណនីនេះមែនទេ?')" title="លុប">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border small">អ្នកបច្ចុប្បន្ន</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
