<?php
/**
 * Reseller System Functions
 */

/**
 * Register a new reseller
 */
function registerReseller($userId, $businessName, $description = '') {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        // Check if user is already a reseller
        $stmt = $conn->prepare("SELECT id FROM resellers WHERE user_id = ?");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'User is already a reseller'];
        }
        
        // Create reseller account
        $stmt = $conn->prepare("
            INSERT INTO resellers (user_id, business_name, description, is_approved, is_active)
            VALUES (?, ?, ?, FALSE, TRUE)
        ");
        
        if ($stmt->execute([$userId, $businessName, $description])) {
            // Update user type
            $stmt = $conn->prepare("UPDATE users SET user_type = 'reseller' WHERE id = ?");
            $stmt->execute([$userId]);
            
            logActivity($userId, 'reseller_registration', 'Registered as reseller: ' . $businessName);
            
            return ['success' => true, 'message' => 'Reseller account created successfully! Awaiting admin approval.'];
        }
        
        return ['success' => false, 'message' => 'Failed to create reseller account'];
    } catch (Exception $e) {
        error_log('Reseller registration error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during registration'];
    }
}

/**
 * Get reseller by user ID
 */
function getResellerByUserId($userId) {
    global $conn;
    
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT r.*, u.username, u.email 
            FROM resellers r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getResellerByUserId error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get reseller by ID
 */
function getResellerById($resellerId) {
    global $conn;
    
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT r.*, u.username, u.email 
            FROM resellers r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$resellerId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getResellerById error: " . $e->getMessage());
        return null;
    }
}



/**
 * Require reseller access
 */
function requireReseller() {
    requireAuth();
    
    if (!isReseller()) {
        header('Location: index.php?page=reseller-apply');
        exit();
    }
}

/**
 * Add reseller product
 */
function addResellerProduct($resellerId, $data) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        $slug = createSlug($data['name']);
        
        // Check if slug already exists
        $stmt = $conn->prepare("SELECT id FROM reseller_products WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . uniqid();
        }
        
        $stmt = $conn->prepare("
            INSERT INTO reseller_products (
                reseller_id, name, slug, description, short_description, image_url,
                price_1_day, price_1_week, price_1_month, is_active, is_featured
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $resellerId,
            $data['name'],
            $slug,
            $data['description'],
            $data['short_description'],
            $data['image_url'],
            $data['price_1_day'],
            $data['price_1_week'],
            $data['price_1_month'],
            $data['is_active'] ?? 1,
            $data['is_featured'] ?? 0
        ]);
        
        if ($result) {
            $productId = $conn->lastInsertId();
            logActivity($_SESSION['user_id'], 'product_added', 'Added reseller product: ' . $data['name']);
            return ['success' => true, 'product_id' => $productId, 'message' => 'Product added successfully!'];
        }
        
        return ['success' => false, 'message' => 'Failed to add product'];
    } catch (Exception $e) {
        error_log('Add reseller product error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while adding product'];
    }
}

/**
 * Update reseller product
 */
function updateResellerProduct($productId, $resellerId, $data) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM reseller_products WHERE id = ? AND reseller_id = ?");
        $stmt->execute([$productId, $resellerId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Product not found or access denied'];
        }
        
        $stmt = $conn->prepare("
            UPDATE reseller_products SET
                name = ?, description = ?, short_description = ?, image_url = ?,
                price_1_day = ?, price_1_week = ?, price_1_month = ?,
                is_active = ?, is_featured = ?, updated_at = NOW()
            WHERE id = ? AND reseller_id = ?
        ");
        
        $result = $stmt->execute([
            $data['name'],
            $data['description'],
            $data['short_description'],
            $data['image_url'],
            $data['price_1_day'],
            $data['price_1_week'],
            $data['price_1_month'],
            $data['is_active'] ?? 1,
            $data['is_featured'] ?? 0,
            $productId,
            $resellerId
        ]);
        
        if ($result) {
            logActivity($_SESSION['user_id'], 'product_updated', 'Updated reseller product: ' . $data['name']);
            return ['success' => true, 'message' => 'Product updated successfully!'];
        }
        
        return ['success' => false, 'message' => 'Failed to update product'];
    } catch (Exception $e) {
        error_log('Update reseller product error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating product'];
    }
}

/**
 * Delete reseller product
 */
