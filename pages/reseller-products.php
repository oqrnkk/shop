<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Check if user is a reseller
$reseller = getResellerByUserId($_SESSION['user_id']);
if (!$reseller) {
    header('Location: index.php?page=reseller-apply');
    exit();
}

$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_product') {
        $productData = [
            'name' => sanitizeInput($_POST['name']),
            'description' => sanitizeInput($_POST['description']),
            'short_description' => sanitizeInput($_POST['short_description']),
            'price_1_day' => floatval($_POST['price_1_day']),
            'price_1_week' => floatval($_POST['price_1_week']),
            'price_1_month' => floatval($_POST['price_1_month']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'image_url' => ''
        ];
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadResellerProductImage($_FILES['image']);
            if ($uploadResult['success']) {
                $productData['image_url'] = $uploadResult['file_path'];
            }
        }
        
        $result = addResellerProduct($reseller['id'], $productData);
        if ($result['success']) {
            $success = $result['message'];
            $action = 'list';
        } else {
            $error = $result['message'];
        }
    }
}

// Get products for listing
$products = getResellerProducts($reseller['id']);
?>

<div class="container">
    <div class="reseller-products">
        <div class="page-header">
            <h1><i class="fas fa-gamepad"></i> My Products</h1>
            <p>Manage your cheat products and pricing</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add'): ?>
            <!-- Add Product Form -->
            <div class="product-form">
                <h2>Add New Product</h2>
                <form method="POST" enctype="multipart/form-data" class="form" id="addProductForm">
                    <input type="hidden" name="action" value="add_product">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="short_description" class="form-label">Short Description</label>
                            <input type="text" id="short_description" name="short_description" class="form-control" 
                                   placeholder="Brief description for product cards">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Full Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="6" required
                                  placeholder="Detailed description of your cheat, features, compatibility..."></textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="price_1_day" class="form-label">1 Day Price *</label>
                            <input type="number" id="price_1_day" name="price_1_day" class="form-control" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_1_week" class="form-label">1 Week Price *</label>
                            <input type="number" id="price_1_week" name="price_1_week" class="form-control" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_1_month" class="form-label">1 Month Price *</label>
                            <input type="number" id="price_1_month" name="price_1_month" class="form-control" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        <small class="form-help">JPG, PNG, or GIF up to 5MB</small>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" checked>
                                <span class="checkmark"></span>
                                Active (visible to customers)
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_featured">
                                <span class="checkmark"></span>
                                Featured (highlighted on store)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </button>
                        <a href="index.php?page=reseller-products" class="btn btn-outline">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Products List -->
            <div class="products-section">
                <div class="section-header">
                    <h2>Your Products</h2>
                    <a href="index.php?page=reseller-products&action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </a>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-gamepad"></i>
                        <p>You haven't added any products yet.</p>
                        <a href="index.php?page=reseller-products&action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Your First Product
                        </a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-content">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-description">
                                        <?php echo htmlspecialchars($product['short_description'] ?: $product['description']); ?>
                                    </p>
                                    
                                    <div class="product-prices">
                                        <div class="price-item">
                                            <span class="price-label">1 Day:</span>
                                            <span class="price-value">$<?php echo number_format($product['price_1_day'], 2); ?></span>
                                        </div>
                                        <div class="price-item">
                                            <span class="price-label">1 Week:</span>
                                            <span class="price-value">$<?php echo number_format($product['price_1_week'], 2); ?></span>
                                        </div>
                                        <div class="price-item">
                                            <span class="price-label">1 Month:</span>
                                            <span class="price-value">$<?php echo number_format($product['price_1_month'], 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="product-status">
                                        <?php if ($product['is_active']): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-circle"></i>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="fas fa-circle"></i>
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_featured']): ?>
                                            <span class="status-badge status-featured">
                                                <i class="fas fa-star"></i>
                                                Featured
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="index.php?page=reseller-products&action=edit&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.reseller-products {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: #cccccc;
    font-size: 1.1rem;
}

.product-form {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    max-width: 800px;
    margin: 0 auto;
}

.product-form h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #ffffff;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #ff69b4;
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.form-help {
    display: block;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 0.25rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
    color: #ffffff;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #ff69b4;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.products-section {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #ffffff;
    margin: 0;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.product-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(255, 105, 180, 0.2);
    border-color: rgba(255, 105, 180, 0.3);
}

.product-image {
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    color: #ccc;
    font-size: 3rem;
}

.product-content {
    padding: 1.5rem;
}

.product-content h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.product-description {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-prices {
    margin-bottom: 1rem;
}

.price-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    color: rgba(255, 255, 255, 0.9);
}

.price-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.price-value {
    color: #ff69b4;
    font-weight: 600;
}

.product-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.product-status {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-active {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-featured {
    background: rgba(255, 105, 180, 0.2);
    color: #ff69b4;
    border: 1px solid rgba(255, 105, 180, 0.3);
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        // Add delete functionality here
        alert('Delete functionality will be implemented soon!');
    }
}

// Simple form submission without AJAX to avoid loading state issues
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addProductForm');
    if (form) {
        // Remove any existing event listeners
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Add simple form submission
        newForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            submitBtn.disabled = true;
            
            // Let the form submit normally
            return true;
        });
    }
});
</script>
