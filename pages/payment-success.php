<?php
require_once 'config/config.php';

// Get database connection
$conn = getDatabaseConnection();

if (!$isLoggedIn) {
    header('Location: index.php?page=login');
    exit();
}

$order_id = $_GET['order_id'] ?? '';

if (!$order_id) {
    header('Location: index.php?page=products');
    exit();
}

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, p.name as product_name, p.slug as product_slug
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'paid'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php?page=products');
    exit();
}

// Get license keys for this order
$stmt = $conn->prepare("
    SELECT * FROM license_keys 
    WHERE order_id = ? AND user_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$license_keys = $stmt->fetchAll();
?>

<div class="container">
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Payment Successful!</h1>
            <p>Thank you for your purchase. Your order has been processed successfully.</p>
        </div>
        
        <div class="success-content">
            <div class="order-details">
                <h2>Order Details</h2>
                <div class="order-info">
                    <div class="info-row">
                        <span class="label">Order ID:</span>
                        <span class="value">#<?php echo $order_id; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Product:</span>
                        <span class="value"><?php echo htmlspecialchars($order['product_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Duration:</span>
                        <span class="value"><?php echo ucfirst(str_replace('_', ' ', $order['duration'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Quantity:</span>
                        <span class="value"><?php echo $order['quantity']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Total Amount:</span>
                        <span class="value total">$<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Purchase Date:</span>
                        <span class="value"><?php echo date('F j, Y g:i A', strtotime($order['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="license-keys">
                <h2>Your License Keys</h2>
                <div class="keys-list">
                    <?php foreach ($license_keys as $key): ?>
                        <div class="license-key-item">
                            <div class="key-info">
                                <span class="key-label">License Key:</span>
                                <span class="key-value"><?php echo htmlspecialchars($key['license_key']); ?></span>
                            </div>
                            <div class="key-details">
                                <span class="expires">Expires: <?php echo date('F j, Y', strtotime($key['expires_at'])); ?></span>
                                <button class="btn btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($key['license_key']); ?>')">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="next-steps">
                <h2>Next Steps</h2>
                <div class="steps-list">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Download the Software</h3>
                            <p>Download the cheat software from your dashboard or the product page.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Install and Run</h3>
                            <p>Install the software and run it as administrator.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Enter License Key</h3>
                            <p>Use one of the license keys above to activate the software.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="success-actions">
            <a href="index.php?page=dashboard" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i>
                Go to Dashboard
            </a>
            <a href="index.php?page=products" class="btn btn-outline">
                <i class="fas fa-shopping-cart"></i>
                Browse More Products
            </a>
        </div>
    </div>
</div>

<style>
.success-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 0;
}

.success-header {
    text-align: center;
    margin-bottom: 3rem;
    color: white;
}

.success-icon {
    font-size: 4rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.success-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.success-header p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.success-content {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.order-details h2,
.license-keys h2,
.next-steps h2 {
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

.order-info {
    display: grid;
    gap: 1rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f5f5f5;
}

.info-row:last-child {
    border-bottom: none;
}

.label {
    font-weight: 600;
    color: #666;
}

.value {
    color: #333;
    font-weight: 500;
}

.value.total {
    color: #667eea;
    font-weight: 700;
    font-size: 1.1rem;
}

.keys-list {
    display: grid;
    gap: 1rem;
}

.license-key-item {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    transition: border-color 0.3s ease;
}

.license-key-item:hover {
    border-color: #667eea;
}

.key-info {
    margin-bottom: 0.5rem;
}

.key-label {
    font-weight: 600;
    color: #666;
    margin-right: 0.5rem;
}

.key-value {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #333;
    background: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.key-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.expires {
    color: #666;
    font-size: 0.9rem;
}

.btn-copy {
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-copy:hover {
    background: #5a6fd8;
}

.steps-list {
    display: grid;
    gap: 1.5rem;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.step-number {
    background: #667eea;
    color: white;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.step-content h3 {
    margin-bottom: 0.5rem;
    color: #333;
    font-size: 1.1rem;
}

.step-content p {
    color: #666;
    margin: 0;
}

.success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.success-actions .btn {
    min-width: 200px;
}

@media (max-width: 768px) {
    .success-header h1 {
        font-size: 2rem;
    }
    
    .success-content {
        padding: 1.5rem;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .key-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .success-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .success-actions .btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        const button = event.target.closest('.btn-copy');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.style.background = '#28a745';
        
        setTimeout(function() {
            button.innerHTML = originalText;
            button.style.background = '#667eea';
        }, 2000);
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        alert('Failed to copy to clipboard. Please copy manually.');
    });
}
</script>
