<?php
// =========================================================================
// ឯកសារ: modules/products/delete.php
// គោលបំណង: លុបទំនិញ (កំណត់សិទ្ធិអនុញ្ញាតតែ admin ប៉ុណ្ណោះ)
// =========================================================================

require_once __DIR__ . '/../../includes/header.php';
require_role(['admin']); // បើមិនមែន admin នឹងត្រូវទាត់ចេញ

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    db_query($pdo, "DELETE FROM products WHERE id = ?", [$id]);
    set_flash('success', 'បានលុបទំនិញដោយជោគជ័យ!');
}

redirect('/modules/products/index.php');
