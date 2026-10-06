<?php
// =========================================================================
// ឯកសារ: includes/sidebar.php
// គោលបំណង: របារម៉ឺនុយចំហៀង (បង្ហាញ/លាក់ ស្វ័យប្រវត្តិតាមសិទ្ធិ Tick Box)
// =========================================================================
$role = $_SESSION['user_role'] ?? 'cashier';
?>
<div class="sidebar p-3">
    <div class="d-flex align-items-center justify-content-center mb-4 pb-2 border-bottom border-secondary">
        <i class="fa fa-boxes-stacked fa-2x text-primary me-2"></i>
        <h4 class="text-white fw-bold mb-0">Stock POS</h4>
    </div>
    
    <nav class="nav flex-column">
        <!-- ផ្ទាំងគ្រប់គ្រង (Dashboard) អាចចូលបានគ្រប់គ្នា -->
        <a href="/index.php" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'index.php') !== false && strpos($_SERVER['REQUEST_URI'], 'modules') === false ? 'active' : '' ?>">
            <i class="fa fa-tachometer-alt me-2"></i> ផ្ទាំងគ្រប់គ្រង
        </a>

        <!-- ផ្នែកលក់ -->
        <?php if (has_permission('pos') || has_permission('sales') || has_permission('products')): ?>
            <div class="text-uppercase text-muted small fw-bold mt-3 mb-1 px-3">ផ្នែកលក់ទំនិញ</div>
            
            <?php if (has_permission('pos')): ?>
                <a href="/modules/sales/create.php" class="nav-link text-success fw-bold">
                    <i class="fa fa-cash-register me-2"></i> កន្លែងលក់ (POS)
                </a>
            <?php endif; ?>

            <?php if (has_permission('sales')): ?>
                <a href="/modules/sales/index.php" class="nav-link">
                    <i class="fa fa-receipt me-2"></i> បញ្ជីវិក្កយបត្រ
                </a>
            <?php endif; ?>

            <?php if (has_permission('products')): ?>
                <a href="/modules/products/index.php" class="nav-link">
                    <i class="fa fa-box me-2"></i> បញ្ជីទំនិញក្នុងស្តុក
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ផ្នែកស្តុកទំនិញ -->
        <?php if (has_permission('categories') || has_permission('suppliers') || has_permission('stock_in') || has_permission('adjustments') || has_permission('movements')): ?>
            <div class="text-uppercase text-muted small fw-bold mt-3 mb-1 px-3">ស្តុក & អ្នកផ្គត់ផ្គង់</div>

            <?php if (has_permission('categories')): ?>
                <a href="/modules/categories/index.php" class="nav-link">
                    <i class="fa fa-tags me-2"></i> ប្រភេទមុខទំនិញ
                </a>
            <?php endif; ?>

            <?php if (has_permission('suppliers')): ?>
                <a href="/modules/suppliers/index.php" class="nav-link">
                    <i class="fa fa-truck me-2"></i> អ្នកផ្គត់ផ្គង់
                </a>
            <?php endif; ?>

            <?php if (has_permission('stock_in')): ?>
                <a href="/modules/stock-in/index.php" class="nav-link">
                    <i class="fa fa-truck-loading me-2"></i> នាំចូលស្តុក
                </a>
            <?php endif; ?>

            <?php if (has_permission('adjustments')): ?>
                <a href="/modules/adjustments/create.php" class="nav-link">
                    <i class="fa fa-sliders me-2"></i> កែតម្រូវស្តុក
                </a>
            <?php endif; ?>

            <?php if (has_permission('movements')): ?>
                <a href="/modules/movements/index.php" class="nav-link">
                    <i class="fa fa-history me-2"></i> ចលនាស្តុក (Audit)
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ផ្នែករបាយការណ៍ -->
        <?php if (has_permission('reports')): ?>
            <div class="text-uppercase text-muted small fw-bold mt-3 mb-1 px-3">របាយការណ៍</div>
            <a href="/modules/reports/daily.php" class="nav-link">
                <i class="fa fa-calendar-day me-2"></i> បិទបញ្ជីប្រចាំថ្ងៃ
            </a>
            <a href="/modules/reports/monthly.php" class="nav-link">
                <i class="fa fa-calendar-check me-2"></i> របាយការណ៍ប្រចាំខែ
            </a>
            <a href="/modules/reports/sales.php" class="nav-link">
                <i class="fa fa-chart-line me-2"></i> របាយការណ៍ចំណេញ
            </a>
        <?php endif; ?>

        <!-- ផ្នែកគ្រប់គ្រងអ្នកប្រើប្រាស់ -->
        <?php if (has_permission('users')): ?>
            <div class="text-uppercase text-muted small fw-bold mt-3 mb-1 px-3">ការកំណត់ប្រព័ន្ធ</div>
            <a href="/modules/users/index.php" class="nav-link">
                <i class="fa fa-users-cog me-2"></i> បុគ្គលិក & សិទ្ធិ (Permissions)
            </a>
        <?php endif; ?>
    </nav>
</div>
