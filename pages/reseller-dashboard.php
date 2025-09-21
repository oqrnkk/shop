<?php
// Get reseller info
$reseller = getResellerByUserId($_SESSION['user_id']);

// Get earnings stats
$earningsAll = getResellerEarnings($reseller['id'], 'all');
$earningsToday = getResellerEarnings($reseller['id'], 'today');
$earningsWeek = getResellerEarnings($reseller['id'], 'week');
$earningsMonth = getResellerEarnings($reseller['id'], 'month');

// Get recent orders
$recentOrders = getResellerOrders($reseller['id'], 10, 0);

// Get products count
$products = getResellerProducts($reseller['id']);
$totalProducts = count($products);
$activeProducts = count(array_filter($products, function($p) { return $p['is_active']; }));
?>

<div class="container">
    <div class="reseller-dashboard">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-store"></i> Reseller Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($reseller['business_name']); ?>!</p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=reseller-products&action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Product
                </a>
                <a href="index.php?page=reseller-orders" class="btn btn-outline">
                    <i class="fas fa-shopping-cart"></i> View Orders
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">$<?php echo number_format($earningsAll['total_earnings'], 2); ?></div>
                    <div class="stat-label">Total Earnings</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $earningsAll['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $totalProducts; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $activeProducts; ?></div>
                    <div class="stat-label">Active Products</div>
                </div>
            </div>
        </div>

        <!-- Period Stats -->
        <div class="period-stats">
            <h2>Recent Performance</h2>
            <div class="period-grid">
                <div class="period-card">
                    <h3>Today</h3>
                    <div class="period-amount">$<?php echo number_format($earningsToday['total_earnings'], 2); ?></div>
                    <div class="period-orders"><?php echo $earningsToday['total_orders']; ?> orders</div>
                </div>
                
                <div class="period-card">
                    <h3>This Week</h3>
                    <div class="period-amount">$<?php echo number_format($earningsWeek['total_earnings'], 2); ?></div>
                    <div class="period-orders"><?php echo $earningsWeek['total_orders']; ?> orders</div>
                </div>
                
                <div class="period-card">
                    <h3>This Month</h3>
                    <div class="period-amount">$<?php echo number_format($earningsMonth['total_earnings'], 2); ?></div>
                    <div class="period-orders"><?php echo $earningsMonth['total_orders']; ?> orders</div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <div class="section-header">
                <h2>Recent Orders</h2>
                <a href="index.php?page=reseller-orders" class="btn btn-sm btn-outline">View All</a>
            </div>
            
            <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>No orders yet. Start adding products to make your first sale!</p>
                    <a href="index.php?page=reseller-products&action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Product
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Your Earnings</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                    <td>$<?php echo number_format($order['order_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($order['reseller_earnings'], 2); ?></td>
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

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="index.php?page=reseller-products&action=add" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3>Add Product</h3>
                    <p>Upload a new cheat or hack</p>
                </a>
                
                <a href="index.php?page=reseller-products" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <h3>Manage Products</h3>
                    <p>Edit or delete your products</p>
                </a>
                
                <a href="index.php?page=reseller-orders" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>View Orders</h3>
                    <p>Check your sales and earnings</p>
                </a>
                
                <a href="index.php?page=reseller-profile" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h3>Edit Profile</h3>
                    <p>Update your business information</p>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.reseller-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
}

.header-content h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.header-content p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 1rem;
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
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.period-stats {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2rem;
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.period-stats::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
}

.period-stats h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 1.5rem;
}

.period-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.period-card {
    text-align: center;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.period-card:hover {
    border-color: rgba(255, 105, 180, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.period-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.5rem;
}

.period-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.25rem;
}

.period-orders {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
}

.recent-orders {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2rem;
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.recent-orders::before {
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
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #ffffff;
    margin: 0;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: rgba(255, 255, 255, 0.7);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: #ff69b4;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.empty-state p {
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
}

.quick-actions {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2rem;
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.quick-actions::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
}

.quick-actions h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 1.5rem;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.action-card {
    display: block;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    text-align: center;
    backdrop-filter: blur(10px);
}

.action-card:hover {
    background: linear-gradient(135deg, #ff69b4, #ff1493);
    color: white;
    transform: translateY(-4px);
    border-color: rgba(255, 105, 180, 0.3);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.action-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ff69b4, #ff1493);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 1.2rem;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.action-card:hover .action-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.action-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.action-card p {
    font-size: 0.9rem;
    opacity: 0.8;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .period-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>
