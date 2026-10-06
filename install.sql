-- =========================================================================
-- NEON POSTGRESQL INITIAL DATABASE SCHEMA & SEED DATA
-- =========================================================================
CREATE EXTENSION IF NOT EXISTS pgcrypto;
SET timezone = 'Asia/Phnom_Penh';

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role VARCHAR(20) DEFAULT 'staff',
    permissions TEXT DEFAULT '["pos"]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS suppliers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    email VARCHAR(100),
    address TEXT
);

CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    barcode VARCHAR(50) UNIQUE,
    name VARCHAR(200) NOT NULL,
    unit VARCHAR(30) DEFAULT 'ដើម',
    cost_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    sale_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    current_stock INT NOT NULL DEFAULT 0,
    min_stock_alert INT DEFAULT 5,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales (
    id SERIAL PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    subtotal NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    discount NUMERIC(12, 2) DEFAULT 0.00,
    total_amount NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(30) DEFAULT 'cash',
    payment_status VARCHAR(20) DEFAULT 'paid',
    customer_name VARCHAR(100) DEFAULT 'អតិថិជនទូទៅ',
    customer_phone VARCHAR(30),
    delivery_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INT REFERENCES sales(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity INT NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    cost_price NUMERIC(12, 2) NOT NULL,
    subtotal NUMERIC(12, 2) NOT NULL
);

CREATE TABLE IF NOT EXISTS stock_ins (
    id SERIAL PRIMARY KEY,
    supplier_id INT REFERENCES suppliers(id) ON DELETE SET NULL,
    reference_no VARCHAR(50) UNIQUE,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    total_cost NUMERIC(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stock_in_items (
    id SERIAL PRIMARY KEY,
    stock_in_id INT REFERENCES stock_ins(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity INT NOT NULL,
    cost_price NUMERIC(12, 2) NOT NULL
);

CREATE TABLE IF NOT EXISTS stock_adjustments (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stock_adjustment_items (
    id SERIAL PRIMARY KEY,
    adjustment_id INT REFERENCES stock_adjustments(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity_adjusted INT NOT NULL
);

CREATE TABLE IF NOT EXISTS stock_movements (
    id SERIAL PRIMARY KEY,
    product_id INT REFERENCES products(id) ON DELETE CASCADE,
    movement_type VARCHAR(20) NOT NULL,
    reference_id INT,
    quantity_change INT NOT NULL,
    balance_after INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_products_barcode ON products(barcode);
CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);
CREATE INDEX IF NOT EXISTS idx_sales_invoice_no ON sales(invoice_no);
CREATE INDEX IF NOT EXISTS idx_sales_created_at ON sales(created_at);
CREATE INDEX IF NOT EXISTS idx_sales_payment_status ON sales(payment_status);

INSERT INTO users (username, password_hash, full_name, role, permissions)
VALUES (
    'admin', 
    crypt('admin123', gen_salt('bf', 10)), 
    'Super Admin', 
    'admin',
    '["pos", "sales", "products", "categories", "suppliers", "stock_in", "adjustments", "movements", "reports", "users"]'
)
ON CONFLICT (username) DO NOTHING;

INSERT INTO categories (name, description) VALUES
('ដែក & លួស', 'ដែកសរសៃ, ដែកប្រអប់, លួសចងដែក, ដែកគោល'),
('ស៊ីម៉ងត៍ & ម្សៅបៀក', 'ស៊ីម៉ងត៍កំពត, ស៊ីម៉ងត៍កម្ពុជា, ម្សៅបៀកជញ្ជាំង'),
('ឥដ្ឋ & ខ្សាច់ & ថ្ម', 'ឥដ្ឋប្លុក, ឥដ្ឋក្រហម, ខ្សាច់បៀក, ថ្ម ១x២'),
('ការ៉ូ & ថ្មម៉ាប', 'ការ៉ូបាត, ការ៉ូជញ្ជាំង, កាវបិទការ៉ូ'),
('ថ្នាំលាប & ថ្នាំទ្រនាប់', 'ថ្នាំលាបក្នុង-ក្រៅផ្ទះ, ថ្នាំទ្រនាប់ការពារច្រែះ'),
('គ្រឿងទឹក & ភ្លើង', 'ទុយោ PVC, កែងតំណ, ខ្សែភ្លើង, កុងតាក់')
ON CONFLICT (name) DO NOTHING;
