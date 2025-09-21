<?php
// Orders page handling
$user = getCurrentUser();

if (!$user) {
    header('Location: index.php?page=login');
    exit();
}

// Get user's orders
$conn = getDatabaseConnection();
$orders = [];

if ($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT o.*, p.name as product_name, p.slug as product_slug, p.image as product_image
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $orders = $stmt->fetchAll();
    } catch (Exception $e) {
        // Handle error silently
    }
}
?>

<div class="container">
    <div class="orders-container">
        <!-- Header Section -->
        <div class="orders-header">
            <div class="header-content">
                <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
                <p>View your order history and download your license keys</p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=products" class="btn btn-outline">
                    <i class="fas fa-plus"></i>
                    Browse Products
                </a>
            </div>
        </div>

        <!-- Orders Stats -->
        <div class="orders-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Orders</h3>
                    <p class="stat-value"><?php echo count($orders); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>Completed</h3>
                    <p class="stat-value"><?php echo count(array_filter($orders, function($order) { return $order['status'] === 'completed'; })); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>Pending</h3>
                    <p class="stat-value"><?php echo count(array_filter($orders, function($order) { return $order['status'] === 'pending'; })); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Spent</h3>
                    <p class="stat-value">$<?php echo number_format(array_sum(array_column($orders, 'total_price')), 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Orders Content -->
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="index.php?page=products" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="product-info">
                                    <div class="product-icon">
                                        <i class="fas fa-gamepad"></i>
                                    </div>
                                    <div class="product-details">
                                        <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                                        <p class="order-id">Order #<?php echo $order['id']; ?></p>
                                    </div>
                                </div>
                                <p class="order-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <div class="order-status-wrapper">
                                <span class="order-status <?php echo $order['status']; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-details">
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="label">
                                        <i class="fas fa-clock"></i>
                                        Duration:
                                    </span>
                                    <span class="value"><?php echo str_replace('_', ' ', $order['duration']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">
                                        <i class="fas fa-hashtag"></i>
                                        Quantity:
                                    </span>
                                    <span class="value"><?php echo $order['quantity']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">
                                        <i class="fas fa-dollar-sign"></i>
                                        Total:
                                    </span>
                                    <span class="value total-price">$<?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">
                                        <i class="fas fa-credit-card"></i>
                                        Payment:
                                    </span>
                                    <span class="value"><?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if ($order['status'] === 'paid' || $order['status'] === 'completed'): ?>
                            <div class="order-actions">
                                <a href="index.php?page=product&slug=<?php echo $order['product_slug']; ?>" class="btn btn-outline">
                                    <i class="fas fa-eye"></i>
                                    View Product
                                </a>
                                <?php if ($order['status'] === 'completed'): ?>
                                    <button class="btn btn-primary" onclick="downloadKey(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-download"></i>
                                        Download Key
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.orders-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

/* Header Section */
.orders-header {
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

/* Orders Stats */
.orders-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.stat-icon.completed {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.pending {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.total {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
    margin: 0;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.empty-icon {
    font-size: 4rem;
    color: rgba(255, 255, 255, 0.3);
    margin-bottom: 1.5rem;
}

.empty-state h2 {
    color: #ffffff;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2rem;
}

/* Orders Grid */
.orders-grid {
    display: grid;
    gap: 1.5rem;
}

.order-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(255, 105, 180, 0.2);
    border-color: rgba(255, 105, 180, 0.3);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.product-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.product-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.product-details h3 {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.order-id {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin: 0;
}

.order-date {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.order-status-wrapper {
    display: flex;
    align-items: center;
}

.order-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    border: 1px solid;
}

.order-status.pending {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
    border-color: rgba(245, 158, 11, 0.3);
}

.order-status.paid {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.3);
}

.order-status.completed {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
    border-color: rgba(59, 130, 246, 0.3);
}

.order-status.cancelled {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
}

.order-status.refunded {
    background: rgba(156, 163, 175, 0.2);
    color: #9ca3af;
    border-color: rgba(156, 163, 175, 0.3);
}

.order-status i {
    font-size: 0.6rem;
}

/* Order Details */
.order-details {
    margin-bottom: 1.5rem;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-item .label {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-item .value {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.95rem;
}

.detail-item .total-price {
    color: #10b981;
    font-size: 1.1rem;
}

/* Order Actions */
.order-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
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
@media (max-width: 768px) {
    .orders-container {
        padding: 1rem 0;
    }
    
    .orders-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .orders-stats {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .product-info {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .order-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .orders-header,
    .order-card {
        padding: 1.5rem;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Download key functionality
function downloadKey(orderId) {
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';
    button.disabled = true;
    
    // Simulate download process
    setTimeout(() => {
        // This would typically make an AJAX request to get the license key
        const event = new CustomEvent('showToast', {
            detail: { message: 'License key downloaded successfully!', type: 'success' }
        });
        document.dispatchEvent(event);
        
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
        
        // For demo purposes, create a fake download
        const fakeKey = `LICENSE-${orderId}-${Date.now()}`;
        const blob = new Blob([fakeKey], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `license-key-${orderId}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }, 1500);
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
    
    // Add hover effects to order cards
    const orderCards = document.querySelectorAll('.order-card');
    orderCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.01)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
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
