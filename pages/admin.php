<?php
// Get database connection
$conn = getDatabaseConnection();

// Initialize variables
$total_users = 0;
$total_products = 0;
$total_orders = 0;
$active_keys = 0;
$total_revenue = 0;
$total_resellers = 0;
$pending_resellers = 0;
$recent_orders = [];
$recent_keys = [];

if ($conn) {
    try {
        // Get admin stats
        $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE is_admin = 0");
        $stmt->execute();
        $total_users = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products");
        $stmt->execute();
        $total_products = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM orders");
        $stmt->execute();
        $total_orders = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) as active_keys FROM license_keys WHERE is_active = 1 AND expires_at > NOW()");
        $stmt->execute();
        $active_keys = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT SUM(total_price) as total_revenue FROM orders WHERE status = 'paid'");
        $stmt->execute();
        $total_revenue = $stmt->fetchColumn() ?: 0;

        // Get reseller stats
        $stmt = $conn->prepare("SELECT COUNT(*) as total_resellers FROM resellers");
        $stmt->execute();
        $total_resellers = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) as pending_resellers FROM resellers WHERE is_approved = 0");
        $stmt->execute();
        $pending_resellers = $stmt->fetchColumn();

        // Get recent orders
        $stmt = $conn->prepare("
            SELECT o.*, p.name as product_name, u.username as user_name
            FROM orders o
            JOIN products p ON o.product_id = p.id
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recent_orders = $stmt->fetchAll();

        // Get recent license keys
        $stmt = $conn->prepare("
            SELECT lk.*, p.name as product_name, u.username as user_name
            FROM license_keys lk
            JOIN products p ON lk.product_id = p.id
            JOIN users u ON lk.user_id = u.id
            ORDER BY lk.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recent_keys = $stmt->fetchAll();
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Database error in admin.php: " . $e->getMessage());
    }
}
?>

<div class="container">
    <!-- Admin Header -->
    <div class="admin-section">
        <div class="admin-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-crown"></i> Admin Panel</h1>
                    <p>Manage your store, users, products, and license keys with powerful tools and insights</p>
                </div>
                <div class="header-actions">
                    <div class="quick-stats">
                        <div class="stat-item">
                            <i class="fas fa-plus"></i>
                            <span>Add Product</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-store"></i>
                            <span>Manage Resellers</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-key"></i>
                            <span>Manage Keys</span>
                        </div>
                    </div>
                    <a href="index.php?page=admin&section=products" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                </div>
            </div>
        </div>

        <!-- Admin Stats -->
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Products</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $active_keys; ?></div>
                    <div class="stat-label">Active Keys</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_resellers; ?></div>
                    <div class="stat-label">Resellers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $pending_resellers; ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
        </div>

        <!-- Admin Navigation -->
        <div class="admin-nav">
            <a href="index.php?page=admin&section=dashboard" class="nav-item <?php echo (!isset($_GET['section']) || $_GET['section'] === 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="index.php?page=admin&section=products" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'products') ? 'active' : ''; ?>">
                <i class="fas fa-gamepad"></i>
                Products
            </a>
            <a href="index.php?page=admin&section=orders" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'orders') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                Orders
            </a>
            <a href="index.php?page=admin&section=users" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'users') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a href="index.php?page=admin&section=keys" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'keys') ? 'active' : ''; ?>">
                <i class="fas fa-key"></i>
                License Keys
            </a>
            <a href="index.php?page=admin&section=resellers" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'resellers') ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                Resellers
                <?php if ($pending_resellers > 0): ?>
                    <span class="badge"><?php echo $pending_resellers; ?></span>
                <?php endif; ?>
            </a>
            <a href="index.php?page=admin&section=settings" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'settings') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </div>

        <!-- Admin Content -->
        <div class="admin-content">
            <?php
            $section = $_GET['section'] ?? 'dashboard';
            
            switch ($section) {
                case 'dashboard':
                    include 'admin/dashboard.php';
                    break;
                case 'products':
                    include 'admin/products.php';
                    break;
                case 'orders':
                    include 'admin/orders.php';
                    break;
                case 'users':
                    include 'admin/users.php';
                    break;
                case 'keys':
                    include 'admin/keys.php';
                    break;
                case 'resellers':
                    include 'admin/resellers.php';
                    break;
                case 'settings':
                    include 'admin/settings.php';
                    break;
                case 'test':
                    include 'admin/test.php';
                    break;
                default:
                    include 'admin/dashboard.php';
                    break;
            }
            ?>
        </div>
    </div>
</div>


