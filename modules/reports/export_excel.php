<?php
// =========================================================================
// ឯកសារ: modules/reports/export_excel.php
// គោលបំណង: ទាញយកទិន្នន័យទាំងអស់ក្នុង File Excel តែមួយ (Multi-Sheet Tabs)
// =========================================================================

session_start();

// ពិនិត្យសិទ្ធិ
$user_role = $_SESSION['user_role'] ?? 'cashier';
if (!isset($_SESSION['user_id']) || ($user_role !== 'admin' && $user_role !== 'staff')) {
    die("អ្នកមិនមានសិទ្ធិទាញយកទិន្នន័យនេះទេ!");
}

require_once __DIR__ . '/../../config/database.php';

$date = date('Y-m-d_H-i-s');
$filename = "inventory_full_report_{$date}.xls";

// ១. ទាញទិន្នន័យ Products
$products = $pdo->query("
    SELECT p.id, p.barcode, p.name, c.name AS category_name, p.unit, p.cost_price, p.sale_price, p.current_stock, p.created_at 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ២. ទាញទិន្នន័យ Stock In
$stock_ins = $pdo->query("
    SELECT si.id, p.barcode, p.name, si.quantity, si.cost_price, (si.quantity * si.cost_price) AS total, si.supplier_name, si.created_at 
    FROM stock_ins si 
    LEFT JOIN products p ON si.product_id = p.id 
    ORDER BY si.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ៣. ទាញទិន្នន័យ Stock Out
$stock_outs = $pdo->query("
    SELECT so.id, p.barcode, p.name, so.quantity, so.reason, so.created_at 
    FROM stock_outs so 
    LEFT JOIN products p ON so.product_id = p.id 
    ORDER BY so.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ៤. ទាញទិន្នន័យ Sales
$sales = $pdo->query("
    SELECT s.invoice_no, s.total_amount, s.discount, s.final_amount, s.payment_method, u.name AS cashier_name, s.created_at 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.id 
    ORDER BY s.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// កំណត់ Headers សម្រាប់ទាញយកជា Excel XML
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Pragma: no-cache");
header("Expires: 0");

// ចាប់ផ្តើមរៀបចំទម្រង់ Excel Multi-Worksheet (SpreadsheetML)
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 
 <Styles>
  <!-- Style ក្បាលតារាង (Header) ផ្ទៃបៃតង អក្សរស ដិត -->
  <Style ss:ID="Header">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Khmer OS Battambang"/>
   <Interior ss:Color="#198754" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <!-- Style ទិន្នន័យទូទៅ -->
  <Style ss:ID="General">
   <Font ss:FontName="Khmer OS Battambang"/>
  </Style>
 </Styles>

 <!-- ==================== SHEET 1: បញ្ជីទំនិញ ==================== -->
 <Worksheet ss:Name="បញ្ជីទំនិញ (Products)">
  <Table ss:DefaultRowHeight="20">
   <Row ss:StyleID="Header">
    <Cell><Data ss:Type="String">លេខកូដ</Data></Cell>
    <Cell><Data ss:Type="String">បាកូដ</Data></Cell>
    <Cell><Data ss:Type="String">ឈ្មោះទំនិញ</Data></Cell>
    <Cell><Data ss:Type="String">ប្រភេទ</Data></Cell>
    <Cell><Data ss:Type="String">ឯកតា</Data></Cell>
    <Cell><Data ss:Type="String">ថ្លៃដើម</Data></Cell>
    <Cell><Data ss:Type="String">តម្លៃលក់</Data></Cell>
    <Cell><Data ss:Type="String">ស្តុកនៅសល់</Data></Cell>
    <Cell><Data ss:Type="String">កាលបរិច្ឆេទ</Data></Cell>
   </Row>
   <?php foreach ($products as $row): ?>
   <Row ss:StyleID="General">
    <Cell><Data ss:Type="Number"><?= $row['id'] ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['barcode'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['name'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['category_name'] ?? 'ទូទៅ') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['unit'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['cost_price'] ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['sale_price'] ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['current_stock'] ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= $row['created_at'] ?></Data></Cell>
   </Row>
   <?php endforeach; ?>
  </Table>
 </Worksheet>

 <!-- ==================== SHEET 2: ស្តុកចូល ==================== -->
 <Worksheet ss:Name="ស្តុកចូល (Stock In)">
  <Table ss:DefaultRowHeight="20">
   <Row ss:StyleID="Header">
    <Cell><Data ss:Type="String">លេខរៀង</Data></Cell>
    <Cell><Data ss:Type="String">បាកូដ</Data></Cell>
    <Cell><Data ss:Type="String">ឈ្មោះទំនិញ</Data></Cell>
    <Cell><Data ss:Type="String">ចំនួននាំចូល</Data></Cell>
    <Cell><Data ss:Type="String">ថ្លៃដើម/ឯកតា</Data></Cell>
    <Cell><Data ss:Type="String">សរុបទឹកប្រាក់</Data></Cell>
    <Cell><Data ss:Type="String">អ្នកផ្គត់ផ្គង់</Data></Cell>
    <Cell><Data ss:Type="String">កាលបរិច្ឆេទ</Data></Cell>
   </Row>
   <?php foreach ($stock_ins as $row): ?>
   <Row ss:StyleID="General">
    <Cell><Data ss:Type="Number"><?= $row['id'] ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['barcode'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['name'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['quantity'] ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['cost_price'] ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['total'] ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['supplier_name'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= $row['created_at'] ?></Data></Cell>
   </Row>
   <?php endforeach; ?>
  </Table>
 </Worksheet>

 <!-- ==================== SHEET 3: ស្តុកចេញ ==================== -->
 <Worksheet ss:Name="ស្តុកចេញ (Stock Out)">
  <Table ss:DefaultRowHeight="20">
   <Row ss:StyleID="Header">
    <Cell><Data ss:Type="String">លេខរៀង</Data></Cell>
    <Cell><Data ss:Type="String">បាកូដ</Data></Cell>
    <Cell><Data ss:Type="String">ឈ្មោះទំនិញ</Data></Cell>
    <Cell><Data ss:Type="String">ចំនួននាំចេញ</Data></Cell>
    <Cell><Data ss:Type="String">មូលហេតុ</Data></Cell>
    <Cell><Data ss:Type="String">កាលបរិច្ឆេទ</Data></Cell>
   </Row>
   <?php foreach ($stock_outs as $row): ?>
   <Row ss:StyleID="General">
    <Cell><Data ss:Type="Number"><?= $row['id'] ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['barcode'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['name'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['quantity'] ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['reason'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= $row['created_at'] ?></Data></Cell>
   </Row>
   <?php endforeach; ?>
  </Table>
 </Worksheet>

 <!-- ==================== SHEET 4: របាយការណ៍លក់ ==================== -->
 <Worksheet ss:Name="ការលក់ (Sales)">
  <Table ss:DefaultRowHeight="20">
   <Row ss:StyleID="Header">
    <Cell><Data ss:Type="String">លេខវិក្កយបត្រ</Data></Cell>
    <Cell><Data ss:Type="String">សរុបទឹកប្រាក់</Data></Cell>
    <Cell><Data ss:Type="String">បញ្ចុះតម្លៃ</Data></Cell>
    <Cell><Data ss:Type="String">ប្រាក់ទទួលបាន</Data></Cell>
    <Cell><Data ss:Type="String">វិធីសាស្ត្រទូទាត់</Data></Cell>
    <Cell><Data ss:Type="String">អ្នកគិតលុយ</Data></Cell>
    <Cell><Data ss:Type="String">កាលបរិច្ឆេទ</Data></Cell>
   </Row>
   <?php foreach ($sales as $row): ?>
   <Row ss:StyleID="General">
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['invoice_no'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['total_amount'] ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['discount'] ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?= $row['final_amount'] ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['payment_method'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= htmlspecialchars($row['cashier_name'] ?? '') ?></Data></Cell>
    <Cell><Data ss:Type="String"><?= $row['created_at'] ?></Data></Cell>
   </Row>
   <?php endforeach; ?>
  </Table>
 </Worksheet>

</Workbook>