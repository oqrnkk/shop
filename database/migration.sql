-- Migration script to add new columns for license key management system
-- Run this if you already have an existing database

-- Add is_reseller column to users table
ALTER TABLE users ADD COLUMN is_reseller BOOLEAN DEFAULT FALSE AFTER is_admin;

-- Add license_key_used column to orders table
ALTER TABLE orders ADD COLUMN license_key_used VARCHAR(100) NULL AFTER stripe_payment_intent_id;

-- Create available_keys table
CREATE TABLE available_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    license_key VARCHAR(100) UNIQUE NOT NULL,
    duration ENUM('1_day', '1_week', '1_month') NOT NULL,
    added_by INT NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    used_by_order_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (used_by_order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Create indexes for better performance
CREATE INDEX idx_available_keys_product ON available_keys(product_id);
CREATE INDEX idx_available_keys_duration ON available_keys(duration);
CREATE INDEX idx_available_keys_used ON available_keys(is_used);
