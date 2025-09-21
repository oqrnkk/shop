<?php
// Get database connection
$conn = getDatabaseConnection();

$slug = $_GET['slug'] ?? '';
$product = null;
$features = [];

if (!$slug) {
    header('Location: index.php?page=products');
    exit();
}

if ($conn) {
    try {
        // Get product details
        $stmt = $conn->prepare("
            SELECT p.*
            FROM products p 
            WHERE p.slug = ? AND p.is_active = 1
        ");
        $stmt->execute([$slug]);
        $product = $stmt->fetch();

        if (!$product) {
            header('Location: index.php?page=products');
            exit();
        }

        // Get product features
        $stmt = $conn->prepare("
            SELECT * FROM product_features 
            WHERE product_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$product['id']]);
        $features = $stmt->fetchAll();
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Database error in product.php: " . $e->getMessage());
        header('Location: index.php?page=products');
        exit();
    }
} else {
    header('Location: index.php?page=products');
    exit();
}

// Handle non-AJAX purchase form submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Only handle non-AJAX requests here (AJAX is handled in index.php)
    if (!$isAjax) {
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
                INSERT INTO orders (user_id, product_id, duration, quantity, unit_price, total_price, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $product['id'], $duration, $quantity, $unit_price, $total_price])) {
                $order_id = $conn->lastInsertId();
                header("Location: index.php?page=payment&order_id=$order_id");
                exit();
            } else {
                $error = 'Failed to create order. Please try again.';
            }
        }
    }
}
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fas fa-chevron-right"></i>
        <a href="index.php?page=products">Products</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <!-- Product Details -->
    <div class="product-detail">
        <div class="product-detail-grid">
            <!-- Product Image and Info -->
            <div class="product-detail-left">
                <div class="product-image-large">
                    <?php 
                    $product_image_url = isset($product['image']) && $product['image'] ? $product['image'] : null;
                    ?>
                    <?php if ($product_image_url): ?>
                        <img src="<?php echo htmlspecialchars($product_image_url); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-gamepad"></i>
                    <?php endif; ?>
                </div>
                
                <div class="product-info-detail">
                    <h1 class="product-title-large"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="product-description-large"><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <!-- Features -->
                    <?php if (!empty($features)): ?>
                        <div class="product-features">
                            <h3>Features</h3>
                            <ul class="features-list">
                                <?php foreach ($features as $feature): ?>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        <strong><?php echo htmlspecialchars($feature['feature_name']); ?>:</strong>
                                        <?php echo htmlspecialchars($feature['feature_value']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
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
                                <?php
                                $dayCount = getAvailableKeyCount($product['id'], '1_day');
                                $weekCount = getAvailableKeyCount($product['id'], '1_week');
                                $monthCount = getAvailableKeyCount($product['id'], '1_month');
                                
                                // Check if any option is available
                                $hasAvailableStock = ($dayCount > 0 || $weekCount > 0 || $monthCount > 0);
                                
                                // Find the first available option to pre-select
                                $defaultDuration = '';
                                if ($dayCount > 0) $defaultDuration = '1_day';
                                elseif ($weekCount > 0) $defaultDuration = '1_week';
                                elseif ($monthCount > 0) $defaultDuration = '1_month';
                                ?>
                                
                                <label class="duration-option <?php echo $dayCount == 0 ? 'disabled' : ''; ?>">
                                    <input type="radio" name="duration" value="1_day" <?php echo ($defaultDuration == '1_day' || $defaultDuration == '') ? 'checked' : ''; ?> <?php echo $dayCount == 0 ? 'disabled' : ''; ?>>
                                    <div class="duration-content <?php echo $dayCount == 0 ? 'out-of-stock' : ''; ?>">
                                        <div class="duration-main">
                                            <div class="duration-name">1 Day</div>
                                            <div class="duration-price">$<?php echo number_format($product['price_1_day'], 2); ?></div>
                                        </div>
                                        <div class="key-availability <?php echo $dayCount > 0 ? 'available' : 'out-of-stock'; ?>">
                                            <?php if ($dayCount > 0): ?>
                                                <i class="fas fa-key"></i>
                                                <span><?php echo formatKeyCount($dayCount); ?> left</span>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle"></i>
                                                <span>Out of stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="duration-option <?php echo $weekCount == 0 ? 'disabled' : ''; ?>">
                                    <input type="radio" name="duration" value="1_week" <?php echo $defaultDuration == '1_week' ? 'checked' : ''; ?> <?php echo $weekCount == 0 ? 'disabled' : ''; ?>>
                                    <div class="duration-content <?php echo $weekCount == 0 ? 'out-of-stock' : ''; ?>">
                                        <div class="duration-main">
                                            <div class="duration-name">1 Week</div>
                                            <div class="duration-price">$<?php echo number_format($product['price_1_week'], 2); ?></div>
                                        </div>
                                        <div class="duration-savings">Save <?php echo round((1 - $product['price_1_week'] / ($product['price_1_day'] * 7)) * 100); ?>%</div>
                                        <div class="key-availability <?php echo $weekCount > 0 ? 'available' : 'out-of-stock'; ?>">
                                            <?php if ($weekCount > 0): ?>
                                                <i class="fas fa-key"></i>
                                                <span><?php echo formatKeyCount($weekCount); ?> left</span>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle"></i>
                                                <span>Out of stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="duration-option <?php echo $monthCount == 0 ? 'disabled' : ''; ?>">
                                    <input type="radio" name="duration" value="1_month" <?php echo $defaultDuration == '1_month' ? 'checked' : ''; ?> <?php echo $monthCount == 0 ? 'disabled' : ''; ?>>
                                    <div class="duration-content <?php echo $monthCount == 0 ? 'out-of-stock' : ''; ?>">
                                        <div class="duration-main">
                                            <div class="duration-name">1 Month</div>
                                            <div class="duration-price">$<?php echo number_format($product['price_1_month'], 2); ?></div>
                                        </div>
                                        <div class="duration-savings">Save <?php echo round((1 - $product['price_1_month'] / ($product['price_1_day'] * 30)) * 100); ?>%</div>
                                        <div class="key-availability <?php echo $monthCount > 0 ? 'available' : 'out-of-stock'; ?>">
                                            <?php if ($monthCount > 0): ?>
                                                <i class="fas fa-key"></i>
                                                <span><?php echo formatKeyCount($monthCount); ?> left</span>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle"></i>
                                                <span>Out of stock</span>
                                            <?php endif; ?>
                                        </div>
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
                        
                        <?php if ($hasAvailableStock): ?>
                            <button type="submit" class="btn btn-primary purchase-btn">
                                <i class="fas fa-shopping-cart"></i>
                                Purchase Now
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-disabled purchase-btn" disabled>
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            </button>
                        <?php endif; ?>
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
    <div class="related-products">
        <h2>Related Products</h2>
        <div class="products-grid">
            <?php
            $stmt = $conn->prepare("
                SELECT p.* 
                FROM products p 
                WHERE p.id != ? AND p.is_active = 1 
                ORDER BY p.is_featured DESC, p.created_at DESC 
                LIMIT 3
            ");
            $stmt->execute([$product['id']]);
            $related_products = $stmt->fetchAll();
            
            foreach ($related_products as $related):
            ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php 
                        $related_image_url = isset($related['image']) && $related['image'] ? $related['image'] : null;
                        ?>
                        <?php if ($related_image_url): ?>
                            <img src="<?php echo htmlspecialchars($related_image_url); ?>" 
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
                        <a href="index.php?page=product&slug=<?php echo $related['slug']; ?>" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i>
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
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

.product-features h3 {
    margin-bottom: 1rem;
    color: #ffffff;
}

.features-list {
    list-style: none;
    padding: 0;
}

.features-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.5;
}

.features-list i {
    color: #10b981;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.features-list {
    list-style: none;
    padding: 0;
}

.features-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: #666;
    line-height: 1.5;
}

