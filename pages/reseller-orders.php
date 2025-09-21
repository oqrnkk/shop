<?php
// Reseller orders page handling
$reseller = getResellerByUserId($_SESSION['user_id']);

// Get reseller's orders
$conn = getDatabaseConnection();
$orders = [];

if ($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT ro.*, o.created_at as order_date, u.username as customer_username, u.email as customer_email
            FROM reseller_orders ro
            JOIN orders o ON ro.order_id = o.id
            JOIN users u ON o.user_id = u.id
            WHERE ro.reseller_id = ? AND ro.status IN ('paid', 'completed')
            ORDER BY ro.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$reseller['id']]);
        $orders = $stmt->fetchAll();
    } catch (Exception $e) {
        // Handle error silently
    }
}

// Calculate earnings
$totalEarnings = 0;
$totalOrders = count($orders);
foreach ($orders as $order) {
    $totalEarnings += $order['reseller_earnings'];
}
?>

<div class="container">
    <div class="reseller-orders-container">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-chart-line"></i> Reseller Dashboard</h1>
                <p>Track your sales performance and earnings from customer orders</p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=reseller-products" class="btn btn-outline">
                    <i class="fas fa-plus"></i>
                    Add Product
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Orders</h3>
                    <p class="stat-value"><?php echo $totalOrders; ?></p>
                    <span class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +12% this month
                    </span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon earnings">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Earnings</h3>
                    <p class="stat-value">$<?php echo number_format($totalEarnings, 2); ?></p>
                    <span class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +8% this month
                    </span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon commission">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3>Commission Rate</h3>
                    <p class="stat-value">10%</p>
                    <span class="stat-change neutral">
                        <i class="fas fa-minus"></i>
                        Standard rate
                    </span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon conversion">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Active Customers</h3>
                    <p class="stat-value"><?php echo count(array_unique(array_column($orders, 'customer_username'))); ?></p>
                    <span class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +5 new this month
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="dashboard-grid">
            <!-- Orders Section -->
            <div class="orders-section">
                <div class="section-header">
                    <h2><i class="fas fa-list-alt"></i> Recent Orders</h2>
                    <div class="section-actions">
                        <button class="btn btn-sm btn-outline" onclick="exportOrders()">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                    </div>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>No Orders Yet</h3>
                        <p>When customers make purchases through your referral, they'll appear here.</p>
                        <a href="index.php?page=reseller-products" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Your First Product
                        </a>
                    </div>
                <?php else: ?>
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="order-id">#<?php echo $order['order_id']; ?></span>
                                        </td>
                                        <td>
                                            <div class="customer-info">
                                                <div class="customer-avatar">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="customer-details">
                                                    <span class="customer-name"><?php echo htmlspecialchars($order['customer_username']); ?></span>
                                                    <span class="customer-email"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="product-info">
                                                <span class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="amount">$<?php echo number_format($order['order_amount'], 2); ?></span>
                                        </td>
                                        <td>
                                            <span class="commission">$<?php echo number_format($order['reseller_earnings'], 2); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $order['status']; ?>">
                                                <i class="fas fa-circle"></i>
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="order-date"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="order-actions">
                                                <button class="btn btn-sm btn-outline" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tools Section -->
            <div class="tools-section">
                <div class="section-header">
                    <h2><i class="fas fa-tools"></i> Reseller Tools</h2>
                </div>
                
                <div class="tools-grid">
                    <div class="tool-card">
                        <div class="tool-icon">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="tool-content">
                            <h3>Referral Link</h3>
                            <p>Share your unique referral link to earn commissions on sales.</p>
                            <div class="referral-link">
                                <div class="input-wrapper">
                                    <i class="fas fa-link input-icon"></i>
                                    <input type="text" value="http://de17.spaceify.eu:25045/?ref=<?php echo $reseller['id']; ?>" readonly>
                                </div>
                                <button onclick="copyReferralLink()" class="btn btn-primary">
                                    <i class="fas fa-copy"></i>
                                    Copy Link
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tool-card">
                        <div class="tool-icon analytics">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="tool-content">
                            <h3>Analytics Dashboard</h3>
                            <p>Track your performance and earnings over time with detailed analytics.</p>
                            <a href="#" class="btn btn-outline">
                                <i class="fas fa-chart-line"></i>
                                View Analytics
                            </a>
                        </div>
                    </div>
                    
                    <div class="tool-card">
                        <div class="tool-icon wallet">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="tool-content">
                            <h3>Withdrawals</h3>
                            <p>Request withdrawals for your earned commissions when you reach the minimum threshold.</p>
                            <a href="#" class="btn btn-outline">
                                <i class="fas fa-money-bill-wave"></i>
                                Request Withdrawal
                            </a>
                        </div>
                    </div>
                    
                    <div class="tool-card">
                        <div class="tool-icon support">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="tool-content">
                            <h3>Support</h3>
                            <p>Get help with your reseller account, products, or any questions you may have.</p>
                            <a href="index.php?page=contact" class="btn btn-outline">
                                <i class="fas fa-envelope"></i>
                                Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.reseller-orders-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 0;
}