function deleteResellerProduct($productId, $resellerId) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("SELECT name FROM reseller_products WHERE id = ? AND reseller_id = ?");
        $stmt->execute([$productId, $resellerId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found or access denied'];
        }
        
        $stmt = $conn->prepare("DELETE FROM reseller_products WHERE id = ? AND reseller_id = ?");
        $result = $stmt->execute([$productId, $resellerId]);
        
        if ($result) {
            logActivity($_SESSION['user_id'], 'product_deleted', 'Deleted reseller product: ' . $product['name']);
            return ['success' => true, 'message' => 'Product deleted successfully!'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete product'];
    } catch (Exception $e) {
        error_log('Delete reseller product error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting product'];
    }
}

/**
 * Get reseller products
 */
function getResellerProducts($resellerId, $limit = null, $offset = 0) {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM reseller_products WHERE reseller_id = ? ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$resellerId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("getResellerProducts error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get reseller product by ID
 */
function getResellerProductById($productId, $resellerId = null) {
    global $conn;
    
    if (!$conn) {
        return null;
    }
    
    try {
        $sql = "SELECT * FROM reseller_products WHERE id = ?";
        $params = [$productId];
        
        if ($resellerId) {
            $sql .= " AND reseller_id = ?";
            $params[] = $resellerId;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getResellerProductById error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all active reseller products (for public display)
 */
function getAllActiveResellerProducts($limit = 20, $offset = 0) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT rp.*, r.business_name, r.is_approved
        FROM reseller_products rp
        JOIN resellers r ON rp.reseller_id = r.id
        WHERE rp.is_active = 1 AND r.is_approved = 1 AND r.is_active = 1
        ORDER BY rp.is_featured DESC, rp.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Process reseller order and commission
 */
function processResellerOrder($orderId, $resellerProductId, $orderAmount) {
    global $conn;
    
    try {
        // Get reseller product details
        $stmt = $conn->prepare("
            SELECT rp.*, r.id as reseller_id, r.commission_rate
            FROM reseller_products rp
            JOIN resellers r ON rp.reseller_id = r.id
            WHERE rp.id = ?
        ");
        $stmt->execute([$resellerProductId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Calculate commission (20% to store, 80% to reseller)
        $commissionAmount = $orderAmount * ($product['commission_rate'] / 100);
        $resellerEarnings = $orderAmount - $commissionAmount;
        
        // Create reseller order record
        $stmt = $conn->prepare("
            INSERT INTO reseller_orders (
                order_id, reseller_id, reseller_product_id, product_name,
                order_amount, reseller_earnings, commission_amount, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $orderId,
            $product['reseller_id'],
            $resellerProductId,
            $product['name'],
            $orderAmount,
            $resellerEarnings,
            $commissionAmount
        ]);
        
        // Update reseller total earnings
        $stmt = $conn->prepare("
            UPDATE resellers 
            SET total_earnings = total_earnings + ? 
            WHERE id = ?
        ");
        $stmt->execute([$resellerEarnings, $product['reseller_id']]);
        
        // Update product sales count
        $stmt = $conn->prepare("
            UPDATE reseller_products 
            SET sales_count = sales_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$resellerProductId]);
        
        return ['success' => true, 'commission' => $commissionAmount, 'reseller_earnings' => $resellerEarnings];
    } catch (Exception $e) {
        error_log('Process reseller order error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred processing the order'];
    }
}

/**
 * Get reseller earnings
 */
function getResellerEarnings($resellerId, $period = 'all') {
    global $conn;
    
    $whereClause = "WHERE reseller_id = ?";
    $params = [$resellerId];
    
    switch ($period) {
        case 'today':
            $whereClause .= " AND DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(order_amount) as total_sales,
            SUM(reseller_earnings) as total_earnings,
            SUM(commission_amount) as total_commission
        FROM reseller_orders 
        $whereClause
    ");
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Get reseller orders
 */
function getResellerOrders($resellerId, $limit = 20, $offset = 0) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT ro.*, o.status as order_status, o.created_at as order_date
        FROM reseller_orders ro
        JOIN orders o ON ro.order_id = o.id
        WHERE ro.reseller_id = ?
        ORDER BY ro.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $resellerId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Upload reseller product image
 */
function uploadResellerProductImage($file) {
    // Try multiple upload directories in order of preference
    $uploadDirs = [
        'uploads/',
        'images/',
        'public/images/',
        'assets/uploads/',
        'tmp/'
    ];
    
    $uploadDir = null;
    
    // Find the first writable directory
    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                $uploadDir = $dir;
                break;
            }
        } elseif (is_writable($dir)) {
            $uploadDir = $dir;
            break;
        }
    }
    
    // If no directory is writable, try to create a temporary solution
    if (!$uploadDir) {
        // Try to create a directory in the current working directory
        $tempDir = 'upload_' . time() . '/';
        if (mkdir($tempDir, 0755, true)) {
            $uploadDir = $tempDir;
        } else {
            return ['success' => false, 'message' => 'No writable upload directory found. Please contact your hosting provider to enable file uploads.'];
        }
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename with timestamp
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'reseller_' . $timestamp . '_' . $randomString . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Try to upload the file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $filePath];
    }
    
    // If move_uploaded_file fails, try alternative method
    if (copy($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $filePath];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file. Please check directory permissions.'];
}

/**
 * Get all resellers (admin function)
 */
function getAllResellers($limit = 50, $offset = 0) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT r.*, u.username, u.email, u.created_at as user_created
        FROM resellers r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Approve/Reject reseller (admin function)
 */
function updateResellerStatus($resellerId, $isApproved) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            UPDATE resellers 
            SET is_approved = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$isApproved ? 1 : 0, $resellerId]);
        
        if ($result) {
            $reseller = getResellerById($resellerId);
            $action = $isApproved ? 'approved' : 'rejected';
            logActivity($_SESSION['user_id'], 'reseller_' . $action, 'Reseller ' . $action . ': ' . $reseller['business_name']);
            return ['success' => true, 'message' => 'Reseller ' . $action . ' successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update reseller status'];
    } catch (Exception $e) {
        error_log('Update reseller status error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred'];
    }
}
?>
