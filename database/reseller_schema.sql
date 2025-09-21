-- Reseller System Database Schema

-- Resellers table
CREATE TABLE resellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    description TEXT,
    commission_rate DECIMAL(5,2) DEFAULT 20.00, -- 20% commission
    total_earnings DECIMAL(10,2) DEFAULT 0.00,
    total_commission_paid DECIMAL(10,2) DEFAULT 0.00,
    is_approved BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_reseller (user_id)
);

-- Reseller products table
CREATE TABLE reseller_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    image_url VARCHAR(500),
    price_1_day DECIMAL(10,2) NOT NULL,
    price_1_week DECIMAL(10,2) NOT NULL,
    price_1_month DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slug (slug)
);

-- Reseller orders table (for commission tracking)
CREATE TABLE reseller_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    reseller_id INT NOT NULL,
    reseller_product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    order_amount DECIMAL(10,2) NOT NULL,
    reseller_earnings DECIMAL(10,2) NOT NULL, -- 80% of order amount
    commission_amount DECIMAL(10,2) NOT NULL, -- 20% of order amount
    license_key_id INT,
    status ENUM('pending', 'paid', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE CASCADE,
    FOREIGN KEY (reseller_product_id) REFERENCES reseller_products(id) ON DELETE CASCADE,
    FOREIGN KEY (license_key_id) REFERENCES license_keys(id) ON DELETE SET NULL
);

-- Reseller commission payouts table
CREATE TABLE reseller_payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(100),
    payment_details TEXT,
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE CASCADE
);

-- Add reseller_id to existing orders table for tracking
ALTER TABLE orders ADD COLUMN reseller_id INT NULL;
ALTER TABLE orders ADD COLUMN reseller_product_id INT NULL;
ALTER TABLE orders ADD FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL;
ALTER TABLE orders ADD FOREIGN KEY (reseller_product_id) REFERENCES reseller_products(id) ON DELETE SET NULL;

-- Add reseller role to users table
ALTER TABLE users ADD COLUMN user_type ENUM('customer', 'reseller', 'admin') DEFAULT 'customer';

-- Create indexes for better performance
CREATE INDEX idx_reseller_products_active ON reseller_products(is_active);
CREATE INDEX idx_reseller_products_featured ON reseller_products(is_featured);
CREATE INDEX idx_reseller_orders_status ON reseller_orders(status);
CREATE INDEX idx_reseller_payouts_status ON reseller_payouts(status);
