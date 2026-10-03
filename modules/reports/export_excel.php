<?php
// modules/reports/export_excel.php
require_once __DIR__ . '/../../config/database.php';

// ទទួលឈ្មោះតារាង (Default យក products)
$table = $_GET['table'] ?? 'products';

// ការពារសុវត្ថិភាពកុំឱ្យគេវាយឈ្មោះ Table ផ្តេសផ្តាស
$allowed_tables = ['products', 'sales', 'sale_items', 'stock_in', 'categories', 'suppliers', 'users'];
if (!in_array($table, $allowed_tables)) {
    die("តារាងមិនត្រឹមត្រូវឡើយ!");
}

$filename = $table . "_backup_" . date('Y-m-d_His') . ".csv";

// កំណត់ Header សម្រាប់ Browser ទាញយកជា Excel/CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '";');

// បញ្ចូល UTF-8 BOM ដើម្បីកុំឱ្យបែកពុម្ពអក្សរខ្មែរក្នុង Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// ទាញយកទិន្នន័យពី Database តាមរយៈ $pdo
$stmt = $pdo->query("SELECT * FROM " . $table);

// ១. សរសេរឈ្មោះ Column (ក្បាលតារាង)
$firstRow = $stmt->fetch(PDO::FETCH_ASSOC);
if ($firstRow) {
    fputcsv($output, array_keys($firstRow));
    fputcsv($output, $firstRow);
}

// ២. បញ្ចូលទិន្នន័យទាំងអស់
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;