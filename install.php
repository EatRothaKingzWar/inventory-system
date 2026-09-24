<?php
// install.php
require_once __DIR__ . '/config/database.php';

try {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            role VARCHAR(20) DEFAULT 'staff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT
        )",
        "CREATE TABLE IF NOT EXISTS suppliers (
            id SERIAL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            phone VARCHAR(30),
            email VARCHAR(100),
            address TEXT
        )",
        "CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            category_id INT REFERENCES categories(id) ON DELETE SET NULL,
            barcode VARCHAR(50) UNIQUE,
            name VARCHAR(200) NOT NULL,
            cost_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
            sale_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
            current_stock INT NOT NULL DEFAULT 0,
            min_stock_alert INT DEFAULT 5,
            image_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS sales (
            id SERIAL PRIMARY KEY,
            invoice_no VARCHAR(50) UNIQUE NOT NULL,
            user_id INT REFERENCES users(id),
            subtotal NUMERIC(12, 2) NOT NULL,
            discount NUMERIC(12, 2) DEFAULT 0.00,
            total_amount NUMERIC(12, 2) NOT NULL,
            payment_method VARCHAR(30) DEFAULT 'cash',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS sale_items (
            id SERIAL PRIMARY KEY,
            sale_id INT REFERENCES sales(id) ON DELETE CASCADE,
            product_id INT REFERENCES products(id),
            quantity INT NOT NULL,
            unit_price NUMERIC(12, 2) NOT NULL,
            cost_price NUMERIC(12, 2) NOT NULL,
            subtotal NUMERIC(12, 2) NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS stock_ins (
            id SERIAL PRIMARY KEY,
            supplier_id INT REFERENCES suppliers(id) ON DELETE SET NULL,
            reference_no VARCHAR(50) UNIQUE,
            user_id INT REFERENCES users(id),
            total_cost NUMERIC(12, 2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS stock_in_items (
            id SERIAL PRIMARY KEY,
            stock_in_id INT REFERENCES stock_ins(id) ON DELETE CASCADE,
            product_id INT REFERENCES products(id),
            quantity INT NOT NULL,
            cost_price NUMERIC(12, 2) NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS stock_adjustments (
            id SERIAL PRIMARY KEY,
            user_id INT REFERENCES users(id),
            reason TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS stock_adjustment_items (
            id SERIAL PRIMARY KEY,
            adjustment_id INT REFERENCES stock_adjustments(id) ON DELETE CASCADE,
            product_id INT REFERENCES products(id),
            quantity_adjusted INT NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS stock_movements (
            id SERIAL PRIMARY KEY,
            product_id INT REFERENCES products(id),
            movement_type VARCHAR(20) NOT NULL,
            reference_id INT,
            quantity_change INT NOT NULL,
            balance_after INT NOT NULL,
            note TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    // ១. Execute បង្កើត Tables នីមួយៗ
    foreach ($queries as $q) {
        $pdo->exec($q);
    }

    // ២. បង្កើតគណនី Admin ដំបូង
    $username = 'admin';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        $insert->execute([$username, $hash, 'Super Admin', 'admin']);
    }

    echo "<div style='font-family: sans-serif; max-width: 500px; margin: 60px auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; text-align: center;'>";
    echo "<h2 style='color: #16a34a; margin-top: 0;'>🎉 ដំឡើង Database ជោគជ័យ!</h2>";
    echo "<p style='color: #475569;'>តារាងទាំងអស់ក្នុង PostgreSQL ត្រូវបានបង្កើតរួចរាល់ ១០០%។</p>";
    echo "<div style='background: #f8fafc; padding: 15px; border-radius: 8px; text-align: left; margin: 20px 0;'>";
    echo "<p style='margin: 5px 0;'><strong>Username:</strong> <code>admin</code></p>";
    echo "<p style='margin: 5px 0;'><strong>Password:</strong> <code>admin123</code></p>";
    echo "</div>";
    echo "<a href='/login.php' style='display: inline-block; padding: 10px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;'>ចូលទៅកាន់ទំព័រ Login</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; color: red;'>";
    echo "<h2>កំហុសក្នុងការដំឡើង:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
