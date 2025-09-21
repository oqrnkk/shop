<?php
// Get database connection
$conn = getDatabaseConnection();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_product') {
        $name = sanitizeInput($_POST['name']);
        $slug = createSlug($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $short_description = sanitizeInput($_POST['short_description']);
        $price_1_day = floatval($_POST['price_1_day']);
        $price_1_week = floatval($_POST['price_1_week']);
        $price_1_month = floatval($_POST['price_1_month']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        $stmt = $conn->prepare("
            INSERT INTO products (name, slug, description, short_description, price_1_day, price_1_week, price_1_month, is_active, is_featured, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$name, $slug, $description, $short_description, $price_1_day, $price_1_week, $price_1_month, $is_active, $is_featured])) {
            $success = "Product added successfully!";
        } else {
            $error = "Failed to add product.";
        }
    } elseif ($_POST['action'] === 'edit_product') {
        $product_id = intval($_POST['product_id']);
        $name = sanitizeInput($_POST['name']);
        $slug = createSlug($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $short_description = sanitizeInput($_POST['short_description']);
        $price_1_day = floatval($_POST['price_1_day']);
        $price_1_week = floatval($_POST['price_1_week']);
        $price_1_month = floatval($_POST['price_1_month']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, slug = ?, description = ?, short_description = ?, 
                price_1_day = ?, price_1_week = ?, price_1_month = ?, is_active = ?, is_featured = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$name, $slug, $description, $short_description, $price_1_day, $price_1_week, $price_1_month, $is_active, $is_featured, $product_id])) {
            $success = "Product updated successfully!";
        } else {
            $error = "Failed to update product.";
        }
    } elseif ($_POST['action'] === 'delete_product') {
        $product_id = intval($_POST['product_id']);
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            $success = "Product deleted successfully!";
        } else {
            $error = "Failed to delete product.";
        }
    }
}

// Get all products with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status_filter) {
    $where_conditions[] = "p.is_active = 1"; // Assuming 'active' means is_active = 1
    if ($status_filter === 'inactive') {
        $where_conditions[] = "p.is_active = 0";
    } elseif ($status_filter === 'featured') {
        $where_conditions[] = "p.is_featured = 1";
    }
}


$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "
    SELECT COUNT(*) 
    FROM products p
    WHERE $where_clause
";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetchColumn();

$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "
    SELECT p.*
    FROM products p
    WHERE $where_clause
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();


?>

<div class="admin-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-gamepad"></i> Products Management</h1>
            <p>Add, edit, and manage all products in your store with advanced features</p>
        </div>
        <div class="header-actions">
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-plus"></i>
                    <span>Add Product</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-download"></i>
                    <span>Export Data</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </div>
            </div>
            <a href="#" onclick="showAddProductModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>
</div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="section" value="products">
            
            <div class="filters-row">
                <div class="filter-group">
                    <div class="input-wrapper">
                        <i class="fas fa-search input-icon"></i>
                        <input
                            type="text"
                            name="search"
                            class="form-input"
                            placeholder="Search products by name or description..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                </div>
                
                <div class="filter-group">
                    <select name="status" class="form-input">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="featured" <?php echo $status_filter === 'featured' ? 'selected' : ''; ?>>Featured</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                    <a href="index.php?page=admin&section=products" class="btn btn-outline">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Info -->
    <div class="results-info">
        <div class="results-stats">
            <div class="stat-item">
                <i class="fas fa-gamepad"></i>
                <span>Showing <?php echo $total_products; ?> product<?php echo $total_products !== 1 ? 's' : ''; ?></span>
            </div>
            <?php if ($search || $status_filter): ?>
                <div class="stat-item">
                    <i class="fas fa-filter"></i>
                    <span>Filtered results</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Products Table -->
    <div class="table-container">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-gamepad"></i>
                <h3>No products found</h3>
                <p>Products will appear here once you add them</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Pricing</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <div class="product-icon">
                                            <i class="fas fa-gamepad"></i>
                                        </div>
                                        <div class="product-details">
                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pricing-info">
                                        <div class="price-item">
                                            <span class="duration">1 Day:</span>
                                            <span class="price">$<?php echo number_format($product['price_1_day'], 2); ?></span>
                                        </div>
                                        <div class="price-item">
                                            <span class="duration">1 Week:</span>
                                            <span class="price">$<?php echo number_format($product['price_1_week'], 2); ?></span>
                                        </div>
                                        <div class="price-item">
                                            <span class="duration">1 Month:</span>
                                            <span class="price">$<?php echo number_format($product['price_1_month'], 2); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($product['is_featured']): ?>
                                        <span class="status-badge status-featured">
                                            <i class="fas fa-star"></i>
                                            Featured
                                        </span>
                                    <?php elseif ($product['is_active']): ?>
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
                                    <?php if (!$product['is_featured'] && $product['is_active']): ?>
                                        <span class="status-badge status-default">
                                            <i class="fas fa-minus"></i>
                                            Standard
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="index.php?page=admin&section=products&action=edit&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline" title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Delete this product? This action cannot be undone.')" 
                                                    title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <?php
                    $current_url = 'index.php?page=admin&section=products';
                    if ($search) $current_url .= '&search=' . urlencode($search);
                    if ($status_filter) $current_url .= '&status=' . urlencode($status_filter);
                    
                    echo getPagination($total_products, $per_page, $page, $current_url);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>


