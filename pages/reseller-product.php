<?php
// Get database connection
$conn = getDatabaseConnection();

// Get product ID from URL
$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    header('Location: index.php?page=products');
    exit();
}

// Get reseller product details
$stmt = $conn->prepare("
    SELECT rp.*, r.business_name, r.is_approved
    FROM reseller_products rp
    JOIN resellers r ON rp.reseller_id = r.id
    WHERE rp.id = ? AND rp.is_active = 1 AND r.is_approved = 1 AND r.is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php?page=products');
    exit();
}

// Increment view count
$stmt = $conn->prepare("UPDATE reseller_products SET views_count = views_count + 1 WHERE id = ?");
$stmt->execute([$product_id]);

// Handle purchase form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    if (!$isLoggedIn) {
        redirectWithMessage('index.php?page=login', 'Please log in to purchase products.', 'error');
    }
    
    $duration = $_POST['duration'] ?? '';
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    
    if (!in_array($duration, ['1_day', '1_week', '1_month'])) {
        $error = 'Invalid duration selected.';
    } else {
        // Calculate price
        $price_field = 'price_' . $duration;
        $unit_price = $product[$price_field];
        $total_price = $unit_price * $quantity;
        
        // Create order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, reseller_product_id, reseller_id, duration, quantity, unit_price, total_price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $product['id'], $product['reseller_id'], $duration, $quantity, $unit_price, $total_price])) {
            $order_id = $conn->lastInsertId();
            
            // Redirect to payment page
            header("Location: index.php?page=payment&order_id=$order_id");
            exit();
        } else {
            $error = 'Failed to create order. Please try again.';
        }
    }
}

