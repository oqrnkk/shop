<?php
// Get database connection
$conn = getDatabaseConnection();

// Initialize variables
$total_products = 0;
$regular_products = [];
$reseller_products = [];
$total_pages = 0;

// Get filter parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

if ($conn) {
    try {
        // Build query
        $where_conditions = ['p.is_active = 1'];
        $params = [];

        if ($search) {
            $where_conditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)';
            $search_term = '%' . $search . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get total count (regular products + reseller products)
        $count_sql = "
            SELECT (
                SELECT COUNT(*) FROM products p 
                WHERE $where_clause
            ) + (
                SELECT COUNT(*) FROM reseller_products rp
                JOIN resellers r ON rp.reseller_id = r.id
                WHERE rp.is_active = 1 AND r.is_approved = 1 AND r.is_active = 1
            ) as total_count
        ";
        $stmt = $conn->prepare($count_sql);
        $stmt->execute($params);
        $total_products = $stmt->fetchColumn();

        $total_pages = ceil($total_products / $per_page);
        $offset = ($page - 1) * $per_page;

        // Get regular products
        $sql = "
            SELECT p.*, 'regular' as product_type
            FROM products p 
            WHERE $where_clause
            ORDER BY 
            CASE 
                WHEN ? = 'name' THEN p.name
                WHEN ? = 'price_low' THEN p.price_1_day
                WHEN ? = 'price_high' THEN p.price_1_day
                WHEN ? = 'newest' THEN p.created_at
                ELSE p.name
            END
            " . ($sort === 'price_high' ? 'DESC' : 'ASC') . "
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);

        // Bind all parameters
        $param_count = 1;
        foreach ($params as $param) {
            $stmt->bindValue($param_count++, $param);
        }

        // Bind sort parameters
        $stmt->bindValue($param_count++, $sort);
        $stmt->bindValue($param_count++, $sort);
        $stmt->bindValue($param_count++, $sort);
        $stmt->bindValue($param_count++, $sort);

        // Bind LIMIT and OFFSET as integers
        $stmt->bindValue($param_count++, $per_page, PDO::PARAM_INT);
        $stmt->bindValue($param_count++, $offset, PDO::PARAM_INT);

        $stmt->execute();
        $regular_products = $stmt->fetchAll();

        // Get reseller products
        $reseller_sql = "
            SELECT rp.*, r.business_name, 'reseller' as product_type, 'Reseller' as category_name
            FROM reseller_products rp
            JOIN resellers r ON rp.reseller_id = r.id
            WHERE rp.is_active = 1 AND r.is_approved = 1 AND r.is_active = 1
            ORDER BY 
            CASE 
                WHEN ? = 'name' THEN rp.name
                WHEN ? = 'price_low' THEN rp.price_1_day
                WHEN ? = 'price_high' THEN rp.price_1_day
                WHEN ? = 'newest' THEN rp.created_at
                ELSE rp.name
            END
            " . ($sort === 'price_high' ? 'DESC' : 'ASC') . "
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($reseller_sql);

        // Bind sort parameters for reseller products
        $stmt->bindValue(1, $sort);
        $stmt->bindValue(2, $sort);
        $stmt->bindValue(3, $sort);
        $stmt->bindValue(4, $sort);

        // Bind LIMIT and OFFSET as integers
        $stmt->bindValue(5, $per_page, PDO::PARAM_INT);
        $stmt->bindValue(6, $offset, PDO::PARAM_INT);

        $stmt->execute();
        $reseller_products = $stmt->fetchAll();

        // Combine and sort all products
        $all_products = array_merge($regular_products, $reseller_products);

        // Sort combined products based on the sort parameter
        usort($all_products, function($a, $b) use ($sort) {
            switch ($sort) {
                case 'name':
                    return strcasecmp($a['name'], $b['name']);
                case 'price_low':
                    return $a['price_1_day'] <=> $b['price_1_day'];
                case 'price_high':
                    return $b['price_1_day'] <=> $a['price_1_day'];
                case 'newest':
                    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
                default:
                    return strcasecmp($a['name'], $b['name']);
            }
        });

        // Apply pagination to combined results
        $products = array_slice($all_products, 0, $per_page);
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Database error in products.php: " . $e->getMessage());
        $products = [];
    }
}


