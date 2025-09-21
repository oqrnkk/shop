<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

/**
 * Register a new user
 */
function registerUser($username, $email, $password) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            $userId = $conn->lastInsertId();
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Registration failed due to database error'];
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, password, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        
        // Update last login
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return ['success' => true, 'user' => $user];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Login failed due to database error'];
    }
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, is_active, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get user by email
 */
function getUserByEmail($email) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireAuth();
    
    if (!isAdmin()) {
        header('Location: index.php?page=dashboard');
        exit();
    }
}

/**
 * Reset password request
 */
function requestPasswordReset($email) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        $user = getUserByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        if ($stmt->execute([$user['id'], $token, $expires])) {
            // Send email with reset link
            $resetLink = SITE_URL . '/index.php?page=reset-password&token=' . $token;
            $subject = 'Password Reset Request';
            $message = "Hello {$user['username']},\n\nYou requested a password reset. Click the link below to reset your password:\n\n{$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\n" . SITE_NAME;
            
            if (sendEmail($user['email'], $subject, $message)) {
                return ['success' => true, 'message' => 'Password reset email sent'];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to send reset email'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send reset email due to database error'];
    }
}

/**
 * Reset password with token
 */
function resetPassword($token, $newPassword) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable'];
    }
    
    try {
        // Check if token is valid and not expired
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $reset['user_id']])) {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            return ['success' => true, 'message' => 'Password reset successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to reset password'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to reset password due to database error'];
    }
}
?>
