<?php
// config/database.php
// ឯកសារតភ្ជាប់ Database PostgreSQL សម្រាប់ទាំង Local និង Render

$database_url = getenv('DATABASE_URL');

if (!empty($database_url)) {
    // ករណីដំណើរការលើ Render (Production)
    $db_parts = parse_url($database_url);
    $host     = $db_parts['host'];
    $port     = $db_parts['port'] ?? 5432;
    $user     = $db_parts['user'];
    $password = $db_parts['pass'];
    $dbname   = ltrim($db_parts['path'], '/');
} else {
    // ករណីដំណើរការលើកុំព្យូទ័រផ្ទាល់ខ្លួន (Local Development)
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
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}