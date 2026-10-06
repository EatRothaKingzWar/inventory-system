<?php
// =========================================================================
// ឯកសារ: includes/auth.php
// គោលបំណង: ផ្ទៀងផ្ទាត់ការ Login និងគ្រប់គ្រងសិទ្ធិ (Tick Box Permission Matrix)
// =========================================================================

require_once __DIR__ . '/functions.php';

// បញ្ជីសិទ្ធិទាំងអស់ក្នុងប្រព័ន្ធសម្រាប់ Tick Boxes
function get_all_permissions_list() {
    return [
        'pos'         => ['label' => 'កន្លែងលក់ (POS)', 'icon' => 'fa-cash-register', 'desc' => 'ចេញវិក្កយបត្រលក់ទំនិញផ្ទាល់'],
        'sales'       => ['label' => 'បញ្ជីវិក្កយបត្រលក់', 'icon' => 'fa-receipt', 'desc' => 'មើលវិក្កយបត្រ, ព្រីន, និងកត់ត្រាសងលុយ'],
        'products'    => ['label' => 'គ្រប់គ្រងទំនិញ', 'icon' => 'fa-box', 'desc' => 'មើលបញ្ជីទំនិញ, បន្ថែម និងកែប្រែតម្លៃ'],
        'categories'  => ['label' => 'ប្រភេទមុខទំនិញ', 'icon' => 'fa-tags', 'desc' => 'បន្ថែម និងគ្រប់គ្រងប្រភេទ'],
        'suppliers'   => ['label' => 'អ្នកផ្គត់ផ្គង់', 'icon' => 'fa-truck', 'desc' => 'គ្រប់គ្រងព័ត៌មានអ្នកផ្គត់ផ្គង់'],
        'stock_in'    => ['label' => 'នាំចូលស្តុក', 'icon' => 'fa-truck-loading', 'desc' => 'បង្កើតការនាំចូលស្តុកថ្មី'],
        'adjustments' => ['label' => 'កែតម្រូវស្តុក', 'icon' => 'fa-sliders', 'desc' => 'កែតម្រូវទំនិញខូច/បាត់/លើស'],
        'movements'   => ['label' => 'ប្រវត្តិចលនាស្តុក', 'icon' => 'fa-history', 'desc' => 'ពិនិត្យ Audit Trail ចេញ-ចូល'],
        'reports'     => ['label' => 'របាយការណ៍ & ចំណេញ', 'icon' => 'fa-chart-line', 'desc' => 'បិទបញ្ជី, របាយការណ៍ប្រចាំខែ និង Excel'],
        'users'       => ['label' => 'គ្រប់គ្រងបុគ្គលិក & សិទ្ធិ', 'icon' => 'fa-users-cog', 'desc' => 'បង្កើតគណនី និង Assign Roles'],
    ];
}

// ពិនិត្យថាបាន Login ឬនៅ
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        set_flash('danger', 'សូមចូលប្រព័ន្ធជាមុនសិន!');
        redirect('/login.php');
    }
}

// ពិនិត្យតាមតួនាទី Role
function require_role($roles = []) {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['user_role'] ?? 'cashier', $roles)) {
        set_flash('danger', 'អ្នកគ្មានសិទ្ធិចូលទំព័រនេះទេ!');
        redirect('/index.php');
    }
}

// ទទួលបានសិទ្ធិទាំងអស់របស់អ្នកប្រើប្រាស់បច្ចុប្បន្ន
function get_current_user_permissions() {
    if (!isset($_SESSION['user_id'])) return [];
    
    // Admin មានសិទ្ធិទាំងអស់ជានិច្ច
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        return array_keys(get_all_permissions_list());
    }

    $perms = $_SESSION['user_permissions'] ?? '[]';
    if (is_string($perms)) {
        $decoded = json_decode($perms, true);
        return is_array($decoded) ? $decoded : [];
    }
    return is_array($perms) ? $perms : [];
}

// ពិនិត្យថាតើ User មានសិទ្ធិលើមុខងារនេះឬអត់ (ប្រើសម្រាប់ Show/Hide Menu)
function has_permission($perm_key) {
    if (!isset($_SESSION['user_id'])) return false;
    if (($_SESSION['user_role'] ?? '') === 'admin') return true;
    
    $user_perms = get_current_user_permissions();
    return in_array($perm_key, $user_perms);
}

// បិទសិទ្ធិមិនឱ្យចូលទំព័រ ប្រសិនបើគ្មានសិទ្ធិដែលបានកំណត់
function require_permission($perm_key) {
    require_login();
    if (!has_permission($perm_key)) {
        set_flash('danger', 'អ្នកគ្មានសិទ្ធិចូលប្រើប្រាស់ផ្នែកនេះទេ! សូមទាក់ទង Admin។');
        redirect('/index.php');
    }
}
