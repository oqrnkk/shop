    <?php
require_once 'config/config.php';
require_once 'config/database.php';

/**
 * Generate a unique license key
 */
function generateLicenseKey($length = 32) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = KEY_PREFIX;
    
    for ($i = 0; $i < $length - strlen(KEY_PREFIX); $i++) {
        $key .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $key;
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Create URL-friendly slug from string
 */
function createSlug($string) {
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace spaces and special characters with hyphens
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    
    // Remove leading/trailing hyphens
    $string = trim($string, '-');
    
    // Ensure it's not empty
    if (empty($string)) {
        $string = 'untitled';
    }
    
    return $string;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format price
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

/**
 * Calculate expiration date
 */
function calculateExpirationDate($duration) {
    $now = new DateTime();
    
    switch ($duration) {
        case '1_day':
            return $now->add(new DateInterval('P1D'));
        case '1_week':
            return $now->add(new DateInterval('P7D'));
        case '1_month':
            return $now->add(new DateInterval('P1M'));
        default:
            return $now;
    }
}

/**
 * Check if key is expired
 */
function isKeyExpired($expirationDate) {
    $now = new DateTime();
    $expiration = new DateTime($expirationDate);
    return $now > $expiration;
}

/**
 * Send email
 */
function sendEmail($to, $subject, $message) {
    // Basic email sending - you can enhance this with PHPMailer
    $headers = "From: " . SITE_EMAIL . "\r\n";
    $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    global $conn;
    
    try {
        // Check if activity_logs table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'activity_logs'");
        if ($stmt->rowCount() == 0) {
            // Table doesn't exist, skip logging
            return;
        }
        
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Log error but don't throw exception
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

/**
 * Get user IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit();
}

/**
 * Display message
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return '';
}

/**
 * Pagination helper
 */
function getPagination($total, $perPage, $currentPage, $url) {
    $totalPages = ceil($total / $perPage);
    $pagination = '';
    
    if ($totalPages > 1) {
        $pagination .= '<div class="pagination">';
        
        if ($currentPage > 1) {
            $pagination .= '<a href="' . $url . '?page=' . ($currentPage - 1) . '" class="page-link">Previous</a>';
        }
        
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i == $currentPage ? 'active' : '';
            $pagination .= '<a href="' . $url . '?page=' . $i . '" class="page-link ' . $active . '">' . $i . '</a>';
        }
        
        if ($currentPage < $totalPages) {
            $pagination .= '<a href="' . $url . '?page=' . ($currentPage + 1) . '" class="page-link">Next</a>';
        }
        
        $pagination .= '</div>';
    }
    
    return $pagination;
}

/**
 * Upload file
 */
function uploadFile($file, $directory = 'uploads/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }
    
    return $filename;
}

/**
 * Delete file
 */
function deleteFile($filename, $directory = 'uploads/') {
    $filepath = $directory . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return $errors;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get database connection
 */
function getDatabaseConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Test the connection
            if ($conn) {
                $test_stmt = $conn->query("SELECT 1");
                if (!$test_stmt) {
                    $conn = null;
                }
            }
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            $conn = null;
        }
    }
    
    return $conn;
}

/**
 * Add license keys to the available keys table
 */
function addLicenseKeys($productId, $duration, $keys, $addedBy) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("
            INSERT INTO available_keys (product_id, license_key, duration, added_by) 
            VALUES (?, ?, ?, ?)
        ");
        
        $addedCount = 0;
        foreach ($keys as $key) {
            try {
                $stmt->execute([$productId, $key, $duration, $addedBy]);
                $addedCount++;
            } catch (PDOException $e) {
                // Key might already exist, skip it
                continue;
            }
        }
        
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => "Successfully added $addedCount license keys",
            'added_count' => $addedCount
        ];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Failed to add license keys: ' . $e->getMessage()];
    }
}

/**
 * Get available license keys for a product and duration
 */
