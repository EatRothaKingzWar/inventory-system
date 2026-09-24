<?php
$role = $_SESSION['user_role'] ?? 'cashier';
?>
<div class="sidebar p-3">
    <h4 class="text-white fw-bold mb-4 text-center"><i class="fa fa-boxes-stacked me-2"></i>Stock POS</h4>
    <nav>
        <a href="/index.php"><i class="fa fa-tachometer-alt me-2"></i> ផ្ទាំងគ្រប់គ្រង</a>
        
        <!-- ផ្នែកលក់ផ្ទាល់ (Cashier & Admin អាចចូលបាន) -->
        <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-2">ការលក់ផ្ទាល់</div>
        <a href="/modules/sales/create.php"><i class="fa fa-cash-register me-2"></i> កន្លែងលក់ (POS)</a>
        <a href="/modules/sales/index.php"><i class="fa fa-receipt me-2"></i> បញ្ជីវិក្កយបត្រ</a>
        <a href="/modules/products/index.php"><i class="fa fa-box me-2"></i> បញ្ជីទំនិញក្នុងស្តុក</a>

        <!-- ផ្នែកស្តុកទំនិញ (Cashier មិនអាចចូលបានទេ - សម្រាប់តែ Admin & Staff) -->
        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-2">ទំនិញ & ស្តុក</div>
            <a href="/modules/categories/index.php"><i class="fa fa-tags me-2"></i> ប្រភេទ</a>
            <a href="/modules/suppliers/index.php"><i class="fa fa-truck me-2"></i> អ្នកផ្គត់ផ្គង់</a>
            <a href="/modules/stock-in/index.php"><i class="fa fa-truck-loading me-2"></i> នាំចូលស្តុក</a>
            <a href="/modules/adjustments/create.php"><i class="fa fa-sliders me-2"></i> កែតម្រូវស្តុក</a>
            <a href="/modules/movements/index.php"><i class="fa fa-history me-2"></i> ចលនាស្តុក</a>
        <?php endif; ?>

        <!-- ផ្នែករបាយការណ៍ & Users (សម្រាប់តែ Admin ប៉ុណ្ណោះ) -->
        <?php if ($role === 'admin'): ?>
            <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-2">របាយការណ៍</div>
            <a href="/modules/reports/daily.php"><i class="fa fa-calendar-day me-2"></i> បិទបញ្ជីប្រចាំថ្ងៃ</a>
            <a href="/modules/reports/monthly.php"><i class="fa fa-calendar-check me-2"></i> របាយការណ៍ប្រចាំខែ</a>
            <a href="/modules/reports/sales.php"><i class="fa fa-chart-line me-2"></i> របាយការណ៍ចំណេញ</a>

            <div class="text-uppercase text-muted small fw-bold mt-3 mb-2 px-2">ការកំណត់ប្រព័ន្ធ</div>
            <a href="/modules/users/index.php"><i class="fa fa-users-cog me-2"></i> អ្នកប្រើប្រាស់ (Users)</a>
        <?php endif; ?>
    </nav>
</div>