?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>All Products</h1>
        <p>Browse our complete collection of premium gaming cheats</p>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <input type="hidden" name="page" value="products">
            
            <div class="filters-row">
                <div class="filter-group">
                    <label for="search" class="filter-label">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-input"
                        placeholder="Search products..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                

                
                <div class="filter-group">
                    <label for="sort" class="filter-label">Sort By</label>
                    <select id="sort" name="sort" class="form-input">
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                    <a href="index.php?page=products" class="btn btn-outline">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>



    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="no-results">
            <div class="no-results-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>No products found</h3>
            <p>Try adjusting your search criteria or browse all products</p>
            <a href="index.php?page=products" class="btn btn-primary">
                <i class="fas fa-gamepad"></i>
                Browse All Products
            </a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php 
                        $image_url = null;
                        if ($product['product_type'] === 'reseller' && isset($product['image_url']) && $product['image_url']) {
                            $image_url = $product['image_url'];
                        } elseif ($product['product_type'] === 'regular' && isset($product['image']) && $product['image']) {
                            $image_url = $product['image'];
                        }
                        ?>
                        <?php if ($image_url): ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-gamepad"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars($product['short_description']); ?></p>
                        <?php if ($product['product_type'] === 'reseller'): ?>
                            <div class="product-category">
                                <span class="reseller-badge">Reseller</span>
                            </div>
                        <?php endif; ?>
                        <div class="product-prices">
                            <div class="price-option">
                                <div class="price-duration">1 Day</div>
                                <div class="price-amount">$<?php echo number_format($product['price_1_day'], 2); ?></div>
                            </div>
                            <div class="price-option">
                                <div class="price-duration">1 Week</div>
                                <div class="price-amount">$<?php echo number_format($product['price_1_week'], 2); ?></div>
                            </div>
                            <div class="price-option">
                                <div class="price-duration">1 Month</div>
                                <div class="price-amount">$<?php echo number_format($product['price_1_month'], 2); ?></div>
                            </div>
                        </div>
                        <?php if ($product['product_type'] === 'reseller'): ?>
                            <a href="index.php?page=reseller-product&id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i>
                                View Details
                            </a>
                        <?php else: ?>
                            <a href="index.php?page=product&slug=<?php echo $product['slug']; ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i>
                                View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <?php
                $current_url = 'index.php?page=products';
                if ($search) $current_url .= '&search=' . urlencode($search);

                if ($sort) $current_url .= '&sort=' . urlencode($sort);
                
                echo getPagination($total_products, $per_page, $page, $current_url);
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.page-header {
    text-align: center;
    margin-bottom: 3rem;
    color: white;
    padding-top: 1rem; /* Add some top padding for better spacing */
}

.page-header h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.page-header p {
    font-size: 1.2rem;
    opacity: 0.9;
}

.filters-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.filters-row {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
}

.results-info {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    text-align: center;
    color: #666;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.no-results-icon {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: #666;
    font-size: 2rem;
}

.no-results h3 {
    margin-bottom: 1rem;
    color: #333;
}

.no-results p {
    color: #666;
    margin-bottom: 2rem;
}

.pagination-container {
    margin-top: 3rem;
    text-align: center;
}

.pagination {
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
}

.page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.page-link:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.page-link.active {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .filters-row {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .filter-group {
        flex-direction: row;
        align-items: center;
        gap: 1rem;
    }
    
    .filter-label {
        min-width: 80px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
}



.reseller-badge {
    background: var(--primary);
    color: white;
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    box-shadow: var(--shadow-pink);
}

.key-indicator {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #10b981;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    min-width: 24px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.key-indicator.out-of-stock {
    background: #ef4444;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.key-indicator.available {
    background: #10b981;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

.price-option {
    position: relative;
}

/* Enhanced Product Card Styling */
.product-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    position: relative;
    padding: 1.5rem;
}

.product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
    opacity: 0;
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-8px);
    border-color: rgba(255, 105, 180, 0.3);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.product-card:hover::before {
    opacity: 1;
}

.product-image {
    background: linear-gradient(135deg, #ff69b4, #ff1493);
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}

.product-image i {
    font-size: 3rem;
    color: white;
}

.product-card:hover .product-image {
    background: linear-gradient(135deg, #ff1493, #ff69b4);
    transform: scale(1.05);
}

.product-info {
    color: white;
}

.product-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.5rem;
    letter-spacing: -0.01em;
}

.product-description {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.product-prices {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.price-option {
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price-option:hover {
    border-color: #ff69b4;
    background: rgba(255, 105, 180, 0.1);
    transform: translateY(-2px);
}

.price-duration {
    color: rgba(255, 255, 255, 0.6);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.8rem;
}

.price-amount {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.1rem;
}

/* Enhanced Page Header */
.page-header h1 {
    font-family: var(--font-display);
    color: #ffffff;
    letter-spacing: -0.02em;
}

.page-header p {
    color: rgba(255, 255, 255, 0.7);
}

/* Enhanced Filters */
.filters-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 16px;
}

.form-input {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff;
    backdrop-filter: blur(10px);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    border-color: #ff69b4;
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
    background: rgba(255, 255, 255, 0.08);
    outline: none;
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-input option {
    background: #000000;
    color: #ffffff;
}

.filter-label {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
    margin-bottom: 0.5rem;
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

.btn-outline {
    background: transparent;
    color: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
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

/* Enhanced Pagination */
.page-link {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.page-link:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    color: #ffffff;
    transform: translateY(-1px);
}

.page-link.active {
    background: #ff69b4;
    border-color: #ff69b4;
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
}
</style>