// Get related reseller products
$stmt = $conn->prepare("
    SELECT rp.*, r.business_name
    FROM reseller_products rp
    JOIN resellers r ON rp.reseller_id = r.id
    WHERE rp.id != ? AND rp.is_active = 1 AND r.is_approved = 1 AND r.is_active = 1
    ORDER BY rp.is_featured DESC, rp.created_at DESC
    LIMIT 3
");
$stmt->execute([$product_id]);
$related_products = $stmt->fetchAll();
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fas fa-chevron-right"></i>
        <a href="index.php?page=products">Products</a>
        <i class="fas fa-chevron-right"></i>
        <a href="index.php?page=products">Reseller Products</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <!-- Product Details -->
    <div class="product-detail">
        <div class="product-detail-grid">
            <!-- Product Image and Info -->
            <div class="product-detail-left">
                <div class="product-image-large">
                    <?php if ($product['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-gamepad"></i>
                    <?php endif; ?>
                </div>
                
                <div class="product-info-detail">
                    
                    <h1 class="product-title-large"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="product-description-large"><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <div class="reseller-info">
                        <span class="reseller-label">Sold by:</span>
                        <span class="reseller-name"><?php echo htmlspecialchars($product['business_name']); ?></span>
                    </div>
                    
                    <div class="product-stats">
                        <div class="stat">
                            <i class="fas fa-eye"></i>
                            <span><?php echo $product['views_count']; ?> views</span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-shopping-cart"></i>
                            <span><?php echo $product['sales_count']; ?> sales</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Purchase Section -->
            <div class="product-detail-right">
                <div class="purchase-card">
                    <h2>Purchase Options</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="purchase-form">
                        <input type="hidden" name="purchase" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Select Duration</label>
                            <div class="duration-options">
                                <label class="duration-option">
                                    <input type="radio" name="duration" value="1_day" checked>
                                    <div class="duration-content">
                                        <div class="duration-name">1 Day</div>
                                        <div class="duration-price">$<?php echo number_format($product['price_1_day'], 2); ?></div>
                                    </div>
                                </label>
                                
                                <label class="duration-option">
                                    <input type="radio" name="duration" value="1_week">
                                    <div class="duration-content">
                                        <div class="duration-name">1 Week</div>
                                        <div class="duration-price">$<?php echo number_format($product['price_1_week'], 2); ?></div>
                                        <div class="duration-savings">Save <?php echo round((1 - $product['price_1_week'] / ($product['price_1_day'] * 7)) * 100); ?>%</div>
                                    </div>
                                </label>
                                
                                <label class="duration-option">
                                    <input type="radio" name="duration" value="1_month">
                                    <div class="duration-content">
                                        <div class="duration-name">1 Month</div>
                                        <div class="duration-price">$<?php echo number_format($product['price_1_month'], 2); ?></div>
                                        <div class="duration-savings">Save <?php echo round((1 - $product['price_1_month'] / ($product['price_1_day'] * 30)) * 100); ?>%</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                class="form-input"
                                value="1"
                                min="1"
                                max="10"
                                onchange="updateTotal()"
                            >
                        </div>
                        
                        <div class="purchase-summary">
                            <div class="summary-row">
                                <span>Unit Price:</span>
                                <span id="unit-price">$<?php echo number_format($product['price_1_day'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Quantity:</span>
                                <span id="summary-quantity">1</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span id="total-price">$<?php echo number_format($product['price_1_day'], 2); ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary purchase-btn">
                            <i class="fas fa-shopping-cart"></i>
                            Purchase Now
                        </button>
                    </form>
                    
                    <div class="purchase-benefits">
                        <div class="benefit">
                            <i class="fas fa-shield-alt"></i>
                            <span>Undetected & Safe</span>
                        </div>
                        <div class="benefit">
                            <i class="fas fa-download"></i>
                            <span>Instant Download</span>
                        </div>
                        <div class="benefit">
                            <i class="fas fa-headset"></i>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div class="related-products">
            <h2>Other Reseller Products</h2>
            <div class="products-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($related['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-gamepad"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($related['short_description']); ?></p>
                            
                            <div class="product-prices">
                                <div class="price-option">
                                    <div class="price-duration">1 Day</div>
                                    <div class="price-amount">$<?php echo number_format($related['price_1_day'], 2); ?></div>
                                </div>
                                <div class="price-option">
                                    <div class="price-duration">1 Week</div>
                                    <div class="price-amount">$<?php echo number_format($related['price_1_week'], 2); ?></div>
                                </div>
                                <div class="price-option">
                                    <div class="price-duration">1 Month</div>
                                    <div class="price-amount">$<?php echo number_format($related['price_1_month'], 2); ?></div>
                                </div>
                            </div>
                            <a href="index.php?page=reseller-product&id=<?php echo $related['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i>
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    color: white;
    font-size: 0.9rem;
    padding-top: 1rem; /* Add some top padding for better spacing */
}

.breadcrumb a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb a:hover {
    color: white;
}

.breadcrumb i {
    font-size: 0.7rem;
    opacity: 0.6;
}

.product-detail {
    margin-bottom: 4rem;
}

.product-detail-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 3rem;
    align-items: start;
}

.product-detail-left {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.product-image-large {
    width: 100%;
    height: 300px;
    background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 5rem;
    overflow: hidden;
}

.product-image-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info-detail {
    padding: 2rem;
    color: white;
}



.product-title-large {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #ffffff;
}

.product-description-large {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
    margin-bottom: 2rem;
}

.reseller-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.reseller-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.reseller-name {
    font-weight: 600;
    color: #ffffff;
}

.product-stats {
    display: flex;
    gap: 2rem;
}

.stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.stat i {
    color: #ff69b4;
}

.product-detail-right {
    position: sticky;
    top: 2rem;
}

.purchase-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.purchase-card h2 {
    margin-bottom: 1.5rem;
    color: #ffffff;
}

.duration-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.duration-option {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.duration-option input[type="radio"] {
    display: none;
}

.duration-content {
    flex: 1;
    padding: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.03);
}

.duration-option input[type="radio"]:checked + .duration-content {
    border-color: #ff69b4;
    background: rgba(255, 105, 180, 0.1);
}

.duration-name {
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
}

.duration-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff69b4;
}

.duration-savings {
    font-size: 0.8rem;
    color: #10b981;
    font-weight: 500;
}

.purchase-summary {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin: 1.5rem 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
}

.summary-row.total {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    font-weight: 700;
    color: #ffffff;
    font-size: 1.1rem;
}

.purchase-btn {
    width: 100%;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.purchase-benefits {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.benefit {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.benefit i {
    color: #10b981;
    width: 16px;
}

.related-products {
    margin-top: 4rem;
}

.related-products h2 {
    text-align: center;
    margin-bottom: 2rem;
    color: white;
    font-size: 2rem;
}

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

.form-input {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff;
    backdrop-filter: blur(10px);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    width: 100%;
}

.form-input:focus {
    border-color: #ff69b4;
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
    background: rgba(255, 255, 255, 0.08);
    outline: none;
}

.form-label {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
}

.form-group {
    margin-bottom: 1.5rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #ff69b4, #ff1493);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 105, 180, 0.4);
}

@media (max-width: 768px) {
    .product-detail-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .product-detail-right {
        position: static;
    }
    
    .breadcrumb {
        flex-wrap: wrap;
    }
}
</style>

<script>
const prices = {
    '1_day': <?php echo $product['price_1_day']; ?>,
    '1_week': <?php echo $product['price_1_week']; ?>,
    '1_month': <?php echo $product['price_1_month']; ?>
};

function updateTotal() {
    const duration = document.querySelector('input[name="duration"]:checked').value;
    const quantity = parseInt(document.getElementById('quantity').value);
    const unitPrice = prices[duration];
    const total = unitPrice * quantity;
    
    document.getElementById('unit-price').textContent = '$' + unitPrice.toFixed(2);
    document.getElementById('summary-quantity').textContent = quantity;
    document.getElementById('total-price').textContent = '$' + total.toFixed(2);
}

// Update total when duration changes
document.querySelectorAll('input[name="duration"]').forEach(radio => {
    radio.addEventListener('change', updateTotal);
});

// Update total when quantity changes
document.getElementById('quantity').addEventListener('input', updateTotal);
</script>