function getAvailableKeys($productId, $duration) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT ak.*, p.name as product_name 
            FROM available_keys ak
            JOIN products p ON ak.product_id = p.id
            WHERE ak.product_id = ? AND ak.duration = ? AND ak.is_used = FALSE
            ORDER BY ak.created_at ASC
        ");
        $stmt->execute([$productId, $duration]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all available keys (for admin/reseller management)
 */
function getAllAvailableKeys($filters = []) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return [];
    }
    
    try {
        $where = "WHERE ak.is_used = FALSE";
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $where .= " AND ak.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['duration'])) {
            $where .= " AND ak.duration = ?";
            $params[] = $filters['duration'];
        }
        
        $stmt = $conn->prepare("
            SELECT ak.*, p.name as product_name, u.username as added_by_username
            FROM available_keys ak
            JOIN products p ON ak.product_id = p.id
            JOIN users u ON ak.added_by = u.id
            $where
            ORDER BY ak.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get used license keys
 */
function getUsedKeys($filters = []) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return [];
    }
    
    try {
        $where = "WHERE ak.is_used = TRUE";
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $where .= " AND ak.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['duration'])) {
            $where .= " AND ak.duration = ?";
            $params[] = $filters['duration'];
        }
        
        $stmt = $conn->prepare("
            SELECT ak.*, p.name as product_name, u.username as added_by_username,
                   o.id as order_id, o.user_id as buyer_id, buyer.username as buyer_username
            FROM available_keys ak
            JOIN products p ON ak.product_id = p.id
            JOIN users u ON ak.added_by = u.id
            LEFT JOIN orders o ON ak.used_by_order_id = o.id
            LEFT JOIN users buyer ON o.user_id = buyer.id
            $where
            ORDER BY ak.used_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Assign a license key to an order
 */
function assignLicenseKeyToOrder($orderId, $productId, $duration) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Get an available key
        $stmt = $conn->prepare("
            SELECT id, license_key 
            FROM available_keys 
            WHERE product_id = ? AND duration = ? AND is_used = FALSE 
            ORDER BY created_at ASC 
            LIMIT 1
        ");
        $stmt->execute([$productId, $duration]);
        $key = $stmt->fetch();
        
        if (!$key) {
            $conn->rollBack();
            return ['success' => false, 'message' => 'No available license keys for this product and duration'];
        }
        
        // Mark the key as used
        $updateStmt = $conn->prepare("
            UPDATE available_keys 
            SET is_used = TRUE, used_at = NOW(), used_by_order_id = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$orderId, $key['id']]);
        
        // Update the order with the license key
        $orderStmt = $conn->prepare("
            UPDATE orders 
            SET license_key_used = ? 
            WHERE id = ?
        ");
        $orderStmt->execute([$key['license_key'], $orderId]);
        
        // Create license key record
        $expiresAt = calculateExpirationDate($duration);
        $licenseStmt = $conn->prepare("
            INSERT INTO license_keys (order_id, user_id, product_id, license_key, duration, expires_at)
            SELECT ?, user_id, product_id, ?, ?, ?
            FROM orders WHERE id = ?
        ");
        $licenseStmt->execute([$orderId, $key['license_key'], $duration, $expiresAt->format('Y-m-d H:i:s'), $orderId]);
        
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'License key assigned successfully',
            'license_key' => $key['license_key']
        ];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Failed to assign license key: ' . $e->getMessage()];
    }
}

/**
 * Delete license keys
 */
function deleteLicenseKeys($keyIds) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $conn->beginTransaction();
        
        $placeholders = str_repeat('?,', count($keyIds) - 1) . '?';
        $stmt = $conn->prepare("
            DELETE FROM available_keys 
            WHERE id IN ($placeholders) AND is_used = FALSE
        ");
        $stmt->execute($keyIds);
        
        $deletedCount = $stmt->rowCount();
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => "Successfully deleted $deletedCount license keys",
            'deleted_count' => $deletedCount
        ];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Failed to delete license keys: ' . $e->getMessage()];
    }
}

/**
 * Get key statistics
 */
function getKeyStatistics() {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.name as product_name,
                ak.duration,
                COUNT(CASE WHEN ak.is_used = FALSE THEN 1 END) as available,
                COUNT(CASE WHEN ak.is_used = TRUE THEN 1 END) as used
            FROM available_keys ak
            JOIN products p ON ak.product_id = p.id
            GROUP BY ak.product_id, ak.duration
            ORDER BY p.name, ak.duration
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all products
 */
function getAllProducts() {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT id, name, slug, is_active 
            FROM products 
            WHERE is_active = TRUE 
            ORDER BY name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Check if user is a reseller
 */
function isReseller($userId = null) {
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    $conn = getDatabaseConnection();
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id FROM resellers WHERE user_id = ? AND is_approved = 1 AND is_active = 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if user is an admin
 */
function isAdmin($userId = null) {
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    $conn = getDatabaseConnection();
    if (!$conn) {
        return false;
    }
    
    try {
        // Check both is_admin column and user_type column for compatibility
        $stmt = $conn->prepare("SELECT is_admin, user_type FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Check if user_type is 'admin' or is_admin is true
        return ($result['user_type'] === 'admin' || $result['is_admin'] == 1);
    } catch (Exception $e) {
        error_log('isAdmin error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update user profile
 */
function updateUserProfile($userId, $updateData) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return false;
    }
    
    try {
        $fields = [];
        $params = [];
        
        foreach ($updateData as $field => $value) {
            if (in_array($field, ['username', 'email'])) {
                $fields[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $conn = getDatabaseConnection();
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT id, username, email, is_active, is_admin, is_reseller, created_at, last_login
            FROM users 
            WHERE id = ? AND is_active = TRUE
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Change user password
 */
function changePassword($userId, $currentPassword, $newPassword) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Get current password hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newPasswordHash, $userId]);
        
        return ['success' => true, 'message' => 'Password changed successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to change password'];
    }
}

/**
 * Get available key count for a product and duration
 */
function getAvailableKeyCount($productId, $duration) {
    $conn = getDatabaseConnection();
    if (!$conn) {
        return 0;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM available_keys 
            WHERE product_id = ? AND duration = ? AND is_used = FALSE
        ");
        $stmt->execute([$productId, $duration]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Format key count for display
 */
function formatKeyCount($count) {
    if ($count > 99) {
        return '99+';
    }
    return (string)$count;
}
?>
