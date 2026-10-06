<?php
// =========================================================================
// ឯកសារ: modules/users/edit.php
// គោលបំណង: កែសម្រួលបុគ្គលិក & កែប្រែសិទ្ធិតាម Tick Box ដោយ Admin
// =========================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role(['admin']);

$user_id = (int)($_GET['id'] ?? 0);
$stmt = db_query($pdo, "SELECT * FROM users WHERE id = ?", [$user_id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'រកមិនឃើញគណនីនេះទេ!');
    redirect('/modules/users/index.php');
}

$all_perms = get_all_permissions_list();
$current_perms = json_decode($user['permissions'] ?? '[]', true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name'] ?? '');
    $role        = $_POST['role'] ?? 'cashier';
    $password    = $_POST['password'] ?? '';
    $permissions = $_POST['permissions'] ?? [];

    if ($role === 'admin') {
        $perms_json = json_encode(array_keys($all_perms));
    } else {
        $perms_json = json_encode(array_values($permissions));
    }

    if (!empty($password)) {
        // កែប្រែទាំងពាក្យសម្ងាត់ថ្មី
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET full_name = ?, role = ?, permissions = ?, password_hash = ? WHERE id = ?";
        db_query($pdo, $sql, [$full_name, $role, $perms_json, $hash, $user_id]);
    } else {
        // រក្សាទុកពាក្យសម្ងាត់ដដែល
        $sql = "UPDATE users SET full_name = ?, role = ?, permissions = ? WHERE id = ?";
        db_query($pdo, $sql, [$full_name, $role, $perms_json, $user_id]);
    }

    // ប្រសិនបើកែប្រែគណនីខ្លួនឯង ធ្វើបច្ចុប្បន្នភាព Session ភ្លាម
    if ($user_id === (int)$_SESSION['user_id']) {
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_permissions'] = $perms_json;
    }

    set_flash('success', "បានធ្វើបច្ចុប្បន្នភាពសិទ្ធិសម្រាប់ '{$user['username']}' ដោយជោគជ័យ!");
    redirect('/modules/users/index.php');
}

$page_title = 'កែសម្រួលសិទ្ធិបុគ្គលិក: ' . $user['username'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 700px;">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary">
            <i class="fa fa-user-shield me-2"></i>កែប្រែព័ត៌មាន & សិទ្ធិ Tick Box (<?= e($user['username']) ?>)
        </h5>
        <a href="index.php" class="btn btn-sm btn-light">ត្រឡប់ក្រោយ</a>
    </div>
    
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ឈ្មោះពេញ (Full Name)</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">តួនាទី (Role)</label>
                    <select name="role" id="user-role-select" class="form-select" onchange="handleRoleChange(this.value)">
                        <option value="cashier" <?= $user['role'] === 'cashier' ? 'selected' : '' ?>>បុគ្គលិកគិតលុយ (Cashier)</option>
                        <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>បុគ្គលិកស្តុក (Staff)</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>អ្នកគ្រប់គ្រង (Admin - សិទ្ធិពេញ)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">ពាក្យសម្ងាត់ថ្មី (ទុកទទេបើមិនចង់ផ្លាស់ប្តូរ)</label>
                    <input type="password" name="password" class="form-control" placeholder="ទុកទទេដើម្បីប្រើពាក្យសម្ងាត់ចាស់">
                </div>
            </div>

            <!-- ប្រអប់ Tick Boxes សម្រាប់គ្រប់គ្រងសិទ្ធិ -->
            <div class="p-3 bg-light rounded-3 border mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label small fw-bold mb-0 text-dark">
                        <i class="fa fa-tasks me-1 text-primary"></i> កំណត់សិទ្ធិចូលប្រើប្រាស់ (Allowed Permissions):
                    </label>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary py-0" onclick="setAllPerms(true)">ជ្រើសរើសទាំងអស់</button>
                        <button type="button" class="btn btn-outline-secondary py-0" onclick="setAllPerms(false)">ដោះចេញទាំងអស់</button>
                    </div>
                </div>
                <small class="text-muted d-block mb-3">Admin គ្រាន់តែចុច Tick លើប្រអប់ខាងក្រោម ដើម្បីបើក ឬបិទសិទ្ធិផ្នែកនីមួយៗបានយ៉ាងងាយស្រួល</small>

                <div class="row g-2">
                    <?php foreach ($all_perms as $key => $p): 
                        $is_checked = ($user['role'] === 'admin') || in_array($key, $current_perms);
                    ?>
                        <div class="col-sm-6">
                            <div class="form-check p-2 bg-white rounded border h-100">
                                <input class="form-check-input perm-box" type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm_<?= $key ?>" <?= $is_checked ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-bold text-dark d-block" for="perm_<?= $key ?>">
                                    <i class="fa <?= $p['icon'] ?> text-secondary me-1"></i> <?= $p['label'] ?>
                                </label>
                                <small class="text-muted d-block" style="font-size: 11px;"><?= $p['desc'] ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-light px-4">បោះបង់</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i> រក្សាទុកការកែប្រែ</button>
            </div>
        </form>
    </div>
</div>

<script>
function setAllPerms(checked) {
    document.querySelectorAll('.perm-box').forEach(cb => cb.checked = checked);
}

function handleRoleChange(role) {
    if (role === 'admin') {
        setAllPerms(true);
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
