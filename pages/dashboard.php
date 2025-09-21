<?php
// Get database connection
$conn = getDatabaseConnection();

// Initialize variables
$total_orders = 0;
$active_keys = 0;
$total_spent = 0;
$recent_orders = [];
$license_keys = [];

if ($conn) {
    try {
        // Get user stats
        $stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $total_orders = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) as active_keys FROM license_keys WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()");
        $stmt->execute([$_SESSION['user_id']]);
        $active_keys = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT SUM(total_price) as total_spent FROM orders WHERE user_id = ? AND status = 'paid'");
        $stmt->execute([$_SESSION['user_id']]);
        $total_spent = $stmt->fetchColumn() ?: 0;

        // Get recent orders
        $stmt = $conn->prepare("
            SELECT o.*, p.name as product_name, p.slug as product_slug
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $recent_orders = $stmt->fetchAll();

        // Get active license keys
        $stmt = $conn->prepare("
            SELECT lk.*, p.name as product_name, p.slug as product_slug
            FROM license_keys lk
            JOIN products p ON lk.product_id = p.id
            WHERE lk.user_id = ? AND lk.is_active = 1
            ORDER BY lk.expires_at ASC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $license_keys = $stmt->fetchAll();
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Database error in dashboard.php: " . $e->getMessage());
    }
}
?>

<div class="container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
            <p>Manage your account, view orders, and access your license keys</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="dashboard-stats">
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
                <div class="stat-number">$<?php echo number_format($total_spent, 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- License Keys -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Your License Keys</h2>
                <a href="index.php?page=orders" class="btn btn-outline">View All Orders</a>
            </div>
            
            <?php if (empty($license_keys)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3>No active license keys</h3>
                    <p>Purchase a product to get your first license key</p>
                    <a href="index.php?page=products" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i>
                        Browse Products
                    </a>
                </div>
            <?php else: ?>
                <div class="license-keys-grid">
                    <?php foreach ($license_keys as $key): ?>
                        <div class="license-key-card">
                            <div class="key-header">
                                <h3><?php echo htmlspecialchars($key['product_name']); ?></h3>
                                <span class="key-status <?php echo isKeyExpired($key['expires_at']) ? 'expired' : 'active'; ?>">
                                    <?php echo isKeyExpired($key['expires_at']) ? 'Expired' : 'Active'; ?>
                                </span>
                            </div>
                            
                            <div class="key-details">
                                <div class="key-info">
                                    <label>License Key:</label>
                                    <div class="key-value">
                                        <code><?php echo htmlspecialchars($key['license_key']); ?></code>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo $key['license_key']; ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="key-info">
                                    <label>Duration:</label>
                                    <span><?php echo ucfirst(str_replace('_', ' ', $key['duration'])); ?></span>
                                </div>
                                
                                <div class="key-info">
                                    <label>Expires:</label>
                                    <span><?php echo date('M j, Y H:i', strtotime($key['expires_at'])); ?></span>
                                </div>
                                
                                <?php if (!isKeyExpired($key['expires_at'])): ?>
                                    <div class="key-info">
                                        <label>Time Remaining:</label>
                                        <span class="time-remaining" data-expires="<?php echo $key['expires_at']; ?>">
                                            Calculating...
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="key-actions">
                                <a href="index.php?page=product&slug=<?php echo $key['product_slug']; ?>" class="btn btn-outline">
                                    <i class="fas fa-download"></i>
                                    Download
                                </a>
                                <a href="#" class="btn btn-secondary">
                                    <i class="fas fa-question-circle"></i>
                                    Support
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Orders -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Orders</h2>
                <a href="index.php?page=orders" class="btn btn-outline">View All</a>
            </div>
            
            <?php if (empty($recent_orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>No orders yet</h3>
                    <p>Start by purchasing your first product</p>
                    <a href="index.php?page=products" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i>
                        Browse Products
                    </a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Duration</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="index.php?page=product&slug=<?php echo $order['product_slug']; ?>" class="product-link">
                                            <?php echo htmlspecialchars($order['product_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $order['duration'])); ?></td>
                                    <td><?php echo $order['quantity']; ?></td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dashboard-header {
    text-align: center;
    margin-bottom: 3rem;
    color: white;
    padding-top: 1rem;
}

.dashboard-title h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.dashboard-title p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 105, 180, 0.3);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.dashboard-content {
    display: grid;
    gap: 3rem;
}

.dashboard-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.dashboard-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.section-header h2 {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: #ff69b4;
    font-size: 2rem;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.empty-state h3 {
    margin-bottom: 1rem;
    color: #ffffff;
    font-weight: 600;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2rem;
}

.license-keys-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.license-key-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.license-key-card:hover {
    border-color: rgba(255, 105, 180, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.key-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.key-header h3 {
    color: #ffffff;
    font-size: 1.1rem;
    margin: 0;
    font-weight: 600;
}

.key-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.key-status.active {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.key-status.expired {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.key-details {
    margin-bottom: 1.5rem;
}

.key-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.key-info label {
    font-weight: 500;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.key-value {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.key-value code {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9rem;
    color: #ffffff;
}

.copy-btn {
    background: none;
    border: none;
    color: #ff69b4;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.copy-btn:hover {
    background: rgba(255, 105, 180, 0.1);
}

.key-actions {
    display: flex;
    gap: 0.75rem;
}

.key-actions .btn {
    flex: 1;
    justify-content: center;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.product-link {
    color: #ff69b4;
    text-decoration: none;
    font-weight: 500;
}

.product-link:hover {
    text-decoration: underline;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffd54f;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-paid {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-completed {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .license-keys-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .key-actions {
        flex-direction: column;
    }
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show success message
        const btn = event.target.closest('.copy-btn');
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        
        icon.className = 'fas fa-check';
        btn.style.color = '#28a745';
        
        setTimeout(() => {
            icon.className = originalClass;
            btn.style.color = '#667eea';
        }, 2000);
    });
}

function updateTimeRemaining() {
    const elements = document.querySelectorAll('.time-remaining');
    
    elements.forEach(element => {
        const expiresAt = new Date(element.dataset.expires);
        const now = new Date();
        const diff = expiresAt - now;
        
        if (diff <= 0) {
            element.textContent = 'Expired';
            element.style.color = '#dc3545';
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        if (days > 0) {
            element.textContent = `${days}d ${hours}h`;
        } else if (hours > 0) {
            element.textContent = `${hours}h ${minutes}m`;
        } else {
            element.textContent = `${minutes}m`;
        }
    });
}

// Update time remaining every minute
updateTimeRemaining();
setInterval(updateTimeRemaining, 60000);
</script>
