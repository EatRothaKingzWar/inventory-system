<?php
// config/database.php
// ឯកសារតភ្ជាប់ Database PostgreSQL សម្រាប់ទាំង Local និង Render

$database_url = getenv('DATABASE_URL');

if (!empty($database_url)) {
    $db_parts = parse_url($database_url);
    $host     = $db_parts['host'];
    $port     = $db_parts['port'] ?? 5432;
    $user     = $db_parts['user'];
    $password = $db_parts['pass'];
    $dbname   = ltrim($db_parts['path'], '/');
} else {
    $host     = getenv('DB_HOST') ?: 'localhost';
    $port     = getenv('DB_PORT') ?: '5432';
    $dbname   = getenv('DB_NAME') ?: 'inventory_db';
    $user     = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: 'postgres';
}

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // បង្កើត Column បន្ថែមដោយស្វ័យប្រវត្តិ (Auto-Migration) ការពារកុំឱ្យ Error
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS unit VARCHAR(30) DEFAULT 'ដើម'");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100)");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(30)");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS delivery_address TEXT");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'paid'");

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