.features-list i {
    color: #28a745;
    margin-top: 0.25rem;
    flex-shrink: 0;
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
    flex-direction: column;
    gap: 0.25rem;
    background: rgba(255, 255, 255, 0.03);
}

.duration-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.duration-option.disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.duration-option.disabled .duration-content {
    border-color: rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.02);
}

.duration-option input[type="radio"]:checked + .duration-content {
    border-color: #ff69b4;
    background: rgba(255, 105, 180, 0.1);
}

.duration-option.disabled input[type="radio"]:checked + .duration-content {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
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

.key-availability {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 0.25rem;
}

.key-availability.available {
    color: #10b981;
}

.key-availability.out-of-stock {
    color: #ef4444;
}

.key-availability i {
    font-size: 0.7rem;
}

.key-availability {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 0.25rem;
}

.key-availability.available {
    color: #28a745;
}

.key-availability.out-of-stock {
    color: #dc3545;
}

.key-availability i {
    font-size: 0.7rem;
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

.purchase-btn {
    width: 100%;
    justify-content: center;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, #ff69b4, #ff1493);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
}

.purchase-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 105, 180, 0.4);
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

.btn-disabled {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
    cursor: not-allowed;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.btn-disabled:hover {
    transform: none;
    box-shadow: none;
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
// Handle purchase form submission
document.addEventListener('DOMContentLoaded', function() {
    const purchaseForm = document.querySelector('.purchase-form');
    if (purchaseForm) {
        // Prevent automatic loading states from modern.js
        const submitBtn = purchaseForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            // Remove any existing click event listeners that might add loading state
            const newBtn = submitBtn.cloneNode(true);
            submitBtn.parentNode.replaceChild(newBtn, submitBtn);
        }
        
        // Add custom form submission handler
        purchaseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted via AJAX');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            console.log('Form data:', Object.fromEntries(formData));
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Invalid JSON response');
                }
            })
            .then(data => {
                if (data.success) {
                    // Redirect to payment page
                    window.location.href = data.redirect;
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: data.message, type: 'error' }
                    });
                    document.dispatchEvent(event);
                    
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // If there's a redirect (like to login), do it
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    }
                }
            })
            .catch(error => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Show error message
                const event = new CustomEvent('showToast', {
                    detail: { message: 'An error occurred. Please try again.', type: 'error' }
                });
                document.dispatchEvent(event);
                
                console.error('Error:', error);
            });
        });
    }
});

const prices = {
    '1_day': <?php echo $product['price_1_day']; ?>,
    '1_week': <?php echo $product['price_1_week']; ?>,
    '1_month': <?php echo $product['price_1_month']; ?>
};

function updateTotal() {
    const selectedRadio = document.querySelector('input[name="duration"]:checked');
    if (!selectedRadio || selectedRadio.disabled) {
        // If no valid option is selected, show "Out of stock"
        document.getElementById('unit-price').textContent = 'Out of stock';
        document.getElementById('summary-quantity').textContent = '0';
        document.getElementById('total-price').textContent = 'Out of stock';
        return;
    }
    
    const duration = selectedRadio.value;
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