/* Dashboard Header */
.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.header-content h1 {
    color: #ffffff;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-content p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

/* Stats Overview */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(255, 105, 180, 0.2);
    border-color: rgba(255, 105, 180, 0.3);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-icon.earnings {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.commission {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.conversion {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    color: #ffffff;
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 500;
}

.stat-change.positive {
    color: #10b981;
}

.stat-change.negative {
    color: #ef4444;
}

.stat-change.neutral {
    color: rgba(255, 255, 255, 0.6);
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

/* Orders Section */
.orders-section {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.section-header h2 {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-actions {
    display: flex;
    gap: 0.5rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    font-size: 4rem;
    color: rgba(255, 255, 255, 0.3);
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    color: #ffffff;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2rem;
}

/* Orders Table */
.orders-table-container {
    overflow-x: auto;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.orders-table td {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.9);
}

.orders-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.order-id {
    font-weight: 600;
    color: #ff69b4;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.customer-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
}

.customer-details {
    display: flex;
    flex-direction: column;
}

.customer-name {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.9rem;
}

.customer-email {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
}

.product-name {
    color: #ffffff;
    font-weight: 500;
}

.amount {
    color: #10b981;
    font-weight: 600;
}

.commission {
    color: #ff69b4;
    font-weight: 600;
}

.order-date {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid;
}

.status-badge.paid {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.3);
}

.status-badge.completed {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
    border-color: rgba(59, 130, 246, 0.3);
}

.status-badge i {
    font-size: 0.6rem;
}

/* Order Actions */
.order-actions {
    display: flex;
    gap: 0.5rem;
}

/* Tools Section */
.tools-section {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.tools-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.tool-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.tool-card:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 105, 180, 0.3);
    transform: translateY(-2px);
}

.tool-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.tool-icon.analytics {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.tool-icon.wallet {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.tool-icon.support {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.tool-content h3 {
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.tool-content p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}

/* Referral Link */
.referral-link {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 1rem;
    color: rgba(255, 255, 255, 0.6);
    z-index: 1;
}

.referral-link input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #ffffff;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.referral-link input:focus {
    outline: none;
    border-color: #ff69b4;
    background: rgba(255, 255, 255, 0.15);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 44px;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    min-height: 36px;
}

.btn-primary {
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 105, 180, 0.4);
}

.btn-outline {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .reseller-orders-container {
        padding: 1rem 0;
    }
    
    .dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .stats-overview {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .orders-table-container {
        font-size: 0.875rem;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 0.75rem 0.5rem;
    }
    
    .customer-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .customer-avatar {
        display: none;
    }
}

@media (max-width: 480px) {
    .dashboard-header,
    .orders-section,
    .tools-section {
        padding: 1.5rem;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<script>
// Copy referral link functionality
function copyReferralLink() {
    const input = document.querySelector('.referral-link input');
    input.select();
    input.setSelectionRange(0, 99999);
    
    // Use modern clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(() => {
            showCopyFeedback();
        });
    } else {
        // Fallback for older browsers
        document.execCommand('copy');
        showCopyFeedback();
    }
}

function showCopyFeedback() {
    const button = document.querySelector('.referral-link button');
    const originalText = button.innerHTML;
    const originalStyle = button.style.cssText;
    
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    button.style.color = 'white';
    button.style.transform = 'scale(1.05)';
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.style.cssText = originalStyle;
    }, 2000);
}

// Export orders functionality
function exportOrders() {
    // Create CSV content
    const table = document.querySelector('.orders-table');
    const rows = table.querySelectorAll('tbody tr');
    
    let csv = 'Order ID,Customer,Product,Amount,Commission,Status,Date\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [];
        
        cells.forEach((cell, index) => {
            if (index < 7) { // Exclude actions column
                let text = cell.textContent.trim();
                // Remove status badge styling
                if (cell.querySelector('.status-badge')) {
                    text = cell.querySelector('.status-badge').textContent.trim();
                }
                // Escape commas and quotes
                text = text.replace(/"/g, '""');
                rowData.push(`"${text}"`);
            }
        });
        
        csv += rowData.join(',') + '\n';
    });
    
    // Download CSV file
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reseller-orders.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    // Show success message
    const event = new CustomEvent('showToast', {
        detail: { message: 'Orders exported successfully!', type: 'success' }
    });
    document.dispatchEvent(event);
}

// View order details functionality
function viewOrderDetails(orderId) {
    // This would typically open a modal or navigate to order details page
    const event = new CustomEvent('showToast', {
        detail: { message: `Viewing order #${orderId} details...`, type: 'info' }
    });
    document.dispatchEvent(event);
    
    // For now, just show a placeholder
    console.log(`Viewing order details for order #${orderId}`);
}

// Add smooth animations and interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add hover effects to tool cards
    const toolCards = document.querySelectorAll('.tool-card');
    toolCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add table row hover effects
    const tableRows = document.querySelectorAll('.orders-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.boxShadow = '0 4px 15px rgba(255, 105, 180, 0.2)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Add loading animation to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (!this.classList.contains('btn-outline')) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            }
        });
    });
});

// Fallback toast function if global showToast is not available
if (typeof window.showToast === 'undefined') {
    window.showToast = function(message, type = 'info') {
        // Create a simple toast notification
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 300px;
        `;
        
        // Set background color based on type
        switch(type) {
            case 'success':
                toast.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                break;
            case 'error':
                toast.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                break;
            case 'warning':
                toast.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                break;
            default:
                toast.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
        }
        
        toast.textContent = message;
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    };
}
</script>