.filters-section {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.filters-row {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    z-index: 1;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: rgba(51, 65, 85, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #f8fafc;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #ec4899;
    box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
}

.form-input::placeholder {
    color: #94a3b8;
}

select.form-input {
    padding-left: 1rem;
}

.results-info {
    margin-bottom: 1.5rem;
}

.results-stats {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #cbd5e1;
    font-size: 0.9rem;
}

.table-container {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
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
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.table th {
    background: rgba(51, 65, 85, 0.5);
    font-weight: 600;
    color: #f8fafc;
}

.table td {
    color: #e2e8f0;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.product-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.product-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
}

.product-details {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #f8fafc;
    margin-bottom: 0.25rem;
}

.product-description {
    color: #94a3b8;
    font-size: 0.875rem;
    line-height: 1.4;
}

.pricing-info {
    display: grid;
    gap: 0.25rem;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.duration {
    color: #94a3b8;
}

.price {
    color: #ec4899;
    font-weight: 600;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
    margin-bottom: 0.25rem;
}

.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-featured {
    background: rgba(236, 72, 153, 0.2);
    color: #f472b6;
    border: 1px solid rgba(236, 72, 153, 0.3);
}

.status-default {
    background: rgba(148, 163, 184, 0.2);
    color: #cbd5e1;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.date-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.9rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 6px;
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

.btn-sm {
    padding: 0.4rem 0.6rem;
    font-size: 0.75rem;
}

.btn-primary {
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #db2777, #7c3aed);
}

.btn-outline {
    background: transparent;
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.3);
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #94a3b8;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    color: #f8fafc;
    margin-bottom: 0.5rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #4ade80;
    border-color: rgba(34, 197, 94, 0.3);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.3);
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .quick-stats {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .table th,
    .table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8rem;
    }
}
</style>

<script>
function exportProducts() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Product Name,Description,1 Day Price,1 Week Price,1 Month Price,Status,Created Date\n";
    
    // Get all product rows
    const rows = document.querySelectorAll('.table tbody tr');
    rows.forEach(row => {
        const productName = row.querySelector('.product-name')?.textContent || '';
        const productDesc = row.querySelector('.product-description')?.textContent || '';
        const prices = row.querySelectorAll('.price');
        const status = row.querySelector('.status-badge')?.textContent.trim() || '';
        const date = row.querySelector('.date-info')?.textContent.trim() || '';
        
        const price1Day = prices[0]?.textContent.replace('$', '') || '';
        const price1Week = prices[1]?.textContent.replace('$', '') || '';
        const price1Month = prices[2]?.textContent.replace('$', '') || '';
        
        csvContent += `"${productName}","${productDesc}",${price1Day},${price1Week},${price1Month},"${status}","${date}"\n`;
    });
    
    // Download the file
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "products_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    if (window.showToast) {
        window.showToast('Products exported successfully!', 'success');
    }
}

// Add event listeners for smooth animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Add click animations to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
});

// Fallback toast function
if (!window.showToast) {
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    };
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}
</script>
