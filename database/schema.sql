    -- Users table
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        is_admin BOOLEAN DEFAULT FALSE,
        is_reseller BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    );



    -- Products table
    CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        slug VARCHAR(200) UNIQUE NOT NULL,
        description TEXT,
        short_description VARCHAR(500),
        image VARCHAR(255),
        price_1_day DECIMAL(10,2) NOT NULL,
        price_1_week DECIMAL(10,2) NOT NULL,
        price_1_month DECIMAL(10,2) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        is_featured BOOLEAN DEFAULT FALSE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    -- Product features table
    CREATE TABLE product_features (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        feature_name VARCHAR(200) NOT NULL,
        feature_value TEXT,
        sort_order INT DEFAULT 0,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );

    -- Orders table
    CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        duration ENUM('1_day', '1_week', '1_month') NOT NULL,
        quantity INT DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        payment_id VARCHAR(255),
        stripe_payment_intent_id VARCHAR(255),
        license_key_used VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );

    -- Available license keys table (for admins/resellers to manage)
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

    -- License keys table (for tracking used keys)
    CREATE TABLE license_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        license_key VARCHAR(100) UNIQUE NOT NULL,
        duration ENUM('1_day', '1_week', '1_month') NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        activated_at TIMESTAMP NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );

    -- Password resets table
    CREATE TABLE password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Activity logs table
    CREATE TABLE activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    );

    -- Settings table
    CREATE TABLE settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );



    -- Insert sample products
    INSERT INTO products (name, slug, description, short_description, price_1_day, price_1_week, price_1_month, is_featured) VALUES
    ('CS2 Premium Cheat', 'cs2-premium-cheat', 'Advanced Counter-Strike 2 cheat with aimbot, wallhack, and more features.', 'Professional CS2 cheat with advanced features', 9.99, 49.99, 149.99, TRUE),
    ('Valorant Elite Hack', 'valorant-elite-hack', 'Premium Valorant cheat with undetected features and regular updates.', 'Undetected Valorant hack with premium features', 12.99, 59.99, 179.99, TRUE),
    ('Fortnite Pro Cheat', 'fortnite-pro-cheat', 'Advanced Fortnite cheat with building assistance and aimbot.', 'Professional Fortnite cheat with building hacks', 8.99, 39.99, 119.99, FALSE),
    ('PUBG Ultimate Hack', 'pubg-ultimate-hack', 'Complete PUBG cheat suite with ESP, aimbot, and vehicle hacks.', 'Complete PUBG hack suite with all features', 11.99, 54.99, 159.99, TRUE),
    ('League of Legends Pro', 'lol-pro-cheat', 'Advanced LoL cheat with script detection and performance optimization.', 'Professional LoL cheat with scripts', 7.99, 34.99, 99.99, FALSE);

    -- Insert product features
    INSERT INTO product_features (product_id, feature_name, feature_value, sort_order) VALUES
    (1, 'Aimbot', 'Advanced aimbot with customizable settings', 1),
    (1, 'Wallhack', 'See enemies through walls', 2),
    (1, 'Triggerbot', 'Automatic shooting when crosshair is on target', 3),
    (1, 'Bhop', 'Bunny hop assistance', 4),
    (1, 'Radar Hack', '2D radar showing enemy positions', 5),
    (2, 'Aimbot', 'Precise aimbot with smooth movement', 1),
    (2, 'ESP', 'Enhanced ESP with customizable colors', 2),
    (2, 'Triggerbot', 'Automatic shooting system', 3),
    (2, 'No Recoil', 'Eliminate weapon recoil', 4),
    (3, 'Aimbot', 'Advanced aimbot system', 1),
    (3, 'Building Assistant', 'Automated building placement', 2),
    (3, 'ESP', 'Player and item ESP', 3),
    (4, 'ESP', 'Complete ESP system', 1),
    (4, 'Aimbot', 'Precise aimbot', 2),
    (4, 'Vehicle ESP', 'Vehicle location and information', 3),
    (5, 'Script Detection', 'Advanced script detection system', 1),
    (5, 'Performance Boost', 'Game performance optimization', 2);

    -- Insert default settings
    INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
    ('site_name', 'CheatStore', 'string', 'Website name'),
    ('site_description', 'Premium gaming cheats and hacks', 'string', 'Website description'),
    ('stripe_publishable_key', 'pk_test_51Rw4M8R7zRXgs80ByPvF5HtZn37fnJEk7kKUGCyfD56N9ZuZgzmXttsn5aHj5TFrMplp9MjBPwFtbkPjIhRs3q4m0077BSeuxY', 'string', 'Stripe publishable key'),
    ('stripe_secret_key', 'sk_test_51Rw4M8R7zRXgs80BibtucGeTIsPBlfi9OW5bVdSof1aQ43HurKkJFvaQxVu8qu1xnjgN1KkjpWPWwybs4PYbutiB00Nt7cUdrg', 'string', 'Stripe secret key'),
    ('support_email', 'support@cheatstore.net', 'string', 'Support email address'),
    ('discord_invite', 'https://discord.gg/cheatstore', 'string', 'Discord invite link'),
    ('maintenance_mode', 'false', 'boolean', 'Maintenance mode status'),
    ('registration_enabled', 'true', 'boolean', 'Allow new user registrations');

    -- Create admin user (password: admin123) - Commented out for now
    -- INSERT INTO users (username, email, password, is_admin) VALUES
    -- ('admin', 'admin@cheatstore.net', '$2y$10$3KyXHxXHxXHxXHxXHxXHxOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

    -- Create indexes for better performance

    CREATE INDEX idx_products_active ON products(is_active);
    CREATE INDEX idx_orders_user ON orders(user_id);
    CREATE INDEX idx_orders_status ON orders(status);
    CREATE INDEX idx_license_keys_user ON license_keys(user_id);
    CREATE INDEX idx_license_keys_active ON license_keys(is_active);
    CREATE INDEX idx_available_keys_product ON available_keys(product_id);
    CREATE INDEX idx_available_keys_duration ON available_keys(duration);
    CREATE INDEX idx_available_keys_used ON available_keys(is_used);
    CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
    CREATE INDEX idx_activity_logs_created ON activity_logs(created_at);
