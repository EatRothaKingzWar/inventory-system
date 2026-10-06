<?php
// =========================================================================
// ឯកសារ: config/database.php
// គោលបំណង: ភ្ជាប់ទៅកាន់ PostgreSQL Database (គាំទ្រ Neon Console & Render)
// =========================================================================

// កំណត់ Timezone កម្ពុជា
date_default_timezone_set('Asia/Phnom_Penh');

$database_url = getenv('DATABASE_URL');

if (!empty($database_url)) {
    // ករណីដាក់លើ Render / Cloud ដោយប្រើ Connection String ពី Neon
    $db_parts = parse_url($database_url);
    $host     = $db_parts['host'];
    $port     = $db_parts['port'] ?? 5432;
    $user     = urldecode($db_parts['user']);
    $password = urldecode($db_parts['pass'] ?? '');
    $dbname   = ltrim($db_parts['path'], '/');
} else {
    // ករណីរត់លើ Localhost ឬកំណត់តាម Environment Variables
    $host     = getenv('DB_HOST') ?: 'localhost';
    $port     = getenv('DB_PORT') ?: '5432';
    $dbname   = getenv('DB_NAME') ?: 'inventory_db';
    $user     = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: '';
}

// ចំណាំ៖ Neon តម្រូវឱ្យមាន sslmode=require
$ssl = (strpos($host, 'neon.tech') !== false || !empty($database_url)) ? ';sslmode=require' : '';
$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}{$ssl}";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true, // គាំទ្រទាំង Neon Connection Pooler (PgBouncer)
    ]);

    // កំណត់ Timezone ក្នុង PostgreSQL ឱ្យស្គាល់ម៉ោងនៅកម្ពុជា
    $pdo->exec("SET timezone = 'Asia/Phnom_Penh'");

    // Auto-migrate Columns សំខាន់ៗ ប្រសិនបើតារាងចាស់មិនទាន់មាន
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS permissions TEXT DEFAULT '["pos"]'");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS unit VARCHAR(30) DEFAULT 'ដើម'");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100)");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(30)");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS delivery_address TEXT");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'paid'");

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
