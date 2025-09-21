<?php
// This is the dashboard section content
?>

<div class="admin-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <p>Welcome back! Here's an overview of your store activity and performance metrics</p>
        </div>
        <div class="header-actions">
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </div>
            </div>
            <a href="index.php?page=admin&section=settings" class="btn btn-primary">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>
</div>

    <div class="dashboard-grid">
        <!-- Recent Orders -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-shopping-cart"></i> Recent Orders</h3>
                <a href="index.php?page=admin&section=orders" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-content">
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No orders yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
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

        <!-- Recent License Keys -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-key"></i> Recent License Keys</h3>
                <a href="index.php?page=admin&section=keys" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-content">
                <?php if (empty($recent_keys)): ?>
                    <div class="empty-state">
                        <i class="fas fa-key"></i>
                        <p>No license keys generated yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Product</th>
                                    <th>User</th>
                                    <th>Expires</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_keys as $key): ?>
                                    <tr>
                                        <td>
                                            <code class="license-key"><?php echo htmlspecialchars($key['license_key']); ?></code>
                                        </td>
                                        <td><?php echo htmlspecialchars($key['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($key['user_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($key['expires_at'])); ?></td>
                                        <td>
                                            <?php if ($key['is_active'] && strtotime($key['expires_at']) > time()): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-expired">Expired</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.dashboard-card {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 16px;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(51, 65, 85, 0.3);
}

.card-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #f8fafc;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-content {
    padding: 1.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #94a3b8;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table th,
.table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.table th {
    background: rgba(51, 65, 85, 0.3);
    font-weight: 600;
    color: #f8fafc;
}

.table td {
    color: #e2e8f0;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.license-key {
    background: rgba(51, 65, 85, 0.5);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.8rem;
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
}

.status-paid {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-pending {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-failed {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-expired {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style>
