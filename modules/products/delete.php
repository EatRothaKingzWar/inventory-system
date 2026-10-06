<?php
// =========================================================================
// ឯកសារ: modules/products/delete.php
// គោលបំណង: លុបទំនិញ (Admin Only)
// =========================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        db_query($pdo, "DELETE FROM products WHERE id = ?", [$id]);
        set_flash('success', 'បានលុបទំនិញដោយជោគជ័យ!');
    } catch (PDOException $e) {
        set_flash('danger', 'មិនអាចលុបទំនិញនេះបានទេ ព្រោះមានប្រវត្តិលក់ ឬនាំចូលជាប់ពាក់ព័ន្ធ!');
    }
}

redirect('/modules/products/index.php');
